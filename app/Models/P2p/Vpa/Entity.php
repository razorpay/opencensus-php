<?php

namespace RZP\Models\P2p\Vpa;

use Database\Factories\P2pVpaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;

class Entity extends Base\Entity
{
    use HasFactory;
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;
    use Base\Traits\SoftDeletes;
    use Base\Traits\HasBankAccount;
    use Base\Traits\BeneficiaryTrait;

    const DEVICE_ID        = 'device_id';
    const HANDLE           = 'handle';
    const GATEWAY_DATA     = 'gateway_data';
    const USERNAME         = 'username';
    const BANK_ACCOUNT_ID  = 'bank_account_id';
    const BENEFICIARY_NAME = 'beneficiary_name';
    const PERMISSIONS      = 'permissions';
    const FREQUENCY        = 'frequency';
    const ACTIVE           = 'active';
    const VALIDATED        = 'validated';
    const VERIFIED         = 'verified';
    const DEFAULT          = 'default';

    /***************** Input Keys ****************/

    const VPA              = 'vpa';
    const BANK_ACCOUNT     = 'bank_account';
    const AEROBASE         = '@';
    const ADDRESS          = 'address';
    const AVAILABLE        = 'available';
    const SUGGESTIONS      = 'suggestions';
    const DEVICE           = 'device';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_vpa';
    protected static $sign        = 'vpa';
    protected $generateIdOnCreate = true;
    protected static $generators  = [
        Entity::PERMISSIONS,
    ];

    protected $publicSetters      = [
        self::ENTITY,
        self::ID,
        self::BANK_ACCOUNT,
    ];

    protected $dates = [
        Entity::DELETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::GATEWAY_DATA,
        Entity::USERNAME,
        Entity::BANK_ACCOUNT_ID,
        Entity::BENEFICIARY_NAME,
        Entity::PERMISSIONS,
        Entity::FREQUENCY,
        Entity::ACTIVE,
        Entity::VALIDATED,
        Entity::VERIFIED,
        Entity::DEFAULT,
        Entity::DELETED_AT,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::USERNAME,
        Entity::ADDRESS,
        Entity::BANK_ACCOUNT_ID,
        Entity::BANK_ACCOUNT,
        Entity::BENEFICIARY_NAME,
        Entity::PERMISSIONS,
        Entity::FREQUENCY,
        Entity::ACTIVE,
        Entity::VALIDATED,
        Entity::VERIFIED,
        Entity::DEFAULT,
        Entity::CREATED_AT,
        Entity::DELETED_AT,
    ];

    protected $public = [
        Entity::ENTITY,
        Entity::ID,
        Entity::ADDRESS,
        Entity::HANDLE,
        Entity::USERNAME,
        Entity::BENEFICIARY_NAME,
        Entity::BANK_ACCOUNT,
        Entity::ACTIVE,
        Entity::VALIDATED,
        Entity::VERIFIED,
        Entity::DEFAULT,
        Entity::CREATED_AT,
        Entity::DELETED_AT,
    ];

    protected $defaults = [
        Entity::GATEWAY_DATA     => [],
        Entity::BANK_ACCOUNT_ID  => null,
        Entity::BENEFICIARY_NAME => null,
        Entity::FREQUENCY        => Frequency::MULTIPLE,
        Entity::ACTIVE           => true,
        Entity::VALIDATED        => true,
        Entity::VERIFIED         => false,
    ];

    protected $casts = [
        Entity::ID               => 'string',
        Entity::DEVICE_ID        => 'string',
        Entity::HANDLE           => 'string',
        Entity::GATEWAY_DATA     => 'array',
        Entity::USERNAME         => 'string',
        Entity::BANK_ACCOUNT_ID  => 'string',
        Entity::BENEFICIARY_NAME => 'string',
        Entity::PERMISSIONS      => 'int',
        Entity::FREQUENCY        => 'string',
        Entity::ACTIVE           => 'bool',
        Entity::VALIDATED        => 'bool',
        Entity::VERIFIED         => 'bool',
        Entity::DEFAULT          => 'bool',
        Entity::DELETED_AT       => 'int',
        Entity::CREATED_AT       => 'int',
        Entity::UPDATED_AT       => 'int',
    ];

    protected $appends = [
        Entity::ADDRESS,
    ];

    /**************** GENERATORS ***************/

    public function generatePermissions(array $input)
    {
        $this->setAttribute(Entity::PERMISSIONS,
                            Permissions::getDefaultBitmask(Permissions::CUSTOMER));
    }

    /***************** SETTERS *****************/

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
    public function setUsername(string $username)
    {
        return $this->setAttribute(self::USERNAME, $username);
    }

    /**
     * @return $this
     */
    public function setBankAccountId(string $bankAccountId)
    {
        return $this->setAttribute(self::BANK_ACCOUNT_ID, $bankAccountId);
    }

    /**
     * @return $this
     */
    public function setBeneficiaryName(string $beneficiaryName)
    {
        return $this->setAttribute(self::BENEFICIARY_NAME, $beneficiaryName);
    }

    /**
     * @return $this
     */
    public function setPermissions(int $permissions)
    {
        return $this->setAttribute(self::PERMISSIONS, $permissions);
    }

    /**
     * @return $this
     */
    public function setFrequency(string $frequency)
    {
        return $this->setAttribute(self::FREQUENCY, $frequency);
    }

    /**
     * @return $this
     */
    public function setActive(bool $active)
    {
        return $this->setAttribute(self::ACTIVE, $active);
    }

    /**
     * @return $this
     */
    public function setValidated(bool $validated)
    {
        return $this->setAttribute(self::VALIDATED, $validated);
    }

    /**
     * @return $this
     */
    public function setVerified(bool $verified)
    {
        return $this->setAttribute(self::VERIFIED, $verified);
    }

    /**
     * @return $this
     */
    public function setDefault(bool $default)
    {
        return $this->setAttribute(self::DEFAULT, $default);
    }

    /***************** GETTERS *****************/

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
     * @return string self::USERNAME
     */
    public function getUsername()
    {
        return $this->getAttribute(self::USERNAME);
    }

    /**
     * @return string self::ADDRESS
     */
    public function getAddress()
    {
        return $this->getAttribute(self::ADDRESS);
    }

    /**
     * @return string self::BANK_ACCOUNT_ID
     */
    public function getBankAccountId()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_ID);
    }

    /**
     * @return string self::BENEFICIARY_NAME
     */
    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    /**
     * @return int self::PERMISSIONS
     */
    public function getPermissions()
    {
        return $this->getAttribute(self::PERMISSIONS);
    }

    /**
     * @return string self::FREQUENCY
     */
    public function getFrequency()
    {
        return $this->getAttribute(self::FREQUENCY);
    }

    /**
     * @return bool self::ACTIVE
     */
    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    /**
     * @return bool self::VALIDATED
     */
    public function isValidated()
    {
        return $this->getAttribute(self::VALIDATED);
    }

    /**
     * @return bool self::VERIFIED
     */
    public function isVerified()
    {
        return $this->getAttribute(self::VERIFIED);
    }

    public function isDefault()
    {
        return $this->getAttribute(self::DEFAULT);
    }

    /***************** SCOPES *****************/

    public function scopeDefault(BuilderEx $query, bool $value = true)
    {
        $query->where(self::DEFAULT, $value);
    }

    public function getAddressAttribute()
    {
        return self::toAddress([
            self::USERNAME  => $this->getUsername(),
            self::HANDLE    => $this->getHandle()
        ]);
    }

    public static function toAddress(array $input)
    {
        return implode(self::AEROBASE, [
            array_get($input, self::USERNAME),
            array_get($input, self::HANDLE)
        ]);
    }

    public function toArrayPartner(): array
    {
        $array = $this->toArrayPublic();

        // In case of beneficiary VPA, device may not exist
        if ($this->device instanceof Device\Entity)
        {
            $array[self::DEVICE]  = $this->device->toArrayPartner(true);
        }

        return $array;
    }

    protected static function newFactory(): P2pVpaFactory
    {
        return P2pVpaFactory::new();
    }
}
