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
use RZP\Gateway\P2p\Upi\Axis\Actions\TransactionAction;
use RZP\Models\P2p\Transaction\UpiTransaction\Entity as Upi;

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

                $output = array_merge($output, $this->transformModeSpecific());

                break;

            case TransactionAction::REQUEST_MONEY:
                $output = [
                    Fields::ACCOUNT_REFERENCE_ID    => $this->getAccountRefenceId(),
                    Fields::AMOUNT                  => $this->getFormattedAmount(),
                    Fields::COLLECT_REQ_EXPIRY_MINS => $this->getCollectExpiryMinutes(),
                    Fields::CUSTOMER_VPA            => $this->getPayeeVpa(),
                    Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                    Fields::MERCHANT_REQUEST_ID     => $this->getMerchantRequestId(),
                    Fields::PAYER_NAME              => $this->getPayerName(),
                    Fields::PAYER_VPA               => $this->getPayerVpa(),
                    Fields::REMARKS                 => $this->getDescription(),
                    Fields::TIME_STAMP              => $this->getTimestamp(),
                    Fields::UPI_REQUEST_ID          => $this->getUpiRequestId(),
                ];
                break;

            case TransactionAction::DECLINE_COLLECT:
                $output = [
                    Fields::ACCOUNT_REFERENCE_ID    => $this->getAccountRefenceId(),
                    Fields::AMOUNT                  => $this->getFormattedAmount(),
                    Fields::CUSTOMER_VPA            => $this->getPayerVpa(),
                    Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                    Fields::MERCHANT_REQUEST_ID     => $this->getMerchantRequestId(),
                    Fields::PAYEE_VPA               => $this->getPayeeVpa(),
                    Fields::TIME_STAMP              => $this->getTimestamp(),
                    Fields::UPI_REQUEST_ID          => $this->getUpiRequestId(),
                ];
                break;

            case TransactionAction::PAY_COLLECT:
                $output = [
                    Fields::ACCOUNT_REFERENCE_ID    => $this->getAccountRefenceId(),
                    Fields::AMOUNT                  => $this->getFormattedAmount(),
                    Fields::CUSTOMER_VPA            => $this->getPayerVpa(),
                    Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                    Fields::MERCHANT_REQUEST_ID     => $this->getMerchantRequestId(),
                    Fields::PAYEE_VPA               => $this->getPayeeVpa(),
                    Fields::TIME_STAMP              => $this->getTimestamp(),
                    Fields::UPI_REQUEST_ID          => $this->getUpiRequestId(),
                ];
                break;

            case TransactionAction::PAY:
                $output = [
                    Fields::MERCHANT_REQUEST_ID     => $this->input[Entity::UPI][Upi::REF_ID],
                    Fields::MERCHANT_CUSTOMER_ID    => $this->getMerchantCustomerId(),
                    Fields::CUSTOMER_VPA            => $this->getPayerVpa(),
                    Fields::MERCHANT_VPA            => $this->getPayeeVpa(),
                    Fields::AMOUNT                  => $this->getFormattedAmount(),
                    Fields::ACCOUNT_REFERENCE_ID    => $this->getAccountRefenceId(),
                    Fields::REMARKS                 => $this->getDescription(),
                    Fields::UPI_REQUEST_ID          => $this->getUpiRequestId(),
                    Fields::TIMESTAMP               => $this->getTimestamp(),
                ];
                break;
        }

        return $output;
    }

    public function transformAction()
    {
        $type = $this->input[Entity::TRANSACTION][Entity::TYPE];
        $flow = $this->input[Entity::TRANSACTION][Entity::FLOW];

        if ($type === Type::PAY)
        {
            if($this->isOnusMerchantPayTransaction())
            {
                return TransactionAction::PAY;
            }

            if ($flow === Flow::DEBIT)
            {
                return TransactionAction::SEND_MONEY;
            }
        }
        else if ($type === Type::COLLECT)
        {
            if ($flow === Flow::DEBIT)
            {
                return TransactionAction::PAY_COLLECT;
            }
            else if ($flow === Flow::CREDIT)
            {
                return TransactionAction::REQUEST_MONEY;
            }
        }
    }

    public function transformUdf()
    {
        $udfParameters = [];

        if (empty($this->input[Entity::UPI][Upi::REF_ID]) === false)
        {
            $udfParameters[Upi::REF_ID] = $this->input[Entity::UPI][Upi::REF_ID];
        }

        return $udfParameters;
    }

    public function transformModeSpecific()
    {
        $output = [];

        if (in_array($this->getPayType(), [Fields::INTENT_PAY, Fields::SCAN_PAY]))
        {
            if ($this->input[Entity::UPI][Upi::REF_ID])
            {
                $output[Fields::TRANSACTION_REFERENCE] = $this->input[Entity::UPI][Upi::REF_ID];
            }

            if ($this->input[Entity::UPI][Upi::REF_URL])
            {
                $output[Fields::REF_URL] = $this->input[Entity::UPI][Upi::REF_URL];
            }

            if ($this->input[Entity::UPI][Upi::MCC])
            {
                $output[Fields::MCC] = $this->input[Entity::UPI][Upi::MCC];
            }
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
        return 'RAZORPAY' .  str_pad($this->input[Entity::TRANSACTION][Entity::ID], 27, '0', STR_PAD_LEFT);
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
        return $this->input[Entity::UPI][Upi::NETWORK_TRANSACTION_ID];
    }

    public function getCollectExpiryMinutes()
    {
        $seconds = $this->input[Entity::TRANSACTION][Entity::EXPIRE_AT] -
                   $this->input[Entity::TRANSACTION][Entity::CREATED_AT];

        return (string) ceil($seconds / 60);
    }

    /**
     * Determines if the intent payment is a P2M,and a onus transaction.
     * @return bool
     */
    protected function isOnusMerchantPayTransaction(): bool
    {
        // mcc should be non-zero, means neither null nor 0000, to categorise it as P2M payment
        if((empty($this->input[Entity::UPI][Upi::MCC]) === true) or
           ($this->input[Entity::UPI][Upi::MCC] === '0000'))
        {
            return false;
        }

        return $this->input[Entity::PAYEE][Entity::HANDLE] === $this->input[Entity::CONTEXT]['handle_code'];
    }
}
