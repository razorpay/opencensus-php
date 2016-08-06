<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID           = 'merchant_id';
    const CUSTOMER_ID           = 'customer_id';
    const TERMINAL_ID           = 'terminal_id';
    const TOKEN                 = 'token';
    const METHOD                = 'method';
    const CARD_ID               = 'card_id';
    const CARD                  = 'card';
    const BANK                  = 'bank';
    const WALLET                = 'wallet';
    const GATEWAY_TOKEN         = 'gateway_token';
    const GATEWAY_TOKEN2        = 'gateway_token2';
    const RECURRING             = 'recurring';
    const EXPIRED_AT            = 'expired_at';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = 'token';

    protected $entity           = 'token';

    protected $table            = \RZP\Constants\Table::TOKEN;

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::TOKEN,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::EXPIRED_AT,
    );

    protected $visible = array(
        self::ID,
        self::MERCHANT_ID,
        self::BANK,
        self::WALLET,
        self::TOKEN,
        self::METHOD,
        self::CARD_ID,
        self::CARD,
        self::CUSTOMER_ID,
        self::TERMINAL_ID,
        self::GATEWAY_TOKEN,
        self::GATEWAY_TOKEN2,
        self::RECURRING,
        self::EXPIRED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $public = array(
        self::TOKEN,
        self::BANK,
        self::WALLET,
        self::METHOD,
        self::CARD,
    );

    protected $defaults = array(
        self::WALLET         => null,
        self::BANK           => null,
        self::CARD_ID        => null,
        self::GATEWAY_TOKEN2 => null,
        self::RECURRING      => false,
        self::EXPIRED_AT     => null
    );

    protected $publicSetters = array(
        self::ID,
        self::ENTITY,
        self::CARD);

    protected $casts = array(
        self::RECURRING => 'boolean'
    );

    protected static $generators = array(
        self::TOKEN
    );

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function card()
    {
        return $this->belongsTo('RZP\Models\Card\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity');
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getWallet()
    {
        return $this->getAttribute(self::WALLET);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getGatewayToken()
    {
        return $this->getAttribute(self::GATEWAY_TOKEN);
    }

    public function getGatewayToken2()
    {
        return $this->getAttribute(self::GATEWAY_TOKEN2);
    }

    public function getRecurring()
    {
        return $this->getAttribute(self::RECURRING);
    }

    public function getExpiredAt()
    {
        return $this->getAttribute(self::EXPIRED_AT);
    }

    public function isExpired()
    {
        $expiredAt = $this->getExpiredAt();

        if ($expiredAt === null)
        {
            return false;
        }

        return ($expiredAt <= time());
    }

    public function isRecurring()
    {
        return $this->getRecurring();
    }

    protected function setPublicCardAttribute(array & $array)
    {
        if ($this->card !== null)
        {
            $array[self::CARD] = $this->card->toArrayToken();
        }
    }

    protected function generateToken($input)
    {
        $rand = '';

        for ($i = 0; $i < 3; $i++)
        {
            $dec = hexdec(bin2hex(openssl_random_pseudo_bytes(5)));

            // Convert the random decimal generated to base 62
            $rand .= self::base62($dec);
        }

        $token = substr($rand, 0, 14);

        $this->setAttribute(self::TOKEN, $token);
    }
}
