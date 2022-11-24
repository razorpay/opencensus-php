<?php

namespace RZP\Models\Card\TokenisedIIN;

use RZP\Models\Base;
use RZP\Models\Base\QueryCache\Cacheable;
use RZP\Models\Card\IIN\Entity as IINEntity;
use RZP\Models\Base\Traits\HardDeletes;

class Entity extends Base\PublicEntity
{
    use Cacheable;
    use Base\Traits\HardDeletes;

    const HIGH_RANGE        =  'high_range';
    const LOW_RANGE         =  'low_range';
    const IIN               =  'iin';
    const TOKEN_IIN_LENGTH  =  'token_iin_length';

    protected $entity = 'tokenised_iin';

    public $incrementing = true;

    protected $fields = [
        self::ID,
        self::IIN,
        self::HIGH_RANGE,
        self::LOW_RANGE,
    ];

    protected $fillable = [
        self::IIN,
        self::HIGH_RANGE,
        self::LOW_RANGE,
        self::TOKEN_IIN_LENGTH
    ];

    protected $primaryKey = self::ID;

    protected $visible = [
        self::IIN,
        self::HIGH_RANGE,
        self::LOW_RANGE,
        self::ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TOKEN_IIN_LENGTH
    ];

    protected $public = [
        self::IIN,
        self::HIGH_RANGE,
        self::LOW_RANGE,
        self::ID,
        self::TOKEN_IIN_LENGTH
    ];

    // --------------------------- Getters -----------------------------------

    public function getIin()
    {
        return $this->getAttribute(self::IIN);
    }

    public function getHighRange()
    {
        return $this->getAttribute(self::HIGH_RANGE);
    }

    public function getLowRange()
    {
        return $this->getAttribute(self::LOW_RANGE);
    }

    public function getIINLength()
    {
        return $this->getAttribute(self::TOKEN_IIN_LENGTH);
    }

    public function setIin($iin)
    {
        return $this->setAttribute(self::IIN, $iin);
    }

    public function setHighRange($highRange)
    {
        return $this->setAttribute(self::HIGH_RANGE, $highRange);
    }

    public function setLowRange($lowRange)
    {
        return $this->setAttribute(self::LOW_RANGE, $lowRange);
    }

    public function setIINLength($iinLength)
    {
        return $this->setAttribute(self::TOKEN_IIN_LENGTH, $iinLength);
    }

    public function getIncrementing()
    {
        return $this->incrementing;
    }
}
