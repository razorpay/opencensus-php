<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                    = 'id';
    const BANK                  = 'bank';
    const NETWORK               = 'network';
    const RATE                  = 'rate';
    const DURATION              = 'duration';
    const METHODS               = 'methods';
    const MIN_AMOUNT            = 'min_amount';
    const ISSUER_PLAN_ID        = 'issuer_plan_id';
    const SUBVENTION            = 'subvention';
    const MERCHANT_PAYBACK      = 'merchant_payback';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    protected $entity           = 'emi_plan';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::ID,
        self::BANK,
        self::NETWORK,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT,
        self::ISSUER_PLAN_ID,
        self::SUBVENTION,
        self::MERCHANT_PAYBACK);

    protected $visible = array(
        self::ID,
        self::BANK,
        self::NETWORK,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT,
        self::ISSUER_PLAN_ID,
        self::SUBVENTION,
        self::MERCHANT_PAYBACK);

    protected $public = array(
        self::BANK,
        self::RATE,
        self::DURATION,
    );

    protected $defaults = array(
        self::MIN_AMOUNT       => 300000,
        self::BANK             => null,
        self::NETWORK          => null,
        self::ISSUER_PLAN_ID   => null,
        self::SUBVENTION       => Subvention::CUSTOMER,
        self::MERCHANT_PAYBACK => 0,
    );

    protected $casts = array(
        self::RATE             => 'int',
        self::MIN_AMOUNT       => 'int',
        self::DURATION         => 'int',
        self::MERCHANT_PAYBACK => 'int',
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

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getMethods()
    {
        return $this->getAttribute(self::METHODS);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getSubvention()
    {
        return $this->getAttribute(self::SUBVENTION);
    }

    public function getMerchantPayback()
    {
        return $this->getAttribute(self::MERCHANT_PAYBACK);
    }

    public function getIssuerPlanId()
    {
        return $this->getAttribute(self::ISSUER_PLAN_ID);
    }

    /**
     * Issuer is either a bank or a network
     */
    public function getIssuer()
    {
        $bank = $this->getBank();

        if (is_null($bank) === false)
        {
            return $bank;
        }

        return $this->getNetwork();
    }
}
