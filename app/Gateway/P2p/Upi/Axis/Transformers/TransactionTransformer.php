<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Vpa;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Type;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Gateway\P2p\Upi\Axis\ErrorMap;
use RZP\Models\P2p\Transaction\Status;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
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

            case TransactionAction::PAY_COLLECT:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::MODE            => $this->getTransactionMode(),
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];
                break;

            case TransactionAction::DECLINE_COLLECT:
                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::MODE            => $this->getTransactionMode(),
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::INTERNAL_STATUS => Status::REJECTED,
                ];
                break;
        }

        $output[Entity::ID] = $this->input[UpiTransaction\Entity::TRANSACTION_ID];

        $this->checkForError($output);

        return $output;
    }

    public function transformIncoming(): array
    {
        switch ($this->input[Fields::TYPE])
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:

                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);
                $payee[Vpa\Entity::BENEFICIARY_NAME] = $this->input[Fields::PAYEE_NAME];

                $output = [
                    Entity::TYPE            => Type::COLLECT,
                    Entity::MODE            => $this->getTransactionMode(),
                    Entity::FLOW            => Flow::DEBIT,
                    Entity::AMOUNT          => $this->toPaisa($this->input[Fields::AMOUNT]),
                    Entity::CURRENCY        => 'INR',
                    Entity::DESCRIPTION     => $this->input[Fields::REMARKS],
                    Entity::PAYER           => $payer,
                    Entity::PAYEE           => $payee,
                    Entity::INTERNAL_STATUS => Status::CREATED,
                ];

                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);

                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);
                $payer[Vpa\Entity::BENEFICIARY_NAME] = $this->input[Fields::PAYER_NAME];

                $output = [
                    Entity::TYPE            => Type::PAY,
                    Entity::MODE            => $this->getTransactionMode(),
                    Entity::FLOW            => Flow::CREDIT,
                    Entity::AMOUNT          => $this->toPaisa($this->input[Fields::AMOUNT]),
                    Entity::CURRENCY        => 'INR',
                    Entity::DESCRIPTION     => 'Money recieved',
                    Entity::PAYER           => $payer,
                    Entity::PAYEE           => $payee,
                    Entity::INTERNAL_STATUS => Status::COMPLETED,
                ];

                break;
        }

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

        $internalStatus = Status::FAILED;

        if (in_array($gatewayCode, ErrorMap::$pendingErrors, true) === true)
        {
            $internalStatus = Status::PENDING;
        }
        else if (in_array($gatewayCode, ErrorMap::$rejectedErrors, true) === true)
        {
            $internalStatus = Status::REJECTED;
        }
        else if (in_array($gatewayCode, ErrorMap::$expiredErrors, true) === true)
        {
            $internalStatus = Status::EXPIRED;
        }

        $output[Entity::INTERNAL_STATUS] = $internalStatus;
    }
}
