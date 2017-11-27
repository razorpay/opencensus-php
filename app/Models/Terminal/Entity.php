<?php

namespace RZP\Models\Terminal;

use Crypt;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Models\Terminal\TpvType;
use RZP\Models\Emi\Subvention as EmiSubvention;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const USED_COUNT                    = 'used_count';
    const USED                          = 'used';
    const CATEGORY                      = 'category';
    const GATEWAY                       = 'gateway';
    const GATEWAY_MERCHANT_ID           = 'gateway_merchant_id';
    const GATEWAY_MERCHANT_ID2          = 'gateway_merchant_id2';
    const GATEWAY_TERMINAL_ID           = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_PASSWORD     = 'gateway_terminal_password';
    const GATEWAY_ACCESS_CODE           = 'gateway_access_code';
    const GATEWAY_SECURE_SECRET         = 'gateway_secure_secret';
    const GATEWAY_RECON_PASSWORD        = 'gateway_recon_password';
    const GATEWAY_ACQUIRER              = 'gateway_acquirer';
    const GATEWAY_CLIENT_CERTIFICATE    = 'gateway_client_certificate';

    const CARD                          = 'card';
    const NETBANKING                    = 'netbanking';
    const EMI                           = 'emi';
    const UPI                           = 'upi';
    const AEPS                          = 'aeps';
    const EMI_DURATION                  = 'emi_duration';
    const EMI_SUBVENTION                = 'emi_subvention';
    const RECURRING                     = 'recurring';
    const INTERNATIONAL                 = 'international';
    const TPV                           = 'tpv';
    const CURRENCY                      = 'currency';
    const SHARED                        = 'shared';
    const ENABLED                       = 'enabled';
    const NETWORK_CATEGORY              = 'network_category';
    const TYPE                          = 'type';
    const MODE                          = 'mode';

    // Used for allowing gateway level changes for coporate netbanking payments.
    const CORPORATE                     = 'corporate';

    const DELETED                       = 'deleted';
    const DELETED_AT                    = 'deleted_at';

    const MAX_TERMINALS_COUNT           = 25;
    const DEFAULT_CURRENCY              = 'INR';

    /**
     * Used for column name in merchant terminal pivot table
     */
    const TERMINAL_ID                   = 'terminal_id';

    const SUB_MERCHANTS                 = 'sub_merchants';

    //const PRIORITY                      = 'priority';

    protected $fillable = [
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::CATEGORY,
        self::NETWORK_CATEGORY,
        self::UPI,
        self::AEPS,
        self::EMI,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
        self::INTERNATIONAL,
        self::TPV,
        self::TYPE,
        self::MODE,
        self::CORPORATE,
        self::CURRENCY,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_MERCHANT_ID2,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_ACCESS_CODE,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_RECON_PASSWORD,
        self::GATEWAY_ACQUIRER,
        self::GATEWAY_CLIENT_CERTIFICATE,
        self::ENABLED
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::CATEGORY,
        self::NETWORK_CATEGORY,
        self::UPI,
        self::AEPS,
        self::EMI,
        self::EMI_DURATION,
        self::EMI_SUBVENTION,
        self::INTERNATIONAL,
        self::SHARED,
        self::TPV,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_MERCHANT_ID2,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_ACQUIRER,
        self::USED_COUNT,
        self::TYPE,
        self::MODE,
        self::CORPORATE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::ENABLED,
        self::SUB_MERCHANTS,
    ];

    protected $hidden = [
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_RECON_PASSWORD,
        self::GATEWAY_CLIENT_CERTIFICATE,
    ];

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal';

    protected static $generators = [
        'method',
    ];

    protected static $modifiers = [
        'inputRemoveBlanks',
        self::INTERNATIONAL,
        self::EMI_SUBVENTION,
    ];

    protected $defaults = [
        self::CATEGORY                  => null,
        self::NETWORK_CATEGORY          => null,
        self::GATEWAY_MERCHANT_ID       => null,
        self::GATEWAY_TERMINAL_ID       => null,
        self::GATEWAY_TERMINAL_PASSWORD => null,
        self::GATEWAY_ACCESS_CODE       => null,
        self::GATEWAY_SECURE_SECRET     => null,
        self::GATEWAY_RECON_PASSWORD    => null,
        self::EMI                       => false,
        self::TPV                       => 0,
        self::TYPE                      => [
            Type::NON_RECURRING => '1'
        ],
        self::MODE                      => Mode::DUAL,
        self::CORPORATE                 => 0,
        self::CURRENCY                  => self::DEFAULT_CURRENCY,
        self::EMI_DURATION              => null,
        self::GATEWAY_ACQUIRER          => null,
        self::INTERNATIONAL             => 0,
        self::ENABLED                   => true,
        self::USED                      => false,
        self::EMI_SUBVENTION            => null,
    ];

    protected $casts = [
        self::CARD                      => 'boolean',
        self::EMI                       => 'boolean',
        self::NETBANKING                => 'boolean',
        self::INTERNATIONAL             => 'boolean',
        self::UPI                       => 'boolean',
        self::AEPS                      => 'boolean',
        self::ENABLED                   => 'boolean',
        self::TPV                       => 'int',
        self::TYPE                      => 'int',
        self::MODE                      => 'int',
        self::CATEGORY                  => 'int',
        self::CORPORATE                 => 'boolean',
        self::USED                      => 'boolean',
    ];

    protected $appends = [
        self::SHARED,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    // ---------------------- GETTERS ----------------------

    public function getGatewayMerchantId()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID);
    }

    public function getGatewayMerchantId2()
    {
        return $this->getAttribute(self::GATEWAY_MERCHANT_ID2);
    }

    public function getGatewayReconPassword()
    {
        $reconPassword = $this->attributes[self::GATEWAY_RECON_PASSWORD];

        if ($reconPassword === null)
        {
            return $reconPassword;
        }

        return Crypt::decrypt($reconPassword);
    }

    public function getGatewayTerminalId()
    {
        return $this->getAttribute(self::GATEWAY_TERMINAL_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getGatewayAcquirer()
    {
        return $this->getAttribute(self::GATEWAY_ACQUIRER);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function isUsed()
    {
        return $this->getAttribute(self::USED);
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getEmiDuration()
    {
        return $this->getAttribute(self::EMI_DURATION);
    }

    public function getEmiSubvention()
    {
        return $this->getAttribute(self::EMI_SUBVENTION);
    }

    /**
     * In case the terminal is a UPI terminal, this returns
     * the VPA that the collect request would have been raised from
     * @return String Virtual Payment Address of the nodal account
     */
    public function getVpa(): string
    {
        if ($this->getUpi() === true)
        {
            return $this->attributes[self::GATEWAY_MERCHANT_ID2];
        }

        return 'razorpay@icici';
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getNetworkCategory()
    {
        return $this->getAttribute(self::NETWORK_CATEGORY);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    // ---------------------- END GETTERS ----------------------

    public function isEnabled()
    {
        return $this->getAttribute(self::ENABLED);
    }

    public function isCardEnabled()
    {
        return $this->getAttribute(self::CARD);
    }

    public function isNetbankingEnabled()
    {
        return $this->getAttribute(self::NETBANKING);
    }

    public function isEmiEnabled()
    {
        return (bool) $this->getAttribute(self::EMI);
    }

    public function isUpiEnabled()
    {
        return $this->getAttribute(self::UPI);
    }

    public function isAepsEnabled()
    {
        return $this->getAttribute(self::AEPS);
    }

    public function isShared(): bool
    {
        $merchantId = $this->getAttribute(self::MERCHANT_ID);

        return ($merchantId === Merchant\Account::SHARED_ACCOUNT);
    }

    /**
     * For a given merchant, checks if the terminal can be considered direct
     * for the merchant based on the below two cases
     * - terminal's primary merchant is given merchant
     * - any of the sub-merchants of the terminal has this merchant
     *
     * @param  Merchant\Entity $merchant    Merchant entity for which we wantto check
     * @return boolean
     */
    public function isDirectForMerchant(Merchant\Entity $merchant): bool
    {
        $result = ($merchant->getId() === $this->getAttribute(self::MERCHANT_ID));

        if ($result === false)
        {
            $result = $this->merchants->contains(function ($subMerchant) use ($merchant)
            {
                return ($merchant->getId() === $subMerchant[Merchant\Entity::ID]);
            });
        }

        return $result;
    }

    public function isCorporate()
    {
        return $this->getAttribute(self::CORPORATE);
    }

    // ---------------------- SETTERS ----------------------

    public function setNetworkCategory($category)
    {
        $this->setAttribute(self::NETWORK_CATEGORY, $category);
    }

    public function setEnabled($status)
    {
        $this->setAttribute(self::ENABLED, $status);
    }

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setMode($mode)
    {
        $this->setAttribute(self::MODE, $mode);
    }

    // ---------------------- END SETTERS ----------------------

    // -----------------------PUBLIC SETTERS -------------------

    protected function setPublicSubMerchantsAttribute(array & $array)
    {
        $subMerchants = $this->merchants()->get();

        $subMerchants->transform(
            function ($item, $key)
            {
                return [
                    Merchant\Entity::ID            => $item[Merchant\Entity::ID],
                    Merchant\Entity::NAME          => $item[Merchant\Entity::NAME],
                    Merchant\Entity::WEBSITE       => $item[Merchant\Entity::WEBSITE],
                    Merchant\Entity::BILLING_LABEL => $item[Merchant\Entity::BILLING_LABEL]
                ];
            });

        $array[self::SUB_MERCHANTS] = $subMerchants;
    }

    //----------------------END PUBLIC SETTERS----------------

    // ---------------------- ACCESSORS ----------------------

    protected function getGatewayTerminalPasswordAttribute()
    {
        $pwd = $this->attributes[self::GATEWAY_TERMINAL_PASSWORD];

        if ($pwd === null)
            return $pwd;

        return Crypt::decrypt($pwd);
    }

    protected function getGatewaySecureSecretAttribute()
    {
        $secret = $this->attributes[self::GATEWAY_SECURE_SECRET];

        if ($secret === null)
        {
            return $secret;
        }

        return Crypt::decrypt($secret);
    }

    protected function getUsedCountAttribute()
    {
        return (int) $this->attributes[self::USED_COUNT];
    }

    protected function getCategoryAttribute()
    {
        $category = $this->attributes[self::CATEGORY];

        if ($category !== null)
        {
            $category = (int) $category;
        }

        return $category;
    }

    protected function getEmiDurationAttribute()
    {
        $emiDuration = $this->attributes[self::EMI_DURATION];

        if ($emiDuration !== null)
        {
            $emiDuration = (int) $emiDuration;
        }

        return $emiDuration;
    }

    protected function getSharedAttribute()
    {
        return $this->isShared();
    }

    // ---------------------- END ACCESSORS ----------------------

    // ---------------------- MODIFIERS ----------------------

    protected function setGatewayTerminalPasswordAttribute($password)
    {
        if ($password === null)
            $password = '';

        $this->attributes[self::GATEWAY_TERMINAL_PASSWORD] = Crypt::encrypt($password);
    }

    protected function setGatewaySecureSecretAttribute($secret)
    {
        if ($secret === null)
        {
            $secret = '';
        }

        $this->attributes[self::GATEWAY_SECURE_SECRET] = Crypt::encrypt($secret);
    }

    protected function setGatewayReconPasswordAttribute($reconPassword)
    {
        if ($reconPassword === null)
        {
            // Default value is set to null anyway.
            return;
        }

        $this->attributes[self::GATEWAY_RECON_PASSWORD] = Crypt::encrypt($reconPassword);
    }

    protected function setEnabledAttribute($status)
    {
        $this->attributes[self::ENABLED] = $status;
    }

    protected function setTypeAttribute($type)
    {
        $hex = 0;

        if (isset($this->attributes[self::TYPE]) === true)
        {
            $hex = $this->attributes[self::TYPE];
        }

        $this->attributes[self::TYPE] = Type::getHexValue($type, $hex);
    }

    protected function getTypeAttribute()
    {
        $type = $this->attributes[self::TYPE];

        return Type::getEnabledType($type);
    }

    protected function modifyInternational(& $input)
    {
        if (empty($input[self::INTERNATIONAL]) === true)
        {
            $gateway = $input[self::GATEWAY];

            if (in_array($gateway, Payment\Gateway::$internationalCardGateways, true) === true)
            {
                $input[self::INTERNATIONAL] = 1;
            }
        }
    }

    protected function modifyEmiSubvention(& $input)
    {
        $isEmi = $input[self::EMI] ?? false;

        if ($isEmi == true)
        {
            $input[self::EMI_SUBVENTION] = $input[self::EMI_SUBVENTION] ?? EmiSubvention::CUSTOMER;
        }
    }

    // ---------------------- END MODIFIERS ----------------------

    // ---------------------- SCOPES ----------------------

    public function scopeEnabled($query)
    {
        return $query->where(Entity::ENABLED, '=', '1');
    }

    // ---------------------- END SCOPES ----------------------

    public function generateMethod($input)
    {
        $gateway = $input[self::GATEWAY];
        $methods = [
            self::CARD,
            self::NETBANKING,
            self::UPI,
            self::AEPS,
        ];

        foreach ($methods as $method)
        {
            if (Payment\Gateway::isMethodSupported($method, $gateway))
            {
                $this->setAttribute($method, 1);
            }
            else
            {
                $this->setAttribute($method, 0);
            }
        }
    }

    public function edit(array $input = [], $operation = 'edit')
    {
        if ($this->isUsed() === false)
        {
            // Essentially we ask for all the input anew and fill it in.
            // Put the values which are not changing like gateway and merchant_id
            // by ourselves.

            $input[Entity::GATEWAY] = $this->getGateway();
            $input[Entity::MERCHANT_ID] = $this->getMerchantId();

            return parent::edit($input, 'create');
        }
        else
        {
            $this->editUsedTerminal($input);
        }
    }

    protected function editUsedTerminal(array $input)
    {
        assert ($this->isUsed() === true);

        $this->getValidator()->usedTerminalValidator($this, $input);

        $this->fill($input);
    }

    public function incrementUsedCount()
    {
        $usedCount = $this->getUsedCount() + 1;

        $this->setAttribute(self::USED_COUNT, $usedCount);
    }

    public function setUsed()
    {
        $this->setAttribute(self::USED, true);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function merchants()
    {
        return $this->belongsToMany('RZP\Models\Merchant\Entity', Table::MERCHANT_TERMINAL);
    }

    public function toArrayWithPassword()
    {
        $terminal = $this->toArray();

        $terminal[self::GATEWAY_TERMINAL_PASSWORD] = $this->getGatewayTerminalPasswordAttribute();

        return $terminal;
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isGatewayAcquirer($acquirer)
    {
        return ($this->getAttribute(self::GATEWAY_ACQUIRER) === $acquirer);
    }

    public function isDeleted()
    {
        return ($this->getAttribute(self::DELETED_AT) !== null);
    }

    public function matchEncryptedAttribute($attribute, $value)
    {
        $actualValue = $this->getAttribute($attribute);

        return ($value === $actualValue);
    }

    public function isTpv()
    {
        return $this->getAttribute(self::TPV);
    }

    public function isNotTpv()
    {
        return ($this->isTpv() === false);
    }

    public function isTpvAllowed() : bool
    {
        $tpv = $this->getAttribute(self::TPV);

        return TpvType::isTpvAllowed($tpv);
    }

    public function isNonTpvAllowed() : bool
    {
        $tpv = $this->getAttribute(self::TPV);

        return TpvType::isNonTpvAllowed($tpv);
    }

    public function isValidEmiTerminal($gateway, $emiDuration, $subvention)
    {
        if (($this->isEmiEnabled()) and
            ($this->getGateway() === $gateway) and
            ($this->getEmiDuration() === $emiDuration) and
            ($this->getEmiSubvention() === $subvention))
        {
            return true;
        }

        return false;
    }

    protected function isTypeApplicable($type)
    {
        $enabledTypes = $this->getType();

        return in_array($type, $enabledTypes, true);
    }

    public function isNonRecurring()
    {
        return ($this->isTypeApplicable(Type::NON_RECURRING) === true);
    }

    public function isRecurring()
    {
        return (($this->is3DSRecurring() === true) or
                ($this->isNon3DSRecurring() === true));
    }

    public function is3DSRecurring()
    {
        return ($this->isTypeApplicable(Type::RECURRING_3DS) === true);
    }

    public function isNon3DSRecurring()
    {
        return ($this->isTypeApplicable(Type::RECURRING_NON_3DS) === true);
    }

    public function isIvr()
    {
        return ($this->isTypeApplicable(Type::IVR) === true);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isAuthCapture()
    {
        return ($this->getAttribute(self::MODE) === Mode::AUTH_CAPTURE);
    }

    public function isPurchase()
    {
        return ($this->getAttribute(self::MODE) === Mode::PURCHASE);
    }

    public function isDomestic()
    {
        return ($this->isCardEnabled() === true);
    }

    /**
     * This is being overridden because, we don't always want to add sub merchants
     * to the serialized data as it involves a db call. Only when serializing
     * an individual terminal entity, we want to do it
     *
     * @param  boolean $subMerchantFlag Flag ti indicate if sub_merchants should be included
     */
    public function toArrayPublic($subMerchantFlag = false)
    {
        if ($subMerchantFlag === true)
        {
            $this->publicSetters[] = Entity::SUB_MERCHANTS;
        }

        return parent::toArrayPublic();
    }

    public function toArrayAdmin($subMerchantFlag = false)
    {
        if ($subMerchantFlag === true)
        {
            $this->publicSetters[] = Entity::SUB_MERCHANTS;
        }

        return parent::toArrayAdmin();
    }
}
