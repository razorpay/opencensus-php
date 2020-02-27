<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Vpa;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Type;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Gateway\P2p\Upi\Axis\ErrorMap;
use RZP\Models\P2p\Transaction\Action;
use RZP\Models\P2p\Transaction\Status;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;

class TransactionTransformer extends Transformer
{
    public function transform(): array
    {
        switch ($this->action)
        {
            case TransactionAction::SEND_MONEY:
            case TransactionAction::PAY:
                $output = [
                    Entity::TYPE            => Type::PAY,
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case TransactionAction::REQUEST_MONEY:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::FLOW            => Flow::CREDIT,
                    Entity::INTERNAL_STATUS => Status::INITIATED,
                ];
                break;

            case TransactionAction::PAY_COLLECT:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case TransactionAction::DECLINE_COLLECT:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::REJECTED,
                ];
                break;

            case UpiAction::COLLECT_REQUEST_RECEIVED:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::REQUESTED,
                ];
                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:
                $output = [
                    Entity::TYPE            => Type::PAY,
                    Entity::FLOW            => Flow::CREDIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::FLOW            => Flow::CREDIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case UpiAction::CUSTOMER_DEBITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case UpiAction::CUSTOMER_DEBITED_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY:
                $output = [
                    Entity::TYPE            => Type::PAY,
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;
        }

        return $output;
    }

    public function transformSdk()
    {
        $request = $this->input[Entity::TRANSACTION];

        $output = $this->transform();

        $this->checkForError($output);

        return array_merge($request, $output);
    }

    public function transformIncoming(): array
    {
        $request = $this->input[Entity::TRANSACTION];

        $output = $this->transform();

        $this->checkForError($output);

        return array_merge($request, $output);
    }

    public function transformCallback(): array
    {
        $request = $this->input[Entity::TRANSACTION];

        $output = $this->transform();

        $this->checkForError($output);

        return array_merge($request, $output);
    }

    public function checkForError(& $output)
    {
        $gatewayCode = $this->input[UpiTransaction\Entity::GATEWAY_ERROR_CODE];

        if ($gatewayCode === '00')
        {
            return;
        }

        $internalErrorCode = ErrorMap::gatewayMap($gatewayCode);

        if (in_array($gatewayCode, ErrorMap::$pendingErrors, true) === true)
        {
            $output[Entity::INTERNAL_STATUS]     = Status::PENDING;
        }
        else if (in_array($gatewayCode, ErrorMap::$rejectedErrors, true) === true)
        {
            $output[Entity::INTERNAL_STATUS]     = Status::REJECTED;
            $output[Entity::INTERNAL_ERROR_CODE] = $internalErrorCode;
        }
        else if (in_array($gatewayCode, ErrorMap::$expiredErrors, true) === true)
        {
            $output[Entity::INTERNAL_STATUS]     = Status::EXPIRED;
            $output[Entity::INTERNAL_ERROR_CODE] = $internalErrorCode;
        }
        else
        {
            $output[Entity::INTERNAL_STATUS]     = Status::FAILED;
            $output[Entity::INTERNAL_ERROR_CODE] = $internalErrorCode;
        }

    }
}
