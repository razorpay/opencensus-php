<?php

namespace RZP\Models\Workflow\Service\StateMap;

use App;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Services\Mutex;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Services\PayoutService;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\Workflow\Service\EntityMap\Entity as WorkflowEntityMap;
use RZP\Models\Payout\DualWrite\WorkflowEntityMap as WorkflowDualWrite;

class Core extends Base\Core
{
    const PAYOUT                  = Constants\Entity::PAYOUT;

    /** @var PayoutService\Workflow*/
    protected $payoutWorkflowServiceClient;

    /**
     * @var Mutex
     */
    protected $mutex;

    /**
     * BasicAuth entity
     * @var BasicAuth
     */
    protected $auth;

    const PAYOUT_MUTEX_LOCK_TIMEOUT = 180;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->auth = $this->app['basicauth'];

        $this->payoutWorkflowServiceClient = $this->app[PayoutService\Workflow::PAYOUT_SERVICE_WORKFLOW];
    }

    /**
     * @param array $input
     * @return Entity
     */
    public function create(array $input): Entity
    {
        try
        {
            $workflowEntityMap = $this->getWorkflowEntityMap($input[Entity::REQUEST_WORKFLOW_ID]);

            if (($this->auth->isPayoutService() === true) ||
                ($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                            $workflowEntityMap->getMerchantId()) === false))
            {
                return $this->createStateMap($workflowEntityMap, $input);
            }
            else
            {
                return $this->mutex->acquireAndRelease(
                    PayoutConstants::MIGRATION_REDIS_SUFFIX . $workflowEntityMap->getEntityId(),
                    function() use ($workflowEntityMap, $input) {
                        $source = $workflowEntityMap->source()->first();

                        if (($workflowEntityMap->getEntityType() === self::PAYOUT) and
                            (empty($source) === false))
                        {
                            // considering if the source is preloaded in workflow , better to reload
                            /** @var PayoutEntity $source */
                            $source->reload();

                            if (($source->getIsPayoutService() === true) and
                                ($this->auth->isPayoutService() === false))
                            {
                                $response = $this->payoutWorkflowServiceClient->createStateCallbackViaMicroservice($input);

                                $stateMapEntity = new Entity();

                                foreach ($response as $key => $value)
                                {
                                    $stateMapEntity->$key = $value;
                                }

                                return $stateMapEntity;
                            }
                        }

                        return $this->createStateMap($workflowEntityMap, $input);
                    },
                    self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                    PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
            }
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

    /**
     * @param Entity $stateMap
     * @param array $input
     * @return Entity
     */
    public function update(Entity $stateMap, array $input, string $id): Entity
    {
        try
        {
            $workflowEntityMap = $this->getWorkflowEntityMap($input[Entity::REQUEST_WORKFLOW_ID]);

            if (($this->auth->isPayoutService() === true) ||
                ($this->isExperimentEnabled(Merchant\RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING,
                                            $workflowEntityMap->getMerchantId()) === false))
            {
                return $this->updateStateMap($stateMap, $input[Entity::REQUEST_STATUS]);
            }
            else
            {
                return $this->mutex->acquireAndRelease(
                    PayoutConstants::MIGRATION_REDIS_SUFFIX . $workflowEntityMap->getEntityId(),
                    function() use ($workflowEntityMap, $input, $id, $stateMap) {
                        $source = $workflowEntityMap->source()->first();

                        if (($workflowEntityMap->getEntityType() === self::PAYOUT) and
                            (empty($source) === false))
                        {
                            // considering if the source is preloaded in workflow , better to reload
                            /** @var PayoutEntity $source */
                            $source->reload();

                            if (($source->getIsPayoutService() === true) and
                                ($this->auth->isPayoutService() === false))
                            {
                                $response = $this->payoutWorkflowServiceClient->updateStateCallbackViaMicroservice($id, $input);

                                $stateMapEntity = new Entity();

                                foreach ($response as $key => $value)
                                {
                                    $stateMapEntity->$key = $value;
                                }

                                return $stateMapEntity;
                            }
                        }

                        return $this->updateStateMap($stateMap, $input[Entity::REQUEST_STATUS]);
                    },
                    self::PAYOUT_MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
                    PayoutConstants::MIGRATION_MUTEX_RETRY_COUNT);
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::WORKFLOW_STATE_MAP_UPDATION_FAILED,
                [
                    'input' => $input,
                ]);

            throw $exception;
        }
    }

    /**
     * @param $workflowId
     *
     * @return WorkflowEntityMap
     * @throws Exception\BadRequestException
     */
    private function getWorkflowEntityMap($workflowId): WorkflowEntityMap
    {
        /** @var WorkflowEntityMap $config */
        $workflowEntityMap = $this->repo->workflow_entity_map->getByWorkflowIdByFirst($workflowId);

        if (empty($workflowEntityMap) === true)
        {
            $workflowEntityMap = $this->getPayoutServiceWorkflowEntityMap($workflowId);
        }

        return $workflowEntityMap;
    }

    protected function getPayoutServiceWorkflowEntityMap(string $workflowId)
    {
        /**
         * In case of payout service payout we might not have entry in API DB for payout_entity_map.
         * Hence we try to get that record from PS DB and store in API DB.
         */

        $psWorkflowEntityMap = (new WorkflowDualWrite())->
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
     * @param WorkflowEntityMap $workflowEntityMap
     * @param array             $input
     *
     * @return Entity
     */
    private function createStateMap(WorkflowEntityMap $workflowEntityMap, array $input): Entity
    {
        /** @var Merchant\Entity $config */
        $merchant = $this->repo->merchant->findOrFailPublic($workflowEntityMap->getMerchantId());

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

    /**
     * @param Entity $stateMap
     * @param string $status
     *
     * @return Entity
     */
    private function updateStateMap(Entity $stateMap, string $status): Entity
    {
        $stateMap->setStatus($status);

        $this->repo->saveOrFail($stateMap);

        return $stateMap;
    }

    protected function isExperimentEnabled($experiment,string $merchantId)
    {
        $app = $this->app;

        if(empty($merchantId) === false)
        {
            $variant = $app['razorx']->getTreatment($merchantId,
                                                    $experiment, $app['basicauth']->getMode() ?? Mode::LIVE);

            return ($variant === 'on');
        }

        return false;
    }

}
