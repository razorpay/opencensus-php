<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Transaction;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Transaction\Status;
use RZP\Models\P2p\Transaction\Action;
use RZP\Gateway\P2p\Upi\Axis\Actions\UpiAction;
use RZP\Models\P2p\Transaction\UpiTransaction\Entity;
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;

class UpiTransactionTransformer extends Transformer
{
    public function transform(): array
    {
        switch ($this->action)
        {
            case TransactionAction::SEND_MONEY:
                $output = [
                    Entity::ACTION  => Action::INITIATE_PAY,
                    Entity::STATUS  => Status::COMPLETED,
                ];
                break;

            case TransactionAction::PAY:
                $output = [
                    Entity::ACTION  => Action::INITIATE_PAY,
                    Entity::STATUS  => Status::COMPLETED,
                ];
                break;

            case TransactionAction::REQUEST_MONEY:
                $output = [
                    Entity::ACTION  => Action::INITIATE_COLLECT,
                    Entity::STATUS  => Status::INITIATED,
                ];
                break;

            case TransactionAction::PAY_COLLECT:
                $output = [
                    Entity::ACTION  => Action::INCOMING_COLLECT,
                    Entity::STATUS  => Status::COMPLETED,
                ];
                break;

            case TransactionAction::DECLINE_COLLECT:
                $output = [
                    Entity::ACTION  => Action::INCOMING_COLLECT,
                    Entity::STATUS  => Status::REJECTED,
                ];
                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:
                $output = [
                    Entity::ACTION  => Action::INCOMING_PAY,
                    Entity::STATUS  => Status::COMPLETED,
                    Entity::HANDLE  => $this->getVpaHandle($this->input[Fields::PAYEE_VPA]),
                ];
                break;

            case UpiAction::COLLECT_REQUEST_RECEIVED:
                $output = [
                    Entity::ACTION  => Action::INCOMING_COLLECT,
                    Entity::STATUS  => Status::CREATED,
                    Entity::HANDLE  => $this->getVpaHandle($this->input[Fields::PAYER_VPA]),
                ];

                $this->input[Fields::GATEWAY_RESPONSE_CODE]     = '00';
                $this->input[Fields::GATEWAY_RESPONSE_MESSAGE]  = 'Incoming collect request';

                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
                $output = [
                    Entity::ACTION  => Action::INITIATE_COLLECT,
                    Entity::STATUS  => Status::COMPLETED,
                    Entity::HANDLE  => $this->getVpaHandle($this->input[Fields::PAYEE_VPA]),
                ];
                break;

            case UpiAction::CUSTOMER_DEBITED_VIA_COLLECT:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_COLLECT:
                $output = [
                    Entity::ACTION  => Action::INCOMING_COLLECT,
                    Entity::STATUS  => Status::COMPLETED,
                    Entity::HANDLE  => $this->getVpaHandle($this->input[Fields::PAYER_VPA]),
                ];
                break;

            case UpiAction::CUSTOMER_DEBITED_VIA_PAY:
            case UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY:
                $output = [
                    Entity::ACTION  => Action::INITIATE_PAY,
                    Entity::STATUS  => Status::COMPLETED,
                    Entity::HANDLE  => $this->getVpaHandle($this->input[Fields::PAYER_VPA]),
                ];
                break;
        }

        return $output;
    }

    public function transformSdk(): array
    {
        $request = $this->transform();

        $output = [
            Entity::TRANSACTION_ID              => $this->transformTransactionId(),
            Entity::NETWORK_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_REFERENCE_ID        => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::RRN                         => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::GATEWAY_ERROR_CODE          => $this->input[Fields::GATEWAY_RESPONSE_CODE],
            Entity::GATEWAY_ERROR_DESCRIPTION   => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::MERCHANT_REQUEST_ID,
            Fields::TYPE,
            Fields::TRANSACTION_TIME_STAMP,
        ]);

        $output[Transaction\Entity::TRANSACTION] = [
            Transaction\Entity::ID               => $this->transformTransactionId(),
            Transaction\Entity::MODE             => $this->transformTransactionMode(),
            Transaction\Entity::AMOUNT           => $this->toPaisa($this->input[Fields::AMOUNT]),
        ];

        return array_merge($request, $output);
    }

    public function transformTransactionId()
    {
        if (isset($this->input[Fields::MERCHANT_REQUEST_ID]) === true)
        {
            return substr($this->input[Fields::MERCHANT_REQUEST_ID], -14);
        }
    }

    public function transformTransactionMode()
    {
        $payType = $this->input[Fields::PAY_TYPE] ?? null;

        switch ($payType)
        {
            case Fields::INTENT_PAY:
                return Transaction\Mode::INTENT;

            case Fields::SCAN_PAY:
                return Transaction\Mode::QR_CODE;

            default:
                return Transaction\Mode::DEFAULT;
        }
    }

    public function transformIncoming(): array
    {
        $request = $this->transform();

        $output = [
            Entity::NETWORK_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_REFERENCE_ID        => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::RRN                         => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::GATEWAY_ERROR_CODE          => $this->input[Fields::GATEWAY_RESPONSE_CODE],
            Entity::GATEWAY_ERROR_DESCRIPTION   => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
            Entity::MCC                         => $this->input[Fields::PAYEE_MCC] ?? '',
            Entity::REF_URL                     => $this->input[Fields::REF_URL] ?? '',
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::TYPE,
            Fields::TRANSACTION_TIME_STAMP,
        ]);

        $output[Transaction\Entity::TRANSACTION] = $this->transformTransaction();

        return array_merge($request, $output);
    }

    public function transformCallback(): array
    {
        $request = $this->transform();

        $output = [
            Entity::TRANSACTION_ID              => $this->transformTransactionId(),
            Entity::NETWORK_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_TRANSACTION_ID      => $this->input[Fields::GATEWAY_TRANSACTION_ID],
            Entity::GATEWAY_REFERENCE_ID        => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::RRN                         => $this->input[Fields::GATEWAY_REFERENCE_ID],
            Entity::GATEWAY_ERROR_CODE          => $this->input[Fields::GATEWAY_RESPONSE_CODE],
            Entity::GATEWAY_ERROR_DESCRIPTION   => $this->input[Fields::GATEWAY_RESPONSE_MESSAGE],
            Entity::MCC                         => $this->input[Fields::PAYEE_MCC] ?? '',
            Entity::REF_URL                     => $this->input[Fields::REF_URL] ?? '',
        ];

        $output[Entity::GATEWAY_DATA] = array_only($this->input, [
            Fields::MERCHANT_REQUEST_ID,
            Fields::TYPE,
            Fields::TRANSACTION_TIME_STAMP,
        ]);

        $output[Transaction\Entity::TRANSACTION] = [
            Transaction\Entity::ID               => $this->transformTransactionId(),
            Transaction\Entity::MODE             => $this->transformTransactionMode(),
            Transaction\Entity::AMOUNT           => $this->toPaisa($this->input[Fields::AMOUNT]),
        ];

        /*
        * Special case: For PAY api which is for a P2P Transaction we need to unset the
        * transaction id for the callback, as we are relying on the (gatewayTransactionId+action)
        * combination to fetch the transaction.
        */
        if ($this->input[Fields::TYPE] === UpiAction::CUSTOMER_DEBITED_FOR_MERCHANT_VIA_PAY)
        {
            unset($output[Entity::TRANSACTION_ID]);
            unset($output[Transaction\Entity::TRANSACTION][Transaction\Entity::ID]);
        }

        return array_merge($request, $output);
    }

    public function transformHandle()
    {
        switch ($this->input[Fields::TYPE])
        {
            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:

                $vpa = $this->input[Fields::PAYEE_VPA];
                break;
        }

        return explode(Vpa\Entity::AEROBASE, $vpa)[1];
    }

    public function transformCallbackRequest()
    {
        switch ($this->input[Fields::TYPE])
        {
            case UpiAction::CUSTOMER_CREDITED_VIA_COLLECT:
                $action = Action::INITIATE_COLLECT;
                $vpa    = $this->input[Fields::PAYEE_VPA];
                break;
        }

        return [
            Entity::ACTION  => $action,
            Entity::HANDLE  => explode(Vpa\Entity::AEROBASE, $vpa)[1],
        ];
    }

    public function getVpaHandle($vpa)
    {
        return explode(Vpa\Entity::AEROBASE, $vpa)[1];
    }

    public function transformTransaction()
    {
        switch ($this->action)
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:

                $payer    = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);

                $payee    = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);
                $verified = $this->input[Fields::IS_VERIFIED_PAYEE] ?? false;
                $payee[Vpa\Entity::BENEFICIARY_NAME]    = $this->input[Fields::PAYEE_NAME];
                $payee[Vpa\Entity::VERIFIED]            = $this->toBoolean($verified);

                $expiryAt = $this->transformExpireAt();

                break;

            case UpiAction::CUSTOMER_CREDITED_VIA_PAY:

                $payee = $this->toUsernameHandle($this->input[Fields::PAYEE_VPA]);

                $payer = $this->toUsernameHandle($this->input[Fields::PAYER_VPA]);
                $payer[Vpa\Entity::BENEFICIARY_NAME]    = $this->input[Fields::PAYER_NAME];

                break;
        }

        $output = [
            Transaction\Entity::CURRENCY         => 'INR',
            Transaction\Entity::MODE             => $this->transformTransactionMode(),
            Transaction\Entity::AMOUNT           => $this->toPaisa($this->input[Fields::AMOUNT]),
            Transaction\Entity::DESCRIPTION      => $this->input[Fields::REMARKS] ?? 'No Remarks',
            Transaction\Entity::PAYER            => $payer,
            Transaction\Entity::PAYEE            => $payee,
        ];

        if (isset($expiryAt) === true)
        {
            $output[Transaction\Entity::EXPIRE_AT] = $expiryAt;
        }

        return $output;
    }

    public function transformExpireAt()
    {
        switch ($this->action)
        {
            case UpiAction::COLLECT_REQUEST_RECEIVED:
                return Carbon::parse($this->input[Fields::EXPIRY])->getTimestamp();
        }
    }
}
