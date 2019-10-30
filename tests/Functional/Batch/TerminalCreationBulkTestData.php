<?php

use RZP\Models\Batch\Header;

return [
    'testBulkTerminalCreation'          => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal_creation',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'type'          => 'terminal_creation',
                'status'        => 'created',
                'total_count'   => 1,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],
    'testBulkTerminalCreationValidateFile' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'terminal_creation',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Header::TERMINAL_CREATION_MERCHANT_ID          => '10NodalAccount',
                        Header::TERMINAL_CREATION_GATEWAY              => 'BILLDESK',
                        Header::TERMINAL_CREATION_GATEWAY_MERCHANT_ID  => '1253',
                        Header::TERMINAL_CREATION_GATEWAY_TERMINAL_ID  => null,
                        Header::TERMINAL_CREATION_GATEWAY_ACCESS_CODE  => null,
                        Header::TERMINAL_CREATION_MC_MPAN              => null,
                        Header::TERMINAL_CREATION_VISA_MPAN            => null,
                        Header::TERMINAL_CREATION_RUPAY_MPAN           => null,
                        Header::TERMINAL_CREATION_VPA                  => null,
                        Header::TERMINAL_CREATION_CATEGORY             => '8211',
                        Header::TERMINAL_CREATION_CARD                 => null,
                        Header::TERMINAL_CREATION_NETBANKING           => null,
                        Header::TERMINAL_CREATION_EMANDATE             => null,
                        Header::TERMINAL_CREATION_EMI                  => null,
                        Header::TERMINAL_CREATION_UPI                  => '1',
                        Header::TERMINAL_CREATION_BANK_TRANSFER        => null,
                        Header::TERMINAL_CREATION_AEPS                 => null,
                        Header::TERMINAL_CREATION_EMI_DURATION         => null,
                        Header::TERMINAL_CREATION_TYPE                 => [
                            'non_recurring'  => '1',
                            'pay'            => '1',
                        ],
                        Header::TERMINAL_CREATION_MODE                 => null,
                        Header::TERMINAL_CREATION_TPV                  => '1',
                        Header::TERMINAL_CREATION_INTERNATIONAL        => null,
                        Header::TERMINAL_CREATION_CORPORATE            => null,
                        Header::TERMINAL_CREATION_EXPECTED             => null,
                        Header::TERMINAL_CREATION_EMI_SUBVENTION       => null,
                        Header::TERMINAL_CREATION_GATEWAY_ACQUIRER     => null,
                        Header::TERMINAL_CREATION_NETWORK_CATEGORY     => null,
                        Header::TERMINAL_CREATION_CURRENCY             => null,
                        Header::TERMINAL_CREATION_ACCOUNT_NUMBER       => null,
                        Header::TERMINAL_CREATION_IFSC_CODE            => null,
                        Header::TERMINAL_CREATION_CARDLESS_EMI         => null,
                        Header::TERMINAL_CREATION_PAYLATER             => null,
                        Header::TERMINAL_CREATION_ENABLED              => null,
                        Header::TERMINAL_CREATION_CAPABILITY           => null,
                    ],
                ],
            ],
        ],
    ],
];
