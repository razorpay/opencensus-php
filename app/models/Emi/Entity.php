<?php

namespace Models\Emi;

use Models\Base;
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Entity extends Base\PublicEntity
{
    use SoftDeletingTrait;
    
    const ID                    = 'id';
    const BANK                  = 'bank';
    const RATE                  = 'rate';
    const DURATION              = 'duration';
    const METHODS               = 'methods';
    const MIN_AMOUNT            = 'min_amount';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected static $sign      = '';

    protected $entity           = 'emi';

    protected $table            = \Constants\Table::EMI_PLAN;

    protected $genereateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT);

    protected $visible = array(
        self::ID,
        self::BANK,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT);

    protected $public = array(
        self::BANK,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT);

    protected $defaults = array(
        self::MIN_AMOUNT => 300000,
    );

    protected $guarded = array(self::ID);

    public function getRate()
    {
        return $this->getAttribute(self::RATE);
    }

    public function getDuration()
    {
        return $this->getAttribute(self::DURATION);
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

    public function getRateAttribute()
    {
        return (integer)$this->attributes[self::RATE];
    }

    public function getDurationAttribute()
    {
        return (integer)$this->attributes[self::DURATION];
    }

    public function getBankAttribute()
    {
       return $this->attributes[self::BANK];
    }

    public function getMethodsAttribute()
    {
       return $this->attributes[self::METHODS];
    }

    public function getMinAmountAttribute()
    {
        return (integer)$this->attributes[self::MIN_AMOUNT];
    }
}