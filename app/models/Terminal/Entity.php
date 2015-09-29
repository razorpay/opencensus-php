<?php

namespace Models\Terminal;

use Crypt;
use Models\Base;
use Models\Payment;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Entity extends Base\PublicEntity
{
    use SoftDeletingTrait;

    const ID                            = 'id';
    const MERCHANT_ID                   = 'merchant_id';
    const USED_COUNT                    = 'used_count';
    const GATEWAY                       = 'gateway';
    const GATEWAY_MERCHANT_ID           = 'gateway_merchant_id';
    const GATEWAY_TERMINAL_ID           = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_PASSWORD     = 'gateway_terminal_password';
    const GATEWAY_ACCESS_CODE           = 'gateway_access_code';
    const GATEWAY_SECURE_SECRET         = 'gateway_secure_secret';

    const CARD                          = 'card';
    const NETBANKING                    = 'netbanking';

    const SHARED                        = 'shared';

    const DELETED_AT                    = 'deleted_at';

    const MAX_TERMINALS_COUNT           = 11;

    protected $fillable = array(
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_ACCESS_CODE,
        self::GATEWAY_SECURE_SECRET,
        self::GATEWAY_TERMINAL_PASSWORD);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::CARD,
        self::GATEWAY_MERCHANT_ID,
        self::GATEWAY_TERMINAL_ID,
        self::USED_COUNT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT);

    protected $table = 'terminals';

    protected $hidden = array(
        self::GATEWAY_TERMINAL_PASSWORD,
        self::GATEWAY_SECURE_SECRET);

    protected $genereateIdOnCreate = true;

    protected $entity = 'terminal';

    protected static $sign = '';

    protected static $delimiter = '';

    protected static $generators = array(
        'method',
        'thedefaults');

    protected $defaults = array(
        self::GATEWAY_MERCHANT_ID       => null,
        self::GATEWAY_TERMINAL_ID       => null,
        self::GATEWAY_TERMINAL_PASSWORD => null,
        self::GATEWAY_ACCESS_CODE       => null,
        self::GATEWAY_SECURE_SECRET     => null);

    public function generateMethod($input)
    {
        $gateway = $input[self::GATEWAY];

        if (Payment\Gateway::isMethodSupported('card', $gateway))
        {
            $this->setAttribute(self::CARD, 1);
        }
        else
        {
            $this->setAttribute(self::CARD, 0);
        }

        if (Payment\Gateway::isMethodSupported('netbanking', $gateway))
        {
            $this->setAttribute(self::NETBANKING, 1);
        }
        else
        {
            $this->setAttribute(self::NETBANKING, 0);
        }
    }

    protected function generateThedefaults($input)
    {
        if (empty($input[self::GATEWAY_MERCHANT_ID]))
        {
            $this->setAttribute(self::GATEWAY_MERCHANT_ID, null);
        }

        if (empty($input[self::GATEWAY_ACCESS_CODE]))
        {
            $this->setAttribute(self::GATEWAY_ACCESS_CODE, null);
        }

        if (empty($input[self::GATEWAY_TERMINAL_ID]))
        {
            $this->setAttribute(self::GATEWAY_TERMINAL_ID, null);
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

    public function getMerchantId()
    {
        return $this->attributes[self::MERCHANT_ID];
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
            return $secret;

        return Crypt::decrypt($secret);
    }

    public function getGatewayTerminalId()
    {
        return $this->getAttribute(self::GATEWAY_TERMINAL_ID);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function getUsedCountAttribute()
    {
        return (int) $this->attributes[self::USED_COUNT];
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function toArrayWithPassword()
    {
        $terminal = $this->toArray();

        $terminal[self::GATEWAY_TERMINAL_PASSWORD] = $this->getGatewayTerminalPasswordAttribute();

        return $terminal;
    }

    public function isCardEnabled()
    {
        return (((int)$this->getAttribute(self::CARD)) === 1);
    }

    public function isNetbankingEnabled()
    {
        return (((int)$this->getAttribute(self::NETBANKING)) === 1);
    }

    public function isGateway($gateway)
    {
        return ($this->getAttribute(self::GATEWAY) === $gateway);
    }
}