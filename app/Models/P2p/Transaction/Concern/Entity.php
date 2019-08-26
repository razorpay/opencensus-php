<?php

namespace RZP\Models\P2p\Transaction\Concern;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Transaction;

class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;

    const TRANSACTION_ID           = 'transaction_id';
    const DEVICE_ID                = 'device_id';
    const HANDLE                   = 'handle';
    const GATEWAY_DATA             = 'gateway_data';
    const STATUS                   = 'status';
    const INTERNAL_STATUS          = 'internal_status';
    const COMMENT                  = 'comment';
    const GATEWAY_REFERENCE_ID     = 'gateway_reference_id';
    const RESPONSE_CODE            = 'response_code';
    const RESPONSE_DESCRIPTION     = 'response_description';
    const CLOSED_AT                = 'closed_at';

    /***************** Input Keys ****************/
    const CONCERN                  = 'concern';
    const CONCERNS                 = 'concerns';
    const TRANSACTION              = 'transaction';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_concern';
    protected $generateIdOnCreate = true;
    protected static $generators  = [];

    protected $dates = [
        Entity::CLOSED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::COMMENT,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RESPONSE_CODE,
        Entity::RESPONSE_DESCRIPTION,
    ];

    protected $visible = [
        Entity::ID,
        Entity::TRANSACTION_ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::COMMENT,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RESPONSE_CODE,
        Entity::RESPONSE_DESCRIPTION,
        Entity::UPDATED_AT,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::TRANSACTION_ID,
        Entity::ENTITY,
        Entity::STATUS,
        Entity::COMMENT,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RESPONSE_CODE,
        Entity::RESPONSE_DESCRIPTION,
        Entity::CREATED_AT,
        Transaction\Entity::TRANSACTION,
    ];

    protected $defaults = [
        Entity::GATEWAY_DATA             => [],
        Entity::STATUS                   => Status::CREATED,
        Entity::INTERNAL_STATUS          => Status::CREATED,
        Entity::COMMENT                  => null,
        Entity::GATEWAY_REFERENCE_ID     => null,
        Entity::RESPONSE_CODE            => null,
        Entity::RESPONSE_DESCRIPTION     => null,
    ];

    protected $casts = [
        Entity::ID                       => 'string',
        Entity::TRANSACTION_ID           => 'string',
        Entity::DEVICE_ID                => 'string',
        Entity::HANDLE                   => 'string',
        Entity::GATEWAY_DATA             => 'array',
        Entity::STATUS                   => 'string',
        Entity::INTERNAL_STATUS          => 'string',
        Entity::COMMENT                  => 'string',
        Entity::GATEWAY_REFERENCE_ID     => 'string',
        Entity::RESPONSE_CODE            => 'string',
        Entity::RESPONSE_DESCRIPTION     => 'string',
        Entity::CREATED_AT               => 'int',
        Entity::UPDATED_AT               => 'int',
    ];

    protected $publicSetters      = [
        self::ENTITY,
        self::TRANSACTION_ID,
    ];

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
    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setInternalStatus(string $status)
    {
        $this->setStatus($status);

        return $this->setAttribute(self::INTERNAL_STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setComment(string $comment)
    {
        return $this->setAttribute(self::COMMENT, $comment);
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
    public function setResponseCode(string $responseCode)
    {
        return $this->setAttribute(self::RESPONSE_CODE, $responseCode);
    }

    /**
     * @return $this
     */
    public function setResponseDescription(string $responseDescription)
    {
        return $this->setAttribute(self::RESPONSE_DESCRIPTION, $responseDescription);
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
     * @return string self::STATUS
     */
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    /**
     * @return string self::COMMENT
     */
    public function getComment()
    {
        return $this->getAttribute(self::COMMENT);
    }

    /**
     * @return string self::GATEWAY_REFERENCE_ID
     */
    public function getGatewayReferenceId()
    {
        return $this->getAttribute(self::GATEWAY_REFERENCE_ID);
    }

    /**
     * @return string self::RESPONSE_CODE
     */
    public function getResponseCode()
    {
        return $this->getAttribute(self::RESPONSE_CODE);
    }

    /**
     * @return string self::RESPONSE_DESCRIPTION
     */
    public function getResponseDescription()
    {
        return $this->getAttribute(self::RESPONSE_DESCRIPTION);
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return ($this->getStatus() === Status::CLOSED);
    }

    /***************** RELATIONS *****************/

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class)->withTrashed();
    }

    public function associateTransaction(Transaction\Entity $entity)
    {
        $this->transaction()->associate($entity);
    }

    public function setPublicTransactionIdAttribute(& $array)
    {
        if (isset($array[self::TRANSACTION_ID]))
        {
            $array[self::TRANSACTION_ID] = Transaction\Entity::getSignedId($array[self::TRANSACTION_ID]);
        }
    }
}
