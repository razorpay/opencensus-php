<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Constants\Mode;
use RZP\Models\Terminal;
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

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway'          => $this->gateway,
                'gateway_response' => $input['gateway'],
                'payment_id'       => $input['payment']['id']
            ]
        );

        $this->assertPaymentId(
            $input['payment']['id'],
            $content[ResponseFields::PAYMENT_ID]
        );

        $this->checkCallbackStatus($content);
        sd($input);
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
}
