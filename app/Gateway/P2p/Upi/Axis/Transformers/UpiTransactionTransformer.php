<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\UpiTransaction\Entity;

class UpiTransactionTransformer extends TransactionTransformer
{
    public function transform(): array
    {
        $output = [
            Entity::TRANSACTION_ID              => $this->transformTransactionId(),
            Entity::ACTION                      => $this->input[Fields::ACTION],
            Entity::STATUS                      => $this->input[Fields::STATUS],
            Entity::NETWORK_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_REFERENCE_ID        => $this->input[Fields::MERCHANT_REQUEST_ID],
            Entity::RRN                         => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::GATEWAY_ERROR_CODE          => $this->input[Fields::GATEWAY_RESPONSE_CODE],
            Entity::GATEWAY_ERROR_DESCRIPTION   => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::PAY_TYPE,
            Fields::TRANSACTION_TIME_STAMP,
        ]);

        return $output;
    }

    public function transformTransactionId()
    {
        return substr($this->input[Fields::MERCHANT_REQUEST_ID], -14);
    }

    public function transformIncoming(): array
    {
        $output = [
            Entity::ACTION                      => $this->input[Fields::TYPE],
            Entity::NETWORK_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::RRN                         => $this->input[Fields::GATEWAY_REFERENCE_ID],
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::TYPE,
            Fields::TRANSACTION_TIME_STAMP,
        ]);

        return $output;
    }
}
