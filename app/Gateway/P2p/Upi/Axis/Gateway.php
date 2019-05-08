<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;

use RZP\Trace\TraceCode;
use RZP\Gateway\P2p\Upi;
use RZP\Models\P2p\Device;
use RZP\Constants\Timezone;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Exception\P2p\GatewayErrorException;

class Gateway extends Upi\Gateway
{
    protected $actionMap = [];

    protected $gateway = 'p2p_upi_axis';

    public function getMerchantSigner()
    {
        $rsa = new RSA();

        $rsa->loadKey($this->config['merchant_private_key'], RSA::PRIVATE_FORMAT_PKCS1);

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $rsa->setSignatureMode(RSA::SIGNATURE_PSS);

        return $rsa;
    }

    public function getMerchantVerifier()
    {
        $rsa = new RSA();

        $rsa->loadKey($this->config['bank_public_key'], RSA::PUBLIC_FORMAT_PKCS1);

        $rsa->setHash('sha256');

        $rsa->setMGFHash('sha256');

        $rsa->setSignatureMode(RSA::SIGNATURE_PSS);

        return $rsa;
    }

    protected function initiateSdkRequest(string $action)
    {
        $request = new Sdk([
            'id' => $this->getRequestId(),
        ]);

        $request->setActionMap($action, $this->actionMap[$action]);

        $request->setSigner($this->getMerchantSigner());

        return $request;
    }

    protected function handleInputSdk(): ArrayBag
    {
        if ($this->isSdkFailure() === true)
        {
            $gatewayCode = $this->inputSdk()->get(Fields::ERROR_CODE, ErrorMap::NOT_AVAILABLE);
            $gatewayDesc = $this->inputSdk()->get(Fields::ERROR_DESCRIPTION, ErrorMap::NOT_AVAILABLE);

            throw $this->p2pGatewayException(
                $gatewayCode,
                [
                    Fields::SDK => $this->inputSdk()
                ],
                $gatewayDesc);
        }

        return $this->inputSdk();
    }

    protected function handleSdkCallback(): ArrayBag
    {
        $action = $this->input->get(Fields::CALLBACK)->get(Fields::ACTION);

        $response = new Response();

        $response->setActionMap($action, $this->actionMap[$action][Actions\Action::RESPONSE] ?? []);

        $response->setVerifier($this->getMerchantVerifier());

        $response->setContent($this->inputSdk());

        $response->finish();

        return $this->input->get(Fields::CALLBACK);
    }

    protected function handleGatewayResponseCode()
    {
        if ($this->isGatewayResponseFailure() === true)
        {
            $gatewayCode = $this->inputSdk()->get(Fields::GATEWAY_RESPONSE_CODE, ErrorMap::NOT_AVAILABLE);
            $gatewayDesc = $this->inputSdk()->get(Fields::GATEWAY_RESPONSE_MESSAGE,
                                            ErrorMap::NOT_AVAILABLE);

            throw $this->p2pGatewayException(
                $gatewayCode,
                [
                    Fields::SDK => $this->inputSdk()
                ],
                $gatewayDesc);
        }
    }

    protected function inputSdk(): ArrayBag
    {
        return $this->input->get(Fields::SDK);
    }

    protected function isSdkFailure(): bool
    {
        return $this->input->get(Fields::SDK)->get(Fields::STATUS) != 'SUCCESS';
    }

    protected function isGatewayResponseFailure(): bool
    {
        return $this->input->get(Fields::SDK)->get(Fields::GATEWAY_RESPONSE_CODE) != '00';
    }

    protected function getTimeStamp()
    {
        return (string) (Carbon::now(Timezone::IST)->getTimestamp() * 1000);
    }

    protected function toBoolean($value)
    {
        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $booleanValue;
    }

    protected function toPaisa($value)
    {
        return round(floatval($value) * 100);
    }

    protected function getMerchantId()
    {
        return $this->config['merchant_id'];
    }

    protected function getMerchantChannelId()
    {
        return $this->config['merchant_channel_id'];
    }

    protected function getMerchantCategoryCode()
    {
        return $this->config['merchant_category_code'];
    }

    protected function formatMerchantCustomerId($customerId)
    {
        return str_replace('_', '.', $customerId);
    }

    protected function getMerchantCustomerId()
    {
        $deviceToken = $this->getContextDeviceToken();

        // Merchant Customer Id needs to be picked from Gateway Data
        $merchantCustomerId = $deviceToken->get(Device\Entity::GATEWAY_DATA)[Fields::MERCHANT_CUSTOMER_ID];

        return $merchantCustomerId;
    }

    protected function throwP2pGatewayException()
    {
        // .Todo Need to fix the implementation
        throw new \Exception('Hi!');
    }

    protected function p2pGatewayException(
        string $gatewayCode,
        array $data = [],
        string $gatewayDesc = null)
    {
        $code = ErrorMap::map($gatewayCode);

        return new GatewayErrorException($code, $gatewayCode, $gatewayDesc, $data);
    }

    protected function getUpiRequestId()
    {
        $prefix = $this->config['merchant_unique_prefix'] ?? 'BJJ';

        return $prefix . $this->request->getId();
    }

    protected function initiateS2sRequest(string $action)
    {
        $map = $this->actionMap[$action];

        $accessor = function(string $method)
        {
            return $this->{$method}();
        };

        switch ($map[Actions\Action::SOURCE])
        {
            case Actions\Action::DIRECT:
                $request = new S2sDirect($accessor, $this->getUrl($action));

                $request->setSigner($this->getMerchantSigner());

                $request->setHeaders([
                    S2sDirect::X_MERCHANT_ID            => $this->getMerchantId(),
                    S2sDirect::X_MERCHANT_CHANNEL_ID    => $this->getMerchantChannelId(),
                    S2sDirect::X_TIMESTAMP              => $this->getTimeStamp(),
                ]);
        }

        $request->setActionMap($action, $this->actionMap[$action], $this->getRequestId());

        $request->setConfig($this->config);

        return $request;
    }

    protected function sendS2sRequest(S2s $s2sRequest)
    {
        $request = $s2sRequest->finish();

        $entity = $this->getEntity();

        $this->trace->info(TraceCode::P2P_GATEWAY_REQUEST, [
            'action'    => $this->action,
            'entity'    => $entity,
            'gateway'   => $this->gateway,
            'request'   => $request,
            'source'    => $s2sRequest->source(),
            'mock'      => $this->mock,
        ]);

        switch ($s2sRequest->source())
        {
            case Actions\Action::DIRECT:
                $response =  parent::sendGatewayRequest($request);
        }

        $response = $s2sRequest->response($response);

        $this->trace->info(TraceCode::P2P_GATEWAY_RESPONSE, [
            'action'    => $this->action,
            'entity'    => $entity,
            'gateway'   => $this->gateway,
            'response'  => $response,
            'source'    => $s2sRequest->source(),
            'mock'      => $this->mock,
        ]);

        if ($this->isS2sFailure($response))
        {
            $gatewayCode = $response->get(Fields::RESPONSE_CODE, ErrorMap::NOT_AVAILABLE);
            $gatewayDesc = $response->get(Fields::RESPONSE_MESSAGE, ErrorMap::NOT_AVAILABLE);

            throw $this->p2pGatewayException(
                $gatewayCode,
                [
                    'response' => $response,
                ],
                $gatewayDesc);
        }

        return $response;
    }

    private function isS2sFailure($input): bool
    {
        return $input[Fields::STATUS] != 'SUCCESS';
    }

    protected function sendGatewayRequest($request)
    {
        $headers = [
            S2s::X_MERCHANT_ID          => $this->getMerchantId(),
            S2s::X_MERCHANT_CHANNEL_ID  => $this->getMerchantChannelId(),
            S2s::X_TIMESTAMP            => $this->getTimeStamp(),
        ];

        $request['headers'] = $headers;

        $request['headers'][S2s::CONTENT_TYPE] = 'application/json';

        $signer = $this->getMerchantSigner();

        $str = $this->getSignatureString($headers);

        $signature = bin2hex($signer->sign($str));

        $request['headers'][S2s::X_MERCHANT_SIGNATURE] = $signature;

        $request['content'] = json_encode($request['content']);

        return parent::sendGatewayRequest($request);
    }

    protected function getSignatureString($content)
    {
        $str = implode($content, '');

        return $str;
    }

    protected function verifySignature(string $signature, array $content)
    {
        $signer = $this->getMerchantSigner();

        $message = implode($content, '');

        return $signer->verify($message, $signature);
    }

    protected function getContentToVerify($content, $map)
    {
        $str = '';

        foreach ($map as $key)
        {
            if (isset ($content[$key]) === true)
            {
                $str .= $content[$key];
            }
        }

        return $str;
    }

    protected function getEntity()
    {
        $action = strtr(static::class, ['RZP\Gateway\P2p\Upi\Axis\\' => '', 'Gateway' => '']);

        return snake_case($action);
    }
}
