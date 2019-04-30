<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Type;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Gateway\P2p\Upi\Axis\ErrorMap;
use RZP\Models\P2p\Transaction\Status;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;

class TransactionTransformer extends Transformer
{
    public function transform(): array
    {
        switch ($this->input[Fields::ACTION])
        {
            case TransactionAction::SEND_MONEY:
                $output = [
                    Entity::TYPE            => Type::PAY,
                    Entity::MODE            => $this->getTransactionMode(),
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case TransactionAction::REQUEST_MONEY:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::MODE            => $this->getTransactionMode(),
                    Entity::FLOW            => Flow::CREDIT,
                    Entity::INTERNAL_STATUS => Status::INITIATED,
                ];
                break;
        }

        $output[Entity::ID] = $this->input[UpiTransaction\Entity::TRANSACTION_ID];

        $this->checkForError($output);

        return $output;
    }

    public function getTransactionMode()
    {
        $payType = $this->input[Entity::GATEWAY_DATA][Fields::PAY_TYPE] ?? null;

        switch ($payType)
        {
            case Fields::INTENT_PAY:
                return Mode::INTENT;

            case Fields::SCAN_PAY:
                return Mode::QR_CODE;

            default:
                return Mode::DEFAULT;
        }
    }

    public function checkForError(& $output)
    {
        $gatewayCode = $this->input[UpiTransaction\Entity::GATEWAY_ERROR_CODE];

        if ($gatewayCode === '00')
        {
            return;
        }

        $output[Entity::INTERNAL_ERROR_CODE] = ErrorMap::gatewayMap($gatewayCode);
        $output[Entity::INTERNAL_STATUS]     = ErrorMap::isDeemedError($gatewayCode) ?
                                                   Status::PENDING :
                                                   Status::FAILED;
    }
}
