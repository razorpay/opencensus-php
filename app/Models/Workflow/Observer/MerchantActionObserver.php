<?php


namespace RZP\Models\Workflow\Observer;

use App;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Action;
use RZP\Services\KafkaProducer;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;

class MerchantActionObserver implements WorkflowObserverInterface
{

    protected $workflowService;

    protected $merchantAction;

    protected $entityId;

    protected $repo;

    protected $fdService;

    protected $permissionName;

    protected $entityName;

    protected $app;

    public function __construct($input)
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->repo = $app['repo'];

        $this->fdService        = new FDService();

        $this->merchantAction   = $input[Entity::PAYLOAD]['action'] ?? "";

        $this->entityId         = $input[Entity::ENTITY_ID];

        $this->entityName       = $input[Entity::ENTITY_NAME];

        $this->permissionName   = $input[DifferEntity::PERMISSION];
    }

    public function onApprove(array $observerData)
    {
        $merchantId = $this->getMerchantId();

        if (key_exists(FDConstants::TICKET_ID, $observerData) and
            key_exists(FDConstants::FD_INSTANCE, $observerData))
        {
            $fdInstance = $observerData[FDConstants::FD_INSTANCE];

            $ticket_id = $observerData[FDConstants::TICKET_ID];

            $this->fdService->postTicketReplyOnAgentBehalf($ticket_id,
                implode("<br><br>",$this->getTicketReplyContent(Constants::APPROVE, $merchantId)), $fdInstance,
                $merchantId);

            $this->fdService->resolveAndAddAutomatedResolvedTagToTicket($observerData[FDConstants::FD_INSTANCE], $observerData[FDConstants::TICKET_ID] );
        }
    }

    public function onClose(array $observerData)
    {
        // TODO: Implement onClose() method.
    }

    public function onReject(array $observerData)
    {
        if( $this->permissionName === PermissionName::EDIT_MERCHANT_SUSPEND)
        {
            if ($this->isMetroMigrateOutExperimentEnabledForCmmaEvents($this->entityId) === true)
            {
                $cmmaCaseEventData = [
                    Constants::WORKFLOW_ACTION_ID =>  'w_action_' . $observerData[DifferEntity::ACTION_ID],
                    Constants::PERMISSION_NAME    => $this->permissionName,
                    Constants::STATUS             => Status::REJECTED,
                    Constants::AGENT_Id           =>  optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,
                    Constants::AGENT_NAME         =>  optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,
                    DifferEntity::ENTITY_ID       => $this->entityId,
                    DifferEntity::ENTITY_NAME     => Constants::MERCHANT,
                    Constants::EVENT_TYPE         => Constants::CMMA_EVENT_WORKFLOW_STATUS_CHANGE,
                    Constants::CMMA_CASE_TYPE     => Constants::CMMA_ACTIVATION_CASE_TYPE,
                ];

                $cmmaCaseEventTopic = env(Constants::CMMA_CASE_EVENTS_KAFKA_TOPIC_ENV_VARIBLE_KEY);

                $this->app['trace']->info(TraceCode::CMMA_CASE_EVENT_KAFKA_PUBLISH, [
                        'data'        => $cmmaCaseEventData,
                        'topic'       => $cmmaCaseEventTopic,
                        'merchant_id' => $this->entityId,
                    ]
                );

                (new KafkaProducer($cmmaCaseEventTopic, stringify($cmmaCaseEventData)))->Produce();
            }
            else
            {
                $data = [
                    DifferEntity::ENTITY_ID       => $this->entityId,
                    DifferEntity::ENTITY_NAME     => $this->entityName,
                    Constants::WORKFLOW_ACTION_ID => 'w_action_' . $observerData[DifferEntity::ACTION_ID],
                    Constants::PERMISSION_NAME    => $this->permissionName,
                    Constants::STATUS             => Status::REJECTED,
                    Constants::AGENT_Id           => optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,
                    Constants::AGENT_NAME         => optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,
                ];

                $this->publishToMetroTopic($data, Constants::CMMA_WORKFLOW_METRO_TOPIC);
            }
        }
    }

    public function onCreate(array $observerData)
    {
        // TODO: Implement onCreate() method.
    }

    public function onExecute(array $observerData)
    {
        if($this->permissionName === PermissionName::EDIT_MERCHANT_SUSPEND)
        {
            if ($this->isMetroMigrateOutExperimentEnabledForCmmaEvents($this->entityId) === true)
            {
                $cmmaCaseEventData = [
                    Constants::WORKFLOW_ACTION_ID =>  'w_action_' . $observerData[DifferEntity::ACTION_ID],
                    Constants::PERMISSION_NAME    => $this->permissionName,
                    Constants::STATUS             => Constants::EXECUTED,
                    Constants::AGENT_Id           =>  optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,
                    Constants::AGENT_NAME         =>  optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,
                    DifferEntity::ENTITY_ID       => $this->entityId,
                    DifferEntity::ENTITY_NAME     => Constants::MERCHANT,
                    Constants::EVENT_TYPE         => Constants::CMMA_EVENT_WORKFLOW_STATUS_CHANGE,
                    Constants::CMMA_CASE_TYPE     => Constants::CMMA_ACTIVATION_CASE_TYPE,
                ];

                $cmmaCaseEventTopic = env(Constants::CMMA_CASE_EVENTS_KAFKA_TOPIC_ENV_VARIBLE_KEY);

                $this->app['trace']->info(TraceCode::CMMA_CASE_EVENT_KAFKA_PUBLISH, [
                        'data'        => $cmmaCaseEventData,
                        'topic'       => $cmmaCaseEventTopic,
                        'merchant_id' => $this->entityId,
                    ]
                );

                (new KafkaProducer($cmmaCaseEventTopic, stringify($cmmaCaseEventData)))->Produce();
            }
            else
            {
                $data = [
                    DifferEntity::ENTITY_ID       => $this->entityId,
                    DifferEntity::ENTITY_NAME     => $this->entityName,
                    Constants::WORKFLOW_ACTION_ID => 'w_action_' . $observerData[DifferEntity::ACTION_ID],
                    Constants::PERMISSION_NAME    => $this->permissionName,
                    Constants::STATUS             => Constants::EXECUTED,
                    Constants::AGENT_Id           => optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT,
                    Constants::AGENT_NAME         => optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT,
                ];

                $this->publishToMetroTopic($data, Constants::CMMA_WORKFLOW_METRO_TOPIC);
            }
        }
    }

    public function getMerchantId()
    {
        return $this->entityId;
    }

    public function getTicketReplyContent(string $workflowAction, string $merchantId) : array
    {
        $merchantName = $this->repo->merchant->findOrFailPublic($merchantId)->getName() ?? "";

        if ($this->merchantAction === Action::RELEASE_FUNDS)
        {
            if ($workflowAction === Constants::APPROVE)
            {

                return [
                    "Hi {$merchantName},",
                    "We have processed the releasing of funds for your account with our banking partners. You will receive settlements as per the said settlement cycle. We look forward to transacting with you soon! ",
                    "Please note that settlements will not be processed to your account on bank holidays. ",
                    "Also, we'd love to hear from you! You can leave your feedback through our satisfaction survey that will reach your inbox soon! ",
                    "We have enhanced our support options, please visit our Support page for more details: https://razorpay.com/support.",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        if ($this->merchantAction === Action::HOLD_FUNDS)
        {
            if ($workflowAction === Constants::APPROVE)
            {
                return [
                    "Hi {$merchantName},",
                    "Thank you for raising a request with us.",
                    "We would like to inform you that we have successfully held the funds for your account as per your request.",
                    "Do reach out to us once you wish to have the funds released again.",
                    "We have enhanced our support options, please visit our Support page for more details: https://razorpay.com/support.",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        return array();
    }

    public function publishToMetroTopic($data, $topic) {
        // publish message on the metro topic business-banking-enabled

        $this->app['trace']->info(
            TraceCode::MERCHANT_ACTION_OBSERVER_METRO_PUBLISH,
            [
                'data' => $data
            ]
        );

        $encodedData = [
            'data' => json_encode($data)
        ];

        try
        {
            $response = $this->app['metro']->publish($topic, $encodedData);

            $this->app['trace']->info(
                TraceCode::MERCHANT_ACTION_OBSERVER_METRO_PUBLISH,
                [
                    'response' => $response
                ]
            );

        } catch (\Throwable $exception)
        {

            $this->app['trace']->traceException(
                $exception,
                Logger::CRITICAL,
                TraceCode::MERCHANT_ACTION_OBSERVER_METRO_PUBLISH);
            // usual flow will not fail if the message publish to metro fails
        }
    }

    protected function isMetroMigrateOutExperimentEnabledForCmmaEvents($entityId): bool
    {
        $properties = [
            'id'            => $entityId,
            'experiment_id' => $this->app['config']->get(Constants::CMMA_METRO_MIGRATE_OUT_EXPERIMENT_ID_KEY),
        ];

        $response = $this->app['splitzService']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? '';

        return $variant === Constants::ENABLE;
    }

}
