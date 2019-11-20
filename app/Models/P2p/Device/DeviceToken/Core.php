<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        // Check if there is already a device token created for the device and handle
        $existing = $this->repo->fetchVerifiedTokens();

        // There should never be more than one verified token for the device and handle
        if ($existing->count() > 0)
        {
//            if ($existing->count() > 1)
//            {
//                // Throw Exception or alert
//            }
            // Eventually expire all token existing tokens
            $existing->each(function($deviceToken)
            {
                $deviceToken->setStatusExpired();

                $this->repo->saveOrFail($deviceToken);
            });
        }

        $deviceToken = $this->repo->newP2pEntity();

        $deviceToken->build($input);

        $this->repo->saveOrFail($deviceToken);

        return $deviceToken;
    }

    public function update(Entity $deviceToken, $input): Entity
    {
        $deviceToken->mergeGatewayData(array_get($input, Entity::GATEWAY_DATA, []));

        $deviceToken->generateRefreshedAt();

        $this->repo->saveOrFail($deviceToken);

        return $deviceToken;
    }

    public function expire(): Entity
    {
        $deviceToken = $this->context()->getDeviceToken();

        $deviceToken->setStatusExpired();

        $this->repo->saveOrFail($deviceToken);

        return $deviceToken;
    }

    public function delete()
    {
        return $this->repo->newP2pQuery()->delete();
    }

    public function deregister()
    {
        $this->delete();
    }
}
