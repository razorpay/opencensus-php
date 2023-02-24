<?php


namespace RZP\Models\Workflow\Observer;

use App;
use Monolog\Logger;
use RZP\Trace\TraceCode;
use function Complex\theta;
use RZP\Services\KafkaProducer;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Metric;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\AccountStatus;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Models\Merchant\FreshdeskTicket\Service as FDService;
use RZP\Models\Workflow\Action\Differ\Entity as DifferEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\FreshdeskTicket\Priority as FDPriority;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FDConstants;


class MerchantActivationStatusObserver implements WorkflowObserverInterface
{
    protected $entityId;

    protected $activationStatus;

    protected $fdService;

    protected $repo;

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

    public function __construct($input)
    {
        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->fdService  = new FDService();

        $this->entityId         = $input[DifferEntity::ENTITY_ID];

        $this->activationStatus   = $input[DifferEntity::PAYLOAD][MerchantDetailEntity::ACTIVATION_STATUS] ?? "";

        if (is_null($input[DifferEntity::DIFF])===false)
        {
            $this->oldActivationStatus  =  $input[DifferEntity::DIFF][DifferEntity::OLD][MerchantDetailEntity::ACTIVATION_STATUS] ?? null;

            $this->newActivationStatus  =  $input[DifferEntity::DIFF][DifferEntity::NEW][MerchantDetailEntity::ACTIVATION_STATUS] ?? null;
        }

        if (key_exists(DifferEntity::PERMISSION, $input) === true) // permission at times might not be present
        {
            $this->permissionName   = $input[DifferEntity::PERMISSION];
        }

    }

    public function onApprove(array $observerData)
    {
        // Not being used
        /*$fdInstance = null;

        $ticket_id = null;

        $merchant_id = $this->getMerchantId();

        if (key_exists(FDConstants::TICKET_ID, $observerData) === true or
            key_exists(FDConstants::FD_INSTANCE, $observerData) === true)
        {
            $fdInstance = $observerData[FDConstants::FD_INSTANCE];

            $ticket_id = $observerData[FDConstants::TICKET_ID];

            $this->fdService->postTicketReplyOnAgentBehalf($ticket_id,
                                                           implode("<br><br>", $this->getTicketReplyContent(Constants::APPROVE, $merchant_id)), $fdInstance,
                                                           $this->getMerchantId());

            $this->fdService->resolveAndAddAutomatedResolvedTagToTicket($fdInstance, $ticket_id);
        }*/
    }

    public function onClose(array $observerData)
    {

    }

    public function onReject(array $observerData)
    {
        $this->app['trace']->info(TraceCode::MERCHANT_ACTIVATION_STATUS_OBSERVER,[
            'on_reject'                => 'on_reject observer invoked.'
        ]);

        $data = Constants::MERCHANT_ACTION_METRO_BODY;

        if( $this->permissionName === PermissionName::EDIT_ACTIVATE_MERCHANT)
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
                $data[DifferEntity::ENTITY_ID] = $this->entityId;

                $data[Constants::WORKFLOW_ACTION_ID] = 'w_action_' . $observerData[DifferEntity::ACTION_ID];

                $data[Constants::PERMISSION_NAME] = $this->permissionName;

                $data[Constants::OLD_DATA][Constants::ACTIVATION_STATUS] = (is_null($this->oldActivationStatus)===true) ? Status::UNDER_REVIEW : $this->oldActivationStatus ;

                $data[Constants::NEW_DATA][Constants::ACTIVATION_STATUS] = (is_null($this->newActivationStatus)===true) ? Status::REJECTED : $this->newActivationStatus ;

                $data[Constants::STATUS] = Status::REJECTED;

                $data[Constants::AGENT_Id] = optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT;

                $data[Constants::AGENT_NAME] = optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT;

                $this->publishToMetroTopic($data, Constants::CMMA_WORKFLOW_METRO_TOPIC);
            }
        }

    }

    public function onCreate(array $observerData)
    {
        $this->app['trace']->info(TraceCode::MERCHANT_ACTIVATION_STATUS_OBSERVER,[
            'on_create'                => 'on_create observer invoked.',
            'permission_name' => $this->permissionName
        ]);

        $data = Constants::MERCHANT_ACTION_METRO_BODY;

        if ( $this->permissionName === PermissionName::NEEDS_CLARIFICATION_RESPONDED)
        {
            if ($this->isMetroMigrateOutExperimentEnabledForCmmaEvents($this->entityId) === true)
            {
                $cmmaCaseEventData = [
                    Constants::WORKFLOW_ACTION_ID =>  'w_action_' . $observerData[DifferEntity::ACTION_ID],
                    Constants::PERMISSION_NAME    => PermissionName::NEEDS_CLARIFICATION_RESPONDED,
                    Constants::STATUS             =>  Constants::OPEN,
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
                $data[DifferEntity::ENTITY_ID] = $this->entityId;

                $data[Constants::WORKFLOW_ACTION_ID] = 'w_action_' . $observerData[DifferEntity::ACTION_ID];

                $data[Constants::PERMISSION_NAME] = PermissionName::NEEDS_CLARIFICATION_RESPONDED;

                $data[Constants::OLD_DATA][Constants::ACTIVATION_STATUS] = (is_null($this->oldActivationStatus)===true) ? Status::NEEDS_CLARIFICATION : $this->oldActivationStatus;

                $data[Constants::NEW_DATA][Constants::ACTIVATION_STATUS] = (is_null($this->newActivationStatus)===true) ? Status::UNDER_REVIEW : $this->newActivationStatus;

                $data[Constants::STATUS] = Constants::OPEN;

                $this->publishToMetroTopic($data, Constants::CMMA_WORKFLOW_METRO_TOPIC);
            }

        }
    }

    public function onExecute(array $observerData)
    {
        $this->app['trace']->info(TraceCode::MERCHANT_ACTIVATION_STATUS_OBSERVER,[
            'on_execute'                => 'on_execute observer invoked.'
        ]);

        $data = Constants::MERCHANT_ACTION_METRO_BODY;

        if ($this->permissionName === PermissionName::EDIT_ACTIVATE_MERCHANT)
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
                        'data'  => $cmmaCaseEventData,
                        'topic' => $cmmaCaseEventTopic,
                        'merchant_id' => $this->entityId,
                    ]
                );

                (new KafkaProducer($cmmaCaseEventTopic, stringify($cmmaCaseEventData)))->Produce();
            }
            else
            {
                $data[DifferEntity::ENTITY_ID] = $this->entityId;

                $data[Constants::WORKFLOW_ACTION_ID] = 'w_action_' . $observerData[DifferEntity::ACTION_ID];

                $data[Constants::PERMISSION_NAME] = $this->permissionName;

                $data[Constants::OLD_DATA][Constants::ACTIVATION_STATUS] = (is_null($this->oldActivationStatus)===true) ? Status::UNDER_REVIEW : $this->oldActivationStatus;

                $data[Constants::NEW_DATA][Constants::ACTIVATION_STATUS] = (is_null($this->newActivationStatus)===true) ? Status::ACTIVATED : $this->newActivationStatus;

                $data[Constants::STATUS] = Constants::EXECUTED;

                $data[Constants::AGENT_Id] = optional($this->app['basicauth']->getAdmin())->getPublicId() ?? Constants::UNDEFINED_AGENT;

                $data[Constants::AGENT_NAME] = optional($this->app['basicauth']->getAdmin())->getName() ?? Constants::UNDEFINED_AGENT;

                $this->publishToMetroTopic($data, Constants::CMMA_WORKFLOW_METRO_TOPIC);
            }
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

    public function getMerchantId()
    {
        return $this->entityId;
    }

    public function getMerchant($merchantId)
    {
        if (empty($this->merchant) === true)
        {
            $this->merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        }

        return $this->merchant;
    }

    public function getTicketReplyContent(string $approve_reject, $merchantId) : array
    {
        $merchantName = $this->getMerchant($merchantId)->getName() ?? "";

        if ($this->activationStatus === AccountStatus::ACTIVATED)
        {
            if ($approve_reject === Constants::APPROVE)
            {

                return [
                    "Hi {$merchantName},",
                    "Greetings for the Day!! Thank You for Choosing Razorpay.",
                    "Your activation form has been accepted by our banking partners The bank usually takes 2 business days to enable settlements post which the funds will be settled basis your settlement cycle.",
                    " [Follow these steps to download the combined report from your dashboard to view the settlement schedule of the payments that you've accepted] : https://i.imgur.com/fcpunly.gif.We are happy to have you on-boarded on our platform and strive to deliver the best experience at Razorpay. ",
                    "Let us know how your experience has been so far by taking the survey that will be sent to your email ID.Happy transacting at Razorpay! ",
                    "We're just an email away in case you need help. [steps to raise a request with us] : https://i.imgur.com/8aeofAz.gif ",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        if ($this->activationStatus === AccountStatus::REJECTED)
        {
            if ($approve_reject === Constants::APPROVE)
            {

                return [
                    "Hi {$merchantName},",
                    "We regret to inform you that, unfortunately we would not be able to support your business as the bank has not approved your activation form.In order to mitigate future losses to Razorpay as a result of chargebacks, a reserve has been set in place on your account.",
                    "Based on the above findings we would need to terminate your account effective immediately with a hold on the funds for the chargeback period of 120 days.",
                    "We request you to kindly look for any other alternative and wish you all the best.",
                    "Regards,<br>Razorpay Team."
                ];
            }
        }

        return array();
    }

    public function publishToMetroTopic($data, $topic) {
        // publish message on the metro topic business-banking-enabled

        $this->app['trace']->info(
            TraceCode::MERCHANT_ACTIVATION_OBSERVER_METRO_PUBLISH,
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
                TraceCode::MERCHANT_ACTIVATION_OBSERVER_METRO_PUBLISH,
                [
                'response' => $response
                ]
            );

        } catch (\Throwable $exception)
        {

            $this->app['trace']->traceException(
                $exception,
                Logger::CRITICAL,
                TraceCode::MERCHANT_ACTIVATION_OBSERVER_METRO_PUBLISH);
            // usual flow will not fail if the message publish to metro fails
        }
    }
}
