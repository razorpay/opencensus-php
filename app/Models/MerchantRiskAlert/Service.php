<?php

namespace RZP\Models\MerchantRiskAlert;

use Mail;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Services\Stork;
use RZP\Models\Merchant;
use RZP\Models\Admin\Permission;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Merchant\Risk as MerchantRiskEmailer;
use RZP\Models\Workflow\Action\MakerType;
use RZP\Models\Workflow\Action;
use RZP\Models\Admin\Org;
use RZP\Models\Dispute\Phase;
use RZP\Exception\BadRequestValidationFailureException;

// TODO: add traces

class Service extends Base\Service
{
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
            $this->repo->transactionOnLiveAndTest(function() use ($merchant)
            {
                $merchant->holdFunds();

                $this->repo->saveOrFail($merchant);
            });
        }

        $this->sendNotifications($merchant);
    }

    public function getMerchantDetails(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setOrgId(Org\Entity::RAZORPAY_ORG_ID);

        $workflowActions = (new Action\Core)->fetchOpenActionOnEntityOperation(
            $merchant->getId(), Constants::MERCHANT_DETAIL_KEY, Permission\Name::MERCHANT_RISK_ALERT_FOH);

        $details = [
            Constants::MERCHANT_FOH_KEY          => $merchant->isFundsOnHold(),
            Constants::MERCHANT_FOH_WORKFLOW_KEY => $workflowActions->isNotEmpty() === true,
            Constants::MERCHANT_CREATED_AT       => $merchant->getCreatedAt(),
            Constants::MERCHANT_HAS_AOV          => false,
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

        if ($workflowActions->isNotEmpty() === true)
        {
            return;
        }

        $workflowTags = $this->getWorkflowTagsFromInput($input);

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

        $workflowTags = $this->getWorkflowTagsFromInput($input);

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

    private function sendNotifications(Merchant\Entity $merchant)
    {
        $this->sendSms($merchant);

        $this->sendWhatsappMessage($merchant);

        $this->sendEmail($merchant);
    }

    private function getMaker()
    {
        // NOTE: maker_email (both maker and checker) should be superadmin
        $makerEmail = $this->app['config']->get('applications.merchant_risk_alerts.maker_email');

        if (empty($makerEmail) === true)
        {
            throw new Exception\LogicException('Merchant Risk Alert Workflow Maker is not initialized');
        }

        $maker = $this->repo->admin->findByEmail($makerEmail);

        return $maker;
    }

    private function sendSms(Merchant\Entity $merchant)
    {
        $receiver = $merchant->merchantDetail->getContactMobile();

        if (empty($receiver) === true)
        {
            return;
        }

        $payload = [
            'receiver' => $receiver,
            'template' => Constants::FOH_SMS_TEMPLATE,
            'source'   => 'api.merchant.risk.alert',
            'params'   => [
                'merchantId'   => $merchant->getId(),
                'merchantName' => $merchant->getName(),
            ]
        ];

        try {
            $this->app['raven']->sendSms($payload);

            $this->app['trace']->info(
                TraceCode::MERCHANT_RISK_ALERT_FOH_SMS_SENT,
                [
                    'merchant_id' => $merchant->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->traceException($e,
                Trace::CRITICAL,
                TraceCode::MERCHANT_RISK_ALERT_FOH_SMS_FAILED,
                [
                    'merchant_id' => $merchant->getId(),
                ]
            );
        }
    }

    private function sendWhatsappMessage(Merchant\Entity $merchant)
    {
        $mode = $this->app['rzp.mode'];

        $receiver = $merchant->merchantDetail->getContactMobile();

        $whatsAppPayload = [
            'ownerId'       => $merchant->getId(),
            'ownerType'     => 'merchant',
            'template_name' => Constants::FOH_WHATSAPP_TEMPLATE_NAME,
            'params'        => [
                'merchantId'   => $merchant->getId(),
                'merchantName' => $merchant->getName(),
            ],
        ];

        (new Stork)->sendWhatsappMessage(
            $mode,
            Constants::FOH_WHATSAPP_TEMPLATE,
            $receiver,
            $whatsAppPayload
        );
    }

    private function sendEmail(Merchant\Entity $merchant)
    {
        $data = [
            'merchant' => [
                'id'    => $merchant->getId(),
                'name'  => $merchant->getName(),
                'email' => $merchant->getEmail(),
            ],
        ];

        Mail::queue(new MerchantRiskEmailer\AlertFundsOnHold($data));

        $this->app['trace']->info(
            TraceCode::MERCHANT_RISK_ALERT_FOH_EMAIL_TRIGGERED,
            [
                'merchant_id' => $merchant->getId(),
            ]);
    }

    public function handleOnRejectWorkflowAction(Action\Entity $action)
    {
        $merchantId = $action->getEntityId();

        $this->app['merchant_risk_alerts']->notifyNonRiskyMerchant($merchantId);
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
}
