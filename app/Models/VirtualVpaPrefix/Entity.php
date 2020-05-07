<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;

class Entity extends Base\PublicEntity
{
    const PREFIX                            = 'prefix';
    const TERMINAL_ID                       = 'terminal_id';

    protected $fillable = [
        self::MERCHANT_ID,
        self::PREFIX,
        self::TERMINAL_ID,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::PREFIX,
        self::TERMINAL_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::PREFIX,
    ];

    protected static $sign = 'vvp';

    protected $entity = Constants\Entity::VIRTUAL_VPA_PREFIX;

    protected $primaryKey = self::ID;

    protected $generateIdOnCreate = true;

    // -------------------- Getters --------------------

    public function getPrefix()
    {
        return $this->getAttribute(self::PREFIX);
    }

    public function getTerminalId()
    {
        return $this->getAttribute(self::TERMINAL_ID);
    }

    // -------------------- End Getters --------------------

    // -------------------- Setters --------------------

    // -------------------- End Setters --------------------

    // -------------------- Relations --------------------

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function terminal()
    {
        return $this->belongsTo(Terminal\Entity::class);
    }

    // -------------------- End Relations --------------------
}
