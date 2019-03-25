<?php

namespace RZP\Gateway\P2p\Upi\Axis;

use Carbon\Carbon;
use phpseclib\Crypt\RSA;

use RZP\Gateway\P2p\Upi;
use RZP\Constants\Timezone;
use RZP\Gateway\P2p\Upi\Axis\Sdk;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Gateway extends Upi\Gateway
{
    const X_MERCHANT_ID = 'X-Merchant-Id';

    const X_MERCHANT_CHANNEL_ID = 'X-Merchant-Channel-Id';

    const X_TIMESTAMP = 'X-Timestamp';

    const CONTENT_TYPE = 'Content-Type';

    const X_MERCHANT_SIGNATURE = 'X-Merchant-Signature';

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

    protected function initiateSdkRequest(string $action)
    {
        $request = new Sdk([
            'id' => $this->getSdkRequestId(),
        ]);

        $request->setActionMap($action, $this->actionMap[$action]);

        $request->setSigner($this->getMerchantSigner());

        return $request;
    }

    protected function handleInputSdk(): ArrayBag
    {
        if ($this->isSdkFailure() === true)
        {
            $this->throwP2pGatewayException();
        }

        return $this->input->get(Fields::SDK);
    }

    protected function isSdkFailure(): bool
    {
        return $this->input->get(Fields::SDK)->get(Fields::STATUS) != 'SUCCESS';
    }

    protected function handleGatewayResponse(ArrayBag $sdk)
    {
        if ($sdk->get(Fields::GATEWAY_RESPONSE_CODE) !== '00')
        {
            $this->throwP2pGatewayException();
        }
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

    protected function throwP2pGatewayException()
    {
        // .Todo Need to fix the implementation
        throw new \Exception('Hi!');
    }

    protected function getSdkRequestId()
    {
        return str_random(14);
    }

    protected function getUpiRequestId()
    {
        $prefix = $this->config['merchant_unique_prefix'] ?? 'BJJ';

        return $prefix . strtolower(str_random(32));
    }

    protected function sendGatewayRequest($request)
    {
        $headers = [
            self::X_MERCHANT_ID          => $this->getMerchantId(),
            self::X_MERCHANT_CHANNEL_ID  => $this->getMerchantChannelId(),
            self::X_TIMESTAMP            => $this->getTimeStamp(),
        ];

        $request['headers'] = $headers;

        $request['headers'][self::CONTENT_TYPE] = 'application/json';

        $signer = $this->getMerchantSigner();

        $str = $this->getSignatureString($headers);

        $signature = bin2hex($signer->sign($str));

        $request['headers'][self::X_MERCHANT_SIGNATURE] = $signature;

        $request['content'] = json_encode($request['content']);

        return parent::sendGatewayRequest($request);
    }

    protected function getSignatureString($content)
    {
        $str = implode($content, '');

        return $str;
    }
}
