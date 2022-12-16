<?php

namespace RZP\Gateway\P2p\Upi\Axis\Transformers;

use RZP\Models\P2p\Vpa;
use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Models\P2p\Mandate\Entity;
use RZP\Gateway\P2p\Upi\Axis\Actions\MandateAction;
use RZP\Models\P2p\Transaction\UpiTransaction\Entity as Upi;

/**
 * Class MandateRequestTransformer
 *
 * This is a class to transform mandate request into axis format for respective type
 * @package RZP\Gateway\P2p\Upi\Axis\Transformers
 */
class MandateRequestTransformer extends Transformer
{
    /**
     * Transform the request based on the request type
     * @return array
     */
    public function transform(): array
    {
        $output = [];

        switch ($this->input[Fields::REQUEST_TYPE])
        {
            case MandateAction::APPROVE:
                $output[Fields::ACCOUNT_REFERENCE_ID]  = $this->getAccountReferenceId();
                $output[Fields::AMOUNT]                = $this->getFormattedAmount();
                $output[Fields::CUSTOMER_VPA]          =  $this->getPayerVpa();
                $output[Fields::MANDATE_REQUEST_ID]    = $this->getMandateRequestId();
                $output[Fields::MERCHANT_CUSTOMER_ID]  = $this->getMerchantCustomerId();
                $output[Fields::MERCHANT_REQUEST_ID]   = $this->getMerchantRequestId();
                $output[Fields::PAYEE_NAME]            = $this->getPayeeName();
                $output[Fields::PAYEE_VPA]             = $this->getPayeeVpa();
                $output[Fields::REQUEST_TYPE]          = MandateAction::APPROVE;
                $output[Fields::TIME_STAMP]            = $this->getTimestamp();
                break;

            case MandateAction::DECLINE:
                $output[Fields::ACCOUNT_REFERENCE_ID]  = $this->getAccountReferenceId();
                $output[Fields::AMOUNT]                = $this->getFormattedAmount();
                $output[Fields::CUSTOMER_VPA]          =  $this->getPayerVpa();
                $output[Fields::MANDATE_REQUEST_ID]    = $this->getMandateRequestId();
                $output[Fields::MERCHANT_CUSTOMER_ID]  = $this->getMerchantCustomerId();
                $output[Fields::MERCHANT_REQUEST_ID]   = $this->getMerchantRequestId();
                $output[Fields::REQUEST_TYPE]          = MandateAction::DECLINE;
                $output[Fields::TIME_STAMP]            = $this->getTimestamp();
                break;

            case MandateAction::PAUSE:
                $output[Fields::ACCOUNT_REFERENCE_ID]  = $this->getAccountReferenceId();
                $output[Fields::AMOUNT]                = $this->getFormattedAmount();
                $output[Fields::CUSTOMER_VPA]          =  $this->getPayerVpa();
                $output[Fields::MERCHANT_CUSTOMER_ID]  = $this->getMerchantCustomerId();
                $output[Fields::MERCHANT_REQUEST_ID]   = $this->getMerchantRequestId();
                $output[Fields::ORG_MANDATE_ID]        = $this->getOrgMandateId();
                $output[Fields::PAUSE_END]             = $this->getPauseEnd();
                $output[Fields::PAUSE_START]           = $this->getPauseStart();
                $output[Fields::PAYEE_NAME]            = $this->getPayeeName();
                $output[Fields::PAYEE_VPA]             = $this->getPayeeVpa();
                $output[Fields::REMARKS]               = $this->getDescription();
                $output[Fields::REQUEST_TYPE]          = MandateAction::PAUSE;
                $output[Fields::TIME_STAMP]            = $this->getTimestamp();
                $output[Fields::UPI_REQUEST_ID]        = $this->getUpiRequestId();
                break;

            case MandateAction::UNPAUSE:
                $output[Fields::ACCOUNT_REFERENCE_ID]  = $this->getAccountReferenceId();
                $output[Fields::AMOUNT]                = $this->getFormattedAmount();
                $output[Fields::CUSTOMER_VPA]          =  $this->getPayerVpa();
                $output[Fields::MERCHANT_CUSTOMER_ID]  = $this->getMerchantCustomerId();
                $output[Fields::MERCHANT_REQUEST_ID]   = $this->getMerchantRequestId();
                $output[Fields::ORG_MANDATE_ID]        = $this->getOrgMandateId();
                $output[Fields::PAYEE_NAME]            = $this->getPayeeName();
                $output[Fields::PAYEE_VPA]             = $this->getPayeeVpa();
                $output[Fields::REMARKS]               = $this->getDescription();
                $output[Fields::REQUEST_TYPE]          = MandateAction::UNPAUSE;
                $output[Fields::TIME_STAMP]            = $this->getTimestamp();
                $output[Fields::UPI_REQUEST_ID]        = $this->getUpiRequestId();
                break;

            case MandateAction::REVOKE:
                $output[Fields::ACCOUNT_REFERENCE_ID]  = $this->getAccountReferenceId();
                $output[Fields::AMOUNT]                = $this->getFormattedAmount();
                $output[Fields::CUSTOMER_VPA]          = $this->getPayerVpa();
                $output[Fields::INITIATED_BY]          = 'PAYER';
                $output[Fields::MERCHANT_CUSTOMER_ID]  = $this->getMerchantCustomerId();
                $output[Fields::MERCHANT_REQUEST_ID]   = $this->getMerchantRequestId();
                $output[Fields::ORG_MANDATE_ID]        = $this->getOrgMandateId();
                $output[Fields::PAYEE_NAME]            = $this->getPayeeName();
                $output[Fields::PAYEE_VPA]             = $this->getPayeeVpa();
                $output[Fields::REMARKS]               = $this->getDescription();
                $output[Fields::REQUEST_TYPE]          = MandateAction::REVOKE;
                $output[Fields::TIME_STAMP]            = $this->getTimestamp();
                $output[Fields::UPI_REQUEST_ID]        = $this->getUpiRequestId();
                break;
        }

        return $output;
    }

    /**
     * Transform the udf parameters
     * @return array
     */
    public function transformUdf()
    {
        $udfParameters = [];

        if (empty($this->input[Entity::UPI][Upi::REF_ID]) === false)
        {
            $udfParameters[Upi::REF_ID] = $this->input[Entity::UPI][Upi::REF_ID];
        }

        return $udfParameters;
    }

    /**
     * get the account reference id
     * @return mixed
     */
    public function getAccountReferenceId()
    {
        return $this->input[Entity::BANK_ACCOUNT][Entity::GATEWAY_DATA][Fields::REFERENCE_ID];
    }

    /**
     * get the mandate id
     * @return string
     */
    public function getMandateId()
    {
        return $this->input[Entity::MANDATE][Fields::ID];
    }

    /**
     * get the formatted amount
     * @return string
     */
    public function getFormattedAmount()
    {
        return number_format($this->input[Entity::MANDATE][Entity::AMOUNT] / 100, 2, '.', '');
    }

    /**
     * Get the payer vpa
     * @return mixed
     */
    public function getPayerVpa()
    {
        return $this->input[Entity::PAYER][Vpa\Entity::ADDRESS];
    }

    /**
     * Get the payer name
     * @return mixed
     */
    public function getPayerName()
    {
        return $this->input[Entity::PAYER][Vpa\Entity::BENEFICIARY_NAME];
    }

    /**
     * get the payee vpa name
     * @return mixed
     */
    public function getPayeeVpa()
    {
        return $this->input[Entity::PAYEE][Vpa\Entity::ADDRESS];
    }

    /**
     * get the payee name
     * @return mixed
     */
    public function getPayeeName()
    {
        return $this->input[Entity::PAYEE][Vpa\Entity::BENEFICIARY_NAME];
    }

    /**
     * Get the merchant customer id
     * @return mixed
     */
    public function getMerchantCustomerId()
    {
        return $this->input[Fields::MERCHANT_CUSTOMER_ID];
    }

    /**
     * Get the merchant request id
     * @return string
     */
    public function getMerchantRequestId()
    {
        return 'RAZORPAY' .  str_pad($this->input[Entity::MANDATE][Entity::ID], 27, '0', STR_PAD_LEFT);
    }

    /**
     * This is the method to get description
     * @return mixed
     */
    public function getDescription()
    {
        return $this->input[Entity::MANDATE][Entity::DESCRIPTION];
    }

    /**
     * This is the method to get timestamp
     * @return mixed
     */
    public function getTimestamp()
    {
        return $this->input[Fields::TIMESTAMP];
    }

    /**
     * Get the mandate request id
     * @return string
     */
    public function getOrgMandateId()
    {
        return $this->input[Entity::UPI][Entity::GATEWAY_DATA][Fields::ORG_MANDATE_ID];
    }

    /**
     * Get the mandate request id
     * @return string
     */
    public function getMandateRequestId()
    {
        return  str_pad($this->input[Entity::UPI][Upi::NETWORK_TRANSACTION_ID], 32, '0', STR_PAD_LEFT);
    }

    /**
     * Get upi request id
     */
    public function getUpiRequestId()
    {
        return 'RZP' . $this->input[Entity::MANDATE][Entity::ID] . str_random(18);
    }

    /**
     * This is the method to get end date
     */
    public function getEndDate()
    {
        return $this->input[Entity::MANDATE][Entity::END_DATE];
    }

    /**
     * This is the method to get end date
     */
    public function getMandateExpiry()
    {
        return $this->input[Entity::MANDATE][Entity::EXPIRE_AT];
    }
    /**
     * This is the method to get pause start date
     */
    public function getPauseStart()
    {
        return date('Y/m/d' ,substr($this->input[Entity::MANDATE][Entity::PAUSE_START], 0, 10));
    }
    /**
     * This is the method to get pause end date
     */
    public function getPauseEnd()
    {
        return date('Y/m/d' ,substr($this->input[Entity::MANDATE][Entity::PAUSE_END], 0, 10));
    }
}
