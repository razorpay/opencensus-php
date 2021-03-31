<?php

namespace RZP\Models\QrCode\QrCodeConfig;

use App;
use Carbon\Carbon;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
    const MERCHANT_ID    = 'merchant_id';
    const GATEWAY        = 'gateway';
    const PAYMENT_METHOD = 'payment_method';
    const PROVIDER       = 'provider';
    const CONFIG         = 'config';
    const DISABLED_AT    = 'disabled_at';

    protected static $sign = 'qrc';

    protected $entity = 'qr_code_config';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::GATEWAY,
        self::PAYMENT_METHOD,
        self::PROVIDER,
        self::CONFIG,
        self::DISABLED_AT
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::PAYMENT_METHOD,
        self::PROVIDER,
        self::CONFIG,
        self::DISABLED_AT
    ];

    public function getConfig()
    {
        return $this->getAttribute(self::CONFIG);
    }

    public function setConfig($config)
    {
        $this->setAttribute(self::CONFIG, $config);
    }

    public function isDisabled()
    {
        return ($this->getAttribute(self::DISABLED_AT) !== null);
    }

    public function disable()
    {
        if ($this->isDisabled() === true)
        {
            return;
        }

        $this->setAttribute(self::DISABLED_AT, Carbon::now()->getTimestamp());
    }

}
