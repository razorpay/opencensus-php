<?php

namespace RZP\Models\CorporateCard;

use RZP\Models\Base;
use RZP\Models\Merchant;

/**
 * Class Entity
 *
 * @package RZP\Models\CorporateCard
 *
 * @property Merchant\Entity $merchant
 */

class Entity extends Base\PublicEntity
{
    // Attributes
    const LAST4         = 'last4';
    const NAME          = 'name';
    const HOLDER_NAME   = 'holder_name';
    const EXPIRY_MONTH  = 'expiry_month';
    const EXPIRY_YEAR   = 'expiry_year';
    const IS_ACTIVE     = 'is_active';
    const NETWORK       = 'network';
    const ISSUER        = 'issuer';
    const BILLING_CYCLE = 'billing_cycle';
    const VAULT_TOKEN   = 'vault_token';
    const CREATED_BY    = 'created_by';
    const UPDATED_BY    = 'updated_by';
    const TOKEN         = 'token';

    /**
     * Number are never saved in the database, but is only referenced in memory
     */
    const NUMBER = 'number';

    const RESPONSE_CODE   = 'response_code';

    protected static $sign = 'corp_card';

    protected $entity = 'corporate_card';

    protected $fillable = [
        self::ID,
        self::NAME,
        self::HOLDER_NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::IS_ACTIVE,
        self::BILLING_CYCLE,
    ];

    protected $defaults = [
        self::IS_ACTIVE => true,
    ];

    protected $guarded = [self::ID];

    protected static $modifiers = [];

    protected static $generators = [
        self::ID,
        self::LAST4,
    ];

    protected $casts = [
        self::IS_ACTIVE => 'bool',
        self::EXPIRY_MONTH => 'int',
        self::EXPIRY_YEAR => 'int',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $hidden = [];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::NAME,
        self::HOLDER_NAME,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::LAST4,
        self::NETWORK,
        self::ISSUER,
        self::BILLING_CYCLE,
        self::VAULT_TOKEN,
        self::IS_ACTIVE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CREATED_BY,
        self::UPDATED_BY,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::HOLDER_NAME,
        self::LAST4,
        self::NETWORK,
        self::ISSUER,
        self::EXPIRY_MONTH,
        self::EXPIRY_YEAR,
        self::BILLING_CYCLE,
        self::IS_ACTIVE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CREATED_BY,
        self::UPDATED_BY,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // --------------- Getters and Setters---------------

    public function setName(string $name = null)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function setHolderName(string $name = null)
    {
        $this->setAttribute(self::HOLDER_NAME, $name);
    }

    public function getHolderName()
    {
        return $this->getAttribute(self::HOLDER_NAME);
    }

    protected function generateLast4($input)
    {
        $last4 = substr($input['number'], -4);

        $this->setAttribute(self::LAST4, $last4);
    }

    public function getLast4()
    {
        return $this->getAttribute(self::LAST4);
    }

    public function getMaskedCardNumber()
    {
        // $this->getLast4() returns the last 4 digits
        return 'XXXX XXXX XXXX ' . $this->getLast4();
    }

    public function setVaultToken(string $vaultToken = null)
    {
        $this->setAttribute(self::VAULT_TOKEN, $vaultToken);
    }

    protected function getVaultToken()
    {
        return $this->getAttribute(self::VAULT_TOKEN);
    }

    protected function setExpiryMonth(int $expiryMonth)
    {
        return $this->setAttribute(self::EXPIRY_MONTH, $expiryMonth);
    }

    protected function getExpiryMonth()
    {
        return $this->getAttribute(self::EXPIRY_MONTH);
    }

    protected function setExpiryYear(int $expiryYear)
    {
        return $this->setAttribute(self::EXPIRY_YEAR, $expiryYear);
    }

    protected function getExpiryYear()
    {
        return $this->getAttribute(self::EXPIRY_YEAR);
    }

    protected function setIsActive(bool $active)
    {
        return $this->setAttribute(self::IS_ACTIVE, $active);
    }

    protected function getIsActive()
    {
        return $this->getAttribute(self::IS_ACTIVE);
    }

    public function setCreatedBy($userId)
    {
        return $this->setAttribute(self::CREATED_BY, $userId);
    }

    protected function getCreatedBy()
    {
        return $this->getAttribute(self::CREATED_BY);
    }

    public function setUpdatedBy($userId)
    {
        return $this->setAttribute(self::UPDATED_BY, $userId);
    }

    protected function getUpdatedBy()
    {
        return $this->getAttribute(self::UPDATED_BY);
    }
}
