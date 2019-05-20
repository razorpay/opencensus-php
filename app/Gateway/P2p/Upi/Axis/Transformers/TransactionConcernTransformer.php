<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Concern\Entity;
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

    public function getGatewayCodeMap($gatewayCode)
    {
        $map = [
            '00'   => [
                Entity::RESPONSE_CODE   => ResponseCode::PENDING,
                Entity::INTERNAL_STATUS => Status::INITIATED,
            ],
            '01'   => [
                Entity::RESPONSE_CODE   => ResponseCode::PENDING,
                Entity::INTERNAL_STATUS => Status::CLOSED,
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
}
