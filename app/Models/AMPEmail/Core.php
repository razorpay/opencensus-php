<?php

namespace RZP\Models\AMPEmail;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\AMPEmail\Entity as AMPEmailEntity;

class Core extends Base\Core
{
    const MUTEX_PREFIX = 'api_amp_email_';

    /**
     * @param AMPEmailEntity $entity
     * @param array          $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function edit(AMPEmailEntity $entity, array $input)
    {
        $this->trace->info(TraceCode::EDIT_AMP_EMAIL,
                           [
                               AMPEmailEntity::ENTITY_ID => $entity->getEntityId(),
                               "input"                   => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($entity, $input) {

            $mutexResource = self::MUTEX_PREFIX . $entity->getId();

            return $this->app[Constants::API_MUTEX]->acquireAndRelease
            ($mutexResource,

                function() use ($entity, $input) {

                    if (empty($input[AMPEmailEntity::METADATA]) === false)
                    {
                        $input[AMPEmailEntity::METADATA] = $this->mergeJson($entity->getMetadata(), $input[AMPEmailEntity::METADATA]);
                    }

                    $entity->edit($input, Constants::EDIT);

                    $this->repo->amp_email->saveOrFail($entity);

                    return $entity;
                },
             Constants::MUTEX_LOCK_TIMEOUT,
             ErrorCode::BAD_REQUEST_EDIT_OPERATION_IN_PROGRESS,
             Constants::MUTEX_RETRY_COUNT);

        });
    }


    /**
     * @param                $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function create($input)
    {
        $this->trace->info(TraceCode::CREATE_AMP_EMAIL,
                           [
                               Constants::INPUT => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($input) {

            $ampEmail = new AMPEmailEntity();

            $ampEmail->generateId();

            $ampEmail->build($input);

            $this->repo->amp_email->saveOrFail($ampEmail);

            return $ampEmail;
        });
    }


    protected function mergeJson($existingDetails, $newDetails)
    {
        if (empty($newDetails) === false)
        {
            foreach ($newDetails as $key => $value)
            {
                $existingDetails[$key] = $value;
            }
        }

        return $existingDetails;
    }
}
