<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{
    protected $gateway = 'netbanking_corporation';

    protected $bank = 'corporation';

    protected $tpv;

    protected $map = [
        RequestFields::CUSTOMER_ID      => NetbankingEntity::CUSTOMER_ID,
        RequestFields::MERCHANT_CODE    => NetbankingEntity::MERCHANT_CODE,
        RequestFields::AMOUNT           => NetbankingEntity::AMOUNT,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getPaymentRequestData($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

        $request = array(
            'url' => $this->getUrl('pay'),
            'method' => 'post',
            'content' => $content);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $content,
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        $this->checkCallbackStatus($content);
        sd($content);
    }

    protected function getPaymentRequestData($input)
    {
        $data = array(
            // Setting this as the merchant code shared with us
            'CustID'            => $this->getMerchantId(),
            'MerCD'             => $this->getMerchantId(),
            'AMT'               => $input['payment']['amount'] / 100,
            'OTC'               => $input['payment']['id'],
            'MD'                => 'P',
            'TT'                => 'T',
        );

        if ($input['merchant']->isTPVRequired())
        {
            $data['AcctNo'] = $input['order']['account_number'];
        }

        return $data;
    }

    public function getMerchantId()
    {
        $mid = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $mid = $this->getTestMerchantId();
        }

        return $mid;
    }

    /**
     * In this case, we have added a custom callback route.
     * When the callback is called from their end, we need
     * to generate the gateway instance from the payment id
     * in the callback.
     *
     * This function identifies the above and returns the same.
     *
     * @param array $input
     * @return String
     */
    public function getPaymentIdFromServerCallback($input)
    {
        return $input[ResponseFields::PAYMENT_ID];
    }

    protected function checkCallbackStatus(array $content)
    {
        if ($content[ResponseFields::STATUS] !== ResponseCodeMap::SUCCESS_CODE)
        {
            $this->trace->info(
                TraceCode::PAYMENT_CALLBACK_FAILURE,
                [
                    'content' => $content
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

}
