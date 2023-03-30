<?php

namespace RZP\Models\Workflow\Service\StateMap;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\DualWrite\WorkflowEntityMap;

class Core extends Base\Core
{
    /**
     * @param array $input
     * @return Entity
     */
    public function create(array $input): Entity
    {
        try
        {
            $workflowId = $input[Entity::REQUEST_WORKFLOW_ID];

            /** @var Entity $config */
            $workflowEntityMap = $this->repo->workflow_entity_map->getByWorkflowIdByFirst($workflowId);

            if (empty($workflowEntityMap) === true)
            {
                $workflowEntityMap = $this->getPayoutServiceWorkflowEntityMap($workflowId);
            }

            $merchantId = $workflowEntityMap->getMerchantId();

            /** @var Merchant\Entity $config */
            $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $attributes = [
                Entity::WORKFLOW_ID      => $input[Entity::REQUEST_WORKFLOW_ID],
                Entity::STATE_ID         => $input[Entity::REQUEST_STATE_ID],
                Entity::STATE_NAME       => $input[Entity::REQUEST_STATE_NAME],
                Entity::TYPE             => $input[Entity::REQUEST_TYPE],
                Entity::GROUP_NAME       => $input[Entity::REQUEST_GROUP_NAME],
                Entity::STATUS           => $input[Entity::REQUEST_STATUS],
                Entity::ACTOR_TYPE_KEY   => $input[Entity::REQUEST_RULES][Entity::REQUEST_ACTOR_PROPERTY_KEY],
                Entity::ACTOR_TYPE_VALUE => $input[Entity::REQUEST_RULES][Entity::REQUEST_ACTOR_PROPERTY_VALUE],
            ];

            $stateMapEntity = (new Entity)->build($attributes);

            $stateMapEntity->merchant()->associate($merchant);

            $stateMapEntity->org()->associate($merchant->org);

            $this->repo->saveOrFail($stateMapEntity);

            return $stateMapEntity;
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::WORKFLOW_STATE_MAP_CREATION_FAILED,
                [
                    'input' => $input,
                ]);

            throw $exception;
        }
    }

    protected function getPayoutServiceWorkflowEntityMap(string $workflowId)
    {
        /**
         * In case of payout service payout we might not have entry in API DB for payout_entity_map.
         * Hence we try to get that record from PS DB and store in API DB.
         */

        $psWorkflowEntityMap = (new WorkflowEntityMap)->
        getAPIWorkflowEntityMapFromPayoutServiceByWorkflowId($workflowId);

        $this->trace->info(
            TraceCode::WORKFLOW_STATE_MAP_FROM_PAYOUTS_MICROSERVICE,
            [
                'input'               => $workflowId,
                'workflow_entity_map' => $psWorkflowEntityMap,
            ]);

        if (empty($psWorkflowEntityMap) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $workflowId);
        }

        $this->repo->workflow_entity_map->saveOrFail($psWorkflowEntityMap);

        return $psWorkflowEntityMap;
    }

    /**
     * @param Entity $stateMap
     * @param array $input
     * @return Entity
     */
    public function update(Entity $stateMap, array $input): Entity
    {
        $stateMap->setStatus($input[Entity::REQUEST_STATUS]);

        $this->repo->saveOrFail($stateMap);

        return $stateMap;
    }
}
