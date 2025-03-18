<?php

namespace RZP\Models\Base\Traits;

use App;
use Neves\Events\TransactionalClosureEvent;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Jobs\Transfers\PaymentUpdate;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

trait ExternalCore
{
    private function saveExternalEntityCommon($entity, $fetchAfterSave  = true)
    {
        if ($entity->getEntity() === Entity::PAYMENT && $entity->hasTransfer())
        {
            \Event::dispatch(new TransactionalClosureEvent(function () use ($entity)
            {
                PaymentUpdate::dispatchNow($entity);
            }));

            return;
        }

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

            if ($fetchAfterSave) {
                $entity = $this->fetchExternalEntity($id, '', []);
            }

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

    private function saveExternalEntity($entity)
    {
        return $this->saveExternalEntityCommon($entity, true);
    }

    private function saveExternalEntityWithoutFetch($entity)
    {
        return $this->saveExternalEntityCommon($entity, false);
    }

}

