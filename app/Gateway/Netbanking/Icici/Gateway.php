<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Constants\Mode as RZPMode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Netbanking\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal\Entity;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use AesTrait;

    protected $gateway = 'netbanking_icici';

    protected $bank = 'icici';

    const MODE_ECB = 1;

    protected $map = [
        RequestFields::AMOUNT  => 'amount'
    ];

    /**
     * Builds the authorize request for ICICI netbanking
     *
     * @param  array $input
     * @return void
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        // Create payment entity before passing it to gateway payment entity
        $entity = $this->createPaymentArray($content);

        $payment = $this->createGatewayPaymentEntity($entity);

        $this->traceGatewayPaymentRequest($content, $input);

        unset($content[RequestFields::AMOUNT]);
        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    /**
     * @param  array $input
     * @return void
     */
    public function callback(array $input)
    {
        parent::callback($input);

        // TODO: returned encrypted string from ICICI sometimes fails decryption
        $content = $this->getDataFromResponse($input['gateway']);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            $content);

        $payment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        // Use maps - Response Fields
        $attrs = $this->getCallbackAttributes($content);

        $payment->fill($attrs);

        $this->repo->saveOrFail($payment);

        if (!isset($attrs['status']) or $attrs['status'] !== Confirmation::YES)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                ['content' => $content]);

            // Payment fails, throw exception
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }


    /**
     * @param  array $input
     * @return void
     */
    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getPaymentVerifyData($verify);

        $request = $this->getStandardRequestArray($content);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY,
            $request);

        $response = $this->sendGatewayRequest($request);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $content;

        return $response;
    }

    public function verifyPayment($verify)
    {
        $content = $verify->verifyResponseBody;

        $status = VerifyResult::STATUS_MATCH;

        // Converting response string to XML format.
        $xml = $this->getResponseArray($content);

        // Should probably trace this
        $this->trace->info(
            TraceCode::VERIFY_CAPTURE_RESPONSE,
            (array)$xml);

        $verify->apiSuccess = true;
        $verify->gatewaySuccess = false;

        if (isset($xml['STATUS']) and $xml['STATUS'] === Constants::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }

        $input = $verify->input;

        // If payment status is either failed or created,
        // this is an api failure
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $verify->apiSuccess = false;
        }

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        return $status;
    }

    protected function getPaymentRequestData($input)
    {
        $encryptedString = $this->getEncryptedString($input);

        $data = $this->createDefaultRequestData($input);

        $data[RequestFields::ENCRYPTED_STRING] = $encryptedString;

        return $data;
    }

    protected function getPaymentVerifyData($verify)
    {
        $input = $verify->input;
        $payment = $verify->payment;

        $data = $this->createDefaultRequestData($input);

        $paymentDate = $this->getPaymentDate($payment);

        // Getting payment date in the specified format
        $data[RequestFields::PAYMENT_DATE] = $paymentDate;

        $data[RequestFields::MODE]  = Mode::VERIFY;

        $prn = $input['payment'][Payment\Entity::ID];

        $additionalData = [
            RequestFields::PAYMENT_REFERENCE_NUBER  => $prn,
            RequestFields::ITEM_CODE                => strtoupper($prn),
            RequestFields::CURRENCY_CODE            => 'INR',
        ];

        if ($input['merchant']->isTPVRequired())
        {
            $additionalData[RequestFields::ACCOUNT_NO] = $input['order']['account_number'];
        }

        $data = array_merge($data, $additionalData);

        return $data;
    }

    protected function getEncryptedString($input)
    {
        $data = $this->getAuthorizeRequestData($input);

        $queryString = $this->createQueryString($data);

        $masterKey = $this->getMasterKey();

        return $this->encryptString($queryString, $masterKey);
    }

    protected function getAuthorizeRequestData($input)
    {
        // Formatted for ICICI
        $callbackUrl = '%22' . $input['callbackUrl'] . '%22';

        $prn = $input['payment'][Payment\Entity::ID];

        $amount = $input['payment']['amount'] / 100;

        $data = [
            RequestFields::PAYMENT_REFERENCE_NUBER => $prn ,
            RequestFields::ITEM_CODE               => strtoupper($prn),
            RequestFields::AMOUNT                  => $amount,
            RequestFields::CURRENCY_CODE           => 'INR',
            RequestFields::RETURN_URL              => $callbackUrl,
            RequestFields::CONFIRMATION            => Confirmation::YES,
        ];

        if ($input['merchant']->isTPVRequired())
        {
            $data[RequestFields::ACCOUNT_NO] = $input['order']['account_number'];
        }

        return $data;
    }

    protected function createDefaultRequestData($input)
    {
        $pid = $this->getPid();

        $spid = $this->getSpid();

        $data = [
            RequestFields::OBJ_NAME   => Constants::LOGIN,
            RequestFields::BAY_BANKID => Constants::BANKID,
            RequestFields::MODE       => Mode::PAY,
            RequestFields::PAYEE_ID   => $pid,
            RequestFields::SPID       => $spid,
            RequestFields::AMOUNT     => $input['payment']['amount'] / 100
        ];

        return $data;
    }

    protected function createQueryString($data)
    {
        $urlArray = [];

        foreach ($data as $key => $value)
        {
            $urlArray[] = $key . '=' . $value;
        }

        $url = implode('&', $urlArray);

        return $url;
    }

    protected function createPaymentArray($content)
    {
        return [
            RequestFields::AMOUNT => $content[RequestFields::AMOUNT]
        ];
    }

    protected function getPaymentDate($payment)
    {
        $timestamp = $payment['original']['created_at'];

        return date('Y-m-d', $timestamp);
    }


    protected function getDataFromResponse($data)
    {
        $masterKey = $this->getMasterKey();

        $decryptedString = $this->decryptString($data['ES'], $masterKey);

        parse_str($decryptedString, $content);

        return $content;
    }

    protected function getCallbackAttributes($content)
    {
        return [
            'received'          => true,
            'status'            => $content[ResponseFields::STATUS],
            'bank_payment_id'   => $content[ResponseFields::BANK_PAYMENT_ID]
        ];
    }

    protected function getResponseArray($content)
    {
        $xml = (array) simplexml_load_string($content);

        return $xml['@attributes'];
    }

    public function getMasterKey()
    {
        // Terminal Entitiy
        $masterKey = $this->terminal[Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === RZPMode::TEST)
        {
            $masterKey = $this->config['test_master_key'];
        }

        return $masterKey;
    }

    public function getPid()
    {
        $pid = $this->terminal[Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === RZPMode::TEST)
        {
            $pid = $this->config['test_pid'];
        }

        return $pid;
    }

    public function getSpid()
    {
        $spid = $this->terminal[Entity::GATEWAY_MERCHANT_ID2];

        if ($this->mode === RZPMode::TEST)
        {
            $spid = $this->config['test_spid'];
        }

        return $spid;
    }
}
