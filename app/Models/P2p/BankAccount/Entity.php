<?php

namespace RZP\Models\P2p\BankAccount;

use Database\Factories\P2pBankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi\ClientLibrary;

/**
 * @property Bank\Entity $parentBank
 *
 * @package RZP\Models\P2p\BankAccount
 */
class Entity extends Base\Entity
{
    use HasFactory;
    use Base\Traits\HasBank;
    use Base\Traits\HasHandle;
    use Base\Traits\HasDevice;
    use Base\Traits\SoftDeletes;
    use Base\Traits\BeneficiaryTrait;

    const DEVICE_ID                = 'device_id';
    const HANDLE                   = 'handle';
    const GATEWAY_DATA             = 'gateway_data';
    const BANK_ID                  = 'bank_id';
    const IFSC                     = 'ifsc';
    const ACCOUNT_NUMBER           = 'account_number';
    const MASKED_ACCOUNT_NUMBER    = 'masked_account_number';
    const BENEFICIARY_NAME         = 'beneficiary_name';
    const CREDS                    = 'creds';
    const TYPE                     = 'type';

    /****************** Input Keys ***************/
    const BANK                     = 'bank';
    const BANK_ACCOUNT             = 'bank_account';
    const BANK_ACCOUNTS            = 'bank_accounts';
    const BANK_NAME                = 'bank_name';
    const REGISTRATION_FORMAT      = 'registration_format';
    const BALANCE                  = 'balance';
    const CURRENCY                 = 'currency';
    const CARD                     = 'card';
    const ADDRESS                  = 'address';
    const AEROBASE                 = '@';
    const ADDRESS_SUFFIX           = 'ifsc.npci';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_bank_account';
    protected static $sign        = 'ba';
    protected $generateIdOnCreate = true;
    protected static $generators  = [
        Entity::REFRESHED_AT,
    ];

    protected $publicSetters      = [
        Entity::ID,
        Entity::BANK,
        Entity::ENTITY,
        Entity::CREDS,
    ];

    protected $dates = [
        Entity::REFRESHED_AT,
        Entity::DELETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::GATEWAY_DATA,
        Entity::BANK_ID,
        Entity::IFSC,
        Entity::ACCOUNT_NUMBER,
        Entity::MASKED_ACCOUNT_NUMBER,
        Entity::BENEFICIARY_NAME,
        Entity::CREDS,
        Entity::TYPE,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::BANK_ID,
        Entity::IFSC,
        Entity::ACCOUNT_NUMBER,
        Entity::MASKED_ACCOUNT_NUMBER,
        Entity::BENEFICIARY_NAME,
        Entity::CREDS,
        Entity::TYPE,
        Entity::BANK,
        Entity::ADDRESS,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::ENTITY,
        Entity::BANK_NAME,
        Entity::IFSC,
        Entity::MASKED_ACCOUNT_NUMBER,
        Entity::BENEFICIARY_NAME,
        Entity::CREDS,
        Entity::TYPE,
        Entity::BANK,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::GATEWAY_DATA             => [],
        Entity::ACCOUNT_NUMBER           => null,
        Entity::MASKED_ACCOUNT_NUMBER    => '',
        Entity::CREDS                    => [],
        Entity::TYPE                     => '',
    ];

    protected $casts = [
        Entity::ID                    => 'string',
        Entity::DEVICE_ID             => 'string',
        Entity::HANDLE                => 'string',
        Entity::GATEWAY_DATA          => 'array',
        Entity::BANK_ID               => 'string',
        Entity::IFSC                  => 'string',
        Entity::ACCOUNT_NUMBER        => 'string',
        Entity::MASKED_ACCOUNT_NUMBER => 'string',
        Entity::BENEFICIARY_NAME      => 'string',
        Entity::CREDS                 => 'array',
        Entity::TYPE                  => 'string',
        Entity::REFRESHED_AT          => 'int',
        Entity::DELETED_AT            => 'int',
        Entity::CREATED_AT            => 'int',
        Entity::UPDATED_AT            => 'int',
    ];

    protected $appends = [
        Entity::ADDRESS,
    ];

    protected $with = [
        Entity::BANK,
    ];

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
    public function setBankId(string $bankId)
    {
        return $this->setAttribute(self::BANK_ID, $bankId);
    }

    /**
     * @return $this
     */
    public function setIfsc(string $ifsc)
    {
        return $this->setAttribute(self::IFSC, $ifsc);
    }

    /**
     * @return $this
     */
    public function setAccountNumber(string $accountNumber)
    {
        return $this->setAttribute(self::ACCOUNT_NUMBER, $accountNumber);
    }

    /**
     * @return $this
     */
    public function setMaskedAccountNumber(string $maskedAccountNumber)
    {
        return $this->setAttribute(self::MASKED_ACCOUNT_NUMBER, $maskedAccountNumber);
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
    public function setCreds(array $creds)
    {
        return $this->setAttribute(self::CREDS, $creds);
    }

    /**
     * @return $this
     */
    public function setType(string $type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    public function setCredsUpiPin(bool $set)
    {
        $creds = new Credentials($this->getCreds());

        $creds->mergeCred(Credentials::UPI_PIN, [
            Credentials::SET => $set
        ]);

        return $this->setCreds($creds->toArray());
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
     * @return string self::BANK
     */
    public function getBank()
    {
        return $this->getAttribute(self::BANK_ID);
    }

    /**
     * @return string self::IFSC
     */
    public function getIfsc()
    {
        return $this->getAttribute(self::IFSC);
    }

    /**
     * @return string self::ACCOUNT_NUMBER
     */
    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    /**
     * @return string self::ADDRESS
     */
    public function getAddress()
    {
        return $this->getAttribute(self::ADDRESS);
    }

    /**
     * @return string self::MASKED_ACCOUNT_NUMBER
     */
    public function getMaskedAccountNumber()
    {
        return $this->getAttribute(self::MASKED_ACCOUNT_NUMBER);
    }

    /**
     * @return string self::BENEFICIARY_NAME
     */
    public function getBeneficiaryName()
    {
        return $this->getAttribute(self::BENEFICIARY_NAME);
    }

    /**
     * @return array self::CREDS
     */
    public function getCreds()
    {
        return $this->getAttribute(self::CREDS);
    }

    /**
     * @return string self::TYPE
     */
    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    /***************** Accessors *****************/

    public function getCredsAttribute($json)
    {
        $creds = new Credentials(json_decode($json, true));

        return $creds->toArray();
    }

    public function getAddressAttribute()
    {
        if ($this->getAccountNumber() === null)
        {
            return null;
        }

        return sprintf('%s@%s.ifsc.npci', $this->getAccountNumber(), $this->getIfsc());
    }

    public function getMaskedAccountNumberAttribute()
    {
        $maskedAccountNumber = $this->attributes[self::MASKED_ACCOUNT_NUMBER] ?? null;

        if (empty($maskedAccountNumber) === true)
        {
            $maskedAccountNumber = mask_except_last4($this->attributes[self::ACCOUNT_NUMBER]) ?? null;

            if ($maskedAccountNumber !== null)
            {
                $this->setMaskedAccountNumber($maskedAccountNumber);
            }
        }

        return $maskedAccountNumber;
    }

    public function setPublicCredsAttribute(& $array)
    {
        $creds = array_map(
            function($cred)
            {
                return array_only($cred, [Credentials::SET, Credentials::LENGTH]);
            }, $this->getCreds());

        $array[self::CREDS] = $creds;
    }

    public function toArrayPartner(): array
    {
        $array = $this->toArrayPublic();

        $array[self::ADDRESS] = self::toAddress([
            self::ACCOUNT_NUMBER => $this->getMaskedAccountNumber(),
            self::IFSC           => $this->getIfsc(),
        ]);

        return $array;
    }

    public static function toAddress(array $input)
    {
        if (empty(array_get($input, self::ACCOUNT_NUMBER)) or
            empty(array_get($input, self::IFSC)))
        {
            return null;
        }

        $handle = implode('.', [
            array_get($input, self::IFSC),
            self::ADDRESS_SUFFIX,
        ]);

        return implode(self::AEROBASE, [
            array_get($input, self::ACCOUNT_NUMBER),
            $handle,
        ]);
    }

    protected static function newFactory(): P2pBankAccountFactory
    {
        return P2pBankAccountFactory::new();
    }
}
