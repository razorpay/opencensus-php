<?php

namespace RZP\Gateway\Upi\Cashfree\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\Cashfree\Fields;

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
        $url = '/callback/cashfree';
        $method = 'post';

        $server = [
            'CONTENT_TYPE'                      => 'application/json',
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
            Fields::ORDER_AMOUNT    => $amount,
            Fields::REFERENCE_ID    => '706607',
            Fields::TX_STATUS       => $status,
            Fields::PAYMENT_MODE    => 'UPI',
            Fields::TX_MSG          => $responseMassage,
            Fields::SIGNATURE       => 'hUeEEAkLqv7SOEvyrwUZxy6Q3fvTnG7KSdxqfHm2WUk=',
            Fields::TX_TIME         => '2021-02-02 01:04:25',
            Fields::ORDER_ID        => $payment['id'],
        ];

        return $content;
    }
}
