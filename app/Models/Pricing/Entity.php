<?php

namespace RZP\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Base\QueryCache\Cacheable;
use RZP\Models\Merchant\Balance\AccountType;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use Cacheable;

    const ID                            = 'id';
    const PLAN_ID                       = 'plan_id';
    const PLAN_NAME                     = 'plan_name';
    const PRODUCT                       = 'product';
    const PROCURER                      = 'procurer';
    const FEATURE                       = 'feature';
    const GATEWAY                       = 'gateway';
    const PAYMENT_METHOD                = 'payment_method';
    const AUTH_TYPE                     = 'auth_type';
    const PAYMENT_METHOD_TYPE           = 'payment_method_type';
    const PAYMENT_METHOD_SUBTYPE        = 'payment_method_subtype';
    const PAYMENT_NETWORK               = 'payment_network';
    const INTERNATIONAL                 = 'international';
    const FEE_BEARER                    = 'fee_bearer';
    const PAYOUTS_FILTER                = 'payouts_filter';

    //
    // By default, all the rules are of type pricing
    // commission type pricing is used in partners to specify partner fixed commission or explicit commission
    //
    const TYPE                 = 'type';

    // Humanized name of the payment network
    const PAYMENT_NETWORK_NAME = 'payment_network_name';
    const PAYMENT_ISSUER       = 'payment_issuer';

    const EMI_DURATION         = 'emi_duration';
    const RECEIVER_TYPE        = 'receiver_type';

    // Amount Range Rule
    const AMOUNT_RANGE_ACTIVE  = 'amount_range_active';
    const AMOUNT_RANGE_MIN     = 'amount_range_min';
    const AMOUNT_RANGE_MAX     = 'amount_range_max';

    const PERCENT_RATE         = 'percent_rate';
    const FIXED_RATE           = 'fixed_rate';

    // Min And Max Rate
    const MIN_FEE              = 'min_fee';
    const MAX_FEE              = 'max_fee';

    //
    // account_type can be shared (for Virtual Accounts) or direct (for Current Accounts)
    //
    const ACCOUNT_TYPE         = 'account_type';
    //
    // channel which provides the account, eg: rbl, yesbank
    // would be null for account_type=shared and null(primary)
    //
    const CHANNEL              = 'channel';

    const EXPIRED_AT           = 'expired_at';
    const DELETED_AT           = 'deleted_at';

    // Input key for array of rules
    const RULES                = 'rules';

    const ORG_ID               = 'org_id';

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $public = [
        self::PERCENT_RATE,
        self::FIXED_RATE,
    ];

    protected $fillable = [
        self::ID,
        self::PLAN_ID,
        self::PLAN_NAME,
        self::PRODUCT,
        self::FEATURE,
        self::PROCURER,
        self::GATEWAY,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_SUBTYPE,
        self::PAYMENT_METHOD_TYPE,
        self::AUTH_TYPE,
        self::PAYMENT_NETWORK,
        self::PAYMENT_ISSUER,
        self::INTERNATIONAL,
        self::FEE_BEARER,
        self::RECEIVER_TYPE,
        self::AMOUNT_RANGE_ACTIVE,
        self::AMOUNT_RANGE_MIN,
        self::AMOUNT_RANGE_MAX,
        self::PERCENT_RATE,
        self::FIXED_RATE,
        self::MIN_FEE,
        self::MAX_FEE,
        self::EMI_DURATION,
        self::ORG_ID,
        self::TYPE,
        self::CHANNEL,
        self::ACCOUNT_TYPE,
        self::FEE_BEARER,
        self::PAYOUTS_FILTER,
    ];

    protected $entity = 'pricing';

    // We are explicitly generating Id so that same Id gets stored in live and test db
    protected $generateIdOnCreate = false;

    /**
     * Fields which will be modified before
     * input validation
     *
     * @var array
     */
    protected static $modifiers = ['inputRemoveBlanks', 'inputProvideDefaults'];

    protected static $generators = ['plan_id', 'org_id'];

    protected $defaults = [
        self::PROCURER                  => null,
        self::PRODUCT                   => Product::PRIMARY,
        self::FEATURE                   => Feature::PAYMENT,
        self::PAYMENT_METHOD_TYPE       => null,
        self::PAYMENT_METHOD_SUBTYPE    => null,
        self::PAYMENT_NETWORK           => null,
        self::AUTH_TYPE                 => null,
        self::PAYMENT_ISSUER            => null,
        self::PERCENT_RATE              => 0,
        self::FIXED_RATE                => 0,
        self::MIN_FEE                   => 0,
        self::MAX_FEE                   => null,
        self::AMOUNT_RANGE_ACTIVE       => '0',
        self::EMI_DURATION              => null,
        self::RECEIVER_TYPE             => null,
        self::TYPE                      => Type::PRICING,
        self::FEE_BEARER                => FeeBearer::PLATFORM,
        self::PAYOUTS_FILTER            => null,
    ];

    protected $proxy = [
        self::PRODUCT,
        self::FEATURE,
        self::PAYMENT_METHOD,
        self::PAYMENT_METHOD_SUBTYPE,
        self::PAYMENT_METHOD_TYPE,
        self::PAYMENT_NETWORK,
        self::PAYMENT_ISSUER,
        self::EMI_DURATION,
        self::AUTH_TYPE,
        self::INTERNATIONAL,
        self::RECEIVER_TYPE,
        self::AMOUNT_RANGE_ACTIVE,
        self::AMOUNT_RANGE_MIN,
        self::AMOUNT_RANGE_MAX,
        self::PERCENT_RATE,
        self::FIXED_RATE,
        self::MIN_FEE,
        self::MAX_FEE,
        self::FEE_BEARER,
    ];

    /**
     * Adds casts for fields
     *
     * @var array
     */
    protected $casts = [
        self::PROCURER            => 'string',
        self::INTERNATIONAL       => 'bool',
        self::AMOUNT_RANGE_ACTIVE => 'bool',
        self::PERCENT_RATE        => 'int',
        self::FIXED_RATE          => 'int',
        self::MIN_FEE             => 'int',
        self::EMI_DURATION        => 'int',
    ];

    const ZERO_PRICING = '10ZeroPricingP';

    protected function modifyInputProvideDefaults(& $input)
    {
        foreach ($this->defaults as $key => $value)
        {
            if (empty($input[$key]))
            {
                $input[$key] = $value;
            }
        }

        if (boolval($input[self::AMOUNT_RANGE_ACTIVE]) !== true)
        {
            $input[self::AMOUNT_RANGE_MIN] = null;
            $input[self::AMOUNT_RANGE_MAX] = null;
        }

        //
        // payment method can be null by default for refunds
        //
        if ((empty($input[self::FEATURE]) === false) and
            ($input[self::FEATURE] === Feature::REFUND) and
            (empty($input[self::PAYMENT_METHOD]) === true))
        {
            $input[self::PAYMENT_METHOD] = null;
        }
    }

    public function build(array $input = array())
    {
        $this->modify($input);

        $this->getValidator()->createPlanValidate($input);

        $this->generate($input);

        $this->fill($input);

        return $this;
    }

    public function addPlanRule($input, Plan $plan)
    {
        $this->modify($input);

        $this->getValidator()->addPlanRuleValidate($input, $plan);

        $this->generate($input);

        $this->fill($input);

        $this->fillRule($input, $plan);

        return $this;
    }

    protected function setFeeBearerAttribute($bearer)
    {
        $this->attributes[self::FEE_BEARER] = FeeBearer::getValueForBearerString($bearer);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    public function isAmountRangeActive()
    {
        return $this->getAttribute(self::AMOUNT_RANGE_ACTIVE);
    }

    public function payments()
    {
        return $this->hasMany('RZP\Models\Transaction\Entity', 'pricing_rule_id');
    }

    public function feesBreakup()
    {
        return $this->hasMany('RZP\Models\Transaction\FeeBreakup\Entity', 'pricing_rule_id');
    }

    protected function generatePlanId()
    {
        $this->setAttribute(self::PLAN_ID, static::generateUniqueId());
    }

    protected function generateOrgId()
    {
        $app = \App::getFacadeRoot();

        $orgId = $app['basicauth']->getOrgId();
        $orgId = Org\Entity::stripDefaultSign($orgId);

        $this->setAttribute(self::ORG_ID, $orgId);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Plan($models);
    }

    public function fillRule($input, $plan)
    {
        $rule = $plan->first();

        $input[self::PLAN_ID] = $rule->getAttribute(self::PLAN_ID);
        $input[self::PLAN_NAME] = $rule->getAttribute(self::PLAN_NAME);
        $input[self::GATEWAY] = $rule->getAttribute(self::GATEWAY);

        return $this->fill($input);
    }

    public function getReceiverType()
    {
        return $this->getAttribute(self::RECEIVER_TYPE);
    }

    public function getRates()
    {
        return [$this->getPercentRate(), $this->getFixedRate()];
    }

    public function getMinMaxFees()
    {
        return [$this->getMinFee(), $this->getMaxFee()];
    }

    public function getPlanId()
    {
        return $this->getAttribute(self::PLAN_ID);
    }

    public function getPlanName()
    {
        return $this->getAttribute(self::PLAN_NAME);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getPaymentNetwork()
    {
        return $this->getAttribute(self::PAYMENT_NETWORK);
    }

    public function getPaymentMethod()
    {
        return $this->getAttribute(self::PAYMENT_METHOD);
    }

    public function getPaymentMethodType()
    {
        return $this->getAttribute(self::PAYMENT_METHOD_TYPE);
    }

    public function getPaymentMethodSubType()
    {
        return $this->getAttribute(self::PAYMENT_METHOD_SUBTYPE);
    }

    public function getAuthType()
    {
        return $this->getAttribute(self::AUTH_TYPE);
    }

    public function getAmountRange()
    {
        return [$this->getAmountRangeMin(), $this->getAmountRangeMax()];
    }

    public function getAmountRangeMin()
    {
        return $this->getAttribute(self::AMOUNT_RANGE_MIN);
    }

    public function getAmountRangeMax()
    {
        return $this->getAttribute(self::AMOUNT_RANGE_MAX);
    }

    public function getFixedRate()
    {
        return $this->getAttribute(self::FIXED_RATE);
    }

    public function getPercentRate()
    {
        return $this->getAttribute(self::PERCENT_RATE);
    }

    public function getMinFee()
    {
        return $this->getAttribute(self::MIN_FEE);
    }

    public function getMaxFee()
    {
        return $this->getAttribute(self::MAX_FEE);
    }

    public function getEmiDuration()
    {
        return $this->getAttribute(self::EMI_DURATION);
    }

    protected function getAmountRangeMinAttribute()
    {
        $min = $this->attributes[self::AMOUNT_RANGE_MIN];

        return ($min === null) ? $min : (int) $min;
    }

    protected function getAmountRangeMaxAttribute()
    {
        $max = $this->attributes[self::AMOUNT_RANGE_MAX];

        return ($max === null) ? $max : (int) $max;
    }

    protected function getMaxFeeAttribute()
    {
        $max = $this->attributes[self::MAX_FEE];

        return ($max === null) ? $max : (int) $max;
    }

    protected function getFeeBearerAttribute()
    {
        return FeeBearer::getBearerStringForValue($this->attributes[self::FEE_BEARER]);
    }

    public function getFeature()
    {
        return $this->getAttribute(self::FEATURE);
    }

    public function getProduct()
    {
        return $this->getAttribute(self::PRODUCT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getAccountType()
    {
        return $this->getAttribute(self::ACCOUNT_TYPE);
    }

    public function getFeeBearer()
    {
        return $this->getAttribute(self::FEE_BEARER);
    }

    public function getPayoutsFilter()
    {
        return $this->getAttribute(self::PAYOUTS_FILTER);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function isPrimaryProduct(): bool
    {
        return ($this->getProduct() === Product::PRIMARY);
    }

    public function isBankingProduct(): bool
    {
        return ($this->getProduct() === Product::BANKING);
    }

    public function isAccountTypeDirect()
    {
        return ($this->getAccountType() === AccountType::DIRECT);
    }

    public function isAccountTypeShared()
    {
        return (($this->getAccountType() === AccountType::SHARED));
    }

    public function isPayoutsFilterFreePayout()
    {
        return (($this->getPayoutsFilter() === Payout\Entity::FREE_PAYOUT));
    }

    /**
     * Returns the org id of pricing plan.
     *
     * @return mixed
     */
    public function getOrgId()
    {
        return Org\Entity::getSignedId($this->getAttribute(self::ORG_ID));
    }

    /**
     * For adding plan_id filter in queries
     *
     * @param $query
     * @param $planId
     */
    public function scopePlanId($query, $planId)
    {
        $query->where(self::PLAN_ID, '=', $planId);
    }

    /**
     * @param        $query
     * @param string $product
     */
    public function scopeProduct(Builder $query, string $product)
    {
        $query->where(self::PRODUCT, '=', $product);
    }

    /**
     * We are tagging the pricing entity cache key by
     * <entityName>_<planID>_<type>
     */
    public static function getCacheTags(string $entity, string $planId, string $planType = null): string
    {
        $cacheTags = implode('_', [$entity, $planId, $planType]);

        return $cacheTags;
    }

    public function toArrayProxy()
    {
        $array = $this->attributesToArray();

        $this->setPublicAttributes($array);

        return array_only($array, $this->proxy);
    }
}
