<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function initiatePay(array $input): array
    {
        $this->initialize(Action::INITIATE_PAY, $input);

        return [
            'request' => [
                'url' => url('{customer_id}/transactions/{transaction_id}/authorize'),
                'method' => 'post'
            ],
            'cl' => [
                'txnId' => 'HDF096A6D8A0F184109B08E697435B44484',
                'txnAmount' => '108.00',
                'note' => 'Enter UPI PIN',
                'refurl' => 'https://razorpay.com',
                'payerAddr' => 'gaurav.kumar@razorhdfc',
                'payeeAddr' => 'saurav.kumar@razoricici',
                'payeeName' => 'Saurav Kumar',
                'mobileNumber' => '9123456780',
                'appId' => 'com.razorpay',
                'deviceId' => '5878323242',
                'account' => '*********1234',
                'CredAllowed' => [
                    [
                        'type' => 'PIN',
                        'subtype' => 'MPIN',
                        'dLength' => '6',
                        'dFormat' => 'NUM'
                    ]
                ]
            ]
        ];
    }

    public function initiateCollect(array $input): array
    {
        $this->initialize(Action::INITIATE_COLLECT, $input);

        return [
            'status' => 'pending',
            'status_url' => url('RAZC13AAEE5E4B44E5AA464B1E4E39CACBC'),
            'expire_at' => 1509622306
        ];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                $this->fetch($input),
                $this->fetch($input),
            ]
        ];
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input);

        return [
            'id' => 'ctxn_AzooTd2JN7kfqo',
            'entity' => 'customer.transaction',
            'txn_id' => 'HDFE47D3D30DCAD4EDBA58945BF229D6A83',
            'status' => 'created',
            'amount' => 100,
            'description' => 'Pay me now',
            'type' => 'collect',
            'currency' => 'INR',
            'error_description' => null,
            'error_code' => null,
            'transaction_type' => 'debit',
            'rrn' => '826305979008',
            'created_at' => 1537402959,
            'completed_at' => null,
            'expire_at' => 1537406559
        ];
    }

    public function initiateAuthorize(array $input): array
    {
        $this->initialize(Action::INITIATE_AUTHORIZE, $input);

        return $this->initiatePay($input);
    }

    public function authorize(array $input): array
    {
        $this->initialize(Action::AUTHORIZE, $input);

        return $this->initiateCollect($input);
    }

    public function reject(array $input): array
    {
        $this->initialize(Action::REJECT, $input);

        return $this->initiateCollect($input);
    }
}
