<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Settings;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const PAYMENT_LINK_ID   = 'payment_link_id';
    const ITEM_ID           = 'item_id';
    const PLAN_ID           = 'plan_id';
    const MANDATORY         = 'mandatory';
    const IMAGE_URL         = 'image_url';
    const STOCK             = 'stock';
    const QUANTITY_SOLD     = 'quantity_sold';
    const TOTAL_AMOUNT_PAID = 'total_amount_paid';
    const MIN_PURCHASE      = 'min_purchase';
    const MAX_PURCHASE      = 'max_purchase';
    const MIN_AMOUNT        = 'min_amount';
    const MAX_AMOUNT        = 'max_amount';

    // Input keys
    const ITEM               = 'item';
    const PAYMENT_PAGE_ITEMS = 'payment_page_items';

    // List of keys stored against entity's settings.
    const SETTINGS  = 'settings';
    const POSITION  = 'position';

    protected $entity             = 'payment_page_item';

    protected static $sign        = 'ppi';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
        self::ITEM_ID,
        self::ITEM,
        self::MERCHANT_ID,
        self::MANDATORY,
        self::IMAGE_URL,
        self::STOCK,
        self::QUANTITY_SOLD,
        self::TOTAL_AMOUNT_PAID,
        self::MIN_PURCHASE,
        self::MAX_PURCHASE,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::SETTINGS,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
        self::ITEM,
        self::MANDATORY,
        self::IMAGE_URL,
        self::STOCK,
        self::QUANTITY_SOLD,
        self::TOTAL_AMOUNT_PAID,
        self::MIN_PURCHASE,
        self::MAX_PURCHASE,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::SETTINGS,
    ];

    protected $fillable = [
        self::ID,
        self::PAYMENT_LINK_ID,
        self::ITEM_ID,
        self::MANDATORY,
        self::IMAGE_URL,
        self::STOCK,
        self::TOTAL_AMOUNT_PAID,
        self::MIN_PURCHASE,
        self::MAX_PURCHASE,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
    ];

    protected $defaults = [
        self::MANDATORY          => true,
        self::IMAGE_URL          => null,
        self::STOCK              => null,
        self::QUANTITY_SOLD      => 0,
        self::TOTAL_AMOUNT_PAID  => 0,
        self::MIN_PURCHASE       => null,
        self::MAX_PURCHASE       => null,
        self::MIN_AMOUNT         => null,
        self::MAX_AMOUNT         => null,
    ];

    protected $casts = [
        self::MANDATORY         => 'bool',
        self::TOTAL_AMOUNT_PAID => 'int',
        self::STOCK             => 'int',
        self::QUANTITY_SOLD     => 'int',
        self::MIN_PURCHASE      => 'int',
        self::MAX_PURCHASE      => 'int',
        self::MIN_AMOUNT        => 'int',
        self::MAX_AMOUNT        => 'int',
    ];

    protected $embeddedRelations   = [
        self::ITEM,
    ];

    public function getQuantitySold(): int
    {
        return $this->getAttribute(self::QUANTITY_SOLD);
    }

    public function getItemId(): string
    {
        return $this->getAttribute(self::ITEM_ID);
    }

    public function getTotalAmountPaid(): int
    {
        return $this->getAttribute(self::TOTAL_AMOUNT_PAID);
    }

    public function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::PAYMENT_PAGE_ITEM);
    }

    /**
     * Get settings associated with payment page item entity.
     * @param  string|null $key
     * @return \Razorpay\Spine\DataTypes\Dictionary|string
     */
    public function getSettings(string $key = null)
    {
        $accessor = $this->getSettingsAccessor();

        return $key === null ? $accessor->all() : $accessor->get($key);
    }

    public function setMinPurchase(int $minPurchase)
    {
        $this->setAttribute(self::MIN_PURCHASE, $minPurchase);
    }

    public function setMinAmount(int $minAmount)
    {
        $this->setAttribute(self::MIN_AMOUNT, $minAmount);
    }

    public function setPublicPaymentLinkIdAttribute(array & $attributes)
    {
        $paymentLinkId = $this->getAttribute(self::PAYMENT_LINK_ID);

        $attributes[self::PAYMENT_LINK_ID] = PaymentLink\Entity::getSignedIdOrNull($paymentLinkId);
    }

    public function incrementQuantitySold(int $incrementValue)
    {
        $this->setAttribute(self::QUANTITY_SOLD, ($this->getQuantitySold() + $incrementValue));
    }

    public function incrementTotalAmountPaidBy(int $incrementValue)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_PAID, ($this->getTotalAmountPaid() + $incrementValue));
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function paymentLink()
    {
        return $this->belongsTo(PaymentLink\Entity::class);
    }

    public function item()
    {
        return $this->belongsTo(Item\Entity::class);
    }
}
