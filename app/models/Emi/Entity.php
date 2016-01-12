<?php

namespace Models\Emi;

use Models\Base;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Entity extends Base\PublicEntity
{
    use SoftDeletingTrait;
    
    const ID                    = 'id';
    const BANK                  = 'bank';
    const EMI_RATES             = 'emi_rates';
    const METHODS               = 'methods';
    const MIN_AMOUNT            = 'min_amount';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = '';

    protected $entity           = 'emi';

    protected $table            = \Constants\Table::EMI_OPTIONS;

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::EMI_RATES,
        self::METHODS,
        self::MIN_AMOUNT);

    protected $visible = array(
        self::ID,
        self::BANK,
        self::EMI_RATES,
        self::METHODS,
        self::MIN_AMOUNT);

    protected $public = array(
        self::ID,
        self::BANK,
        self::EMI_RATES,
        self::METHODS,
        self::MIN_AMOUNT);

    protected $defaults = array(
        self::MIN_AMOUNT => 5000);

    protected $guarded = array(self::ID);

    public function getEmiRates()
    {
        return $this->getAttribute(self::EMI_RATES);
    }

    public function getBank()
    {
        return $this->getAttribute(self::BANK);
    }

    public function getMethods()
    {
        return $this->getAttribute(self::METHODS);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getEmiRatesAttribute()
    {
        $emi_rates = $this->attribures[self::EMI_RATES];
        return $emi_rates;
    }

    public function getBankAttribute()
    {
       return $this->attribures[self::BANK];
    }

    public function getMethodsAttribute()
    {
       return $this->attribures[self::METHODS];
    }

    public function getMinAmountAttribute()
    {
        return $this->attribures[self::MIN_AMOUNT];
    }
}