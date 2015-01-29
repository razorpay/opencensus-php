<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;

class Banks extends Base\UniqueIdEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const BANKS             = 'banks';

    protected $primaryKey = self::MERCHANT_ID;

    protected $table = \Constants\Table::MERCHANT_BANKS;

    protected $fillable = array(
        self::MERCHANT_ID,
        self::BANKS);

    protected $visible = array(
        self::BANKS);

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function getBanks()
    {
        return $this->getAttribute(self::BANKS);
    }

    public function setBanks(array $banks)
    {
        $this->setAttribute(self::BANKS, $banks);
    }

    public function getBanksAttribute()
    {
        return json_decode($this->attributes[self::BANKS], true);
    }

    public function setBanksAttribute(array $banks)
    {
        $this->attributes[self::BANKS] = json_encode($banks);
    }

    public function toArrayWithBankNames()
    {
        $banks = $this->getBanks();

        $names = \Models\Bank\Name::getNames($banks);

        return $names;
    }
}
