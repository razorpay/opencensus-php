<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Transaction;

class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;

    const TRANSACTION_ID               = 'transaction_id';
    const DEVICE_ID                    = 'device_id';
    const HANDLE                       = 'handle';
    const GATEWAY_DATA                 = 'gateway_data';
    const ACTION                       = 'action';
    const STATUS                       = 'status';
    const NETWORK_TRANSACTION_ID       = 'network_transaction_id';
    const GATEWAY_TRANSACTION_ID       = 'gateway_transaction_id';
    const GATEWAY_REFERENCE_ID         = 'gateway_reference_id';
    const RRN                          = 'rrn';
    const REF_ID                       = 'ref_id';
    const REF_URL                      = 'ref_url';
    const MCC                          = 'mcc';
    const GATEWAY_ERROR_CODE           = 'gateway_error_code';
    const GATEWAY_ERROR_DESCRIPTION    = 'gateway_error_description';
    const RISK_SCORES                  = 'risk_scores';
    const PAYER_ACCOUNT_NUMBER         = 'payer_account_number';
    const PAYER_IFSC_CODE              = 'payer_ifsc_code';
    const PAYEE_ACCOUNT_NUMBER         = 'payee_account_number';
    const PAYEE_IFSC_CODE              = 'payee_ifsc_code';

    /************** Input Properties ************/
    const TRANSACTION                  = 'transaction';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_upi_transaction';
    protected $primaryKey         = Entity::TRANSACTION_ID;
    protected $generateIdOnCreate = false;
    protected static $generators  = [
        Entity::REF_ID,
    ];

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::GATEWAY_DATA,
        Entity::ACTION,
        Entity::STATUS,
        Entity::NETWORK_TRANSACTION_ID,
        Entity::GATEWAY_TRANSACTION_ID,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RRN,
        Entity::REF_ID,
        Entity::REF_URL,
        Entity::MCC,
        Entity::GATEWAY_ERROR_CODE,
        Entity::GATEWAY_ERROR_DESCRIPTION,
        Entity::RISK_SCORES,
        Entity::PAYER_ACCOUNT_NUMBER,
        Entity::PAYER_IFSC_CODE,
        Entity::PAYEE_ACCOUNT_NUMBER,
        Entity::PAYEE_IFSC_CODE,
    ];

    protected $visible = [
        Entity::TRANSACTION_ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::ACTION,
        Entity::STATUS,
        Entity::NETWORK_TRANSACTION_ID,
        Entity::GATEWAY_TRANSACTION_ID,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RRN,
        Entity::REF_ID,
        Entity::REF_URL,
        Entity::MCC,
        Entity::GATEWAY_ERROR_CODE,
        Entity::GATEWAY_ERROR_DESCRIPTION,
        Entity::RISK_SCORES,
        Entity::PAYER_ACCOUNT_NUMBER,
        Entity::PAYER_IFSC_CODE,
        Entity::PAYEE_ACCOUNT_NUMBER,
        Entity::PAYEE_IFSC_CODE,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::RRN,
        Entity::REF_ID,
        Entity::REF_URL,
        Entity::MCC,
        Entity::NETWORK_TRANSACTION_ID,
        Entity::GATEWAY_ERROR_CODE,
        Entity::GATEWAY_ERROR_DESCRIPTION,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::GATEWAY_DATA                 => [],
        Entity::ACTION                       => null,
        Entity::STATUS                       => null,
        Entity::NETWORK_TRANSACTION_ID       => null,
        Entity::GATEWAY_TRANSACTION_ID       => null,
        Entity::GATEWAY_REFERENCE_ID         => null,
        Entity::RRN                          => null,
        Entity::REF_ID                       => null,
        Entity::GATEWAY_ERROR_CODE           => null,
        Entity::GATEWAY_ERROR_DESCRIPTION    => null,
        Entity::RISK_SCORES                  => null,
        Entity::PAYER_ACCOUNT_NUMBER         => null,
        Entity::PAYER_IFSC_CODE              => null,
        Entity::PAYEE_ACCOUNT_NUMBER         => null,
        Entity::PAYEE_IFSC_CODE              => null,
    ];

    protected $casts = [
        Entity::TRANSACTION_ID               => 'string',
        Entity::DEVICE_ID                    => 'string',
        Entity::HANDLE                       => 'string',
        Entity::GATEWAY_DATA                 => 'array',
        Entity::ACTION                       => 'string',
        Entity::STATUS                       => 'string',
        Entity::NETWORK_TRANSACTION_ID       => 'string',
        Entity::GATEWAY_TRANSACTION_ID       => 'string',
        Entity::GATEWAY_REFERENCE_ID         => 'string',
        Entity::RRN                          => 'string',
        Entity::REF_ID                       => 'string',
        Entity::GATEWAY_ERROR_CODE           => 'string',
        Entity::GATEWAY_ERROR_DESCRIPTION    => 'string',
        Entity::RISK_SCORES                  => 'string',
        Entity::PAYER_ACCOUNT_NUMBER         => 'string',
        Entity::PAYER_IFSC_CODE              => 'string',
        Entity::PAYEE_ACCOUNT_NUMBER         => 'string',
        Entity::PAYEE_IFSC_CODE              => 'string',
        Entity::CREATED_AT                   => 'int',
        Entity::UPDATED_AT                   => 'int',
    ];

    protected static $unsetCreateInput = [
        Entity::TRANSACTION,
    ];

    protected static $unsetEditInput = [
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::ACTION,
        Entity::TRANSACTION,
        Entity::TRANSACTION_ID,
    ];

    protected function generateRefId($input)
    {
        return $this->setAttribute(Entity::REF_ID, gen_uuid());
    }

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setTransactionId(string $transactionId)
    {
        return $this->setAttribute(self::TRANSACTION_ID, $transactionId);
    }

    /**
     * @return $this
     */
    public function setDeviceId(string $deviceId)
    {
        return $this->setAttribute(self::DEVICE_ID, $deviceId);
    }

    /**
     * @return $this
     */
    public function setHandle(string $handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    /**
     * @return $this
     */
    public function setGatewayData(array $gatewayData)
    {
        return $this->setAttribute(self::GATEWAY_DATA, $gatewayData);
    }

    /**
     * @return $this
     */
    public function setAction(string $action)
    {
        return $this->setAttribute(self::ACTION, $action);
    }

    /**
     * @return $this
     */
    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setNetworkTransactionId(string $networkTransactionId)
    {
        return $this->setAttribute(self::NETWORK_TRANSACTION_ID, $networkTransactionId);
    }

    /**
     * @return $this
     */
    public function setGatewayTransactionId(string $gatewayTransactionId)
    {
        return $this->setAttribute(self::GATEWAY_TRANSACTION_ID, $gatewayTransactionId);
    }

    /**
     * @return $this
     */
    public function setGatewayReferenceId(string $gatewayReferenceId)
    {
        return $this->setAttribute(self::GATEWAY_REFERENCE_ID, $gatewayReferenceId);
    }

    /**
     * @return $this
     */
    public function setRrn(string $rrn)
    {
        return $this->setAttribute(self::RRN, $rrn);
    }

    /**
     * @return $this
     */
    public function setRefId(string $refId)
    {
        return $this->setAttribute(self::REF_ID, $refId);
    }

    /**
     * @return $this
     */
    public function setRefUrl(string $refUrl)
    {
        return $this->setAttribute(self::REF_URL, $refUrl);
    }

    /**
     * @return $this
     */
    public function setMcc(string $mcc)
    {
        return $this->setAttribute(self::MCC, $mcc);
    }

    /**
     * @return $this
     */
    public function setGatewayErrorCode(string $gatewayErrorCode)
    {
        return $this->setAttribute(self::GATEWAY_ERROR_CODE, $gatewayErrorCode);
    }

    /**
     * @return $this
     */
    public function setGatewayErrorDescription(string $gatewayErrorDescription)
    {
        return $this->setAttribute(self::GATEWAY_ERROR_DESCRIPTION, $gatewayErrorDescription);
    }

    /**
     * @return $this
     */
    public function setRiskScores(string $riskScores)
    {
        return $this->setAttribute(self::RISK_SCORES, $riskScores);
    }

    /**
     * @return $this
     */
    public function setPayerAccountNumber(string $payerAccountNumber)
    {
        return $this->setAttribute(self::PAYER_ACCOUNT_NUMBER, $payerAccountNumber);
    }

    /**
     * @return $this
     */
    public function setPayerIfscCode(string $payerIfscCode)
    {
        return $this->setAttribute(self::PAYER_IFSC_CODE, $payerIfscCode);
    }

    /**
     * @return $this
     */
    public function setPayeeAccountNumber(string $payeeAccountNumber)
    {
        return $this->setAttribute(self::PAYEE_ACCOUNT_NUMBER, $payeeAccountNumber);
    }

    /**
     * @return $this
     */
    public function setPayeeIfscCode(string $payeeIfscCode)
    {
        return $this->setAttribute(self::PAYEE_IFSC_CODE, $payeeIfscCode);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::TRANSACTION_ID
     */
    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    /**
     * @return string self::DEVICE_ID
     */
    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }

    /**
     * @return string self::HANDLE
     */
    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    /**
     * @return array self::GATEWAY_DATA
     */
    public function getGatewayData()
    {
        return $this->getAttribute(self::GATEWAY_DATA);
    }

    /**
     * @return string self::ACTION
     */
    public function getAction()
    {
        return $this->getAttribute(self::ACTION);
    }

    /**
     * @return string self::STATUS
     */
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    /**
     * @return string self::NETWORK_TRANSACTION_ID
     */
    public function getNetworkTransactionId()
    {
        return $this->getAttribute(self::NETWORK_TRANSACTION_ID);
    }

    /**
     * @return string self::GATEWAY_TRANSACTION_ID
     */
    public function getGatewayTransactionId()
    {
        return $this->getAttribute(self::GATEWAY_TRANSACTION_ID);
    }

    /**
     * @return string self::GATEWAY_REFERENCE_ID
     */
    public function getGatewayReferenceId()
    {
        return $this->getAttribute(self::GATEWAY_REFERENCE_ID);
    }

    /**
     * @return string self::RRN
     */
    public function getRrn()
    {
        return $this->getAttribute(self::RRN);
    }

    /**
     * @return string self::REF_ID
     */
    public function getRefId()
    {
        return $this->getAttribute(self::REF_ID);
    }

    /**
     * @return string self::REF_URL
     */
    public function getRefUrl()
    {
        return $this->getAttribute(self::REF_URL);
    }

    /**
     * @return string self::MCC
     */
    public function getMcc()
    {
        return $this->getAttribute(self::MCC);
    }

    /**
     * @return string self::GATEWAY_ERROR_CODE
     */
    public function getGatewayErrorCode()
    {
        return $this->getAttribute(self::GATEWAY_ERROR_CODE);
    }

    /**
     * @return string self::GATEWAY_ERROR_DESCRIPTION
     */
    public function getGatewayErrorDescription()
    {
        return $this->getAttribute(self::GATEWAY_ERROR_DESCRIPTION);
    }

    /**
     * @return string self::RISK_SCORES
     */
    public function getRiskScores()
    {
        return $this->getAttribute(self::RISK_SCORES);
    }

    /**
     * @return string self::PAYER_ACCOUNT_NUMBER
     */
    public function getPayerAccountNumber()
    {
        return $this->getAttribute(self::PAYER_ACCOUNT_NUMBER);
    }

    /**
     * @return string self::PAYER_IFSC_CODE
     */
    public function getPayerIfscCode()
    {
        return $this->getAttribute(self::PAYER_IFSC_CODE);
    }

    /**
     * @return string self::PAYEE_ACCOUNT_NUMBER
     */
    public function getPayeeAccountNumber()
    {
        return $this->getAttribute(self::PAYEE_ACCOUNT_NUMBER);
    }

    /**
     * @return string self::PAYEE_IFSC_CODE
     */
    public function getPayeeIfscCode()
    {
        return $this->getAttribute(self::PAYEE_IFSC_CODE);
    }

    /***************** RELATIONS *****************/

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function associateTransaction(Transaction\Entity $entity)
    {
        $this->transaction()->associate($entity);
    }
}
