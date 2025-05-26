<?php


namespace RZP\Models\Workflow\Observer;

use App;
use RZP\Constants\Metric;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;

class MerchantReKYCStatusObserver implements WorkflowObserverInterface
{
    protected $entityId;

    protected $app;

    protected $merchant;

    /**
     * @var mixed
     */
    protected $actionId;

    /**
     * @var mixed
     */
    protected $permissionName;
    protected $oldReKYCStatus;
    protected $newReKYCStatus;

    public function __construct($input)
    {
        $this->app = App::getFacadeRoot();

        $this->entityId = $input[DifferEntity::ENTITY_ID];

        if (is_null($input[DifferEntity::DIFF]) === false) {
            $this->oldReKYCStatus = $input[DifferEntity::DIFF][DifferEntity::OLD][MerchantDetailEntity::SELF_SERVE_REKYC_STATUS] ?? null;

            $this->newReKYCStatus = $input[DifferEntity::DIFF][DifferEntity::NEW][MerchantDetailEntity::SELF_SERVE_REKYC_STATUS] ?? null;
        }

        if (key_exists(DifferEntity::PERMISSION, $input) === true) // permission at times might not be present
        {
            $this->permissionName = $input[DifferEntity::PERMISSION];
        }

    }

    public function onApprove(array $observerData)
    {
        $this -> onExecute($observerData);
    }

    public function onClose(array $observerData)
    {
        $this -> onReject($observerData);
    }

    public function onReject(array $observerData)
    {
        $this->app['trace']->info(TraceCode::MERCHANT_REKYC_STATUS_OBSERVER, [
            'on_reject' => 'on_reject observer invoked.',
            'permission_name' => $this->permissionName
        ]);

        $cmmaCaseEventData = [

            Constants::WORKFLOW_ACTION_ID => 'w_action_' . $observerData[DifferEntity::ACTION_ID],

            Constants::PERMISSION_NAME => $this->permissionName,

            Constants::STATUS => Status::REJECTED,

            Constants::AGENT_Id => optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,

            Constants::AGENT_NAME => optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,

            DifferEntity::ENTITY_ID => $this->entityId,

            DifferEntity::ENTITY_NAME => Constants::MERCHANT,

            Constants::EVENT_TYPE => Constants::CMMA_EVENT_WORKFLOW_STATUS_CHANGE,

            Constants::CMMA_CASE_TYPE => Constants::CMMA_REKYC_CASE_TYPE,
        ];

        $cmmaCaseEventTopic = $this->app['config']->get('kafka_consumer.cmma_case_events_topic');

        $this->app['trace']->info(TraceCode::CMMA_CASE_EVENT_KAFKA_PUBLISH, [
                'data' => $cmmaCaseEventData,
                'topic' => $cmmaCaseEventTopic,
                'merchant_id' => $this->entityId,
            ]
        );

        (new KafkaProducer($cmmaCaseEventTopic, stringify($cmmaCaseEventData)))->Produce();
    }

    public function onCreate(array $observerData)
    {
        try
        {
            $this->app['trace']->info(TraceCode::MERCHANT_REKYC_STATUS_OBSERVER, [
                'on_create'       => 'on_create observer invoked.',
                'permission_name' => $this->permissionName
            ]);

            $cmmaCaseEventData = [

                Constants::WORKFLOW_ACTION_ID => 'w_action_' . $observerData[DifferEntity::ACTION_ID],

                Constants::PERMISSION_NAME => $this->permissionName,

                Constants::STATUS => Constants::OPEN,

                Constants::AGENT_Id => optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,

                Constants::AGENT_NAME => optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,

                DifferEntity::ENTITY_ID => $this->entityId,

                DifferEntity::ENTITY_NAME => Constants::MERCHANT,

                Constants::EVENT_TYPE => Constants::CMMA_EVENT_WORKFLOW_STATUS_CHANGE,

                Constants::CMMA_CASE_TYPE => Constants::CMMA_REKYC_CASE_TYPE,
            ];

            $cmmaCaseEventTopic = $this->app['config']->get('kafka_consumer.cmma_case_events_topic');

            $this->app['trace']->info(TraceCode::CMMA_CASE_EVENT_KAFKA_PUBLISH, [
                    'data' => $cmmaCaseEventData,
                    'topic' => $cmmaCaseEventTopic,
                    'merchant_id' => $this->entityId,
                ]
            );

            (new KafkaProducer($cmmaCaseEventTopic, stringify($cmmaCaseEventData)))->Produce();
        }
        catch (\Throwable $exception)
        {
            $this->app['trace']->traceException($exception);

            $this->app['trace']->count(
                Metric::MERCHANT_REKYC_STATUS_OBSERVER_CREATE_FAILED,
                [
                    'merchant_id' => $this->entityId,
                    'error' => $exception->getMessage(),
                    'permission_name' => $this->permissionName
                ]
            );
        }
    }

    public function onExecute(array $observerData)
    {
        $this->app['trace']->info(TraceCode::MERCHANT_REKYC_STATUS_OBSERVER, [
            'on_execute' => 'on_execute observer invoked.',
            'permission_name' => $this->permissionName
        ]);

        $cmmaCaseEventData = [

            Constants::WORKFLOW_ACTION_ID => 'w_action_' . $observerData[DifferEntity::ACTION_ID],

            Constants::PERMISSION_NAME => $this->permissionName,

            Constants::STATUS => Constants::EXECUTED,

            Constants::AGENT_Id => optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,

            Constants::AGENT_NAME => optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,

            DifferEntity::ENTITY_ID => $this->entityId,

            DifferEntity::ENTITY_NAME => Constants::MERCHANT,

            Constants::EVENT_TYPE => Constants::CMMA_EVENT_WORKFLOW_STATUS_CHANGE,

            Constants::CMMA_CASE_TYPE => Constants::CMMA_REKYC_CASE_TYPE,
        ];

        $cmmaCaseEventTopic = $this->app['config']->get('kafka_consumer.cmma_case_events_topic');

        $this->app['trace']->info(TraceCode::CMMA_CASE_EVENT_KAFKA_PUBLISH, [
                'data' => $cmmaCaseEventData,
                'topic' => $cmmaCaseEventTopic,
                'merchant_id' => $this->entityId,
            ]
        );

        (new KafkaProducer($cmmaCaseEventTopic, stringify($cmmaCaseEventData)))->Produce();
    }
}
