<?php

namespace RZP\Gateway\Netbanking\Oriental;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;
use RZP\Exception\GatewayErrorException;

class Gateway extends Base\Gateway
{
    protected $bank = 'oriental';

    protected $gateway = Payment\Gateway::NETBANKING_ORIENTAL;

    /**
     * Variable to store the gateway attributes after mapping
     * @var array
     */
    private $gatewayAttribues = [];

    protected $map = [
        // Auth request mapping
        RequestFields::TXN_AMOUNT       => Base\Entity::AMOUNT,
        RequestFields::ITEM_CODE        => Base\Entity::REFERENCE1,

        // Auth response mapping
        ResponseFields::PAID            => Base\Entity::STATUS,
        ResponseFields::BANK_PAYMENT_ID => Base\Entity::BANK_PAYMENT_ID,
        ResponseFields::DEBIT_ACC_NUM   => Base\Entity::ACCOUNT_NUMBER
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity($this->gatewayAttribues);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $this->parseGatewayResponse($input['gateway']);

        $this->assertPaymentId($input['payment']['id'],
                               $content[RequestFields::PAY_REF_NUM]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentEntity($gatewayPayment, $content, true);

        $this->checkActionStatus($content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    private function checkActionStatus(array $content, $status = Status::SUCCESS)
    {
        if ((empty($content[ResponseFields::PAID]) === false) and
            ($content[ResponseFields::PAID] !== $status))
        {
            throw new GatewayErrorException(ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    private function getAuthorizeRequest(array $input)
    {
        $content = [
            RequestFields::RETURN_URL   => $input['callbackUrl'],
            RequestFields::CATEGORY_ID  => Constants::CATEGORY_ID,
            RequestFields::QUERY_STRING => $this->getQueryString($input)
        ];

        return $this->getStandardRequestArray($content);
    }

    /**
     * This method maps the query array into the required query string format
     * For eg. $queryArray = ['key1' => 'value1', 'key2' => 'value2'] becomes key1~value1&key2~value2
     *
     * @param array $input
     * @return string
     */
    private function getQueryString(array $input)
    {
        $queryArray = [
            RequestFields::TRAN_CRN    => Currency::INR,
            RequestFields::TXN_AMOUNT  => $input['payment']['amount'] / 100,
            RequestFields::PAYEE_ID    => $this->getMerchantId(),
            RequestFields::PAY_REF_NUM => $input['payment']['id'],
            RequestFields::ITEM_CODE   => strtoupper($input['payment']['id'])
        ];

        // We will be using this to map to our gateway entity
        $this->gatewayAttribues = $queryArray;

        return implode(
            "|",
            array_map(
                function($key, $value)
                {
                    return $key . '~' . $value;
                },
                array_keys($queryArray),
                array_values($queryArray)
            ));
    }

    private function parseGatewayResponse(array $response)
    {
        // TODO: Handle decryption here
        return $response;
    }

    private function getMerchantId()
    {
        $merchantId = $this->getLiveMerchantId();

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->getTestMerchantId();
        }

        return $merchantId;
    }
}
