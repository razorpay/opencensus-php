<?php

namespace RZP\Gateway\Netbanking\Corporation;

use RZP\Constants\Mode;
use RZP\Gateway\Netbanking\Base;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;

class Gateway extends Base\Gateway
{

    protected $gateway = 'netbanking_corporation';

    protected $bank = 'corporation';

    protected $tpv;

    protected $map = [
        'CustID'    => NetbankingEntity::CUSTOMER_ID,
        'MerCD'     => NetbankingEntity::MERCHANT_CODE,
        'AMT'       => NetbankingEntity::AMOUNT,
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

    protected function getPaymentRequestData($input)
    {
        // Hardcoding the client code for now
        $clientCode = '123';

        $data = array(
            'CustID'            => $clientCode,
            'MerCD'             => $input['terminal']['gateway_merchant_id'],
            'AMT'               => $input['payment']['amount'] / 100,
            'OTC'               => $input['payment']['id'],
            'MD'                => 'P',
            'TT'                => 'T',
        );

        if ($this->mode === Mode::TEST)
        {
            $data['MerchantCode'] = 'RAZORPAY';
        }

        if ($input['merchant']->isTPVRequired())
        {
            $data['AcctNo'] = $input['order']['account_number'];

            if ($this->mode === Mode::TEST)
            {
                $data['MerchantCode'] = 'RAZORPAY1';
            }
        }

        return $data;
    }

}
