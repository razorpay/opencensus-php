<?php

namespace RZP\Models\Merchant\AccessMap;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create(
        Merchant\Entity $merchant,
        array $input = null,
        Base\PublicEntity $entity = null)
    {
        $merchantMapping = (new Entity)->build($input);

        $merchantMapping->generateId();

        $merchantMapping->merchant()->associate($merchant);

        if (empty($entity) === false)
        {
            $merchantMapping->entity()->associate($entity);
        }

        $this->repo->saveOrFail($merchantMapping);

        return $merchantMapping;
    }

    /**
     * Here we add the mapping between merchant and application  We
     * maintain this mapping so that we can run flows like webhook calls based
     * on this relation. This can be otherwise fetched from auth-service but
     * since it is read-heavy, we maintain it in the access_map table too.
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     *
     * @return Entity
     */
    public function addMappingForOAuthApp(Merchant\Entity $merchant, array $input): Entity
    {
        $merchantId = $merchant->getId();

        $accessMapping = $this->repo
                              ->merchant_access_map
                              ->findMerchantAccessMapOnEntityId(
                                  $merchantId,
                                  $input[Entity::APPLICATION_ID],
                                  Entity::APPLICATION
                              );

        if ($accessMapping !== null)
        {
            return $accessMapping;
        }

        $data = [
            Entity::ENTITY_TYPE => Entity::APPLICATION,
            Entity::ENTITY_ID   => $input[Entity::APPLICATION_ID],
        ];

        return $this->create($merchant, $data);
    }

    /**
     * Here we delete the mapping between merchant and application. We
     * delete this mapping when the last of the access tokens given to this
     * app for the given merchant is revoked. This check for number of tokens
     * is handled by the auth-service.
     *
     * @param Merchant\Entity $merchant
     * @param string          $appId
     */
    public function deleteMappingForOAuthApp(Merchant\Entity $merchant, string $appId)
    {
        $merchantId = $merchant->getId();

        $mapping = $this->repo
                        ->merchant_access_map
                        ->findMerchantAccessMapOnEntityId(
                            $merchantId,
                            $appId,
                            Entity::APPLICATION
                        );

        if (empty($mapping) === false)
        {
            return $this->repo->merchant_access_map->deleteOrFail($mapping);
        }
    }
}
