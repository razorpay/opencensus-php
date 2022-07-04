<?php

namespace RZP\Models\PaymentLink\NocodeCustomUrl;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Exception\BadRequestValidationFailureException;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    // columns
    const SLUG                  = 'slug';
    const MERCHANT_ID           = 'merchant_id';
    const DOMAIN                = 'domain';
    const PRODUCT               = 'product';
    const PRODUCT_ID            = 'product_id';
    const META_DATA             = 'meta_data';

    // column Lengths
    const SLUG_LEN          = 128;
    const DOMAIN_LEN        = 256;
    const PRODUCT_LEN       = 100;

    // cache
    const BASE_CACHE_KEY    = 'CUSTOM:SLUG:ENTITY';

    /**
     * ordered list of columns to query
     *
     * The arrangement is based on indexing
     */
    const ALLOWED_QUERY_KEYS = [
        self::ID,
        self::SLUG,
        self::DOMAIN,
        self::PRODUCT_ID,
        self::MERCHANT_ID,
    ];

    protected $entity   = 'nocode_custom_url';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::SLUG,
        self::MERCHANT_ID,
        self::DOMAIN,
        self::PRODUCT,
        self::PRODUCT_ID,
        self::META_DATA,
    ];

    protected $casts = [
        self::META_DATA => 'json',
    ];

    protected $defaults = [
        self::META_DATA => [],
    ];

    protected $visible = [
        self::ID,
        self::SLUG,
        self::MERCHANT_ID,
        self::DOMAIN,
        self::PRODUCT,
        self::PRODUCT_ID,
        self::META_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public = [
        self::ID,
        self::SLUG,
        self::MERCHANT_ID,
        self::DOMAIN,
        self::PRODUCT,
        self::PRODUCT_ID,
        self::META_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    /**
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity $entity
     *
     * @return string
     */
    public static function getCacheKey(self $entity): string
    {
        return self::getCacheKeyBySlugAndDomain($entity->getSlug(), $entity->getDomain());
    }

    /**
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity $entity
     *
     * @return void
     */
    public static function clearCache(self $entity)
    {
        Cache::forget(self::getCacheKey($entity));
    }

    /**
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity $entity
     *
     * @return void
     */
    public static function updateCache(self $entity)
    {
        Cache::put(self::getCacheKey($entity), $entity, self::getCacheTTL());
    }

    /**
     * @return int
     */
    public static function getCacheTTL(): int
    {
        return (int) Config::get('app.nocode.cache.custom_url_ttl');
    }

    /**
     * @param string $slug
     * @param string $domain
     *
     * @return string
     */
    public static function getCacheKeyBySlugAndDomain(string $slug, string $domain): string
    {
        $prefix = Config::get('app.nocode.cache.prefix');

        $baseKey = self::BASE_CACHE_KEY;

        return $prefix . ':'
            . $baseKey . ':'
            . $slug . ':'
            . $domain;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productEntity()
    {
        return $this->belongsTo(PaymentLink\Entity::class, self::PRODUCT_ID);
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->getAttribute(self::SLUG);
    }

    /**
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->getAttribute(self::DOMAIN);
    }

    /**
     * @return string|null
     */
    public function getProduct(): ?string
    {
        return $this->getAttribute(self::PRODUCT);
    }

    /**
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->getAttribute(self::PRODUCT_ID);
    }

    /**
     * @return mixed
     */
    public function getMetaData()
    {
        return $this->getAttribute(self::META_DATA);
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public static function determineDomainFromUrl(string $url): string
    {
        $parsed = parse_url($url);

        $host = array_get($parsed, 'host');

        if (empty($host) !== true)
        {
            return $host;
        }

        // the above simpler parsing did not yield a host
        // let's use regex now

        $regex = "/^((?!-)[A-Za-z0-9-]" .
            "{1,63}(?<!-)\\.)" .
            "+[A-Za-z]{2,6}/";

        $matched = preg_match($regex, $url);

        if($matched && filter_var($url, FILTER_VALIDATE_DOMAIN))
        {
            // it's a valid url as per PHP's FILTER_VALIDATE_DOMAIN
            // to be on the safer side using slpit by / and taking the first index value
            // for example: somedomain.com/v1/execute, we need to extract only somedomain.com
            return explode("/", $url)[0];
        }

        throw new BadRequestValidationFailureException("Domain could not be determined.");
    }
}
