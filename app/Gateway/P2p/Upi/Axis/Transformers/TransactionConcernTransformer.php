<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Concern\Entity;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Models\P2p\Transaction\Concern\Status;
use RZP\Models\P2p\Transaction\Concern\ResponseCode;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;

class TransactionConcernTransformer extends Transformer
{
    public function transform(): array
    {
        $request = $this->transformRequest();

        $output = [
            Entity::ID                      => $this->input[Entity::ID],
            Entity::TRANSACTION_ID          => $this->input[Entity::TRANSACTION_ID],
            Entity::GATEWAY_REFERENCE_ID    => $this->input[Fields::QUERY_REFERENCE_ID],
            Entity::INTERNAL_STATUS         => $request[Entity::INTERNAL_STATUS],
            Entity::RESPONSE_CODE           => $request[Entity::RESPONSE_CODE],
            Entity::RESPONSE_DESCRIPTION    => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::GATEWAY_RESPONSE_CODE,
            Fields::GATEWAY_RESPONSE_MESSAGE,
            Fields::QUERY_CLOSING_TIMESTAMP,
        ]);

        return $output;
    }

    public function transformRequest()
    {
        if ($this->action === TransactionAction::RAISE_QUERY)
        {
            return [
                Entity::RESPONSE_CODE   => ResponseCode::PENDING,
                Entity::INTERNAL_STATUS => Status::INITIATED,
            ];
        }

        $gatewayCode = $this->input[Fields::GATEWAY_RESPONSE_CODE];

        $map = $this->getGatewayCodeMap($gatewayCode);

        return $map;
    }

    public function transformCallback()
    {
        $request = $this->transformRequest();

        $udf = json_decode($this->input[Fields::UDF_PARAMETERS], true);

        $output = [
            Entity::ID                      => $udf[Entity::ID],
            Entity::HANDLE                  => $udf[Entity::HANDLE],
            Entity::GATEWAY_REFERENCE_ID    => $this->input[Fields::QUERY_REFERENCE_ID],
            Entity::INTERNAL_STATUS         => $request[Entity::INTERNAL_STATUS],
            Entity::RESPONSE_CODE           => $request[Entity::RESPONSE_CODE],
            Entity::RESPONSE_DESCRIPTION    => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::GATEWAY_RESPONSE_CODE,
            Fields::GATEWAY_RESPONSE_MESSAGE,
            Fields::QUERY_CLOSING_TIMESTAMP,
            Fields::GATEWAY_TRANSACTION_ID,
        ]);

        return $output;
    }

    public function getGatewayCodeMap($gatewayCode)
    {
        $map = [
            '00'   => [
                Entity::RESPONSE_CODE   => ResponseCode::PENDING,
                Entity::INTERNAL_STATUS => Status::INITIATED,
            ],
            '01'   => [
                Entity::RESPONSE_CODE   => ResponseCode::PENDING,
                Entity::INTERNAL_STATUS => Status::PENDING,
            ],
            '102'  => [
                Entity::RESPONSE_CODE   => ResponseCode::FAILED,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '103'  => [
                Entity::RESPONSE_CODE   => ResponseCode::FAILED,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '104'  => [
                Entity::RESPONSE_CODE   => ResponseCode::FAILED,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '105'  => [
                Entity::RESPONSE_CODE   => ResponseCode::SUCCESS,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '106'  => [
                Entity::RESPONSE_CODE   => ResponseCode::FAILED,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '107'  => [
                Entity::RESPONSE_CODE   => ResponseCode::SUCCESS,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '108'  => [
                Entity::RESPONSE_CODE   => ResponseCode::SUCCESS,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '109'  => [
                Entity::RESPONSE_CODE   => ResponseCode::FAILED,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
            '144'  => [
                Entity::RESPONSE_CODE   => ResponseCode::FAILED,
                Entity::INTERNAL_STATUS => Status::CLOSED,
            ],
        ];

        if (isset($map[$gatewayCode]) === true)
        {
            return $map[$gatewayCode];
        }

        return [
            Entity::RESPONSE_CODE   => ResponseCode::FAILED,
            Entity::INTERNAL_STATUS => Status::CLOSED,
        ];
    }

    public function transformInternal()
    {
        $map = [
            '00' => [
                ResponseCode::SUCCESS,
                'Your transaction was successfully completed',
            ],
            'Z9' => [
                ResponseCode::FAILED,
                'Your transaction failed as there was insufficient balance in your bank account',
            ],
            'K1' => [
                ResponseCode::FAILED,
                'Your transaction could not be processed due to a technical issue at your bank ',
            ],
            'Z8' => [
                ResponseCode::FAILED,
                'Your transaction failed as your transaction limit set by your bank has exceeded',
            ],
            'Z7' => [
                ResponseCode::FAILED,
                'Your transaction failed as you have exceeded the transaction frequency limit set by your bank',
            ],
            'Z6' => [
                ResponseCode::FAILED,
                'Your transaction has failed as you have exceeded the number of UPI PIN entry attempts',
            ],
            'ZM' => [
                ResponseCode::FAILED,
                'Your transaction has failed due to incorrect UPI PIN',
            ],
            'ZX' => [
                ResponseCode::FAILED,
                'Your transaction has failed as your account is either inactive or dormant',
            ],
            'XD' => [
                ResponseCode::FAILED,
                'Your transaction has failed as the amount entered was invalid',
            ],
            'XF' => [
                ResponseCode::FAILED,
                'Your transaction could not be processed due to a technical issue at your bank ',
            ],
            'XH' => [
                ResponseCode::FAILED,
                'Your transaction has failed as the remitting account does not exist',
            ],
            'XJ' => [
                ResponseCode::FAILED,
                'Your transaction could not be processed due to a technical issue at your bank ',
            ],
            'XN' => [
                ResponseCode::FAILED,
                'Your transaction has failed as no card record found',
            ],
            'XP' => [
                ResponseCode::FAILED,
                'Your transaction could not be processed due to a technical issue at your bank ',
            ],
            'XR' => [
                ResponseCode::FAILED,
                'Your transaction could not be processed due to a technical issue at your bank ',
            ],
            'YA' => [
                ResponseCode::FAILED,
                'Your transaction has declined as card details are not valid',
            ],
            'YE' => [
                ResponseCode::FAILED,
                'Your transaction has failed as your account is either blocked or frozen',
            ],
            'ZA' => [
                ResponseCode::FAILED,
                'Transaction was declined',
            ],
            'ZH' => [
                ResponseCode::FAILED,
                'Your transaction has failed as the UPI ID was not valid',
            ],
            'UX' => [
                ResponseCode::FAILED,
                'Your transaction has failed as the UPI ID/Virtual address has expired',
            ],
            'ZG' => [
                ResponseCode::FAILED,
                'Your transaction has failed as the UPI ID/Virtual address is restricted to send/ receive payments',
            ],
            'ZE' => [
                ResponseCode::FAILED,
                'Your transaction has failed as transaction is not permitted to this UPI ID/Virtual address',
            ],
            'RM' => [
                ResponseCode::FAILED,
                'Your transaction has failed due to invalid UPI PIN',
            ],
            'AM' => [
                ResponseCode::FAILED,
                'Your transaction could not be completed as UPI PIN for the account is not set. ',
            ],
        ];

        $gatewayErrorCode = $this->input[Entity::UPI][UpiTransaction\Entity::GATEWAY_ERROR_CODE];

        if (isset($map[$gatewayErrorCode]) === true)
        {
            $output = [
                Entity::ID                      => $this->input[Transaction\Entity::CONCERN][Entity::ID],
                Entity::TRANSACTION_ID          => $this->input[Transaction\Entity::CONCERN][Entity::TRANSACTION_ID],
                Entity::GATEWAY_REFERENCE_ID    => $this->input[Transaction\Entity::CONCERN][Entity::TRANSACTION_ID],
                Entity::INTERNAL_STATUS         => Status::CLOSED,
                Entity::RESPONSE_CODE           => $map[$gatewayErrorCode][0],
                Entity::RESPONSE_DESCRIPTION    => $map[$gatewayErrorCode][1],
            ];

            return $output;
        }
    }
}
