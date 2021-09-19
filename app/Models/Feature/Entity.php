<?php

namespace RZP\Models\Feature;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Entity as E;
use RZP\Models\Pricing;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Models\Base\QueryCache\Cacheable;

class Entity extends Base\PublicEntity
{
    use Cacheable;
    use HardDeletes;

    const NAME        = 'name';
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';

    // Input request keys, not part of actual entity
    const NAMES       = 'names';
    const SHOULD_SYNC = 'should_sync';

    // Keys used for tracing requests
    const OLD_FEATURES = 'old_features';
    const NEW_FEATURE  = 'new_feature';
    const FEATURE      = 'feature';

    protected $table = Table::FEATURE;

    protected $entity = E::FEATURE;

    // We are explicitly generating Id so that same Id gets stored in live and test db
    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::NAME,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::ENTITY_ID,
        self::ENTITY_TYPE
    ];

    protected static $modifiers = [
        self::NAME,
    ];

    /**
     * The routes of the features added here are not accessible by the OAuth applications, even if the feature is
     * enabled on the merchant account.
     *
     * @var array
     */
    public static $appBlacklistedFeatures = [
        Constants::S2S,
    ];

    /**
     * The features added here are selectively added to the merchants and the applications upon proper verification
     * through the activations team. And hence,
     * - If a merchant directly tries to access a route which requires one of these features, it is allowed to access
     *   the route only if feature is enabled for the merchant [regular flow]
     * - If an app tries to access a route which requires one of these features, it is allowed to access
     *   the route only if the merchant as well as the application has the feature enabled.
     *
     * @var array
     */
    public static $restrictedAccessFeatures = [
        Constants::MARKETPLACE,
        Constants::SUBSCRIPTIONS,
        Constants::VIRTUAL_ACCOUNTS,
    ];

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function setEntityId(string $entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setEntityType(string $entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    protected function modifyName(& $input)
    {
        if (isset($input[self::NAME]) === true)
        {
            $input[self::NAME] = strtolower($input[self::NAME]);
        }
    }

    /**
     * Returns true if a feature is a public facing product feature
     *
     * @return bool
     */
    public function isProductFeature(): bool
    {
        return (in_array($this->getName(), Constants::PRODUCT_FEATURES) === true);
    }

    /**
     * @return bool
     */
    public function isMerchantFeature(): bool
    {
        return ($this->getEntityType() === Constants::MERCHANT);
    }

    /**
     * Returns the cache_tags to be used for Feature entities
     *
     * feature_<entity_name>_<entity_id>
     *
     * @param string $entityType
     * @param string $entityId
     *
     * @return string
     */
    public static function getCacheTagsForEntities(string $entityType, string $entityId): string
    {
        return implode('_', [Entity::FEATURE, $entityType, $entityId]);
    }

    public static function getCacheTagsForNames(string $entityType, string $entityId): string
    {
        return implode('_', [Entity::FEATURE , 'names', $entityType, $entityId]);
    }

    public static function getDistrubutedCacheTagsForNames(string $entityType, string $entityId): string
    {
        $prefix = Pricing\Repository::getQueryCachePrefixForDistributingLoad();

        return implode('_', [$prefix, Entity::FEATURE , 'names', $entityType, $entityId]);
    }
}
