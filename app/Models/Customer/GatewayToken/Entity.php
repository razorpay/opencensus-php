<?php

namespace RZP\Models\Customer\GatewayToken;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID   = 'merchant_id';
    const TERMINAL_ID   = 'terminal_id';
    const TOKEN_ID      = 'token_id';
    const REFERENCE     = 'reference';

    protected static $sign = 'gt';

    protected $entity = 'gateway_token';

    protected $generateIdOnCreate = true;

    protected $guarded = ['*'];

    // protected $fillable = [
    //     self::ID,
    // ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TERMINAL_ID,
        self::TOKEN_ID,
        self::REFERENCE,
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
        self::CREATED_AT
    ];

    protected $defaults = [
        self::REFERENCE         => null,
    ];

    // -------------------- Relations --------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function token()
    {
        return $this->belongsTo('RZP\Models\Token\Entity');
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

    // -------------------- End Setters --------------------
}
