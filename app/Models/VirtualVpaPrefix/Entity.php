<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;

class Entity extends Base\PublicEntity
{
    use AsvGetAttribute;

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

    public function getTerminalAttribute()
    {
        $terminal = null;

        if ($this->relationLoaded('terminal') === true)
        {
            $terminal = $this->getRelation('terminal');
        }

        if ($terminal !== null)
        {
            return $terminal;
        }

        if(!(new Terminal\Service())->removeAPIEntityTerminalReads("virtualVpaPrefix"))
        {

            $terminal = $this->terminal()->first();

            if (empty($terminal) === false)
            {
                (new Terminal\Service())->pushTerminalReadMetrics( "virtualVpaPrefix",false);

                $this->terminal()->associate($terminal);

                return $terminal;
            }

        }

        if (empty($this->getTerminalId()))
        {
            return null;
        }

        $terminal = (new Terminal\Repository)->fetchTerminalsToAssociate("virtualVpaPrefix",$this->getTerminalId());

        $this->terminal()->associate($terminal);

        return $terminal;

    }
}
