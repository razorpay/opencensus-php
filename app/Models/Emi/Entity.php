<?php

namespace RZP\Models\Emi;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Bank;
use RZP\Models\Card;

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

    // Appended attributes
    const ISSUER_NAME           = 'issuer_name';

    protected $entity           = 'emi_plan';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::BANK,
        self::NETWORK,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT,
        self::ISSUER_PLAN_ID,
        self::SUBVENTION,
        self::MERCHANT_PAYBACK,
    ];

    protected $visible = [
        self::ID,
        self::BANK,
        self::NETWORK,
        self::ISSUER_NAME,
        self::RATE,
        self::DURATION,
        self::METHODS,
        self::MIN_AMOUNT,
        self::ISSUER_PLAN_ID,
        self::SUBVENTION,
        self::MERCHANT_PAYBACK,
    ];

    protected $public = [
        self::ISSUER_NAME,
        self::RATE,
        self::DURATION,
    ];

    protected $defaults = [
        self::MIN_AMOUNT       => 300000,
        self::BANK             => null,
        self::NETWORK          => null,
        self::ISSUER_PLAN_ID   => null,
        self::SUBVENTION       => Subvention::CUSTOMER,
        self::MERCHANT_PAYBACK => 0,
    ];

    protected $casts = [
        self::RATE             => 'int',
        self::MIN_AMOUNT       => 'int',
        self::DURATION         => 'int',
        self::MERCHANT_PAYBACK => 'int',
    ];

    protected $appends = [
        self::ISSUER_NAME,
    ];

    protected static $modifiers = [
        self::MERCHANT_PAYBACK,
    ];

    public function modifyMerchantPayback(& $input)
    {
        if ((isset($input[self::RATE]) === true) and
            (isset($input[self::DURATION]) == true))
        {
            $input[self::MERCHANT_PAYBACK] = Calculator::calculateMerchantPayback($input[self::RATE], $input[self::DURATION]);
        }
    }

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

    public function getIssuerNameAttribute(): string
    {
        return $this->getIssuerName();
    }

    /**
     * Issuer is either a bank or a network
     *
     * @return string
     */
    public function getIssuer(): string
    {
        $bank = $this->getBank();

        if (is_null($bank) === false)
        {
            return $bank;
        }

        return $this->getNetwork();
    }

    /**
     * Issuer is either a bank or a network, returns mapped full name.
     *
     * @return string
     */
    public function getIssuerName(): string
    {
        $bank = $this->getBank();

        if (is_null($bank) === false)
        {
            return Bank\Name::getBankName($bank);
        }

        $network = $this->getNetwork();

        return Card\Network::getFullName($network);
    }

}
