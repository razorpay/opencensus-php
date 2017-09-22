<?php

namespace RZP\Models\Customer\GatewayToken;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID   = 'merchant_id';
    const TERMINAL_ID   = 'terminal_id';
    const TOKEN_ID      = 'token_id';
    const REFERENCE     = 'reference';
    const ACCESS_TOKEN  = 'access_token';
    const REFRESH_TOKEN = 'refresh_token';
    const RECURRING     = 'recurring';

    protected static $sign = 'gt';

    protected $entity = 'gateway_token';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::REFERENCE,
        self::RECURRING,
        self::ACCESS_TOKEN,
        self::REFRESH_TOKEN,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TERMINAL_ID,
        self::TOKEN_ID,
        self::REFERENCE,
        self::ACCESS_TOKEN,
        self::REFRESH_TOKEN,
        self::RECURRING,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::TOKEN_ID,
        self::TERMINAL_ID,
        self::MERCHANT_ID,
        self::REFERENCE,
        self::RECURRING,
        self::CREATED_AT
    ];

    protected $defaults = [
        self::REFERENCE         => null,
        self::ACCESS_TOKEN      => null,
        self::REFRESH_TOKEN     => null,
        self::RECURRING         => null,
    ];

    protected $casts = [
        self::RECURRING => 'bool',
    ];

    // -------------------- Relations --------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function token()
    {
        return $this->belongsTo('RZP\Models\Customer\Token\Entity');
    }

    public function terminal()
    {
        return $this->belongsTo('RZP\Models\Terminal\Entity');
    }

    // -------------------- End Relations --------------------

    // -------------------- Setters --------------------

    public function setReference($reference)
    {
        $this->setAttribute(self::REFERENCE, $reference);
    }

    public function setRecurring(bool $recurring)
    {
        $this->setAttribute(self::RECURRING, $recurring);
    }

    // -------------------- End Setters --------------------
}
