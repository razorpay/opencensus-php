<?php

namespace RZP\Models\Emi;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Base\Traits\ExternalOwner;
use RZP\Models\Merchant\Account;

class Entity extends Base\PublicEntity
{
    use SoftDeletes, ExternalOwner;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const BANK                  = 'bank';
    const NETWORK               = 'network';
    const TYPE                  = 'type';
    const RATE                  = 'rate';
    const DURATION              = 'duration';
    const METHODS               = 'methods';
    const MIN_AMOUNT            = 'min_amount';
    const ISSUER_NAME           = 'issuer_name';
    const COBRANDING_PARTNER    = 'cobranding_partner';
    const ISSUER_PLAN_ID        = 'issuer_plan_id';
    const SUBVENTION            = 'subvention';
    const MERCHANT_PAYBACK      = 'merchant_payback';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';

    // Appended attributes
    const ISSUER                = 'issuer';


    // These are the valid durations that emi plan can have
    const VALID_DURATIONS = [2, 3, 6, 9, 12, 18, 24];

    protected $entity           = 'emi_plan';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::BANK,
        self::NETWORK,
        self::COBRANDING_PARTNER,
        self::TYPE,
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
        self::MERCHANT_ID,
        self::BANK,
        self::NETWORK,
        self::COBRANDING_PARTNER,
        self::TYPE,
        self::ISSUER,
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
        self::ISSUER,
        self::ISSUER_NAME,
        self::TYPE,
        self::RATE,
        self::DURATION,
    ];

    protected $defaults = [
        self::MIN_AMOUNT         => 300000,
        self::BANK               => null,
        self::NETWORK            => null,
        self::TYPE               => Type::CREDIT,
        self::ISSUER_PLAN_ID     => null,
        self::COBRANDING_PARTNER => null,
        self::SUBVENTION         => Subvention::CUSTOMER,
        self::MERCHANT_PAYBACK   => 0,
    ];

    protected $casts = [
        self::RATE             => 'int',
        self::MIN_AMOUNT       => 'int',
        self::DURATION         => 'int',
        self::MERCHANT_PAYBACK => 'int',
    ];

    protected $appends = [
        self::ISSUER,
    ];

    protected static $modifiers = [
        self::MERCHANT_PAYBACK,
    ];

    public function modifyMerchantPayback(& $input)
    {
        // Should respect the merchant payback field if sent from the create request
        if ((isset($input[self::MERCHANT_PAYBACK]) === false) and
            (isset($input[self::RATE]) === true) and
            (isset($input[self::DURATION]) == true))
        {
            $input[self::MERCHANT_PAYBACK] = Calculator::calculateMerchantPayback($input[self::RATE], $input[self::DURATION]);
        }
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
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

    public function getCobrandingPartner()
    {
        return $this->getAttribute(self::COBRANDING_PARTNER);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
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

    public function getIssuerAttribute()
    {
        return $this->getBank();
    }

    public function getIssuerNameAttribute(): string
    {
        return $this->getIssuerName();
    }

    /**
     * Check whether this emi plan is a shared/global emi plan.
     *
     * @return bool
     */
    public function isShared(): bool
    {
        $merchantId = $this->getAttribute(self::MERCHANT_ID);

        return ($merchantId === Account::SHARED_ACCOUNT);
    }

    /**
     * Issuer is a bank, a network or a cobranding partner
     *
     * @return string
     */
    public function getIssuer(): string
    {
        $bank = $this->getBank();

        $network = $this->getNetwork();

        if (is_null($bank) === false)
        {
            return $bank;
        }
        else if (is_null($network) === false)
        {
            return $network;
        }

        return $this->getCobrandingPartner();
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
