<?php

namespace RZP\Models\MerchantRiskAlert;

use RZP\Services\MerchantRiskAlertClient;
use View;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Comment;
use RZP\Constants\Mode;
use RZP\Services\Stork;
use RZP\Models\Dispute;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission;
use RZP\Trace\TraceCode;
use RZP\lib\TemplateEngine;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;
use RZP\Mail\Merchant\Risk as MerchantRiskEmailer;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Org;
use RZP\Models\Dispute\Phase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Services\Harvester\Constants as HarvesterConstants;
use RZP\Exception\BadRequestValidationFailureException;
use \RZP\Models\Merchant\FreshdeskTicket\Processor\WebsiteCheckerReply as WebsiteCheckerReply;
use RZP\Models\Merchant\FreshdeskTicket\Constants as FreshdeskConstants;
use RZP\Models\RiskWorkflowAction\Constants as RiskWorkflowActionConstants;

// TODO: add traces

class Service extends Base\Service
{
    private $freshdeskConfig;

    public function __construct()
    {
        parent::__construct();

        $this->freshdeskConfig = $this->app['config']->get('applications.freshdesk');
    }

    public function createFOHWorkflow(array $input)
    {
        $this->app['trace']->info(
            TraceCode::MERCHANT_RISK_ALERT_FOH_CREATE_WORKFLOW_REQUEST,
            [
                'input' => $input,
            ]);

        $merchantId = $input['merchant_id'];

        $this->app['api.mutex']->acquireAndRelease(
            Constants::MUTEX_PREFIX . $merchantId,
            function() use ($merchantId, $input)
            {
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $this->app['basicauth']->setOrgId(Org\Entity::RAZORPAY_ORG_ID);

                $action = $input['action'];

                if ($action === Constants::ACTION_MANUAL_FOH || $action === Constants::ACTION_AUTO_REVIEW_FOH)
                {
                    $this->handleManualFOH($merchant, $input);
                }

                if ($action === Constants::ACTION_AUTO_FOH)
                {
                    $this->handleAutoFOH($merchant, $input);
                }
            });
    }

    public function executeFOHWorkflow(array $input)
    {
        $this->app['trace']->info(
            TraceCode::MERCHANT_RISK_ALERT_FOH_EXECUTE_WORKFLOW_REQUEST,
            [
                'input' => $input,
            ]);

        $merchantId = $input['merchant_id'];
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setOrgId(Org\Entity::RAZORPAY_ORG_ID);

        if ($merchant->isFundsOnHold() === false)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($input, $merchant)
            {
                $merchant->holdFunds();

                $riskAttributes = $input["tags"];
                (new Merchant\Core)->addOrClearRiskTagAndSetFraudType($merchant, $riskAttributes, Merchant\Action::HOLD_FUNDS);

                $this->repo->saveOrFail($merchant);
            });

            // get current opened workflow
            $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
                $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, Permission\Name::MERCHANT_RISK_ALERT_FOH);

            if ($workflowActions->isNotEmpty() === false)
            {
                return;
            }

            $workflowAction = $workflowActions->first();

            $fdTicketId = $this->getFdTicketIfApplicable($workflowAction, $input);

            if (is_null($fdTicketId) === false)
            {
                $input[Constants::FD_TICKET_ID_KEY] = $fdTicketId;
            }

            $this->sendNotificationsIfApplicable($merchant, $input, Constants::FOH_CONFIRMATION_NOTIFICATION);

            // remove website checker or app checker scheduled reminder
            // firing this action irrespective of the trigger category

            foreach (Constants::REDIS_REMINDER_MAP_NAME as $redisMap)
            {
                $this->app['cache']->connection()->hdel($redisMap, $merchantId);
            }
        }
    }

    public function getMerchantDetails(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setOrgId(Org\Entity::RAZORPAY_ORG_ID);

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, Permission\Name::MERCHANT_RISK_ALERT_FOH);

        $workflowActionsForInternationalDisablement = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_KEY, Permission\Name::EDIT_MERCHANT_DISABLE_INTERNATIONAL);

        $lastUpdatedAt = 0;
        $workflowActionsForLastUpdated = (new Action\Core)->fetchLastUpdatedWorkflowActionInPermissionList(
            $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, [Permission\Name::MERCHANT_RISK_ALERT_FOH]);
        if (empty($workflowActionsForLastUpdated) === false) {
            $lastUpdatedAt = $workflowActionsForLastUpdated->getUpdatedAt();
        }

        $details = [
            Constants::MERCHANT_FOH_KEY                     => $merchant->isFundsOnHold(),
            Constants::MERCHANT_INTERNATIONAL_KEY           => $merchant->isInternational(),
            Constants::MERCHANT_LIVE_KEY                    => $merchant->isLive(),
            Constants::MERCHANT_SUSPENDED_KEY               => $merchant->isSuspended(),
            Constants::MERCHANT_FOH_WORKFLOW_KEY            => $workflowActions->isNotEmpty() === true,
            Constants::MERCHANT_INTERNATIONAL_WORKFLOW_KEY  => $workflowActionsForInternationalDisablement->isNotEmpty() === true,
            Constants::MERCHANT_CREATED_AT                  => $merchant->getCreatedAt(),
            Constants::MERCHANT_HAS_AOV                     => false,
            Constants::MERCHANT_ODS                         => $merchant->isFeatureEnabled(Feature\Constants::ES_ON_DEMAND),
            Constants::MERCHANT_LAST_UPDATED_WORKFLOW       => $lastUpdatedAt,
        ];

        $merchantAov = $merchant->merchantDetail->avgOrderValue;

        // Getting the current aov value (there could be skews, but given the current experiment phase shouldnt matter)
        if (is_null($merchantAov) === false)
        {
            $details[Constants::MERCHANT_HAS_AOV] = true;
            $details[Constants::MERCHANT_MIN_AOV] = $merchantAov->getMinAov();
            $details[Constants::MERCHANT_MAX_AOV] = $merchantAov->getMaxAov();
        }

        return $details;
    }

    private function handleManualFOH(Merchant\Entity $merchant, array $input)
    {
        // NOTE: only workflow action create call comes here
        // check if open workflow, then return
        // check if no open workflows then create one

        if ($merchant->isFundsOnHold() === true)
        {
            return;
        }

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, Permission\Name::MERCHANT_RISK_ALERT_FOH);

        $workflowTags = $this->getWorkflowTags($input, $merchant);

        if ($workflowActions->isNotEmpty() === true)
        {
            $action = $workflowActions->first();

            $action->tag($workflowTags);

            $actionId = $action->getId();

            (new Comment\Service())->createForWorkflowAction([
                'comment'   => sprintf('NEW_TRIGGER: %s', json_encode($workflowTags)),
            ], Action\Entity::getSignedId($actionId), $this->getMaker());


            $this->repo->workflow_action->saveOrFail($action);

            return;
        }

        try
        {
            $this->app['workflow']
                ->setPermission(Permission\Name::MERCHANT_RISK_ALERT_FOH)
                ->setController(Constants::FOH_WORKFLOW_EXECUTE_CONTROLLER)
                ->setMakerFromAuth(false)
                ->setWorkflowMaker($this->getMaker())
                ->setWorkflowMakerType(MakerType::ADMIN)
                ->setEntityAndId(Constants::MERCHANT_DETAIL_KEY, $merchant->getId())
                ->setTags($workflowTags)
                ->handle(["funds_on_hold" => false], ["funds_on_hold" => true]);
        }
        catch (Exception\EarlyWorkflowResponse $ex)
        {
            $workflowActionData = json_decode($ex->getMessage(), true);

            $workflowActionId = $workflowActionData['id'];

            $fdTicketId = $this->sendNotificationsIfApplicable($merchant, $input, Constants::FOH_NC_NOTIFICATION);

            $additionalData = [
                Constants::FD_TICKET_ID_KEY          => $fdTicketId,
                Constants::WORKFLOW_ACTION_INPUT_KEY => $input,
            ];

            $this->postProcessOnSuccessfulWfActionCreation($workflowActionId, $additionalData);

            throw $ex;
        }
    }

    private function handleAutoFOH(Merchant\Entity $merchant, array $input)
    {
        if ($merchant->isFundsOnHold() === true)
        {
            return;
        }

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, Permission\Name::MERCHANT_RISK_ALERT_FOH);

        $maker = $this->getMaker();

        // Ideally should have only one action
        foreach ($workflowActions as $workflowAction)
        {
            (new Action\Core)->close($workflowAction, $maker, true);
        }

        $workflowTags = $this->getWorkflowTags($input, $merchant);

        try
        {
            $this->app['workflow']
                ->setPermission(Permission\Name::MERCHANT_RISK_ALERT_FOH)
                ->setController(Constants::FOH_WORKFLOW_EXECUTE_CONTROLLER)
                ->setMakerFromAuth(false)
                ->setWorkflowMaker($maker)
                ->setWorkflowMakerType(MakerType::ADMIN)
                ->setEntityAndId(Constants::MERCHANT_DETAIL_KEY, $merchant->getId())
                ->setTags($workflowTags)
                ->handle(["funds_on_hold" => false], ["funds_on_hold" => true]);
        }
        catch (Exception\EarlyWorkflowResponse $ex){}

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, Permission\Name::MERCHANT_RISK_ALERT_FOH);

        // sleep for a sec to retrieve the doc
        // Alernatives:
        // 1. index the final doc // this will require bypassing workflow creation code, not a good idea
        // 2. enquque the operation and run a bot job to hit the checker route. // this is the ideal thing to do
        //
        // as per P0 time contrainst related to setting up queue (by devops) going ahead with the sleep thingy
        // also note that auto foh flow only runs on strict checks / hard limits, hence not supposed to be hit
        // that frequently
        sleep(1);

        foreach ($workflowActions as $workflowAction) {
            // cant call action checker create because of auth restrictions
            (new Action\Core)->approveActionForcefully($workflowAction, $maker);

            (new Action\Core)->executeAction($workflowAction, $maker, $maker->getSuperAdminRole());
        }
    }

    protected function sendNotificationForMobileSignup($merchant, $notificationType, $rasTriggerReason, $input)
    {
        try
        {
            $fdRasReasonTag = sprintf(Constants::FD_TAG_RAS_REASON_FOH, strtoupper($rasTriggerReason));

            $fdTags = [Constants::FD_TAG_RAS_FOH, $fdRasReasonTag];

            [$subject, $viewTemplate, $data, $fdSubcategory, $notificationType, $rasTriggerReason, $groupId, $emailConfigId]
                = $this->getEmailData($merchant, $notificationType, $rasTriggerReason, $input);

            $requestParams = [
                'type'          =>  'Question',
                'tags'          =>  $fdTags,
                'groupId'       =>  $groupId,
                'subCategory'   =>  $fdSubcategory,
            ];

            $fdTicket = (new Merchant\RiskMobileSignupHelper())->createFdTicket($merchant, $viewTemplate, $subject, $data, $requestParams);

            $supportTicketLink = (new Merchant\RiskMobileSignupHelper())->getSupportTicketLink($fdTicket, $merchant);

            $smsContent = $this->getSmsData($merchant, $notificationType, $rasTriggerReason, $supportTicketLink);

            $this->sendSms($merchant, $smsContent);

            $whatsAppContent = $this->getWhatsAppData($merchant, $notificationType, $rasTriggerReason, $supportTicketLink);

            $this->sendWhatsappMessage($merchant, $whatsAppContent);

            return $fdTicket['ticket_id'];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::RAS_FOH_SEND_NOTIFICATION_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]);

            return $fdTicket['ticket_id'] ?? '';
        }
    }

    public function sendNotificationsIfApplicable(Merchant\Entity $merchant, array $input, string $notificationType)
    {
        $rasTriggerReason = $this->getRasTriggerReasonFromPayload($input);

        $canTriggerNotification = $this->canSendNotification($notificationType, $rasTriggerReason);

        if ($canTriggerNotification === false)
        {
            return;
        }

        if (Merchant\RiskMobileSignupHelper::isEligibleForMobileSignUp($merchant) === true)
        {
            return $this->sendNotificationForMobileSignup($merchant, $notificationType, $rasTriggerReason, $input);
        }
        else
        {
            $smsContent = $this->getSmsData($merchant, $notificationType, $rasTriggerReason);

            $this->sendSms($merchant, $smsContent);

            $whatsAppContent = $this->getWhatsAppData($merchant, $notificationType, $rasTriggerReason);

            $this->sendWhatsappMessage($merchant, $whatsAppContent);

            $emailContent = $this->getEmailData($merchant, $notificationType, $rasTriggerReason, $input);

            return $this->sendEmail($merchant, $emailContent, $input);
        }
    }

    private function getMaker()
    {
        //using default razorpay org for now, to be fixed by code owner
        $makerOrgId = Org\Entity::RAZORPAY_ORG_ID;

        // NOTE: maker_email (both maker and checker) should be superadmin
        $makerEmail = $this->app['config']->get('applications.merchant_risk_alerts.maker_email');

        if (empty($makerEmail) === true)
        {
            throw new Exception\LogicException('Merchant Risk Alert Workflow Maker is not initialized');
        }

        $maker = $this->repo->admin->findByOrgIdAndEmail($makerOrgId, $makerEmail);

        return $maker;
    }

    private function sendSms($merchant, array $content)
    {
        $receiver = $merchant->merchantDetail->getContactMobile();

        if (empty($receiver) === true)
        {
            return;
        }

        [$template, $params, $notificationType, $rasTriggerReason] = $content;

        $payload = [
            'receiver' => $receiver,
            'template' => $template,
            'source'   => 'api.merchant.risk.alert',
            'params'   => $params,
            'stork' => [
                'context' => [
                    'org_id' => $merchant->getOrgId(),
                ],
            ]
        ];

        try {
            $this->app['raven']->sendSms($payload);

            $this->app['trace']->info(
                TraceCode::MERCHANT_RISK_ALERT_FOH_SMS_SENT,
                [
                    'merchant_id'        => $merchant->getId(),
                    'notification_type'  => $notificationType,
                    'ras_trigger_reason' => $rasTriggerReason,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_RISK_ALERT_FOH_SMS_FAILED,
                [
                    'merchant_id'        => $merchant->getId(),
                    'notification_type'  => $notificationType,
                    'ras_trigger_reason' => $rasTriggerReason,
                ]
            );
        }
    }

    private function sendWhatsappMessage($merchant, array $content)
    {
        $mode = $this->app['rzp.mode'];

        $receiver = $merchant->merchantDetail->getContactMobile();

        [$templateName, $template, $params, $notificationType, $rasTriggerReason] = $content;

        $whatsAppPayload = [
            'ownerId'       => $merchant->getId(),
            'ownerType'     => 'merchant',
            'template_name' => $templateName,
            'params'        => $params,
        ];

        (new Stork)->sendWhatsappMessage(
            $mode,
            $template,
            $receiver,
            $whatsAppPayload
        );
    }

    private function sendEmail($merchant, array $content, array $input)
    {
        try
        {
            [$subject, $emailBody, $fdSubcategory, $notificationType, $rasTriggerReason, $groupId, $emailConfigId] = $content;

            $merchantEmail = $merchant->merchantDetail->getContactEmail();

            $ccEmails = (new Dispute\Service)->getCCEmailsWithSalesPOC($merchant->getId());

            $fdRasReasonTag = sprintf(Constants::FD_TAG_RAS_REASON_FOH, strtoupper($rasTriggerReason));

            $fdTags = [Constants::FD_TAG_RAS_FOH, $fdRasReasonTag];

            $fdTicketId = $input[Constants::FD_TICKET_ID_KEY] ?? null;

            if (is_null($fdTicketId) === true)
            {
                $fdOutboundEmailRequest = [
                    'subject'         => $subject,
                    'description'     => $emailBody,
                    'status'          => 6,
                    'type'            => 'Question',
                    'priority'        => 1,
                    'email'           => $merchantEmail,
                    'tags'            => $fdTags,
                    'group_id'        => (int) $groupId,
                    'email_config_id' => (int) $emailConfigId,
                    'custom_fields'  => [
                        'cf_ticket_queue' => 'Merchant',
                        'cf_category'     => 'Risk Report_Merchant',
                        'cf_subcategory'  => $fdSubcategory,
                        'cf_product'      => 'Payment Gateway',
                    ],
                ];

                if (empty($ccEmails) === false)
                {
                    $fdOutboundEmailRequest['cc_emails'] = $ccEmails;
                }

                $response = $this->app['freshdesk_client']->sendOutboundEmail(
                    $fdOutboundEmailRequest, FreshdeskConstants::URLIND);

                $fdTicketId = $response['id'] ?? null;
            }
            else
            {
                $this->app['freshdesk_client']->postTicketReply($fdTicketId, ['body' => $emailBody]);
            }

            $this->app['trace']->info(
                TraceCode::MERCHANT_RISK_ALERT_FOH_EMAIL_SENT,
                [
                    'merchant_id'        => $merchant->getId(),
                    'notification_type'  => $notificationType,
                    'ras_trigger_reason' => $rasTriggerReason,
                ]);

            return $fdTicketId;
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_RISK_ALERT_FOH_EMAIL_FAILED,
                [
                    'merchant_id'        => $merchant->getId(),
                    'notification_type'  => $notificationType,
                    'ras_trigger_reason' => $rasTriggerReason,
                ]
            );
        }
    }

    public function handleOnRejectWorkflowAction(Action\Entity $action)
    {
        $merchantId = $action->getEntityId();

        $this->app['merchant_risk_alerts']->notifyNonRiskyMerchant($merchantId);

        foreach (Constants::REDIS_REMINDER_MAP_NAME as $redisMap)
        {
            $this->app['cache']->connection()->hdel($redisMap, $merchantId);
        }
    }

    private function getWorkflowTags($input, $merchant = null)
    {
        $tags = $this->getWorkflowTagsFromInput($input);

        if ($merchant === null)
        {
            return $tags;
        }

        if ($merchant->isFeatureEnabled(Feature\Constants::APPS_EXTEMPT_RISK_CHECK) === false)
        {
            return $tags;
        }

        $tags[] = Constants::MANAGED_MERCHANT_TAG;

        return $tags;
    }

    private function getWorkflowTagsFromInput(array $input)
    {
        $tags = $input['tags'] ?? [];

        $workflowTags = [];

        foreach ($tags as $tagName => $tagValue) {
            $workflowTags[] = $tagName . ':' . $tagValue;
        }

        $action = $input['action'];

        if ($action === Constants::ACTION_MANUAL_FOH)
        {
            $workflowTags[] = Constants::MANUAL_FOH_TAG;
        }
        else
        {
            $workflowTags[] = Constants::AUTO_FOH_TAG;
        }

        return $workflowTags;
    }


    public function getMerchantDisputeDetails(string $merchantId, array $input)
    {
        // checking if merchantId exists
        $this->repo->merchant->findOrFail($merchantId);

        // we can add a range limit, but as its used by ras, an acceptable range will be sent
        // the retrieval if details from api is temporary.. once the use case of disputes in ras
        // is finalized, this code can be removed..

        if (isset($input['from']) === false ||
            is_string($input['from']) === false ||
            $input['from'] != intval($input['from']) ||
            intval($input['from']) < 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid start timestamp', 'from', $input);
        }

        if (isset($input['to']) === false ||
            is_string($input['to']) === false ||
            $input['to'] != intval($input['to']) ||
            intval($input['to']) < 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid end timestamp', 'to', $input);
        }

        $fromTimestamp = intval($input['from']);
        $toTimestamp   = intval($input['to']);

        if ($fromTimestamp > $toTimestamp)
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid range specified', null, $input);
        }

        $paymentDisputedGmv = $this->repo->dispute->getMerchantDisputedPaymentsGmvForRiskAnalysis(
            $merchantId, $fromTimestamp, $toTimestamp);

        $paymentDisputedCount = $this->repo->dispute->getMerchantDisputedPaymentsCountForRiskAnalysis(
            $merchantId, $fromTimestamp, $toTimestamp);

        $paymentHigherDisputedCount = $this->repo->dispute->getMerchantDisputedPaymentsCountbyPhaseForRiskAnalysis(
            $merchantId, $fromTimestamp, $toTimestamp, [Phase::PRE_ARBITRATION, Phase::ARBITRATION]);

        $details = [
            Constants::MERCHANT_PAYMENTS_DISPUTED_GMV          => intval($paymentDisputedGmv),
            Constants::MERCHANT_PAYMENTS_DISPUTED_COUNT        => intval($paymentDisputedCount),
            Constants::MERCHANT_PAYMENTS_HIGHER_DISPUTED_COUNT => intval($paymentHigherDisputedCount),
        ];

        return $details;
    }

    private function getEmailData(
        Merchant\Entity $merchant, string $notificationType, string $rasTriggerReason, array $input)
    {
        $subject       = '';
        $viewTemplate  = '';
        $data          = '';
        $fdSubcategory = '';

        $groupId = (int) $this->freshdeskConfig['group_ids']['rzpind']['merchant_risk'];

        $emailConfigId = (int) $this->freshdeskConfig['email_config_ids']['rzpind']['risk_notification'];

        if ($notificationType === Constants::FOH_NC_NOTIFICATION)
        {
            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === false)
            {
                throw new Exception\LogicException('Need clarification notification not enabled');
            }

            $fdSubcategory = Constants::FD_SUB_CATEGORY_NEED_CLARIFICATION;

            $subject = Constants::FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_MAIL_SUBJECT[$rasTriggerReason];

            $viewTemplate = Constants::FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL[$rasTriggerReason];

            $emailConfigId = (int) $this->freshdeskConfig['email_config_ids']['rzpind']['foh_notification'];

            $groupId = (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'];

            $data = [
                'merchant_id'   => $merchant->getId(),
                'merchant_name' => $merchant->getName(),
                'days_to_foh'   => $input['days_to_foh'] ?? Constants::HEALTH_CHECKER_NC_DAYS_TO_FOH,
            ];
        }
        else
        {
            $fdSubcategory = Constants::FD_SUB_CATEGORY_FUNDS_ON_HOLD;

            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === true)
            {
                $subject = Constants::FOH_GENERIC_CONFIRMATION_MAIL_SUBJECT;

                $viewTemplate = Constants::FOH_HEALTH_CHECKER_CONFIRMATION_MAIL_TPL[$rasTriggerReason];

                $data = [
                    'merchant_id'   => $merchant->getId(),
                    'merchant_name' => $merchant->getName(),
                ];
            }
            else
            {
                $groupId = (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'];

                $emailConfigId = (int) $this->freshdeskConfig['email_config_ids']['rzpind']['foh_notification'];

                $subject = Constants::FOH_GENERIC_CONFIRMATION_MAIL_SUBJECT;

                $viewTemplate = Constants::FOH_GENERIC_CONFIRMATION_MAIL_TPL;

                $data = [
                    'merchant_id'   => $merchant->getId(),
                    'merchant_name' => $merchant->getName(),
                ];
            }
        }

        return [$subject, $viewTemplate, $data, $fdSubcategory, $notificationType, $rasTriggerReason, $groupId, $emailConfigId];
    }

    private function getSmsData(Merchant\Entity $merchant, string $notificationType, string $rasTriggerReason, $supportTicketLink = null)
    {
        $template = '';
        $data     = '';

        if ($notificationType === Constants::FOH_NC_NOTIFICATION)
        {
            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === false)
            {
                throw new Exception\LogicException('Need clarification notification not enabled');
            }

            $template = (isset($supportTicketLink) === false)
                ? Constants::FOH_SMS_HEALTH_CHECKER_NEEDS_CLARIFICATION_TEMPLATE[$rasTriggerReason]
                : Constants::FOH_SMS_WEBSITE_CHECKER_NEEDS_CLARIFICATION_TEMPLATE_MOBILE_SIGNUP;

            $data = [
                'merchantId'   => $merchant->getId(),
                'merchantName' => $merchant->getName(),
            ];
        }
        else
        {
            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === true)
            {
                $template = (isset($supportTicketLink) === false)
                    ? Constants::FOH_SMS_HEALTH_CHECKER_CONFIRMATION_TEMPLATE[$rasTriggerReason]
                    : Constants::FOH_SMS_WEBSITE_CHECKER_CONFIRMATION_TEMPLATE_MOBILE_SIGNUP;

                $data = [
                    'merchantId'   => $merchant->getId(),
                    'merchantName' => $merchant->getName(),
                ];
            }
            else
            {
                $template = (isset($supportTicketLink) === false)
                ? Constants::FOH_SMS_GENERIC_CONFIRMATION_TEMPLATE
                : Constants::FOH_GENERIC_CONFIRMATION_SMS_TEMPLATE_MOBILE_SIGNUP;

                $data = [
                    'merchantName' => $merchant->getName(),
                ];
            }
        }

        if (isset($supportTicketLink) === true)
        {
            $data['supportTicketLink'] = $supportTicketLink;
        }

        return [$template, $data, $notificationType, $rasTriggerReason];
    }

    private function getWhatsAppData(Merchant\Entity $merchant, string $notificationType, string $rasTriggerReason, $supportTicketLink = null)
    {
        $templateName = '';
        $template     = '';
        $data         = '';

        if ($notificationType === Constants::FOH_NC_NOTIFICATION)
        {
            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === false)
            {
                throw new Exception\LogicException('Need clarification notification not enabled');
            }

            if (isset($supportTicketLink) === true)
            {
                $templateName   = Constants::FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP;
                $template       = Constants::FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_MOBILE_SIGNUP;
            }
            else
            {
                $templateName   = Constants::FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME[$rasTriggerReason];
                $template       = Constants::FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE[$rasTriggerReason];
            }

            $data = [
                'merchantId'   => $merchant->getId(),
                'merchantName' => $merchant->getName(),
            ];
        }
        else
        {
            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === true)
            {
                if (isset($supportTicketLink) === true)
                {
                    $templateName   = Constants::FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP;
                    $template       = Constants::FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_MOBILE_SIGNUP;
                }
                else
                {
                    $templateName   = Constants::FOH_HEALTH_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME[$rasTriggerReason];
                    $template       = Constants::FOH_HEALTH_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE[$rasTriggerReason];
                }

                $data = [
                    'merchantId'   => $merchant->getId(),
                    'merchantName' => $merchant->getName(),
                ];
            }
            else
            {
                if (isset($supportTicketLink) === true)
                {
                    $templateName   = Constants::FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP;
                    $template       = Constants::FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE_MOBILE_SIGNUP;
                }
                else
                {
                    $templateName   = Constants::FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE_NAME;
                    $template       = Constants::FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE;
                }

                $data = [
                    'merchantName' => $merchant->getName(),
                ];
            }
        }

        if (isset($supportTicketLink) === true)
        {
            $data['supportTicketLink'] = $supportTicketLink;
        }

        return [$templateName, $template, $data, $notificationType, $rasTriggerReason];
    }

    private function getRasTriggerReasonFromPayload(array $input)
    {
        $tags = $input['tags'] ?? [];

        $rasTriggerReason = Constants::RAS_TRIGGER_REASON_GENERIC;

        foreach ($tags as $tagName => $tagValue)
        {
            if ($tagName === Constants::RAS_TRIGGER_REASON_KEY)
            {
                $rasTriggerReason = $tagValue;

                break;
            }
        }

        return $rasTriggerReason;
    }

    private function isHealthCheckTriggerReason($rasTriggerReason): bool
    {
        return in_array($rasTriggerReason, Constants::RAS_TRIGGER_REASONS_HEALTH_CHECKER);
    }

    private function canSendNotification(string $notificationType, string $rasTriggerReason)
    {
        if ($notificationType === Constants::FOH_NC_NOTIFICATION)
        {
            return false;
        }

        return true;
    }

    private function postProcessOnSuccessfulWfActionCreation(string $workflowActionId, array $additionalData)
    {
        try
        {
            $workflowActionInput = $additionalData[Constants::WORKFLOW_ACTION_INPUT_KEY];

            $rasTriggerReason = $this->getRasTriggerReasonFromPayload($workflowActionInput);

            if ($this->isHealthCheckTriggerReason($rasTriggerReason) === true)
            {
                $fdTicketId = $additionalData[Constants::FD_TICKET_ID_KEY] ?? null;

                $workflowActionId = Action\Entity::verifyIdAndStripSign($workflowActionId);

                $workflowAction = $this->repo->workflow_action->findOrFailPublic($workflowActionId);

                if (is_null($fdTicketId) === false)
                {
                    $fdTag = sprintf(Constants::RAS_FD_TICKET_ID_TAG_FMT, $rasTriggerReason, $fdTicketId);

                    $workflowAction->tag($fdTag);
                }

                $this->app['cache']->connection()->hset(
                    Constants::REDIS_REMINDER_MAP_NAME[$rasTriggerReason],
                    $workflowAction->getEntityId(),
                    now()->timestamp
                );
            }
        }
        catch(\Throwable $ex)
        {
            $this->app['trace']->traceException($ex,
                Trace::CRITICAL,
                TraceCode::MERCHANT_RISK_ALERT_FOH_REMINDER_ENQUEUE_FAILED,
                [
                    'workflow_action_id' => $workflowActionId,
                ]
            );
        }
    }

    private function getFdTicketIfApplicable(Action\Entity $workflowAction, array $input)
    {
        $fdTicketId = null;

        $rasTriggerReason = $this->getRasTriggerReasonFromPayload($input);

        $fdTicketTagPrefix = sprintf(Constants::RAS_FD_TICKET_TAG_PREFIX, $rasTriggerReason);

        foreach ($workflowAction->tagNames() as $tagName)
        {
            $tagName = strtolower($tagName);

            if (starts_with($tagName, $fdTicketTagPrefix) === true)
            {
                $fdTicketId = substr($tagName, strlen($fdTicketTagPrefix));

                break;
            }
        }

        if (empty($fdTicketId) === true)
        {
            $fdTicketId = $input[Constants::FD_TICKET_ID_KEY] ?? null;
        }

        return $fdTicketId;
    }

    public function identifyBlacklistCountryAlerts(array $input)
    {
        return $this->app['merchant_risk_alerts']->identifyBlacklistCountryAlerts($input);
    }

    protected  function createRASRulesWorkflowIfApplicable($action, $entityId ,$inputs)
    {
        $permission = in_array($action, ['create', 'update']) === true ? Permission\Name::MERCHANT_RISK_ALERT_UPSERT_RULE : Permission\Name::MERCHANT_RISK_ALERT_DELETE_RULE;

        $this->app['workflow']
            ->setPermission($permission)
            ->setInput($inputs)
            ->setEntityAndId(Constants::RAS_RULE_ENTITY , $entityId)
            ->handle([], $inputs);
    }

    public function createRule(array $input)
    {
        $entityId = UniqueIdEntity::generateUniqueId();

        $this->createRASRulesWorkflowIfApplicable('create', $entityId, [Constants::RAS_RULES_CREATE_PAYLOAD => $input ]);

        $inputs = $input[Constants::RAS_RULES_CREATE_PAYLOAD];

        $this->app['trace']->info(
            TraceCode::RAS_RULES_CREATION_WORKFLOW_APPROVE,
            [
               'inputs' => $inputs
            ]);

        return $this->app['merchant_risk_alerts']->sendRequest(Constants::CREATE_RULE_URL, $input);
    }

    public function updateRule($ruleId, $input)
    {
        $input['id'] = $ruleId;

        $this->createRASRulesWorkflowIfApplicable('update', $ruleId, [Constants::RAS_RULES_UPDATE_PAYLOAD => $input ]);

        $inputs = $input[Constants::RAS_RULES_UPDATE_PAYLOAD];

        $this->app['trace']->info(
            TraceCode::RAS_RULES_UPDATE_WORKFLOW_APPROVE,
            [
                'inputs' => $inputs
            ]);

        return $this->app['merchant_risk_alerts']->sendRequest(Constants::UPDATE_RULE_URL, $inputs);
    }

    public function deleteRule($inputs)
    {
        $ruleId = $inputs[Constants::RAS_RULES_ID];

        $this->createRASRulesWorkflowIfApplicable('delete', $ruleId, [Constants::RAS_RULES_DELETE_PAYLOAD => $inputs]);

        $this->app['trace']->info(
            TraceCode::RAS_RULES_DELETE_WORKFLOW_APPROVE,
            [
                'ruleId' => $ruleId
            ]);

        return $this->app['merchant_risk_alerts']->sendRequest(Constants::DELETE_RULE_URL, [
            'id'   =>  $ruleId,
        ]);
    }

    public function sendMobileSignUpNotificationForNC($merchant)
    {
        try
        {
            $merchantName = $merchant->getName() ?? '';

            $data = [
                Merchant\Entity::MERCHANT_ID => $merchant->getId(),
                'merchant_name'              => $merchantName,
                'merchantName'               => $merchantName,
            ];

            $rasTriggerReason = Constants::RAS_TRIGGER_REASON_NC_FLOW;

            $fdRasReasonTag = sprintf(Constants::FD_TAG_RAS_REASON_FOH, strtoupper($rasTriggerReason));

            $fdTags = [Constants::FD_TAG_RAS_FOH, $fdRasReasonTag];

            $requestParams = [
                'type'          =>  'Question',
                'tags'          =>  $fdTags,
                'subCategory'   =>  Constants::FD_SUB_CATEGORY_NEED_CLARIFICATION,
                'groupId'        => (int) $this->freshdeskConfig['group_ids']['rzpind']['foh'],
            ];

            $fdTicket = (new Merchant\RiskMobileSignupHelper())->createFdTicket($merchant, Constants::FOH_ADMIN_TRIGGER_NEEDS_CLARIFICATION_TPL, Constants::FOH_ADMIN_TRIGGER_NEEDS_CLARIFICATION_SUBJECT, $data, $requestParams);

            $supportTicketLink = (new Merchant\RiskMobileSignupHelper())->getSupportTicketLink($fdTicket, $merchant);

            $data['supportTicketLink'] = $supportTicketLink;

            $smsContent = [Constants::RAS_NC_SMS_TEMPLATE, $data, Constants::FOH_NC_NOTIFICATION, $rasTriggerReason];

            $whatsappContent = [Constants::RAS_NC_WHATSAPP_TEMPLATE_NAME, Constants::RAS_NC_WHATSAPP_TEMPLATE, $data, Constants::FOH_NC_NOTIFICATION, $rasTriggerReason];

            $this->sendSms($merchant, $smsContent);

            $this->sendWhatsappMessage($merchant, $whatsappContent);

            return $fdTicket['ticket_id'];
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::RAS_FOH_NC_FLOW_SEND_NOTIFICATION_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]);

            return $fdTicket['ticket_id'] ?? '';
        }
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function triggerNeedsClarification(string $workflowActionId, array $input)
    {
        (new Validator)->validateInput('needs_clarification_request', $input);

        $this->trace->info(TraceCode::MERCHANT_RISK_ALERT_TRIGGER_NC_FOR_WORKFLOW, [
            'workflow_action_id' => $workflowActionId,
        ]);

        $workflowActionId = Action\Entity::verifyIdAndStripSign($workflowActionId);

        $action = $this->repo->workflow_action->findOrFailPublic($workflowActionId);

        (new Validator)->validateTriggerNeedsClarificationRequest($action);

        $merchant = (new Merchant\Repository())->findOrFail($action->getEntityId());

        if (Merchant\RiskMobileSignupHelper::isEligibleForMobileSignUp($merchant) === false)
        {
            $ticketId = $this->sendOutboundEmailForTriggerNeedsClarification($merchant, $input);
        }
        else
        {
            $ticketId = $this->sendMobileSignUpNotificationForNC($merchant);
        }

        $this->addCommentToWorkflowForTriggerNeedsClarification($action, $ticketId);

        $this->markNeedsClarificationAsTriggeredForAction($action);

        return ['success' => true];
    }

    protected function sendOutboundEmailForTriggerNeedsClarification($merchant, array $params)
    {
        $emailSubject = sprintf( " %s | %s | %s", Constants::RISK_CLARIFICATION, $merchant->getId(), $merchant->getName());

        $emailBody = array_get($params, 'email');

        return $this->sendEmail($merchant, [
            $emailSubject,
            $emailBody,
            Constants::FD_SUB_CATEGORY_NEED_CLARIFICATION,
            Constants::FOH_NC_NOTIFICATION,
            Constants::RAS_TRIGGER_REASON_NC_FLOW,
            $this->freshdeskConfig['group_ids']['rzpind']['foh'],
            $this->freshdeskConfig['email_config_ids']['rzpind']['foh_notification'],
        ], []);
    }

    protected function addCommentToWorkflowForTriggerNeedsClarification($action, $ticketId = ''): void
    {
        $comment = sprintf(Constants::RAS_NC_OUTBOUND_EMAIL_FRESHDESK_TICKET_URL_FORMAT, $ticketId);

        $commentEntity = (new Comment\Core())->create([
            Comment\Entity::COMMENT => $comment,
        ]);

        $commentEntity->entity()->associate($action);

        $this->repo->saveOrFail($commentEntity);
    }

    public function getCacheKeyForNeedsClarificationRequest($action): string
    {
        return Constants::RAS_NC_WORKFLOW_CACHE_KEY . $action->getId();
    }

    protected function markNeedsClarificationAsTriggeredForAction($action): void
    {
        $cacheKey = $this->getCacheKeyForNeedsClarificationRequest($action);

        $this->app['cache']->put($cacheKey, true, Constants::RAS_NC_WORKFLOW_CACHE_TTL);
    }

    public function setMerchantDedupeKey($merchantId)
    {
        $this->app['cache']->connection()->hset(
            Constants::REDIS_DEDUPE_SIGNUP_CHECKER_MAP,
            $merchantId,
            now()->timestamp
        );

        $this->app['trace']->info(
        TraceCode::MERCHANT_RISK_ALERT_SET_SIGNUP_CHECKER_DEDUPE_KEY,
        [
            'merchant_id' => $merchantId,
        ]);
    }

    public function isRasSignupFraudMerchant($merchantId): bool
    {
        $mode = $this->mode ??  'live';

        $variant = $this->app->razorx->getTreatment(
            $merchantId,
            Constants::RAS_SIGN_UP_CHECKER_POST_ACTION_FEATURE_FLAG,
            $mode);

        if (strtolower($variant) !== 'ok')
        {
            return false;
        }

        return (empty($this->app['cache']->connection()->hget(
                Constants::REDIS_DEDUPE_SIGNUP_CHECKER_MAP, $merchantId))
                === false);
    }

    public function fetchMappings(): array
    {
        return (new MerchantRiskAlertClient($this->app))->sendRequest(Constants::FETCH_MAPPING_URL, []);
    }
}
