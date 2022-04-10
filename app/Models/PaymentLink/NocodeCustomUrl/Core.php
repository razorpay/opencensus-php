<?php

namespace RZP\Models\PaymentLink\NocodeCustomUrl;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    const ENTITY_DUPLICATE_ERROR    = "Slug is already taken.";

    /**
     * @param array                          $input
     * @param \RZP\Models\Merchant\Entity    $merchant
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function upsert(
        array $input,
        Merchant\Entity $merchant,
        PaymentLink\Entity $paymentLink
    ): Entity
    {
        $this->repo->assertTransactionActive();

        $this->preProcessInput($input, $paymentLink);

        // get existing active entity for the paymentlink
        $existingEntity = $this->getExistingLinkedEntity($paymentLink->getId(), $merchant->getId());

        $uniqueEntity = $this->getUniqueEntity($input[Entity::SLUG], $input[Entity::DOMAIN]);

        $this->integrityCheck($uniqueEntity, $paymentLink, $merchant);

        if ($uniqueEntity === null && $existingEntity === null)
        {
            return $this->create($input, $merchant, $paymentLink);
        }

        if ($existingEntity !== null
            && $uniqueEntity === null
            && ($existingEntity->getSlug() !== $input[Entity::SLUG]
                || $existingEntity->getDomain() !== $input[Entity::DOMAIN]))
        {
            $existingEntity->deleteOrFail();

            Entity::clearCache($existingEntity);

            $this->trace->count(PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_BUILD_COUNT);

            return $this->create($input, $merchant, $paymentLink);
        }

        if ($existingEntity !== null
            && ($existingEntity->getSlug() !== $input[Entity::SLUG]
                || $existingEntity->getDomain() !== $input[Entity::DOMAIN]))
        {
            $existingEntity->deleteOrFail();

            Entity::clearCache($existingEntity);

            $this->trace->count(PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_BUILD_COUNT);
        }

        return $this->updateAndActivateIfRequired($input, $uniqueEntity, $paymentLink, $merchant);
    }

    /**
     * @param string $slug
     * @param string $domain
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getForHosted(string $slug, string $domain): ?Entity
    {
        $entity = $this->getUniqueEntity($slug, $domain);

        return $entity ?? null;
    }

    /**
     * @param string $productId
     * @param string $merchantId
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getExistingLinkedEntity(string $productId, string $merchantId): ? Entity
    {
        return $this
            ->repo
            ->nocode_custom_url
            ->findByAttributes([
                Entity::PRODUCT_ID  => $productId,
                Entity::MERCHANT_ID => $merchantId,
            ]);
    }

    /**
     * @param string $slug
     * @param string $domain
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getUniqueEntity(string $slug, string $domain): ?Entity
    {
        $cacheKey = Entity::getCacheKeyBySlugAndDomain($slug, $domain);

        $fromCache = true;

        $entity = $this->cache->get($cacheKey);

        if (empty($entity) === true)
        {
            $entity = $this
                ->repo
                ->nocode_custom_url
                ->findByAttributes([
                    Entity::SLUG    => $slug,
                    Entity::DOMAIN  => $domain,
                ], true);

            if (empty($entity) === false)
            {
                Entity::updateCache($entity);

                $this->trace->count(PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_BUILD_COUNT);
            }

            $fromCache = false;
        }

        $metric = $fromCache
            ? PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_HIT_COUNT
            : PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_MISS_COUNT;

        $this->trace->count($metric);

        return $entity;
    }

    /**
     * @param array                          $input
     * @param \RZP\Models\Merchant\Entity    $merchant
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity
     */
    private function create(
        array $input,
        Merchant\Entity $merchant,
        PaymentLink\Entity $paymentLink
    ): Entity
    {
        $entity = (new Entity)->generateId();

        $entity->fill($input);

        $this->processUpsert($entity, $paymentLink, $merchant);

        Entity::clearCache($entity);

        $this->trace->count(PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_BUILD_COUNT);

        return $entity;
    }

    /**
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity $entity
     * @param \RZP\Models\PaymentLink\Entity                 $paymentLink
     * @param \RZP\Models\Merchant\Entity                    $merchant
     *
     * @return void
     */
    private function processUpsert(
        Entity & $entity,
        PaymentLink\Entity $paymentLink,
        Merchant\Entity $merchant
    ): void
    {
        $entity->merchant()->associate($merchant);

        $entity->productEntity()->associate($paymentLink);

        $this->repo->saveOrFail($entity);
    }

    /**
     * @param array                          $input
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     *
     * @return void
     */
    private function preProcessInput(array & $input, PaymentLink\Entity $paymentLink): void
    {
        $input[Entity::PRODUCT] = $paymentLink->getViewType();

        (new Entity)->validateInput('upsert', $input);
    }

    /**
     * @param array                                          $input
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity $entity
     * @param \RZP\Models\PaymentLink\Entity                 $paymentLink
     * @param \RZP\Models\Merchant\Entity                    $merchant
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity
     */
    private function updateAndActivateIfRequired(
        array $input,
        Entity $entity,
        PaymentLink\Entity $paymentLink,
        Merchant\Entity $merchant
    ): Entity
    {
        $entity->fill($input);

        $this->processUpsert($entity, $paymentLink, $merchant);

        if ($entity->trashed() === true)
        {
            $entity->restore();
        }

        Entity::clearCache($entity);

        $this->trace->count(PaymentLink\Metric::NOCODE_CUSTOM_URL_CACHE_BUILD_COUNT);

        return $entity;
    }

    /**
     * This method will remaing only for the initial phase.
     * Once all calls are shifted from gimli to this module,
     * we will remove this method.
     *
     * @param array                          $input
     * @param \RZP\Models\PaymentLink\Entity $paymentPage
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function insertForHostedFlow(array $input, PaymentLink\Entity $paymentPage)
    {
        $this->preProcessInput($input, $paymentPage);

        $existingEntity = $this->getExistingLinkedEntity($paymentPage->getId(), $paymentPage->merchant->getId());

        $entity = $this->create($input, $paymentPage->merchant, $paymentPage);

        $entitySlug = $paymentPage->getSlugFromShortUrl();

        if ((empty($existingEntity) === false && $existingEntity->getSlug() !== $input[Entity::SLUG])
            || ($entitySlug !== $input[Entity::SLUG]))
        {
            $entity->deleteOrFail();

            Entity::clearCache($entity);
        }
    }

    /**
     * @param \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null $uniqueEntity
     * @param \RZP\Models\PaymentLink\Entity                      $paymentLink
     * @param \RZP\Models\Merchant\Entity                         $merchant
     *
     * @return void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    private function integrityCheck(?Entity $uniqueEntity, PaymentLink\Entity $paymentLink, Merchant\Entity $merchant)
    {
        if ($uniqueEntity !== null && $uniqueEntity->getMerchantId() !== $merchant->getMerchantId())
        {
            throw new BadRequestValidationFailureException(self::ENTITY_DUPLICATE_ERROR);
        }

        if ($uniqueEntity !== null && $uniqueEntity->getProductId() !== $paymentLink->getId() && ! $uniqueEntity->trashed())
        {
            throw new BadRequestValidationFailureException(self::ENTITY_DUPLICATE_ERROR);
        }
    }

    /**
     * @param string                         $slug
     * @param string                         $domain
     * @param \RZP\Models\PaymentLink\Entity $paymentLink
     * @param \RZP\Models\Merchant\Entity    $merchant
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function validateAndDetermineShouldCreate(
        string $slug,
        string $domain,
        PaymentLink\Entity $paymentLink,
        Merchant\Entity $merchant
    ): bool
    {
        $uniqueEntity = $this->getUniqueEntity($slug, $domain);

        $this->integrityCheck($uniqueEntity, $paymentLink, $merchant);

        return $uniqueEntity === null;
    }
}
