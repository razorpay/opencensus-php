<?php

namespace RZP\Gateway\Netbanking\Oriental;

use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Gateway\Netbanking\Base;
use RZP\Models\Currency\Currency;

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
        RequestFields::TXN_AMOUNT => Base\Entity::AMOUNT,
        RequestFields::ITEM_CODE  => Base\Entity::REFERENCE1
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $request = $this->getAuthorizeRequest($input);

        $this->createGatewayPaymentEntity($this->gatewayAttribues);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
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
