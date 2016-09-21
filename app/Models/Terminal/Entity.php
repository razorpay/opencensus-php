<?php

namespace RZP\Models\Terminal;

use Crypt;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Recurring;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const USED_COUNT                    = 'used_count';
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

    const CARD                          = 'card';
    const NETBANKING                    = 'netbanking';
    const EMI                           = 'emi';
    const UPI                           = 'upi';
    const EMI_DURATION                  = 'emi_duration';
    const RECURRING                     = 'recurring';

    const SHARED                        = 'shared';

    const NETWORK_CATEGORY              = 'network_category';

    const DELETED_AT                    = 'deleted_at';

    const MAX_TERMINALS_COUNT           = 25;

    const ENABLED                       = 'enabled';

    //const PRIORITY                      = 'priority';

    protected $fillable = array(
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::CATEGORY,
        self::UPI,
        self::EMI,
        self::EMI_DURATION,
        self::SHARED,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_MERCHANT_ID2,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_ACCESS_CODE,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_RECON_PASSWORD,
        self::GATEWAY_ACQUIRER,
        self::ENABLED
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::CATEGORY,
        self::UPI,
        self::EMI,
        self::EMI_DURATION,
        self::SHARED,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_MERCHANT_ID2,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_ACQUIRER,
        self::USED_COUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::ENABLED
    );

    protected $table = 'terminals';

    protected $hidden = array(
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_RECON_PASSWORD,
    );

    protected $generateIdOnCreate = true;

    protected $entity = 'terminal';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array('method');

    protected static $modifiers = array('inputRemoveBlanks');

    protected $defaults = array(
        self::CATEGORY                  => null,
        self::GATEWAY_MERCHANT_ID       => null,
        self::GATEWAY_TERMINAL_ID       => null,
        self::GATEWAY_TERMINAL_PASSWORD => null,
        self::GATEWAY_ACCESS_CODE       => null,
        self::GATEWAY_SECURE_SECRET     => null,
        self::GATEWAY_RECON_PASSWORD    => null,
        self::SHARED                    => false,
        self::EMI                       => false,
        self::EMI_DURATION              => null,
        self::GATEWAY_ACQUIRER          => null,
        self::RECURRING                 => Recurring::NON_RECURRING,
        self::ENABLED                   => true,
    );

    protected $casts = array(
        self::CARD                      => 'boolean',
        self::EMI                       => 'boolean',
        self::NETBANKING                => 'boolean',
        self::RECURRING                 => 'int',
        self::SHARED                    => 'boolean',
        self::UPI                       => 'boolean',
        self::ENABLED                   => 'enabled',
    );

    public function generateMethod($input)
    {
        $gateway = $input[self::GATEWAY];
        $methods = array(self::CARD, self::NETBANKING);

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

    public function edit(array $input = array(), $operation = 'edit')
    {
        if ($this->getUsedCount() === 0)
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

    protected function editUsedTerminal($input)
    {
        assert ($this->getUsedCount() !== 0);

        $this->getValidator()->usedTerminalValidator($this, $input);

        $this->fill($input);
    }

    public function incrementUsedCount()
    {
        $usedCount = $this->getUsedCount() + 1;

        $this->setAttribute(self::USED_COUNT, $usedCount);
    }

    public function getGatewayMerchantId()
    {
        return $this->attributes[self::GATEWAY_MERCHANT_ID];
    }

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

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getShared()
    {
        return $this->getAttribute(self::SHARED);
    }

    public function getRecurring()
    {
        return $this->getAttribute(self::RECURRING);
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

    public function getEmiDuration()
    {
        return $this->getAttribute(self::EMI_DURATION);
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

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function toArrayWithPassword()
    {
        $terminal = $this->toArray();

        $terminal[self::GATEWAY_TERMINAL_PASSWORD] = $this->getGatewayTerminalPasswordAttribute();

        return $terminal;
    }

    public function isCardEnabled()
    {
        return $this->getAttribute(self::CARD);
    }

    public function isNetbankingEnabled()
    {
        return $this->getAttribute(self::NETBANKING);
    }

    public function isUpiTerminal()
    {
        return (substr($this->gateway, 0, 3) === 'upi');
    }

    public function isEmiEnabled()
    {
        return (bool) $this->getAttribute(self::EMI);
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }

    public function isGatewayAcquirer($acquirer)
    {
        return ($this->getAttribute(self::GATEWAY_ACQUIRER) === $acquirer);
    }

    public function isShared()
    {
        return (bool) $this->getAttribute(self::SHARED);
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

    public function isTPVTerminal()
    {
        if (is_null($this->getCategory()) === false)
        {
            $tpvCategories = (new Merchant\Entity)->getTPVCategories();

            return in_array($this->getCategory(), $tpvCategories);
        }

        return false;
    }

    public function isValidEmiTerminal($gateway, $emiDuration)
    {
        if (($this->isEmiEnabled()) and
            ($this->getGateway() === $gateway) and
            ($this->getEmiDuration() === $emiDuration))
        {
            return true;
        }

        return false;
    }

    public function getNetworkCategory()
    {
        return $this->getAttribute(self::NETWORK_CATEGORY);
    }

    public function setNetworkCategory($category)
    {
        $this->setAttribute(self::NETWORK_CATEGORY, $category);
    }
}
