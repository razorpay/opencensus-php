<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;
use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Base\Reconciliate;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const ACTION                = 'action';
    const TYPE                  = 'type';
    const NAME                  = 'name';
    const AMOUNT                = 'amount';
    const ACQUIRER              = 'acquirer';
    const BANK                  = 'bank';
    const PROVIDER              = 'provider';
    const CONTACT               = 'contact';
    const MERCHANT_REFERENCE    = 'merchant_reference';
    const REMARK                = 'remark';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    const GATEWAY_PAYMENT_ID    = 'gateway_payment_id';
    const GATEWAY               = 'gateway';
    const NPCI_REFERENCE_ID     = 'npci_reference_id';
    const NPCI_TXN_ID           = 'npci_txn_id';
    const PAYMENT_ID            = 'payment_id';
    const REFUND_ID             = 'refund_id';
    const EXPIRY_TIME           = 'expiry_time';
    const ACCOUNT_NUMBER        = 'account_number';
    const IFSC                  = 'ifsc';
    const RECEIVED              = 'received';
    const STATUS_CODE           = 'status_code';
    const VPA                   = 'vpa';
    const RECONCILED_AT         = 'reconciled_at';

    const GATEWAY_DATA          = 'gateway_data';
    const GATEWAY_ERROR         = 'gateway_error';

    // Input Keys
    const PAYMENT               = 'payment';
    const UPI                   = 'upi';

    public $incrementing = true;

    protected $entity = 'upi';

    protected $fields = [
        self::ID,
        self::ACTION,
        self::AMOUNT,
        self::TYPE,
        self::ACQUIRER,
        self::BANK,
        self::PROVIDER,
        self::CONTACT,
        self::GATEWAY_DATA,
        self::NAME,
        self::MERCHANT_REFERENCE,
        self::REMARK,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::GATEWAY,
        self::NPCI_REFERENCE_ID,
        self::NPCI_TXN_ID,
        self::REFUND_ID,
        self::PAYMENT_ID,
        self::ACCOUNT_NUMBER,
        self::IFSC,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
        self::EXPIRY_TIME,
        self::RECONCILED_AT,
        self::GATEWAY_ERROR,
    ];

    protected $fillable = [
        self::ACTION,
        self::TYPE,
        self::AMOUNT,
        self::ACQUIRER,
        self::BANK,
        self::PROVIDER,
        self::CONTACT,
        self::NAME,
        self::GATEWAY_DATA,
        self::MERCHANT_REFERENCE,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_PAYMENT_ID,
        self::NPCI_REFERENCE_ID,
        self::NPCI_TXN_ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::ACCOUNT_NUMBER,
        self::IFSC,
        self::RECEIVED,
        self::STATUS_CODE,
        self::VPA,
        self::EXPIRY_TIME,
        self::GATEWAY_ERROR,
    ];

    protected $casts = [
        'amount'       => 'int',
        'gateway_data' => 'array',
        'gateway_error'=> 'array',

    ];

    protected static $generators = [
        self::PROVIDER,
        self::BANK,
        self::RECONCILED_AT,
    ];

    public function setAcquirer($acquirer)
    {
        $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setBank($bank)
    {
        $this->setAttribute(self::BANK, $bank);
    }

    public function setProvider($provider)
    {
        $this->setAttribute(self::PROVIDER, $provider);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setGateway($gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setReconciledAt(int $value)
    {
        return $this->setAttribute(self::RECONCILED_AT, $value);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getGatewayPaymentId()
    {
        return $this->getAttribute(self::GATEWAY_PAYMENT_ID);
    }

    public function setGatewayPaymentId(string $value)
    {
        return $this->setAttribute(self::GATEWAY_PAYMENT_ID, $value);
    }

    public function getNpciReferenceId()
    {
        return $this->getAttribute(self::NPCI_REFERENCE_ID);
    }

    public function getNpciTransactionId()
    {
        return $this->getAttribute(self::NPCI_TXN_ID);
    }

    public function getMerchantReference()
    {
        return $this->getAttribute(self::MERCHANT_REFERENCE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getReconciledAt()
    {
        return $this->getAttribute(self::RECONCILED_AT);
    }

    public function setNpciReferenceId(string $value)
    {
        $this->setAttribute(self::NPCI_REFERENCE_ID, $value);
    }

    public function setNpciTransactionId(string $value)
    {
        $this->setAttribute(self::NPCI_TXN_ID, $value);
    }

    public function setReceived(int $value)
    {
        $this->setAttribute(self::RECEIVED, $value);
    }

    public function getRefundId()
    {
        return $this->getAttribute(self::REFUND_ID);
    }

    public function getStatusCode()
    {
        return $this->getAttributes(self::STATUS_CODE);
    }

    public function extractProviderFromVpa()
    {
        $vpa = $this->getAttribute(self::VPA);

        $vpaParts = explode('@', $vpa);

        return $vpaParts[1] ?? null;
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }

    public function generatePspData($input)
    {
        $this->generateProvider($input);

        $this->generateBank($input);
    }

    protected function generateProvider(array &$input)
    {
        if (isset($input[self::VPA]) === false)
        {
            return;
        }

        $vpa = $input[self::VPA];

        $vpaParts = explode('@', $vpa);

        $provider = $vpaParts[1];

        $this->setAttribute(self::PROVIDER, $provider);
    }

    protected function generateBank($input)
    {
        $provider = $this->getAttribute(self::PROVIDER);

        if (isset($provider) === false)
        {
            return;
        }

        $bank = ProviderCode::getBankCode($provider);

        $this->setAttribute(self::BANK, $bank);
    }

    protected function generateReconciledAt($input)
    {
        // Setting reconciled_At flag for recon flow
        //To handle cases when reconciliation fails after creating unexpected payments
        if (Reconciliate::$isReconRunning === true)
        {
            $this->setAttribute(self::RECONCILED_AT, $this->freshTimestamp());
        }

    }

    public function setGatewayData($value)
    {
        $this->setAttribute(self::GATEWAY_DATA, $value);
    }

    public function getGatewayData()
    {
        return $this->getAttribute(self::GATEWAY_DATA);
    }

    public function setGatewayError($value)
    {
        $this->setAttribute(self::GATEWAY_ERROR, $value);
    }

    public function getGatewayError()
    {
        return $this->getAttribute(self::GATEWAY_ERROR);
    }

    public function getNpciReferenceIdAttribute()
    {
        if ($this->gateway === Gateway::UPI_SBI)
        {
            return $this->parseDataForSbi()[0] ?? null;
        }

        return $this->attributes[self::NPCI_REFERENCE_ID] ?? null;
    }

    public function getGatewayPaymentIdAttribute()
    {
        if ($this->gateway === Gateway::UPI_SBI)
        {
            return $this->parseDataForSbi()[1] ?? null;
        }

        return $this->attributes[self::GATEWAY_PAYMENT_ID] ?? null;
    }

    /**
     * Parses the attributes, returns [NPCI_REFERENCE_ID, GATEWAY_PAYMENT_ID]
     * @return array
     */
    public function parseDataForSbi()
    {
        $param1 = $this->attributes[self::NPCI_REFERENCE_ID];
        $param2 = $this->attributes[self::GATEWAY_PAYMENT_ID];

        if (strlen($param1) === 12)
        {
            return [$param1, $param2];
        }
        else
        {
            return [$param2, $param1];
        }
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
