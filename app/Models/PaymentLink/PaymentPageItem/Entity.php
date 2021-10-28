<?php

namespace RZP\Models\PaymentLink\PaymentPageItem;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Item;
use RZP\Models\Settings;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink;
use RZP\Models\Store\Entity as StoreEntity;

/**
 * @property PaymentLink\Entity $paymentLink
 */
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
    const PRODUCT_CONFIG    = 'product_config';
    const SUBSCRIPTION_DETAILS  = 'subscription_details';
    const PLAN_DETAILS          = 'plan_details';

    const SUBSCRIPTION_TOTAL_COUNT = 'total_count';
    const SUBSCRIPTION_QUANTITY     = 'quantity';
    const SUBSCRIPTION_CUSTOMER_NOTIFY = 'customer_notify';

    const PRODUCT_IMAGES = 'images';
    const SELLING_PRICE = 'selling_price';

    // Input keys
    const ITEM               = 'item';
    const PAYMENT_PAGE_ITEMS = 'payment_page_items';
    const ITEM_DELETED_AT    = 'item_deleted_at';

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
        self::PLAN_ID,
        self::PLAN_DETAILS,
        self::PRODUCT_CONFIG,
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
        self::PLAN_ID,
        self::PLAN_DETAILS,
        self::PRODUCT_CONFIG,
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
        self::PLAN_ID,
        self::PLAN_DETAILS,
        self::PRODUCT_CONFIG,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
        self::PLAN_ID,
        self::PRODUCT_CONFIG,
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

    protected $hosted = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_LINK_ID,
        self::ITEM,
        self::MANDATORY,
        self::IMAGE_URL,
        self::MIN_PURCHASE,
        self::MAX_PURCHASE,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::SETTINGS,
        self::PLAN_ID,
        self::PLAN_DETAILS,
        self::SUBSCRIPTION_DETAILS,
        self::PRODUCT_CONFIG,
    ];

   const SUBSCRIPTION_KEYS = [
       self::SUBSCRIPTION_CUSTOMER_NOTIFY,
       self::SUBSCRIPTION_TOTAL_COUNT,
       self::SUBSCRIPTION_QUANTITY,
   ];

   const ALLOWED_PRODUCT_CONFIG_CREATE_KEYS = [
       self::SUBSCRIPTION_DETAILS,
       self::PRODUCT_IMAGES,
       self::SELLING_PRICE,
   ];

   public function toStoreProductArrayPublic(): array
   {
       return [
           Entity::ID                              => $this->getPublicId(),
           StoreEntity::PRODUCT_NAME               => $this->itemWithTrashed->getName(),
           StoreEntity::PRODUCT_DESCRIPTION        => $this->itemWithTrashed->getDescription(),
           StoreEntity::PRODUCT_IMAGES             => $this->getProductConfig(StoreEntity::PRODUCT_IMAGES),
           StoreEntity::PRODUCT_SELLING_PRICE      => $this->getProductConfigAsInt(StoreEntity::PRODUCT_SELLING_PRICE),
           StoreEntity::PRODUCT_DISCOUNTED_PRICE   => $this->itemWithTrashed->getAmount(),
           StoreEntity::PRODUCT_STOCK              => $this->getStock(),
           StoreEntity::PRODUCT_STOCK_AVAILABLE    => $this->getQuantityAvailable(),
           StoreEntity::PRODUCT_STOCK_SOLD         => $this->getQuantitySold(),
           StoreEntity::STATUS                     => $this->itemWithTrashed->getDeletedAt() === null ? StoreEntity::PRODUCT_STATUS_ACTIVE : StoreEntity::PRODUCT_STATUS_INACTIVE,
       ];
   }

    public function getQuantitySold(): int
    {
        return $this->getAttribute(self::QUANTITY_SOLD);
    }

    public function getItemId(): string
    {
        return $this->getAttribute(self::ITEM_ID);
    }

    public function getPlanId()
    {
        return $this->getAttribute(self::PLAN_ID);
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

    public function getDeletedAt()
    {
        return $this->getAttribute(self::DELETED_AT);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getMinPurchase()
    {
        return $this->getAttribute(self::MIN_PURCHASE);
    }

    public function getMaxPurchase()
    {
        return $this->getAttribute(self::MAX_PURCHASE);
    }

    public function getStock()
    {
        return $this->getAttribute(self::STOCK);
    }

    public function getQuantityAvailable()
    {
        $stockAvailable = $this->getStock();

        if (isset($stockAvailable) === true)
        {
            return $stockAvailable - $this->getQuantitySold();
        }

        return null;
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

    public function setPublicPlanIdAttribute(array & $attributes)
    {
        $paymentLinkId = $this->getAttribute(self::PLAN_ID);

        if (empty($paymentLinkId) === true)
        {
            return;
        }

        $attributes[self::PLAN_ID] = 'plan_'.$paymentLinkId;
    }

    public function setPublicProductConfigAttribute(array & $attributes)
    {

        $productConfig = $this->getAttribute(self::PRODUCT_CONFIG);

        if (empty($productConfig) === true)
        {
            return;
        }

        $attributes[self::PRODUCT_CONFIG] = json_decode($productConfig);
    }

    public function setProductConfig(string $productConfig)
    {
        $this->setAttribute(self::PRODUCT_CONFIG, $productConfig);
    }

    public function setDeletedAt($timestamp)
    {
        $this->attributes[self::DELETED_AT] = $timestamp;
    }

    public function getProductConfig($key = null)
    {
        $productConfig = $this->getAttribute(self::PRODUCT_CONFIG);

        if (empty($productConfig) === true)
        {
            return null;
        }

        $productConfigArray = json_decode($productConfig, true);

        if ($key === null)
        {
            return $productConfigArray;
        }

        return $productConfigArray[$key] ?? null;
    }
    public function getProductConfigAsInt($key)
    {
        if ($key === null)
        {
            return null;
        }

        $productConfig = $this->getAttribute(self::PRODUCT_CONFIG);

        if (empty($productConfig) === true)
        {
            return null;
        }

        $productConfigArray = json_decode($productConfig, true);

        $value = $productConfigArray[$key]?? null;

        if ($value !== null)
        {
            return (int)$value;
        }

        return null;
    }

    public function incrementQuantitySold(int $incrementValue)
    {
        $this->setAttribute(self::QUANTITY_SOLD, ($this->getQuantitySold() + $incrementValue));
    }

    public function incrementTotalAmountPaidBy(int $incrementValue)
    {
        $this->setAttribute(self::TOTAL_AMOUNT_PAID, ($this->getTotalAmountPaid() + $incrementValue));
    }

    public function isStockLeft(): bool
    {
        if (is_null($this->getStock()) === true)
        {
            return true;
        }

        $remainingStock = $this->getStock() - $this->getQuantitySold();

        return $remainingStock > 0;
    }

    public function isSlotLeft(int $slot): bool
    {
        if (is_null($this->getStock()) === true)
        {
            return true;
        }

        $remainingStock = $this->getStock() - $this->getQuantitySold();

        return $remainingStock >= $slot;
    }

    public function doesPlanExists(): bool
    {
        $planId = $this->getPlanId();

        if (empty($planId) === true)
        {
            return false;
        }

        return true;
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

    public function itemWithTrashed()
    {
        return $this->belongsTo(Item\Entity::class, self::ITEM_ID)->withTrashed();
    }
}
