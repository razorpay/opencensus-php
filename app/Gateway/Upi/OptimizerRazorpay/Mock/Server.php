<?php

namespace RZP\Gateway\Upi\OptimizerRazorpay\Mock;

use RZP\Gateway\Base;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Upi\OptimizerRazorpay\Fields;

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
        $url = '/callback/optimizer_razorpay';
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
            Fields::PAYLOAD => [
                Fields::PAYMENT => [
                    Fields::ENTITY => [
                        Fields::ENTITY  => 'payment',
                        'amount'        => $amount,
                        'status'        => $status,
                        'method'        => 'upi',
                        'msg'           => $responseMassage,
                        Fields::NOTES   => [
                            Fields::RECEIPT => $payment['id'],
                        ],
                    ],
                ],
            ],
        ];

        return $content;
    }
}
