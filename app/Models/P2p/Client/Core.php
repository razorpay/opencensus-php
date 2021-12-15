<?php

namespace RZP\Models\P2p\Client;

use RZP\Models\P2p\Base;
use RZP\Models\P2p\Vpa\Handle;
use RZP\Models\P2p\Client\Repository;
use RZP\Models\P2p\Client\Validator;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(Handle\Entity $handle, array $input): Entity
    {
        // TODO: Use $this->build , remove this
        $client = $this->repo->getEntityObject();

        $client->build($input);

        $this->repo->saveOrFail($client);

        $handle->setClient($client);

        return $client;
    }

    private function mergeInputWithExistingClient(Entity $client, array $input): array
    {
        // Make sure getter exists for the added attribute
        $attributesToMerge = [
            Entity::SECRETS,
            Entity::CONFIG,
            Entity::GATEWAY_DATA
        ];

        foreach ($attributesToMerge as $attribute)
        {
            $getter = 'get' . studly_case($attribute);

            if (is_array($input[$attribute] ?? null) === true)
            {
                $mergedAttribute = array_merge($client->{$getter}()->toArray(), $input[$attribute]);

                $input[$attribute] = $mergedAttribute;
            }
        }

        return $input;
    }

    public function update(Handle\Entity $handle, Entity $client, array $input): Entity
    {
        $input = $this->mergeInputWithExistingClient($client, $input);

        $client->edit($input);

        $this->repo->saveOrFail($client);

        $handle->setClient($client);

        return $client;
    }

    /**
     * Creates the entity or updates the existing client with a unique
     * set of handle, client id, and client type.
     * @param array $input
     * @return Entity
     */
    public function createOrUpdate(Handle\Entity $handle, array $input): Entity
    {
        $code       = array_get($input, Entity::HANDLE);
        $clientId   = array_get($input, Entity::CLIENT_ID);
        $clientType = array_get($input, Entity::CLIENT_TYPE);

        $client = $this->repo->findByClientAndHandle($clientId, $clientType, $code);

        if ($client === null)
        {
            return $this->create($handle, $input);
        }
        else
        {
            return $this->update($handle, $client, $input);
        }
    }
}
