<?php

namespace RZP\Gateway\Netbanking\Axis;

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
use RZP\Models\Terminal;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    use AesTrait;

    protected $gateway = 'netbanking_axis';

    protected $bank = 'axis';

    const MODE_ECB = 1;

    protected $map = [
        RequestFields::AMOUNT                    => 'amount',
        RequestFields::MERCHANT_UNIQUE_REFERENCE => 'payment_id',
        RequestFields::ITEM_CODE                 => 'caps_payment_id'
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $entity = $this->getDefaultRequestData($input);

        $payment = $this->createGatewayPaymentEntity($entity);

        $this->traceGatewayPaymentRequest($content, $input);

        $request = $this->getStandardRequestArray($content);

        return $request;
    }

    public function callback(array $input)
    {
        sd($input);
    }

    public function verify(array $input)
    {

    }

    protected function getPaymentRequestData($input)
    {
        $pid = $this->getPid();

        $encryptedString = $this->getEncryptedString($input);

        return [
            RequestFields::PAYEE_ID         => $pid,
            RequestFields::ENCRYPTED_STRING => $encryptedString,
            RequestFields::RETURN_URL       => $input['callbackUrl']
        ];
    }

    protected function getEncryptedString($input)
    {
        $masterKey = $this->getMasterKey();

        $defaultData = $this->getDefaultRequestData($input);

        $data = [
            RequestFields::MODE_OF_OPERATION => Constants::PAY,
            RequestFields::CURRENCY_CODE     => Constants::INDIAN_RUPEE,
            RequestFields::CONFIRMATION      => Constants::CONFIRMATION,
            RequestFields::RESPONSE          => Constants::RESPONSE
        ];

        // if tpv is enabled, add tpv account number

        $data = array_merge($defaultData, $data);

        $stringToEncrypt = $this->prepareStringToEncrypt($data);

        return $this->encryptString($stringToEncrypt, $masterKey);
    }

    protected function getVerifyRequestData($input)
    {

    }

    protected function getDefaultRequestData($input)
    {
        $paymentId = $input['payment']['id'];

        $amount = number_format($input['payment']['amount'] /100, 2, '.', ' ');

        return [
            RequestFields::MERCHANT_UNIQUE_REFERENCE => $paymentId,
            RequestFields::ITEM_CODE                 => strtoupper($paymentId),
            RequestFields::AMOUNT                    => $amount
        ];
    }

    protected function prepareStringToEncrypt($data)
    {
        $queryArray = [];

        foreach ($data as $key => $value)
        {
            $queryArray[] = $key . '~' . $value;
        }

        $queryString = implode('$', $queryArray);



        return $queryString;
    }

    protected function createPaymentArray($content)
    {
        $amount = number_format($input['payment']['amount'] /100, 2, '.', ' ');

        return [
            RequestFields::AMOUNT => $amount
        ];
    }

    public function getMasterKey()
    {
        $masterKey = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        if ($this->mode === RZPMode::TEST)
        {
            $masterKey = $this->config['test_master_key'];
        }

        return $masterKey;
    }

    public function getPid()
    {
        $pid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === RZPMode::TEST)
        {
            $pid = $this->config['test_pid'];
        }

        return $pid;
    }
}
