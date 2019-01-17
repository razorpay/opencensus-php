<?php

namespace RZP\Models\Origin;

use Razorpay\OAuth\Application as OAuthApp;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(Base\PublicEntity $entity, $originEntity, array $input = []): Entity
    {
        $origin = new Entity;

        $origin->build($input);

        $origin->origin()->associate($originEntity);

        $origin->entity()->associate($entity);

        $this->repo->saveOrFail($origin);

        return $origin;
    }

    public function setOriginDetails(Base\PublicEntity $entity)
    {
        try
        {
            list($originType, $originId) = app('basicauth')->getOriginDetailsFromAuth();

            $originEntity = $this->fetchOriginEntity($originType, $originId);

            //
            // Non null origin details are returned only for public auth, partner auth and bearer auth.
            // Return if null values are returned.
            //
            if (empty($originEntity) === true)
            {
                return;
            }

            $origin = $this->create($entity, $originEntity);

            return $origin;
        }
        catch (\Throwable $e)
        {
            // The payment should not be blocked even if the origin cannot be created. Log an error and proceed.
            $this->trace->error(TraceCode::ORIGIN_SET_FAILED, [
                'message'           => $e->getMessage(),
                Entity::ENTITY_TYPE => $entity->getEntity(),
                Entity::ENTITY_ID   => $entity->getId(),
            ]);

            return;
        }
    }

    protected function fetchOriginEntity(string $originType, string $originId)
    {
        if (empty($originId) === true)
        {
            return null;
        }

        $originEntity = null;

        switch ($originType)
        {
            case Constants::MERCHANT:
                $originEntity = $this->repo->merchant->find($originId);
                break;

            case Constants::APPLICATION:
                $originEntity = (new OAuthApp\Repository)->find($originId);
                break;

            default:
                break;
        }

        return $originEntity;
    }
}
