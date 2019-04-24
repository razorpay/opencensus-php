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
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;

class TransactionRequestTransformer extends TransactionTransformer
{
    protected $modeToPayType = [
        Mode::INTENT        => Fields::INTENT_PAY,
        Mode::QR_CODE       => Fields::SCAN_PAY,
        Mode::DEFAULT       => Fields::P2P_PAY,
    ];

    public function transform(): array
    {
        switch ($this->input[Fields::ACTION])
        {
            case TransactionAction::SEND_MONEY:
                $output = [
                    Fields::ACCOUNT_REFERENCE_ID    => $this->getAccountRefenceId(),
                    Fields::AMOUNT                  => $this->getFormattedAmount(),
                    Fields::CURRENCY                => $this->getCurrency(),
                    Fields::CUSTOMER_VPA            => $this->getPayerVpa(),
                    Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                    Fields::MERCHANT_REQUEST_ID     => $this->getMerchantRequestId(),
                    Fields::PAYEE_NAME              => $this->getPayeeName(),
                    Fields::PAYEE_VPA               => $this->getPayeeVpa(),
                    Fields::PAY_TYPE                => $this->getPayType(),
                    Fields::REMARKS                 => $this->getDescription(),
                    Fields::TIME_STAMP              => $this->getTimestamp(),
                    Fields::UPI_REQUEST_ID          => $this->getUpiRequestId(),
                ];
                break;

            case TransactionAction::REQUEST_MONEY:
                $output = [
                    Fields::ACCOUNT_REFERENCE_ID    => $this->getAccountRefenceId(),
                    Fields::AMOUNT                  => $this->getFormattedAmount(),
                    Fields::COLLECT_REQ_EXPIRY_MINS => $this->getCollectExpiryMinutes(),
                    Fields::CURRENCY                => $this->getCurrency(),
                    Fields::CUSTOMER_VPA            => $this->getPayeeVpa(),
                    Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                    Fields::MERCHANT_REQUEST_ID     => $this->getMerchantRequestId(),
                    Fields::PAYER_NAME              => $this->getPayerName(),
                    Fields::PAYER_VPA               => $this->getPayerVpa(),
                    Fields::REMARKS                 => $this->getDescription(),
                    Fields::TIME_STAMP              => $this->getTimestamp(),
                    Fields::UPI_REQUEST_ID          => $this->getUpiRequestId(),
                ];
        }

        return $output;
    }

    public function getAccountRefenceId()
    {
        return $this->input[Entity::BANK_ACCOUNT][Entity::GATEWAY_DATA][Fields::REFERENCE_ID];
    }

    public function getFormattedAmount()
    {
        return number_format($this->input[Entity::TRANSACTION][Entity::AMOUNT] / 100, 2, '.', '');
    }

    public function getCurrency()
    {
        return $this->input[Entity::TRANSACTION][Entity::CURRENCY];
    }

    public function getPayerVpa()
    {
        return $this->input[Entity::PAYER][Vpa\Entity::ADDRESS];
    }

    public function getPayerName()
    {
        return $this->input[Entity::PAYER][Vpa\Entity::BENEFICIARY_NAME];
    }
    public function getPayeeVpa()
    {
        return $this->input[Entity::PAYEE][Vpa\Entity::ADDRESS];
    }
    public function getPayeeName()
    {
        return $this->input[Entity::PAYEE][Vpa\Entity::BENEFICIARY_NAME];
    }

    public function getMerchantCustomerId()
    {
        return $this->input[Fields::MERCHANT_CUSTOMER_ID];
    }

    public function getMerchantRequestId()
    {
        return 'RZP' .  str_pad($this->input[Entity::TRANSACTION][Entity::ID], 32, '0', STR_PAD_LEFT);
    }

    public function getPayType()
    {
        return $this->modeToPayType[$this->input[Entity::TRANSACTION][Entity::MODE]];
    }

    public function getDescription()
    {
        return $this->input[Entity::TRANSACTION][Entity::DESCRIPTION];
    }

    public function getTimestamp()
    {
        return $this->input[Fields::TIMESTAMP];
    }

    public function getUpiRequestId()
    {
        return $this->input[Fields::UPI_REQUEST_ID];
    }

    public function getCollectExpiryMinutes()
    {
        return '100';
    }
}
