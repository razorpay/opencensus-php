<?php

namespace RZP\Models\P2p\BankAccount;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Processor extends Base\Processor
{
    public function fetchBanks(array $input): array
    {
        $this->initialize(Action::FETCH_BANKS, $input);

        return [
            'entity' => 'collection',
            'count'  => 2,
            'items'  => [
                [
                    'entity'  => 'bank',
                    'ifsc'    => 'HDFC',
                    'name'    => 'HDFC Bank',
                    'upi'     => true
                ],
                [
                    'entity'  => 'bank',
                    'ifsc'    => 'ICICI',
                    'name'    => 'ICICI Bank',
                    'upi'     => true
                ]
            ]
        ];
    }

    public function retrieve(array $input): array
    {
        $this->initialize(Action::RETRIEVE, $input);

        return [
            'entity' => 'collection',
            'count' => 1,
            'items' => [
                $this->bankAccount('ba_AtIZbXUOTDp3ND'),
            ]
        ];
    }

    public function fetchAll(array $input): array
    {
        $this->initialize(Action::FETCH_ALL, $input);

        return [
            'entity' => 'collection',
            'count' => 2,
            'items' => [
                $this->bankAccount('ba_AtIZbXUOTDp1ND'),
                $this->bankAccount('ba_AtIZbXUOTDp2ND'),
            ]
        ];
    }

    public function fetch(array $input): array
    {
        $this->initialize(Action::FETCH, $input);

        return $this->bankAccount('ba_AtIZbXUOTDp1ND');
    }

    public function initiateSetUpiPin(array $input): array
    {
        $this->initialize(Action::INITIATE_SET_UPI_PIN, $input);

        return [
            'cl' => [
            'registration_format' => 'FORMAT1',
            'mobileNumber'        => '+919123456780',
            'appId'               => 'com.razorpay',
            'deviceId'            => '5878323242',
            'note'                => 'Set UPI Pin',
            'txnId'               => 'RAZ18FCE7E4597443C7963B999CCD70C869',
            'CredAllowed'         => [
                [
                    'type'        => 'PIN',
                    'subtype'     => 'MPIN',
                    'dLength'     => 6,
                    'dFormat'     => 'NUM'
                ],
                [
                    'type'        => 'OTP',
                    'subtype'     => 'ATMPIN',
                    'dLength'     => 4,
                    'dFormat'     => 'NUM'
                ]
            ]
            ]
        ];
    }

    public function setUpiPin(array $input): array
    {
        $this->initialize(Action::SET_UPI_PIN, $input);

        return [
            'id'      => 'ba_AtIZbXUOTDp1ND',
            'success' => true
        ];
    }

    public function initiateFetchBalance(array $input): array
    {
        $this->initialize(Action::INITIATE_FETCH_BALANCE, $input);

        return [
            'cl' => [
                'account'      => '12*********3456',
                'mobileNumber' => '987654321',
                'appId'        => 'com.razorpay',
                'deviceId'     => '5878323242',
                'note'         => 'Balance enquiry',
                'txnId'        => 'RAZ18FCE7E4597443C7963B999CCD70C869',
                'CredAllowed'  => [
                [
                    'type'     => 'PIN',
                    'subtype'  => 'MPIN',
                    'dLength'  => 6,
                    'dType'    => 'NUM',
                ]
                ],
            ]
        ];
    }

    public function fetchBalance(array $input): array
    {
        $this->initialize(Action::FETCH_BALANCE, $input);

        return [
            'id'       => 'ba_AtIZbXUOTDp1ND',
            'balance'  => 2928200,
            'currency' => 'INR'
        ];
    }

    // TODO: To be removed
    private function bankAccount($id)
    {
        return [
            'id' => $id,
            'entity' => 'bank_account',
            'ifsc' => 'ACME000001',
            'bank_name' => 'Acme Bank',
            'beneficiary_name' => 'Gaurav Kumar',
            'masked_account_number' => 'XXXX103101',
            'creds' => [
                [
                    'type' => 'upipin',
                    'set' => true,
                    'length' => 6,
                    'format' => 'numeric',
                    'cl.format' => 'NUM'
                ],
                [
                    'type' => 'atmpin',
                    'length' => 6,
                    'format' => 'numeric',
                    'cl.format' => 'NUM'
                ],
                [
                    'type' => 'otp',
                    'length' => 6,
                    'format' => 'numeric',
                    'cl.format' => 'NUM'
                ]
            ],
            'cl.registration_format' => 'FORMAT1',
            'refreshed_at'  => 1609622306,
            'created_at' => 1509622306
        ];
    }
}
