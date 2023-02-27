<?php

namespace RZP\Models\Partner\Config;

use App;
use Config;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Merchant;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\AccessMap;
use RZP\Constants as AppConstants;
use RZP\Models\Merchant\MerchantApplications\Repository as MerchantAppRepo;
use RZP\Models\Merchant\MerchantApplications\Entity as MerchantAppEntity;

class Entity extends PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID               = 'entity_id';
    const ORIGIN_ID               = 'origin_id';
    const REVISIT_AT              = 'revisit_at';
    const ORIGIN_TYPE             = 'origin_type';
    const ENTITY_TYPE             = 'entity_type';
    const TDS_PERCENTAGE          = 'tds_percentage';
    const DEFAULT_PLAN_ID         = 'default_plan_id';
    const IMPLICIT_PLAN_ID        = 'implicit_plan_id';
    const EXPLICIT_PLAN_ID        = 'explicit_plan_id';
    const COMMISSION_MODEL        = 'commission_model';
    const SETTLE_TO_PARTNER       = 'settle_to_partner';
    const IMPLICIT_EXPIRY_AT      = 'implicit_expiry_at';
    const COMMISSIONS_ENABLED     = 'commissions_enabled';
    const HAS_GST_CERTIFICATE     = 'has_gst_certificate';
    const EXPLICIT_REFUND_FEES    = 'explicit_refund_fees';
    const EXPLICIT_SHOULD_CHARGE  = 'explicit_should_charge';
    const DEFAULT_PAYMENT_METHODS = 'default_payment_methods';
    const SUB_MERCHANT_CONFIG     = 'sub_merchant_config';
    const PARTNER_METADATA        = 'partner_metadata';

    const DEFAULT_TDS_PERCENTAGE             = 500;
    const TDS_PERCENTAGE_FOR_MISSING_DETAILS = 2000;

    protected $entity             = AppConstants\Entity::PARTNER_CONFIG;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::DEFAULT_PLAN_ID,
        self::IMPLICIT_PLAN_ID,
        self::EXPLICIT_PLAN_ID,
        self::COMMISSION_MODEL,
        self::IMPLICIT_EXPIRY_AT,
        self::COMMISSIONS_ENABLED,
        self::DEFAULT_PAYMENT_METHODS,
        self::EXPLICIT_REFUND_FEES,
        self::EXPLICIT_SHOULD_CHARGE,
        self::SETTLE_TO_PARTNER,
        self::TDS_PERCENTAGE,
        self::HAS_GST_CERTIFICATE,
        self::REVISIT_AT,
        self::SUB_MERCHANT_CONFIG,
        self::PARTNER_METADATA
    ];

    protected $public = [
        self::ID,
        self::ORIGIN_ID,
        self::ENTITY_ID,
        self::ORIGIN_TYPE,
        self::ENTITY_TYPE,
        self::DEFAULT_PLAN_ID,
        self::IMPLICIT_PLAN_ID,
        self::EXPLICIT_PLAN_ID,
        self::COMMISSION_MODEL,
        self::IMPLICIT_EXPIRY_AT,
        self::COMMISSIONS_ENABLED,
        self::DEFAULT_PAYMENT_METHODS,
        self::EXPLICIT_REFUND_FEES,
        self::EXPLICIT_SHOULD_CHARGE,
        self::SETTLE_TO_PARTNER,
        self::TDS_PERCENTAGE,
        self::HAS_GST_CERTIFICATE,
        self::REVISIT_AT,
        self::CREATED_AT,
        self::SUB_MERCHANT_CONFIG,
        self::PARTNER_METADATA
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REVISIT_AT,
    ];

    protected $defaults = [
        self::COMMISSIONS_ENABLED     => 0,
        self::EXPLICIT_REFUND_FEES    => 0,
        self::EXPLICIT_SHOULD_CHARGE  => 0,
        self::COMMISSION_MODEL        => CommissionModel::COMMISSION,
        self::SETTLE_TO_PARTNER       => 0,
        self::TDS_PERCENTAGE          => self::DEFAULT_TDS_PERCENTAGE,
        self::HAS_GST_CERTIFICATE     => 0,
        self::DEFAULT_PAYMENT_METHODS => null,
        self::SUB_MERCHANT_CONFIG     => null,
        self::PARTNER_METADATA        => null
    ];

    protected $casts = [
        self::COMMISSIONS_ENABLED     => 'bool',
        self::DEFAULT_PAYMENT_METHODS => 'array',
        self::EXPLICIT_REFUND_FEES    => 'bool',
        self::EXPLICIT_SHOULD_CHARGE  => 'bool',
        self::SETTLE_TO_PARTNER       => 'bool',
        self::TDS_PERCENTAGE          => 'int',
        self::HAS_GST_CERTIFICATE     => 'bool',
        self::SUB_MERCHANT_CONFIG     => 'array',
        self::PARTNER_METADATA        => 'array'
    ];

    protected $publicSetters = [
        self::PARTNER_METADATA,
    ];

    protected static $unsetCreateInput = [Constants::APPLICATION_ID, Constants::PARTNER_ID];

    protected static $generators       = [self::REVISIT_AT, self::ID];

    protected $dispatchesEvents = [
        // Event 'saved' fires on insert and update both.
        'saved'   => EventSaved::class
    ];

    // --------------------- Relations ------------------
    public function entity()
    {
        return $this->morphTo();
    }

    public function origin()
    {
        return $this->morphTo();
    }

    // --------------------- GETTERS ---------------------
    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getOriginId()
    {
        return $this->getAttribute(self::ORIGIN_ID);
    }

    public function isCommissionsEnabled() : bool
    {
        return ($this->getAttribute(self::COMMISSIONS_ENABLED) === true);
    }

    public function getDefaultPlanId()
    {
        return $this->getAttribute(self::DEFAULT_PLAN_ID);
    }

    public function getImplicitPricingPlanId()
    {
        return $this->getAttribute(self::IMPLICIT_PLAN_ID);
    }

    public function getExplicitPricingPlanId()
    {
        return $this->getAttribute(self::EXPLICIT_PLAN_ID);
    }

    public function getImplicitExpiryAt()
    {
        return $this->getAttribute(self::IMPLICIT_EXPIRY_AT);
    }

    public function getCommissionModel(): string
    {
        return $this->getAttribute(self::COMMISSION_MODEL);
    }

    public function getDefaultPaymentMethods()
    {
        return $this->getAttribute(self::DEFAULT_PAYMENT_METHODS);
    }

    public function getSubMerchantConfig()
    {
        return $this->getAttribute(self::SUB_MERCHANT_CONFIG);
    }

    public function shouldSettleToPartner(): bool
    {
        return ($this->getAttribute(self::SETTLE_TO_PARTNER) === true);
    }

    public function getTdsPercentage(): int
    {
        return $this->getAttribute(self::TDS_PERCENTAGE);
    }

    public function getLogoUrl()
    {
        $metadata = $this->getPartnerMetadata();

        return empty($metadata) ? null : $metadata[Constants::LOGO_URL];
    }

    public function shouldCreditGst(): bool
    {
        return ($this->getAttribute(self::HAS_GST_CERTIFICATE) === false);
    }

    public function isDefaultConfig(): bool
    {
        return ($this->getAttribute(self::ENTITY_TYPE) === AccessMap\Entity::APPLICATION);
    }

    public function getPartnerMetadata(): array|null
    {
        return $this->getAttribute(self::PARTNER_METADATA);
    }

    public function getBrandName(): string
    {
        $partnerMetaData = $this->getPartnerMetadata();

        return (empty($partnerMetaData) ? $this->getDefaultPartnerBrandName() : $partnerMetaData[Constants::BRAND_NAME]);
    }

    // --------------------- SETTERS ---------------------
    public function setEntityType($entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setOriginType($originType)
    {
        $this->setAttribute(self::ORIGIN_TYPE, $originType);
    }

    public function setOriginId($originId)
    {
        $this->setAttribute(self::ORIGIN_ID, $originId);
    }

    public function setEntityId($entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setSubMerchantConfig($subMerchantConfig)
    {
        return $this->setAttribute(self::SUB_MERCHANT_CONFIG, $subMerchantConfig);
    }

    // --------------------- GENERATORS ---------------------
    public function generateRevisitAt(array $input)
    {
        if (empty($input[self::REVISIT_AT]) === true)
        {
            // current time + 1 year
            $this->setAttribute(self::REVISIT_AT, Carbon::now()->addYear()->getTimestamp());
        }
    }

    public function setImplicitPlanIdAttribute($value)
    {
        $value = $value ?: null;

        $this->attributes[self::IMPLICIT_PLAN_ID] = $value;
    }

    public function setExplicitPlanIdAttribute($value)
    {
        $value = $value ?: null;

        $this->attributes[self::EXPLICIT_PLAN_ID] = $value;
    }

    public function setImplicitExpiryAtAttribute($value)
    {
        $value = $value ?: null;

        $this->attributes[self::IMPLICIT_EXPIRY_AT] = $value;
    }

    public function setPartnerMetaData($metaData)
    {
        $this->setAttribute(self::PARTNER_METADATA, $metaData);
    }

    // --------------------- END -----------------------------

    public function isExplicitRecordOnly(): bool
    {
        return ($this->getAttribute(self::EXPLICIT_SHOULD_CHARGE) === false);
    }

    public function toArrayPublic(): array
    {
        $app = App::getFacadeRoot();

        $response = parent::toArrayPublic();

        // Don't return the whole entity if the request is not from admin for confidentiality
        if ($app['basicauth']->isAdminAuth() === false and $app['basicauth']->isDashboardApp() === true)
        {
            $response = array_only($response, Constants::PARTNER_CONFIG_PUBLIC);

            $response[self::PARTNER_METADATA] = array_merge($this->getDefaultPartnerMetaData(), array_filter($response[self::PARTNER_METADATA]??[]));
        }

        return $response;
    }

    protected function setPublicPartnerMetadataAttribute(array & $array)
    {
        if (empty($array[Entity::PARTNER_METADATA][Constants::LOGO_URL]) === false)
        {
            $array[Entity::PARTNER_METADATA][Constants::LOGO_URL] = $this->getFullLogoUrlWithSize();

        }
    }

    private function getLogoUrlBasedOnSize(string $logoUrl, string $size): string
    {
        // Gets the position of last dot.
        // Gets the substring until before the last dot.
        // Appends '_size' to the substring.
        // Appends the substring from the last dot to the end of url.

        $extension_pos = strrpos($logoUrl, '.');

        return substr($logoUrl, 0, $extension_pos) . '_' . $size . substr($logoUrl, $extension_pos);
    }

    private function getFullLogoUrlWithSize($size = Constants::ORIGINAL_SIZE): ?string
    {
        $relativeLogoUrl = $this->getLogoUrl();

        if ($relativeLogoUrl === null)
        {
            return null;
        }

        // Different cdn urls for different contexts.
        $context = Config::get('app.context');
        $cdnUrl = Config::get('url.cdn')[$context];

        // Sample base URL : 'https://cdn.razorpay.com' + '/logos/a.png'
        // Sample actual URL : 'https://cdn.razorpay.com' + 'logos/' + 'a_medium.png'
        $baseLogoUrl = $cdnUrl . $relativeLogoUrl;

        // In DB, we are storing the base URL. The actual URL has the respective size appended to it.
        return $this->getLogoUrlBasedOnSize($baseLogoUrl, $size);
    }

    private function getDefaultPartnerMetaData() : array
    {
        $defaultMetaData = Constants::PARTNER_METADATA_DEFAULT_VALUES;

        $defaultMetaData[Constants::BRAND_NAME] = $this->getDefaultPartnerBrandName();

        return $defaultMetaData;
    }

    private function getDefaultPartnerBrandName()
    {
        $app = App::getFacadeRoot();

        $merchant = $app['basicauth']->getMerchant();

        if (empty($merchant) === true)
        {
            $application = (new MerchantAppRepo())->fetchMerchantApplication($this->getEntityId(), MerchantAppEntity::APPLICATION_ID);
            $merchant = (new Merchant\Service)->getMerchantFromMid($application[0][self::MERCHANT_ID]);
        }

        return ($merchant->merchantDetail->getBusinessName())??($merchant->getName());
    }
}
