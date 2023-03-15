<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use Base\Traits\NotesTrait;

    const ID                                = "id";
    const MERCHANT_ID                       = "merchant_id";
    const INTEGRATION_ENTITY                = "integration_entity";
    const INTEGRATION_KEY                   = "integration_key";
    const REFERENCE_ID                      = "reference_id";
    const BANK_ACCOUNT                      = "bank_account";
    const NOTES                             = "notes";
    const PAYMENT_METHODS                   = "payment_methods";
    const CREATED_AT                        = "created_at";
    const UPDATED_AT                        = "updated_at";
    const DELETED_AT                        = "deleted_at";

    protected $entity      = 'merchant_international_integrations';

    protected $primaryKey  = self::ID;

    protected $fillable    = [
        self::MERCHANT_ID,
        self::INTEGRATION_ENTITY,
        self::INTEGRATION_KEY,
        self::NOTES,
        self::PAYMENT_METHODS,
        self::REFERENCE_ID,
        self::BANK_ACCOUNT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public      = [
        self::ID,
        self::MERCHANT_ID,
        self::INTEGRATION_ENTITY,
        self::INTEGRATION_KEY,
        self::NOTES,
        self::PAYMENT_METHODS,
        self::REFERENCE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $dates        = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults     = [
        self::NOTES           => [],
        self::PAYMENT_METHODS => [],
        self::REFERENCE_ID    => null,
        self::UPDATED_AT      => null,
        self::DELETED_AT      => null,
    ];

    protected $casts        = [
        self::PAYMENT_METHODS => 'array',
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getIntegrationEntity()
    {
        return $this->getAttribute(self::INTEGRATION_ENTITY);
    }

    public function getIntegrationKey()
    {
        return $this->getAttribute(self::INTEGRATION_KEY);
    }

    public function getReferenceId()
    {
        return $this->getAttribute(self::REFERENCE_ID);
    }

    public function getBankAccount()
    {
        return $this->getAttribute(self::BANK_ACCOUNT);
    }

    public function getPaymentMethods()
    {
        return $this->getAttribute(self::PAYMENT_METHODS);
    }

    public function getNotes()
    {
        return $this->getAttribute(self::NOTES);
    }

    public function setPaymentMethods(array $paymentMethods)
    {
        return $this->setAttribute(self::PAYMENT_METHODS, $paymentMethods);
    }

    public function setNotes(array $notes)
    {
        return $this->setAttribute(self::NOTES, $notes);
    }
}
