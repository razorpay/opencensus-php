<?php

namespace RZP\Gateway\Upi\Payu\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Payu\Fields;

class Server extends Base\Mock\Server
{
    public function getCallback(array $upiEntity, array $payment)
    {
        $this->action = Action::CALLBACK;
        $content = $this->getCallbackData($upiEntity, $payment);
        $this->content($content, 'callback');

        return $this->getCallbackRequest($content);
    }

    public function getCallbackRequest($data)
    {
        $url = '/callback/payu';
        $method = 'post';

        $server = [
            'CONTENT_TYPE'            => 'application/json',
        ];

        $raw = json_encode($data);

        return [
            'url'       => $url,
            'method'    => $method,
            'raw'       => $raw,
            'server'    => $server,
        ];
    }

    protected function getCallbackData(array $upiEntity, array $payment)
    {
        $status     = 'SUCCESS';
        $amount     = $payment['amount'];
        $responseMassage = 'Transaction Successful';

        switch ($payment['description'])
        {
            case 'callback_failed_v2':
                $status     = 'FAILED';
                $responseMassage = 'Transaction Failed';
                break;

            case 'callback_amount_mismatch_v2':
                $amount = $payment['amount'] + 100;
                break;
        }

        $content = [
            Fields:: MIHPAYID          => '12965951609',
            Fields:: MODE              => 'UPI',
            Fields:: STATUS            => $status,
            Fields:: KEY               => 'iVELey',
            Fields:: TXNID             => $payment['id'],
            Fields:: AMOUNT            => $amount,
            Fields:: ADDEDON           => '2021-05-11 16:58:53',
            Fields:: PRODUCTINFO       => 'test payment',
            Fields:: FIRSTNAME         => 'Saurabh Arya',
            Fields:: LASTNAME          => '',
            Fields:: ADDRESS1          => '',
            Fields:: ADDRESS2          => '',
            Fields:: CITY              => '',
            Fields:: STATE             => '',
            Fields:: COUNTRY           => '',
            Fields:: ZIPCODE           => '',
            Fields:: EMAIL             => 'test@test.com',
            Fields:: PHONE             => '',
            Fields:: UDF1              => '',
            Fields:: UDF2              => '',
            Fields:: UDF3              => '',
            Fields:: UDF4              => '',
            Fields:: UDF5              => '',
            Fields:: UDF6              => '',
            Fields:: UDF7              => '',
            Fields:: UDF8              => '',
            Fields:: UDF9              => '',
            Fields:: UDF10             => '',
            Fields:: CARD_TOKEN        => '',
            Fields:: CARD_NO           => '',
            Fields:: FIELD0            => '',
            Fields:: FIELD1            => '9557147404@apl',
            Fields:: FIELD2            => '29846387767',
            Fields:: FIELD3            => '9557147404@apl',
            Fields:: FIELD4            => 'SAURABH ARYA',
            Fields:: FIELD5            => 'HDFEDED9BBBE83B40C6A0E409352D6ACFCC',
            Fields:: FIELD6            => 'Paytm Payments Bank!919557147404!PYTM0123456!919557147404',
            Fields:: FIELD7            => $responseMassage,
            Fields:: FIELD8            => '',
            Fields:: FIELD9            => 'SUCCESS|Completed Using Callback',
            Fields:: PAYMENT_SOURCE    => 'payuPureS2S',
            Fields:: PG_TYPE           => 'UPI-PG',
            Fields:: ERROR             => 'E000',
            Fields:: ERROR_MESSAGE     => 'No Error',
            Fields:: NET_AMOUNT_DEBIT  => '1',
            Fields:: UNMAPPEDSTATUS    => 'captured',
            Fields:: HASH              => '7e9aaa962617f31dee8a19f5c32f60d2d55a999fe2a9268da3e900f3214ea8a008c2752ef62113d51aff4213472958ba48b4d25f2eb66024932cb22bdc210050',
            Fields:: BANK_REF_NO       => '113116977715',
            Fields:: BANK_REF_NUM      => '113116977715',
            Fields:: BANKCODE          => 'UPI',
            Fields:: SURL              => 'https://zeta-api.razorpay.in/v1/callback/payu',
            Fields:: CURL              => 'https://zeta-api.razorpay.in/v1/callback/payu',
        ];

        return $content;
    }
}
