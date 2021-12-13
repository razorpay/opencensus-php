<?php

namespace RZP\Models\Base\Traits;

use App;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;

trait ExternalCore
{
    private function saveExternalEntity($entity)
    {
        $class = Entity::getexternalRepoSingleton($entity->getEntity());

        try
        {
            $id = $entity->getId();

            $merchantId = '';

            if ($entity->merchant !== null)
            {
                $merchantId = $entity->merchant->getId();
            }

            $entity = $class->save($entity->getEntityName(), $id, $merchantId, $entity->toArray());

            $entity = $this->fetchExternalEntity($id, '', []);

            return $entity;
        }
        catch (\Throwable $e)
        {
            App::getFacadeRoot()['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_SAVE_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

    }

}
