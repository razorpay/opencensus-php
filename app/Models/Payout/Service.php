<?php

namespace RZP\Models\Payout;

use App;
use Mail;
use Carbon\Carbon;
use RZP\Constants\Product;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\PayoutOutbox\RequestType;
use RZP\Models\User;
use RZP\Models\Card;
use RZP\Models\Admin;
use RZP\Models\Batch;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Pricing;
use RZP\Models\Reversal;
use RZP\Models\PayoutOutbox;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Models\Admin\Org;
use RZP\Traits\TrimSpace;
use RZP\Models\FundAccount;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Services\PayoutService;
use RZP\Models\Admin\Permission;
use RZP\Exception\LogicException;
use RZP\Exception\DbQueryException;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankingAccountService;
use RZP\Mail\Payout\PendingApprovals;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payout\Batch as PayoutsBatch;
use RZP\Models\Feature\Constants as Features;
use RZP\Jobs\PayoutPostCreateProcessLowPriority;
use RZP\Models\Application\ApplicationMerchantMaps;
use RZP\Models\PayoutsStatusDetails\StatusReasonMap;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Payout\BatchHelper as PayoutBatchHelper;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;
use RZP\Models\FundAccount\Service as FundAccountService;
use RZP\Services\RazorpayLabs\SlackApp as SlackAppService;
use RZP\Models\FundAccount\BatchHelper as FundAccountHelper;
use RZP\Models\FundAccount\Validation as FundAccountValidation;
use RZP\Models\Workflow\Service\Config\Service as WorkflowConfigService;

class Service extends Base\Service
{
    use TrimSpace;
    use Base\Traits\ProcessAccountNumber;

    /**
     * @var FundAccountService
     */
    protected $fundAccountService;

    /**
     * @var Contact\Core
     */
    protected $contactCore;

    /**
     * @var ApplicationMerchantMaps\Core
     */
    protected $appframeworkCore;

    protected $slackAppService;

    protected $workflowMigration;

    protected $workflowConfigService;

    protected const IS_VALID_PURPOSE = "is_valid_purpose";

    protected const PAYOUTS_ON_HOLD_SLA_SETTINGS_KEY = "payouts_on_hold_sla";

    protected const ON_HOLD_FETCH_LIMIT = 5000;

    protected const PAYOUT_NOTIFICATION_COUNT = 5;

    protected $compositePayoutSaveOrFail = true;

    /**
     * @var PayoutService\OnHoldCron
     */
    protected $payoutServiceOnHoldCronClient;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payout\Core;

        $this->contactCore = new Contact\Core;

        $this->fundAccountService = new FundAccountService;

        $this->appframeworkCore = new ApplicationMerchantMaps\Core;

        $this->workflowConfigService = new WorkflowConfigService;

        $this->workflowMigration = new WorkflowMigration();

        $this->slackAppService = new SlackAppService($this->app);

        $this->payoutServiceOnHoldCronClient = $this->app[PayoutService\OnHoldCron::PAYOUT_SERVICE_ON_HOLD_CRON];
    }

    public function createPayoutEntry($input)
    {
        return $this->core->createPayoutEntry($input);
    }

    public function createWorkflowForPayout($input)
    {
        return $this->core->createWorkflowForPayout($input);
    }

    public function createFTAForPayoutService(string $payoutId)
    {
        return $this->core->createFTAForPayoutService($payoutId);
    }

    public function createPayoutServiceTransaction(array $input)
    {
        return $this->core->createPayoutServiceTransaction($input);
    }

    public function fundAccountPayoutOnInternalContact(array $input): array
    {
        // check that the auth in internal
        if ($this->auth->isPrivilegeAuth() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        // check that the contact is internal type
        if (key_exists(Entity::FUND_ACCOUNT_ID, $input) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_ID_IS_REQUIRED,
                null,
                $input
            );
        }

        $contact = $this->repo->fund_account->findByPublicId($input[Entity::FUND_ACCOUNT_ID])->contact;

        if (Contact\Type::isInInternal($contact->getType()) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_ONLY_INTERNAL_CONTACT_PERMITTED,
                null,
                $input
            );
        }

        Contact\Type::validateInternalAppAllowedCreatingPayoutsOnType($contact->getType(),
                                                                      $this->auth->getInternalApp());

        return $this->fundAccountPayout($input, true);
    }

    public function validatePayout(array $input): array
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        (new User\Core)->verifyOtp($input + ['action' => 'create_payout'],
            $this->merchant,
            $this->user,
            $this->mode === Constants\Mode::TEST);

        $payoutInput = array_except($input, ['otp', 'token']);

        // Only allowed for Rx payouts, mandates account number
        $this->processAccountNumber($payoutInput);

        (new Validator)->setStrictFalse()
            ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT_WITH_OTP, $input);

        return ['OK'];
    }

    public function fundAccountPayout(array $input, bool $internal = false): array
    {
        $payoutInput = $input;

        $requestTime = microtime(true);

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_REQUEST,
            [
                'input' => $input,
                'time'  => $requestTime
            ]);

        // Only allow access over strictly private auth, for proxy auth: OTP auth flow is mandated.
        if ($this->auth->isStrictPrivateAuth() === false and
            ($this->isAllowedInternalApp() === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        // Only allowed for Rx payouts, mandates account number
        // TODO: Cache the Balance ID
        $balance = $this->processAccountNumber($input);

        (new Validator)->setStrictFalse()
                       ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

        (new Validator)->validateAndUpdateCardMode($input);

        $isCompositePayout = false;

        if (isset($input[Entity::FUND_ACCOUNT]) === true)
        {
            $isCompositePayout = true;
        }

        $this->checkIfPayoutIsAllowed($isCompositePayout, $input);

        if ($isCompositePayout === true)
        {
            $startTime = microtime(true);

            $newFlowFlag = $this->merchant->isFeatureEnabled(Features::HIGH_TPS_COMPOSITE_PAYOUT);

            $asyncIngressFlag = $this->merchant->isFeatureEnabled(Features::PAYOUT_ASYNC_INGRESS);

            $this->compositePayoutSaveOrFail = !$asyncIngressFlag;

            $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
                'step'       => 'feature_enabled_check',
                'time_taken' => (microtime(true) - $startTime) * 1000,
            ]);

            if ($newFlowFlag === true)
            {
                if ($balance->isAccountTypeDirect() === true)
                {
                    throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, [
                        Payout\Entity::BALANCE_ID             => $balance->getId(),
                        Merchant\Balance\Entity::ACCOUNT_TYPE => $balance->getAccountType()
                    ],
                                                            'High tps not supported for direct accounts');
                }

                [$payout, $contact, $fundAccount] = $this->newCompositePayoutFlow($input, $balance);

                $compositePayoutResponse = $this->postCreationProcessingForCompositePayout($payout);

                $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
                    'step'       => 'entire_composite_flow',
                    'time_taken' => (microtime(true) - $startTime) * 1000,
                ]);

                $response = $compositePayoutResponse->toArrayPublic();

                if ($this->compositePayoutSaveOrFail === false)
                {
                    $metadata = [
                        Entity::PAYOUT => [
                            Entity::ID         => $payout->getId(),
                            Entity::CREATED_AT => $payout->getCreatedAt()
                        ],
                        Entity::CONTACT      => [
                            Entity::ID         => $contact->getId(),
                            Entity::CREATED_AT => $contact->getCreatedAt()
                        ],
                        Entity::FUND_ACCOUNT => [
                            Entity::ID         => $fundAccount->getId(),
                            Entity::CREATED_AT => $fundAccount->getCreatedAt()
                        ]
                    ];

                    PayoutPostCreateProcessLowPriority::dispatch($this->mode,
                                                                 $payout->getId(),
                                                                 $payout->toBeQueued(),
                                                                 $metadata,
                                                                 $payout->getMerchantId(),
                                                                 $payoutInput);

                    $this->trace->info(
                        TraceCode::PAYOUT_CREATE_SUBMITTED_REQUEST_ENQUEUED_LOW_PRIORITY,
                        [
                            'payout_id'         => $payout->getId(),
                            'metadata'          => $metadata,
                            Entity::MERCHANT_ID => $payout->getMerchantId()
                        ]);

                    $response[Entity::FUND_ACCOUNT][Entity::CONTACT] = $contact->toArrayPublic();
                }

                return $response;
            }

            $input = $this->createContactAndFundAccountAndGetPayoutInputForCompositeRequest($input);
        }

        $payout = $this->core->createPayoutToFundAccount($input, $this->merchant, null, $internal);

        if ($isCompositePayout === true)
        {
            $payout = $this->postCreationProcessingForCompositePayout($payout);
        }

        $responseTime = microtime(true);

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_RESPONSE,
            [
                'input'         => $input,
                'payout_id'     => $payout->getId(),
                'is_composite'  => $isCompositePayout,
                'time'          => $responseTime,
                'response_time' => $responseTime - $requestTime
            ]);

        return $payout->toArrayPublic();
    }



    public function fundAccountCompositePayoutForHighTpsMerchants(array $input,
                                                                  string $merchantId,
                                                                  array $metadata = [])
    {
        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $startTime = microtime(true);

        // Only allowed for Rx payouts, mandates account number
        // TODO: Cache the Balance ID

        $input[Entity::MERCHANT_ID] = $merchantId;
        $balance = $this->processAccountNumber($input);

        $this->compositePayoutSaveOrFail = true;

        [$payout, $contact, $fundAccount]  = $this->newCompositePayoutFlow($input, $balance, $metadata);

        $compositePayoutResponse = $this->postCreationProcessingForCompositePayout($payout);

        $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
            'step'       => 'entire_composite_flow',
            'time_taken' => (microtime(true) - $startTime) * 1000,
        ]);

        return $compositePayoutResponse;
    }

    public function isAllowedInternalApp(): bool
    {
        return $this->auth->isPayoutLinkApp() or
               $this->auth->isVendorPaymentApp() or
               $this->auth->isSettlementsApp() or
               $this->auth->isXPayrollApp() or
               $this->auth->isScroogeApp() or
               $this->auth->isCapitalCollectionsApp();
    }

    public function isSettlementsApp(): bool
    {
        return $this->auth->isSettlementsApp();
    }

    public function isXPayrollApp(): bool
    {
        return $this->auth->isXPayrollApp();
    }

    public function isPayoutLinkApp(): bool
    {
        return $this->auth->isPayoutLinkApp();
    }

    public function isScroogeApp(): bool
    {
        return $this->auth->isScroogeApp();
    }

    public function approveFundAccountPayout(string $id, array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_APPROVE_REQUEST, ['id' => $id, 'input' => $input]);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);

        $payoutValidator =  $payout->getValidator();

        $payoutValidator->validatePayoutStatusForApproveOrReject();

        $payoutValidator->setStrictFalse()->validateInput(Validator::APPROVE_PAYOUT_RULES, $input);

        $this->user->validateInput('verifyOtp', array_only($input, [User\Entity::OTP, User\Entity::TOKEN]));

        (new User\Core)->verifyOtp($input + ['action' => 'approve_payout'], $this->merchant, $this->user);

        $payout = (new Core)->approvePayout($payout, $input);

        return $payout->toArrayPublic();
    }

    public function processActionOnFundAccountPayoutInternal(string $id, bool $approved, array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_ACTION_REQUEST,
            ['id' => $id, 'approved' => $approved, 'input' => $input]);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicId($id);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        $payout = (new Core)->processActionOnPayout($approved, $payout, $input);

        return $payout->toArrayPublic();
    }

    public function sendPendingPayoutApprovalEmails()
    {
        $startAt = millitime();

        $approverList = $this->repo->payout->fetchMerchantUserDataHavingPendingPayouts();

        $this->trace->info(TraceCode::PENDING_APPROVAL_EMAILS_MERCHANT_QUERY_DURATION, [
            'query_execution_time' => millitime() - $startAt,
            'approver_list'         => $approverList
        ]);

        return $this->core->prepareTemplateAndDispatchEmail($approverList);
    }

    public function sendPendingPayoutApprovalReminder()
    {
        $startAt = millitime();

        $approverList = $this->repo->payout->fetchMerchantUserDataHavingPendingPayouts();

        $this->trace->info(TraceCode::PENDING_APPROVAL_REMINDER_MERCHANT_QUERY_DURATION, [
            'query_execution_time' => millitime() - $startAt,
            'approver_list'         => $approverList
        ]);

        return $this->core->getPendingPayoutsDataAndDispatchEvents($approverList);
    }

    public function sendPendingPayoutsNotificationToSlack()
    {
        try
        {
            $slackAppSubscribedMerchants = $this->slackAppService->getSubscribedMerchantList()['data'];

            $input = [
                'count' => self::PAYOUT_NOTIFICATION_COUNT,
                'skip'  => 0,
                Entity::STATUS => Status::PENDING,
                Entity::PENDING_ON_ROLES => [ User\BankingRole::OWNER ],
                'product' => Constants\Product::BANKING,
                'expand'  => ['fund_account.contact' ,'user']
            ];

            foreach ($slackAppSubscribedMerchants as $merchantData)
            {
                // Here slack app send merchant id with acc_ prefix, stripping that
                $merchantId = substr($merchantData['merchant_id'],4);

                try
                {
                    $this->merchant = $this->repo->merchant->findOrFailPublic($merchantId);

                    $this->app['basicauth']->setMerchant($this->merchant);

                    $this->repo->payout->setMerchant($this->merchant);

                    $payouts = $this->fetchMultiple($input)['items'];

                    if(count($payouts) !== 0)
                    {
                        $payload = [
                            'merchant_id' => $merchantId,
                            'payouts'     => $payouts,
                            'count'       => count($payouts)
                        ];

                        $this->slackAppService->sendPendingPayoutNotificationRequestToSlack($payload);
                    }
                }
                catch (\Exception $exception)
                {
                    $this->trace->info(
                        TraceCode::PENDING_PAYOUT_NOTIFICATION_TO_SLACK_APP_FAILED,
                        [
                            'exception' => $exception->getMessage(),
                        ]
                    );
                }
            }
        }
        catch (\Requests_Exception $exception)
        {
            $this->trace->info(
                TraceCode::PENDING_PAYOUT_NOTIFICATION_TO_SLACK_APP_FAILED,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    public function bulkApproveFundAccountPayouts(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_BULK_APPROVE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('bulk_approve', $input);

        $this->user->validateInput('verify_otp', array_only($input, [User\Entity::OTP, User\Entity::TOKEN]));

        (new User\Core)->verifyOtp($input + ['action' => 'approve_payout_bulk'], $this->merchant, $this->user);

        $payouts = $this->repo->payout->findManyByPublicIdsAndMerchant($input[Entity::PAYOUT_IDS], $this->merchant);

        $totalCount = count($input[Entity::PAYOUT_IDS]);

        unset($input[Entity::PAYOUT_IDS]);

        foreach ($payouts as $payout)
        {
            $payout->getValidator()->validatePayoutStatusForApproveOrReject();
        }

        $failedIds = [];

        foreach ($payouts as $payout)
        {
            try
            {
                (new Core)->approvePayout($payout, $input);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_APPROVE_REJECT_EXCEPTION,
                    ['payout_id' => $payout->getId()]);

                $failedIds[] = $payout->getId();
            }
        }

        return [
            'total_count' => $totalCount,
            'failed_ids'  => $failedIds,
        ];
    }

    public function rejectFundAccountPayout(string $id, array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_REJECT_REQUEST, ['id' => $id]);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        $payout = (new Core)->rejectPayout($payout, $input);

        return $payout->toArrayPublic();
    }

    public function bulkRejectFundAccountPayout(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_BULK_REJECT_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('bulk_reject', $input);

        // Since this route can be used byb admins to reject payouts, we
        // will avoid searching w.r.t merchant in this case
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            /** @var Entity $payout */
            $payouts = $this->repo->payout->findManyByPublicIds($input[Entity::PAYOUT_IDS]);
        }
        else
        {
            /** @var Entity $payout */
            $payouts = $this->repo->payout->findManyByPublicIdsAndMerchant($input[Entity::PAYOUT_IDS], $this->merchant);
        }

        foreach ($payouts as $payout)
        {
            $payout->getValidator()->validatePayoutStatusForApproveOrReject();
        }

        $failedIds = [];
        $processedIds = [];

        foreach ($payouts as $payout)
        {
            try
            {
                $payout = (new Core)->rejectPayout($payout, $input);

                $processedIds[] = $payout->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_APPROVE_REJECT_EXCEPTION,
                    [
                        'payout_id'         => $payout->getId(),
                        'failure_reason'    => $e->getMessage(),
                    ]);

                $failedIds[] = ["{$payout->getPublicId()} - {$e->getMessage()}"];
            }
        }

        return [
            'total_count'   => count($payouts),
            'processed_ids' => $processedIds,
            'failed_ids'    => $failedIds,
        ];
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\BadRequestException
     */
    public function bulkRetryWorkflowOnPayout(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_BULK_RETRY_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('bulk_retry_workflow', $input);

        if ($this->app['basicauth']->isAdminAuth() !== true)
        {
            throw new Exception\BadRequestValidationFailureException('route can be accessed by admins only');
        }

        /** @var Entity $payout */
        $payouts = $this->repo->payout->findManyByPublicIds($input[Entity::PAYOUT_IDS]);

        $failedIds = [];
        $processedIds = [];

        foreach ($payouts as $payout)
        {
            try
            {
                $payout->getValidator()->validatePayoutStatusForApproveOrReject();

                (new Core)->retryPayoutWorkflow($payout, $input);

                $processedIds[] = $payout->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_WORKFLOW_SERVICE_WORKFLOW_CREATE_RETRY_FAILED,
                    ['payout_id' => $payout->getId()]);

                $failedIds[] = [$payout->getId() . ' -> ' . $e->getMessage()];
            }
        }

        return [
            'total_count'   => count($payouts),
            'processed_ids' => $processedIds,
            'failed_ids'    => $failedIds,
        ];
    }

    /**
     * Business banking: Forwards request to `fundAccountPayout()` after verifying user's otp for the action.
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fundAccountPayoutWithOtp(array $input): array
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        (new User\Core)->verifyOtp($input + ['action' => 'create_payout'],
                                   $this->merchant,
                                   $this->user,
                             $this->mode === Constants\Mode::TEST);

        $payoutInput = array_except($input, ['otp', 'token']);

        // Only allowed for Rx payouts, mandates account number
        $this->processAccountNumber($payoutInput);

        (new Validator)->setStrictFalse()
                       ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT_WITH_OTP, $input);

        if (isset($payoutInput[Entity::ORIGIN]) === false)
        {
            $payoutInput[Entity::ORIGIN] = Entity::DASHBOARD;
        }

        /*
         * If Undo payout feature is enabled for the merchant, then create payout in pending state else go with core payout create flow
        */

        if ($this->shouldCreateUndoablePayout()) {
            $payoutOutboxInput = $this->prepareInputForPayoutOutbox($payoutInput);

            $outboxPayout = (new PayoutOutbox\Core())->create($payoutOutboxInput);

            $outboxPayout[PayoutOutbox\Entity::STATUS] = Status::PENDING_ON_CONFIRMATION;

            return $outboxPayout->toArrayPublic();
        } else {
            $payout = $this->core->createPayoutToFundAccount($payoutInput, $this->merchant);

            return $payout->toArrayPublic();
        }
    }

    private function shouldCreateUndoablePayout()
    {
        $undoPayoutExperimentVariant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::RX_UNDO_PAYOUTS_FEATURE,
            Constants\Mode::LIVE);

        $isUndoPayoutPreferenceEnabled = $this->fetchUserPreferenceForUndoPayouts();

        return ((strtolower($undoPayoutExperimentVariant) === 'on') && ($isUndoPayoutPreferenceEnabled));
    }

    private function fetchUserPreferenceForUndoPayouts() {
        try {
            // Fetch merchant preferences for undo payouts
            $merchantPreferences  = (new Merchant\Attribute\Core())->fetchKeyValuesByMerchantId(
                $this->merchant->getId(),
                Product::BANKING,
                Merchant\Attribute\Group::X_MERCHANT_PREFERENCES,
                [Merchant\Attribute\Type::UNDO_PAYOUTS]
            )->toArrayPublic();

        } catch (\Exception $e) {
            $merchantPreferences = [];
        }

        // If merchant has no preference configured then return true (default
        if (sizeof($merchantPreferences) === 0 || sizeof($merchantPreferences['items']) === 0)
        {
            return true;
        }

        return $merchantPreferences['items'][0]['value'] === 'true';
    }

    private function prepareInputForPayoutOutbox(array $input): array {
        $payoutOutboxInput[PayoutOutbox\Entity::PAYOUT_DATA] = json_encode($input);
        $payoutOutboxInput[PayoutOutbox\Entity::MERCHANT_ID] = $this->merchant['id'];
        $payoutOutboxInput[PayoutOutbox\Entity::USER_ID] = $this->user['id'];
        $payoutOutboxInput[PayoutOutbox\Entity::SOURCE] = Entity::DASHBOARD;
        $payoutOutboxInput[PayoutOutbox\Entity::PRODUCT] = $this->auth->getProduct();
        $payoutOutboxInput[PayoutOutbox\Entity::REQUEST_TYPE] = RequestType::PAYOUTS;
        return $payoutOutboxInput;
    }

    public function internalMerchantPayout(array $input): array
    {
        $merchantId = $input[Entity::MERCHANT_ID] ?? null;

        if (is_string($merchantId) === false)
        {
            throw new Exception\BadRequestValidationFailureException('merchant_id is mandatory for the payout');
        }

        (new Validator)->validateInput('merchant', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $payout = $this->core->createPayoutToMerchant($input, $merchant);

        return $payout->toArrayPublic();
    }

    public function merchantPayoutOnDemand(array $input)
    {
        (new Validator)->validateInput('merchant_payout_on_demand', $input);

        // Here value true specifies payout on demand mode enabled
        $input[Entity::TYPE] = Entity::ON_DEMAND;

        $payout = (new Payout\Core)->createPayoutToMerchant($input, $this->merchant);

        return $payout->toArrayPublic();
    }

    public function calculateEsOnDemandFees(array $input)
    {
        return (new Payout\Core)->calculateEsOnDemandFees($input, $this->merchant);
    }

    public function fetch(string $id, array $input): array
    {
        if ($this->app['basicauth']->authCreds->checkIfOrgAxisCC() === true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_AXIS_CC_GET_REQUEST,
                [
                    'input' => $input,
                    'id' => $id,

                ]);

            $payout = $this->core->fetchFromPayoutsService($id, $this->merchant);

            return $payout;
        }

        // currently keeping this feature under razorx
        if ($this->shouldSkipPayrollEntries())
        {
            $input[Entity::SOURCE_TYPE_EXCLUDE] = PayoutSourceEntity::XPAYROLL;
        }

        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant, $input);

        //tracking slack app related events
        $this->trackPayoutsFetchEvent($input, $payout);

        return $payout->toArrayPublic();
    }

    /**
     * @param array $input
     * @return array
     */
    public function fetchMultiple(array $input): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateToAccountNumberForBankingIfApplicable($input);

        $useMasterConnection = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::USE_MASTER_DB_CONNECTION]);

        if (empty($useMasterConnection) == true)
        {
            $useMasterConnection = false;
        }

        // This is a temporary solution to hide junk data from X Demo accounts.
        if ($this->merchant->isXDemoAccount())
        {
            $prevDt = isset($input['from']) ? (int)$input['from'] : 0;

            $maxFrom = max($prevDt, Carbon::now(Timezone::IST)->timestamp - Constants\BankingDemo::MAX_TIME_DURATION);

            $input['from'] = (string)$maxFrom;
        }

        // currently keeping this feature under razorx
        if ($this->shouldSkipPayrollEntries())
        {
            $input[Entity::SOURCE_TYPE_EXCLUDE] = PayoutSourceEntity::XPAYROLL;
        }

        $payouts = $this->repo->payout->fetchMultiple($input, $this->merchant->getId(), $useMasterConnection);

        // Since pending payouts can be on both the api workflow system and workflow service
        // therefore we need to fetch and merge payouts from both systems
        $payoutsArr = $this->mergePendingPayoutsViaWorkflowService($input, $payouts);

        if (empty($payoutsArr) == true) {
            $this->trace->info(
                TraceCode::PAYOUT_GET_EMPTY_RESPONSE,
                [
                    Entity::MERCHANT_ID => $this->merchant->getId(),
                    'is_reference_id_present' => array_key_exists(Entity::REFERENCE_ID, $input),
                    'useMasterConnection' => $useMasterConnection
                ]);
        }

        //tracking slack app related events
        $this->trackPayoutsFetchEvent($input);

        return $payoutsArr;
    }

    public function shouldSkipPayrollEntries()
    {
        $skipPayrollPayoutsExperimentVariant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::RX_SKIP_PAYROLL_PAYOUTS,
            $this->mode);

        return strtolower($skipPayrollPayoutsExperimentVariant) === 'on';
    }

    public function processReversedPayout(string $id)
    {
        $payout = $this->repo->payout->findByPublicId($id);

        $newPayout = (new Core)->retryReversedPayout($payout);

        return $newPayout->toArrayPublic();
    }

    public function processInitiateForScheduledPayouts($input)
    {
        (new Validator)->validateInput(Validator::PROCESS_SCHEDULED_PAYOUTS, $input);

        $balanceIdsWhitelist = $input[Entity::BALANCE_IDS] ?? [];
        $balanceIdsBlacklist = $input[Entity::BALANCE_IDS_NOT] ?? [];

        $scheduledPayoutList = $this->repo->payout->getScheduledPayoutsToBeProcessed($balanceIdsWhitelist,
                                                                                     $balanceIdsBlacklist);

        return $this->core->processDispatchForScheduledPayouts($scheduledPayoutList);
    }

    public function getPurposes(): array
    {
        return (new Purpose)->getAll($this->merchant);
    }

    public function getPurposesInternal($merchantId): array
    {
        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $allCustomPurposes = (new Purpose)->getCustom($merchant);

        $purposes = new PublicCollection;

        foreach ($allCustomPurposes as $purpose => $type)
        {
            $purposes->push([
                                Entity::PURPOSE      => $purpose,
                                Entity::PURPOSE_TYPE => $type,
                            ]);
        }

        return $purposes->toArrayWithItems();
    }

    public function getOnHoldMerchantSlas(array $input): array
    {
        $this->trace->info(TraceCode::ON_HOLD_MERCHANT_SLAS_INTERNAL_REQUEST, $input);

        $merchantIds = array_pull($input, 'merchant_ids', null);

        if ($merchantIds === null)
        {
            return [];
        }

        $merchantSlaConfigList = (new Admin\Service)->getConfigKey([
            'key' => Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA
        ]);

        $this->trace->info(TraceCode::ON_HOLD_MERCHANT_SLAS_REDIS_RESPONSE, $merchantSlaConfigList);

        $result = [];

        foreach ($merchantIds as $merchantId)
        {
            if (in_array($merchantId, array_keys($merchantSlaConfigList), true) === true)
            {
                $result['merchant_slas'][$merchantId] = $merchantSlaConfigList[$merchantId];
            }
            else
            {
                $result['merchant_slas'][$merchantId] = 0;
            }
        }

        $this->trace->info(TraceCode::ON_HOLD_MERCHANT_SLAS_INTERNAL_RESPONSE, $result);

        return $result;
    }

    public function validatePurpose(array $input): array
    {
        try
        {
            (new Validator)->validateInput(Validator::VALIDATE_PAYOUT_PURPOSE, $input);

            $this->trace->info(TraceCode::PAYOUT_PURPOSE_VALIDATE_REQUEST, [
                Entity::PURPOSE         => $input[Entity::PURPOSE],
                Entity::MERCHANT_ID     => $this->merchant->getPublicId()
            ]);

            (new Purpose())->validatePurpose($this->merchant, $input[Entity::PURPOSE]);
        }
        catch (\Exception $e)
        {
            $this->trace->warning(
                TraceCode::PAYOUT_PURPOSE_VALIDATE_EXCPETION,
                [
                    Entity::PURPOSE         => $input[Entity::PURPOSE],
                    Entity::MERCHANT_ID     => $this->merchant->getPublicId()
                ]
            );

            return array(self::IS_VALID_PURPOSE => false);
        }

        return array(self::IS_VALID_PURPOSE => true);
    }

    public function postPurpose(array $input): array
    {
        (new Validator)->validateInput('create_purpose', $input);

        $purposeObj = new Purpose;

        $purposeObj->addNewCustom($input[Entity::PURPOSE], $input[Entity::PURPOSE_TYPE], $this->merchant);

        return $purposeObj->getAll($this->merchant);
    }

    public function postBulkPurpose(string $merchantId, array $input): array
    {
        $count = count($input);

        (new Validator) -> validatebulkPurposeCreation($count);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $purposeObj = new Purpose;

        $purposeObj->addNewBulkCustom($input, $merchant);

        return $purposeObj->getAll($merchant);
    }

    public function fetchReversalOfPayout(string $id): array
    {
        $merchantId = $this->merchant->getId();

        $input = [
            Reversal\Entity::ENTITY_ID      => Entity::verifyIdAndStripSign($id),
            Reversal\Entity::ENTITY_TYPE    => Constants\Entity::PAYOUT
        ];

        $reversals = $this->repo->reversal->fetch($input, $merchantId);

        if ($reversals->count() > 0)
        {
            return $reversals->first()->toArrayPublic();
        }

        return $reversals->toArrayPublic();
    }

    public function migrateOldConfigToNewOnes($input)
    {
        $merchantIds = $input['merchant_ids'];
        $skipFetchFromWfs = $input['skip_wfs_fetch'] ?? true;
        $returnOld = $input['return_old'] ?? false;
        $success = [];
        $failed = [];

        foreach ($merchantIds as $merchantId)
        {
            $this->merchant = $this->repo->merchant->findOrFailPublic($merchantId);

            $this->app['basicauth']->setMerchant($this->merchant);

            $newConfig = (new WorkflowMigration())->convertOldSummaryIntoNew($this->merchant, $skipFetchFromWfs, $returnOld);

            try
            {
                $config = $this->workflowConfigService->create($newConfig);
                $success[] = $merchantId . ' - ' . $config['id'];
            }
            catch (\Throwable $e)
            {
                $failed[] = $merchantId;
                $this->trace->traceException($e);
            }
        }

        return [
            'failed'  => $failed,
            'success' => $success,
        ];
    }

    /**
     * Return a summary of workflows for RazorpayX dashboard consumption.
     *
     * Works ONLY for create_payout workflows right now.
     *
     * @return array
     * @throws \Exception
     */

    public function getWorkflowSummary()
    {
        // For test mode, we haven't enabled workflows yet
        // therefore returning empty array
        if ($this->mode === Constants\Mode::TEST)
        {
            return [];
        }

        return $this->core->getFetchWorkflowSummary();
    }

    public function processEventNotificationFromFts(array $input)
    {
        return $this->core->processEventNotificationFromFts($input);
    }

    public function getDashboardSummary(): array
    {
        $queued = $this->getQueuedPayoutsSummary();

        $pending   = [];

        if ($this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS) === true)
        {
            try
            {
                $pending = $this->getPendingPayoutsSummary();
            }
            catch(Exception\UserWorkflowNotApplicableException $exception)
            {
                // If user role is not a workflow role, pending will remain empty
            }
        }

        $scheduled = $this->getScheduledPayoutsSummary();

        $completeSummary = $this->getCompleteSummary($pending, $queued, $scheduled);

        return $completeSummary;
    }

    public function processInitiateForQueuedPayouts(array $input)
    {
        (new Validator)->validateInput(Validator::PROCESS_QUEUED_PAYOUTS_INITIATE, $input);

        $balanceIdsWhitelist = $input[Entity::BALANCE_IDS] ?? [];
        $balanceIdsBlacklist = $input[Entity::BALANCE_IDS_NOT] ?? [];

        $this->trace->info(TraceCode::PAYOUT_QUEUED_PROCESSING_INITIATED, [
            'input'             => $input,
        ]);

        $balanceIds = $this->repo->payout->getBalanceIdsWithAtleastOneQueuedPayout();

        // Filters the balance IDs for balances where balance changed in last 6 hours
        $balanceIdsFilteredOnBalanceUpdate = $this->repo
                                                  ->balance
                                                  ->getBankingBalanceIdsWhereBalanceUpdatedRecently($balanceIds);

        // Filters the balance IDs for balances where gateway balance changed in last 6 hours
        $balanceIdsFilteredOnGatewayBalanceUpdate = $this->repo
                                                         ->banking_account_statement_details
                                                         ->getBalanceIdsWhereGatewayBalanceUpdatedRecently($balanceIds);

        $balanceIdList = array_unique(array_merge($balanceIdsFilteredOnBalanceUpdate,
                                                  $balanceIdsFilteredOnGatewayBalanceUpdate));

        if (empty($balanceIdsWhitelist) === false)
        {
            $balanceIdList = array_values(array_intersect($balanceIdList, $balanceIdsWhitelist));
        }

        if (empty($balanceIdsBlacklist) === false)
        {
            $balanceIdList = array_values(array_diff($balanceIdList, $balanceIdsBlacklist));
        }

        $this->core->dispatchBalanceIdsForQueuedPayoutsToPayoutsService($balanceIdList);

        $this->core->dispatchBalanceIdsForQueuedPayouts($balanceIdList);

        $this->trace->info(TraceCode::PAYOUT_QUEUED_PROCESSING_COMPLETED, [
            'balance_id_list' => $balanceIdList,
        ]);

        return ['balance_id_list' => $balanceIdList];
    }

    /**
     * TODO : Remove this code. Has been kept here for backward compatibility
     *
     * @param array $input
     *
     * @return array
     */
    public function processDispatchForQueuedPayouts(array $input)
    {
        $merchantIdsWhitelist = $input['merchant_ids'] ?? [];
        $merchantIdsBlacklist = $input['merchant_ids_not'] ?? [];

        $queuedPayouts = $this->repo->payout->fetchQueuedPayouts($merchantIdsWhitelist,
                                                                 $merchantIdsBlacklist);

        $summary = $this->core->processDispatchForQueuedPayouts($queuedPayouts);

        return $summary;
    }

    public function processInitiateForBatchSubmittedPayouts(array $input)
    {
        $merchantIds = $this->repo->payout->fetchMIDsWithBatchSubmittedPayouts();

        $this->core->processInitiateForBatchSubmittedPayouts($merchantIds);

        return $merchantIds;
    }

    public function cancelPayout(string $payoutId, $input)
    {
        (new Validator)->validateInput(Validator::CANCEL_PAYOUT, $input);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($payoutId, $this->merchant);

        $remarks = $input[Entity::REMARKS] ?? null;

        $payout = $this->core->cancelPayout($payout, $remarks);

        return $payout->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function createBulkPayout(array $input): array
    {
        $payoutBatch = new Base\PublicCollection;

        $validator = new Validator;

        $validator->validateBulkPayoutCount($input);

        $idempotencyKey = null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        $validator->validateBatchId($batchId);

        // This used to rely on a razorx experiment but we never ended up using this.
        // As part of code cleanup, we're setting this to default `false`
        $createDuplicate = false;

        $this->trace->info(
            TraceCode::BATCH_SERVICE_PAYOUT_BULK_REQUEST_RAW,
            [
                Entity::BATCH_ID => $batchId,
                'input'          => $input
            ]);

        //This is to create an entry in App Framework Merchant Mapping table, to signify
        //the merchant is using bulk payout feature
//        $this->createAppFrameworkMerchantMapping();

        foreach ($input as $item)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BATCH_SERVICE_PAYOUT_BULK_REQUEST,
                    [
                        Entity::BATCH_ID => $batchId,
                        'input'          => $item
                    ]);

                $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

                $validator->validateIdempotencyKey($idempotencyKey, $batchId);

                $result = $this->repo->payout->fetchByIdempotentKey($item[Entity::IDEMPOTENCY_KEY],
                                                                    $this->merchant->getId(),
                                                                    $batchId);

                if ($result !== null)
                {
                    $this->trace->info(TraceCode::PAYOUT_EXIST_WITH_SAME_IDEMPOTENCY_KEY,
                        [
                            'input' => $result->toArrayPublic(),
                            Entity::IDEMPOTENCY_KEY => $item[Entity::IDEMPOTENCY_KEY],
                        ]);

                    $payoutBatch->push($result->toArrayPublic() +
                        [Entity::IDEMPOTENCY_KEY => $result->getIdempotencyKey()]);
                }
                else
                {
                    $fundAccountId = $item[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

                    $fundAccount = null;

                    //
                    // Check if fund_id is present in input and exists in DB
                    // If yes skip contact and fund_account creation step
                    //
                    if (empty($fundAccountId) === false)
                    {
                        $fundAccount = $this->fundAccountService->checkFundAccountExistence($fundAccountId);
                    }
                    else
                    {
                        $contact = $this->contactCore->processEntryForContact($item, $batchId, $createDuplicate);

                        $fundAccount = $this->fundAccountService->createFundAcccount($item,
                            $contact,
                            $batchId,
                            $createDuplicate);
                    }

                    $payout = $this->processEntryForPayoutForFundAccount($item,
                        $fundAccount,
                        $batchId
                    );

                    $payoutArr = $payout->toArrayPublic() + [Entity::IDEMPOTENCY_KEY => $idempotencyKey];

                    $payoutBatch->push($payoutArr);
                }
            }
            catch (Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                                             Trace::INFO,
                                             TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                if ($this->merchant->isFeatureEnabled(Features::PAYOUTS_BATCH))
                {
                    (new PayoutsBatch\Core())
                        ->pushWebhookForPayoutCreationFailure($exceptionData, $item, $this->merchant);
                }

                $payoutBatch->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                                             Trace::CRITICAL,
                                             TraceCode::BATCH_SERVICE_BULK_EXCEPTION);

                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $payoutBatch->push($exceptionData);
            }
        }

        $this->trace->info(TraceCode::BATCH_SERVICE_PAYOUT_BULK_RESPONSE, $payoutBatch->toArrayWithItems());

        return $payoutBatch->toArrayWithItems();
    }

    public function createAppFrameworkMerchantMapping()
    {
        $bulkPayoutApp = $this->repo->application->getAppByName(Entity::BULK_PAYOUT_APP);

        if (empty($bulkPayoutApp) === false)
        {
            $input = [
                ApplicationMerchantMaps\Entity::APP_ID      => $bulkPayoutApp['id'],
                ApplicationMerchantMaps\Entity::MERCHANT_ID => $this->merchant->getMerchantId(),
            ];

            $this->appframeworkCore->create($input);
        }
        else
        {
            $this->trace->info(TraceCode::APPLICATION_PAYOUT_BULK_NOT_PRESENT, ['bulk_payout' => Entity::BULK_PAYOUT_APP]);
        }
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestValidationFailureException
     */
    public function approveBulkPayout(array $input): array
    {
        $payoutBatch = new Base\PublicCollection;

        $validator = new Validator;

        // Max is 15
        $validator->validateBulkPayoutCount($input);

        $idempotencyKey = null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);
        // bad request if not from batch service
        $validator->validateBatchId($batchId);

        foreach ($input as $item)
        {
            try
            {
                $this->trace->info(
                    TraceCode::BATCH_SERVICE_PAYOUT_APPROVAL_BULK_RESPONSE,
                    [
                        Entity::BATCH_ID => $batchId,
                        'input'          => $item
                    ]);

                $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

                $this->validateInputFields($item, $validator, $batchId);

                $this->repo->transaction(function() use (
                    & $item,
                    & $payoutBatch,
                    & $batchId)
                {
                    $payout = $this->processEntryForBulkPayoutApproval($item);

                    $payoutArr = $payout->toArrayPublic() + [Entity::IDEMPOTENCY_KEY =>  $item[Entity::IDEMPOTENCY_KEY]];

                    $payoutBatch->push($payoutArr);
                });

            }
            catch (Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                    Trace::INFO,
                    TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $payoutBatch->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                    Trace::CRITICAL,
                    TraceCode::BATCH_SERVICE_BULK_EXCEPTION);

                $exceptionData = [
                    Entity::BATCH_ID        => $batchId,
                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $payoutBatch->push($exceptionData);
            }
        }

        $this->trace->info(TraceCode::BATCH_SERVICE_PAYOUT_APPROVAL_BULK_RESPONSE, $payoutBatch->toArrayWithItems());

        return $payoutBatch->toArrayWithItems();
    }

    /**
     * @param array $entry
     * @return Entity
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function processEntryForBulkPayoutApproval(array $entry): Entity
    {
        $inputPayout = $entry + [Entity::PAYOUT_IDS => [$entry[Entity::PAYOUT][Entity::ID]]];

        $action = strtoupper($entry[BatchHelper::PAYOUT_UPDATE_ACTION]);

        if($action === 'A')
        {
            return $this->approvePayoutsFromBatchService($inputPayout);

        }
        else if ($action === 'R')
        {
            return $this->rejectPayoutFromBatchService($inputPayout);
        }
        else
        {
            throw  new Exception\BadRequestValidationFailureException(
                'Unknown update action '.$action.' found');
        }

    }

    protected function approvePayoutsFromBatchService(array & $input): Entity
    {
        $this->trace->info(TraceCode::PAYOUT_BULK_APPROVE_REQUEST, ['input' => $input]);

        (new Validator)->setStrictFalse()->validateInput('batch_approve', $input);

        $payout = $this->repo->payout->findByPublicId($input[Entity::PAYOUT][Entity::ID]);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        $payout = (new Core)->approvePayout($payout, $input);

        return $payout;

    }

    protected function rejectPayoutFromBatchService(array $input): Entity
    {
        $this->trace->info(TraceCode::PAYOUT_BULK_REJECT_REQUEST, ['input' => $input]);

        (new Validator)->setStrictFalse()->validateInput('batch_reject', $input);

        $payout = $this->repo->payout->findByPublicId($input[Entity::PAYOUT][Entity::ID]);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        $payout = (new Core)->rejectPayout($payout, $input);

        return $payout;
    }

    /**
     * This route has been added to update payout status in test mode
     * Since we don't actually hit the banks in test mode
     * In live mode this is taken care of by FTS
     *
     * @param string $id
     * @param array  $input
     * @return array
     */
    public function updateTestPayoutStatus(string $id, array $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_STATUS_UPDATE_REQUEST,
            [
                'payout_id' => $id,
                'input'     => $input
            ]);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);

        $payout = $this->core->updateTestPayoutStatus($payout, $input);

        return $payout->toArrayPublic();
    }

    public function getSampleFileForBulkPayouts($input)
    {
        (new Validator)->validateInput(Validator::PAYOUT_BULK_SAMPLE_FILE, $input);

        $extension  = $input[Entity::FILE_EXTENSION];
        $type       = $input[Entity::FILE_TYPE];

        if ($type === Entity::SAMPLE_FILE)
        {
            return (new Bulk\SampleFile)->createAndSaveSampleFile($extension, $this->merchant);
        }
        else
        {
            return (new Bulk\TemplateFile)->createAndSaveSampleFile($extension, $this->merchant);
        }
    }

    public function getScheduleSlotsForPayouts()
    {
        return Schedule::getTimeSlotsForScheduledPayouts();
    }

    protected function getQueuedPayoutsSummary()
    {
        $queuedPayoutsSummary = [];

        $merchantId = $this->merchant->getId();

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'going to fetch queued payouts',
            ]);

        $queuedPayouts = $this->repo->payout->fetchQueuedAndOnHoldPayouts($merchantId);

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'completed fetching queued payouts',
            ]);

        $allQueuedReasons = QueuedReasons::QUEUED_REASONS_WITH_DESCRIPTION;

        $groupedQueuedPayouts = $queuedPayouts->groupBy(Entity::BALANCE_ID);

        foreach ($groupedQueuedPayouts as $balanceId => $queuedPayouts)
        {
            $bankingAccountId = (new BankingAccountService\Core())->fetchBankingAccountId($balanceId);

            foreach ($allQueuedReasons as $queuedReason=>$queuedDesc)
            {
                $summaryForQueuedReason = $this->processQueuedSummaryForReason($queuedReason, $queuedPayouts);

                if($summaryForQueuedReason['count'] > 0)
                {
                    $queuedPayoutsSummary[$bankingAccountId][Status::QUEUED][$queuedReason] = $summaryForQueuedReason;
                }
            }
        }

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'queued summary response formed',
            ]);

        return $queuedPayoutsSummary;
    }

    protected function processQueuedSummaryForReason(string $reason, $queuedPayouts)
    {
        $currentBalance = $queuedPayouts->first()->balance->getBalance();

        $queuedPayoutsForReason = $this->filterQueuedPayoutsBasedOnReason($queuedPayouts, $reason);

        $totalAmount = $totalFees = 0;

        foreach ($queuedPayoutsForReason as $payout)
        {
            $totalAmount += $payout->getAmount();

            list($fees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

            $totalFees += $fees;
        }

        return [
            'balance'       => $currentBalance,
            'count'         => count($queuedPayoutsForReason),
            'total_amount'  => $totalAmount,
            'total_fees'    => $totalFees,
        ];
    }

    protected function filterQueuedPayoutsBasedOnReason($queuedPayouts, string $reason)
    {
        return $queuedPayouts->where(Entity::QUEUED_REASON, '=', $reason);
    }

    protected function getScheduledPayoutsSummary()
    {
        $scheduledPayoutsSummary = [];

        $merchantId = $this->merchant->getId();

        $allTimePeriods = Entity::SCHEDULED_PAYOUTS_SUMMARY;

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'going to fetch scheduled payouts from db',
            ]);

        $allScheduledPayouts = $this->repo->payout->fetchScheduledPayouts($merchantId);

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'fetch complete for scheduled payouts from db',
            ]);

        $groupedScheduledPayouts = $allScheduledPayouts->groupBy(Entity::BALANCE_ID);

        foreach ($groupedScheduledPayouts as $balanceId => $scheduledPayouts)
        {
            $bankingAccountId = (new BankingAccountService\Core())->fetchBankingAccountId($balanceId);

            foreach ($allTimePeriods as $timePeriod)
            {
                $summaryForTimePeriod = $this->processScheduledSummaryForTimePeriod($timePeriod, $scheduledPayouts);

                $scheduledPayoutsSummary[$bankingAccountId][Status::SCHEDULED][$timePeriod] = $summaryForTimePeriod;
            }
        }

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'response ready for scheduled payouts',
            ]);

        return $scheduledPayoutsSummary;
    }

    protected function processScheduledSummaryForTimePeriod(string $timePeriod, $scheduledPayouts)
    {
        $currentBalance = $scheduledPayouts->first()->balance->getBalance();

        $scheduledPayoutsForTimePeriod = $this->filterScheduledPayoutsBasedOnTimePeriod($scheduledPayouts, $timePeriod);

        $totalAmount = $totalFees = 0;

        foreach ($scheduledPayoutsForTimePeriod as $payout)
        {
            $totalAmount += $payout->getAmount();

            list($fees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

            $totalFees += $fees;
        }

        return [
            'balance'       => $currentBalance,
            'count'         => count($scheduledPayoutsForTimePeriod),
            'total_amount'  => $totalAmount,
            'total_fees'    => $totalFees,
        ];
    }

    protected function filterScheduledPayoutsBasedOnTimePeriod($scheduledPayouts, string $timePeriod)
    {
        switch ($timePeriod)
        {
            // To understand these timestamps :
            // https://razorpay.slack.com/archives/CQMU9NMNY/p1591695297056900?thread_ts=1591612315.027000&cid=CQMU9NMNY
            case Entity::TODAY:
                $startTime  = Carbon::now(Timezone::IST)->getTimestamp();
                $endTime    = Carbon::now(Timezone::IST)->endOfDay()->getTimestamp();
                break;
            case Entity::NEXT_TWO_DAYS:
                $startTime  = Carbon::now(Timezone::IST)->addDays(1)->startOfDay()->getTimestamp();
                $endTime    = Carbon::now(Timezone::IST)->addDays(2)->endOfDay()->getTimestamp();
                break;
            case Entity::NEXT_WEEK:
                $startTime  = Carbon::now(Timezone::IST)->addDays(1)->startOfDay()->getTimestamp();
                $endTime    = Carbon::now(Timezone::IST)->addDays(7)->endOfDay()->getTimestamp();
                break;
            case Entity::NEXT_MONTH:
                $startTime  = Carbon::now(Timezone::IST)->addDays(1)->startOfDay()->getTimestamp();
                $endTime    = Carbon::now(Timezone::IST)->addDays(30)->endOfDay()->getTimestamp();
                break;
            default:
                return $scheduledPayouts;
        }

        return $scheduledPayouts->where(Entity::SCHEDULED_AT, '>=', $startTime)
                                ->where(Entity::SCHEDULED_AT, '<=', $endTime);
    }


    /**
     * @return array
     * @throws Exception\UserWorkflowNotApplicableException
     */
    protected function getPendingPayoutsSummary()
    {
        $user = $this->auth->getUser();

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'going to fetch pending payouts',
            ]);


        $pending = $this->repo->payout->fetchPayoutsPendingOnUserRole($user, $this->merchant, $this->auth->getUserRole());

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'fetch complete for pending payouts from db',
            ]);

        $groupedPendingPayouts = $pending->groupBy(Entity::BALANCE_ID);

        $pendingPayoutsSummary = [];

        foreach ($groupedPendingPayouts as $balanceId => $payouts)
        {
            $bankingAccountId = (new BankingAccountService\Core())->fetchBankingAccountId($balanceId);

            $amount = 0;

            foreach ($payouts as $payout)
            {
                $amount += $payout->getAmount();
            }

            $pendingPayoutsSummary[$bankingAccountId][Status::PENDING] = [
                'count'         => count($payouts),
                'total_amount'  => $amount
            ];
        }

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'response ready for pending payouts',
            ]);


        return $pendingPayoutsSummary;
    }

    /**
     * @param array $pending
     * @param array $queued
     * @param array $scheduled
     * @return array
     */
    protected function getCompleteSummary(array $pending, array $queued, array $scheduled): array
    {
        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'building complete summary response',
            ]);

        $bankingAccountList = $this->merchant->activeBankingAccounts();

        $completeSummary = [];

        $allBankingAccounts = [];

        foreach ($bankingAccountList as $bankingAccount)
        {
            $allBankingAccounts[$bankingAccount->getPublicId()] = $bankingAccount->balance->getBalance();
        }

        foreach ($allBankingAccounts as $bankingAccountId => $balance)
        {
            $completeSummary[$bankingAccountId]= [
                Status::QUEUED =>   [
                    'balance'       => $balance,
                    'count'         => 0,
                    'total_amount'  => 0,
                    'total_fees'    => 0,
                ],
                Status::PENDING =>  [
                    'count'         => 0,
                    'total_amount'  => 0,
                ],
                Status::SCHEDULED => [
                    Entity::TODAY   => [
                        'balance'       => $balance,
                        'count'         => 0,
                        'total_amount'  => 0,
                        'total_fees'    => 0,
                    ],
                    Entity::NEXT_TWO_DAYS   => [
                        'balance'       => $balance,
                        'count'         => 0,
                        'total_amount'  => 0,
                        'total_fees'    => 0,
                    ],
                    Entity::NEXT_WEEK   => [
                        'balance'       => $balance,
                        'count'         => 0,
                        'total_amount'  => 0,
                        'total_fees'    => 0,
                    ],
                    Entity::NEXT_MONTH   => [
                        'balance'       => $balance,
                        'count'         => 0,
                        'total_amount'  => 0,
                        'total_fees'    => 0,
                    ],
                    Entity::ALL_TIME   => [
                        'balance'       => $balance,
                        'count'         => 0,
                        'total_amount'  => 0,
                        'total_fees'    => 0,
                    ],
                ]
            ];
        }

        foreach ($pending as $bankingAccountId => $pendingSummary)
        {
            $completeSummary[$bankingAccountId] = array_merge($completeSummary[$bankingAccountId], $pendingSummary);
        }

        foreach ($queued as $bankingAccountId => $queuedSummary)
        {
            $completeSummary[$bankingAccountId] = array_merge($completeSummary[$bankingAccountId], $queuedSummary);
        }

        foreach ($scheduled as $bankingAccountId => $scheduledSummary)
        {
            $completeSummary[$bankingAccountId] = array_merge($completeSummary[$bankingAccountId], $scheduledSummary);
        }

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'complete summary response ready',
            ]);

        return $completeSummary;
    }

    protected function processEntryForPayoutForFundAccount(array $entry,
                                                           FundAccount\Entity $fundAccount,
                                                           string $batchId): Entity
    {
        $input = PayoutBatchHelper::getPayoutInput($entry, $fundAccount->toArrayPublic(), $this->merchant);

        return $this->core->createPayoutToFundAccount($input, $this->merchant, $batchId);
    }

    /**
     * Ideally, this function should not be calling service of other entities. But, this function is being
     * written as a wrapper for the merchant. All the calls being made from this function to other services
     * is to be considered as individual calls being made by the merchant.
     * *
     * @param array $input
     *
     * @return array
     */
    protected function createContactAndFundAccountAndGetPayoutInputForCompositeRequest(array $input): array
    {
        $traceRequest = $this->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::PAYOUT_COMPOSITE_CREATE_REQUEST, $traceRequest);

        (new Validator)->validateInput(Validator::FUND_ACCOUNT_PAYOUT_COMPOSITE, $input);

        $contactData = $this->createContactForCompositePayout($input);

        $contactId = $contactData[Contact\Entity::ID];

        $fundAccountData = $this->createFundAccountForCompositePayout($input, $contactId);

        $fundAccountId = $fundAccountData[FundAccount\Entity::ID];

        $payoutInput = $this->getInputForPayoutCreateFromComposite($input, $fundAccountId);

        return $payoutInput;
    }

    // @TODO: refactor this/move it to FundAccount entity.
    protected function unsetSensitiveCardDetails(array $input): array
    {
        $input = $this->trimCardNumberIfRequired($input);

        $fundAccountInput = $input[Entity::FUND_ACCOUNT] ?? [];

        if ((isset($fundAccountInput[FundAccount\Entity::CARD]) === true) and
            (is_array($fundAccountInput[FundAccount\Entity::CARD]) === true))
        {
            if (empty($fundAccountInput[FundAccount\Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::IIN] =
                    substr($fundAccountInput[FundAccount\Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::CVV]);
            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::NUMBER]);
        }

        return $input;
    }

    protected function getInputForContactCreateFromComposite(array $input): array
    {
        return $input[Entity::FUND_ACCOUNT ][Entity::CONTACT];
    }

    protected function getInputForFundAccountCreateFromComposite(array $input, string $contactId): array
    {
        $fundAccountInput = $input[Entity::FUND_ACCOUNT];

        unset($fundAccountInput[Entity::CONTACT]);

        $fundAccountInput[FundAccount\Entity::CONTACT_ID] = $contactId;

        return $fundAccountInput;
    }

    protected function getInputForPayoutCreateFromComposite(array $input, string $fundAccountId): array
    {
        $payoutInput = $input;

        unset($payoutInput[Entity::FUND_ACCOUNT]);

        $payoutInput[Payout\Entity::FUND_ACCOUNT_ID] = $fundAccountId;

        return $payoutInput;
    }

    protected function createContactForCompositePayout(array $input): array
    {
        $contactInput = $this->getInputForContactCreateFromComposite($input);

        $contactResponse = (new Contact\Service)->create($contactInput);

        return $contactResponse[Constants\Entity::CONTACT];
    }

    protected function createFundAccountForCompositePayout(array $input, string $contactId): array
    {
        $fundAccountInput = $this->getInputForFundAccountCreateFromComposite($input, $contactId);

        $fundAccountResponse = (new FundAccount\Service)->create($fundAccountInput);

        return $fundAccountResponse[Constants\Entity::FUND_ACCOUNT]->toArrayPublic();
    }


    protected function trimCardNumberIfRequired(array $input)
    {
        if (isset($input[Entity::FUND_ACCOUNT][Entity::CARD][Entity::NUMBER]) === true)
        {
            $cardNumber = $input[Entity::FUND_ACCOUNT][Entity::CARD][Entity::NUMBER];

            $input[Entity::FUND_ACCOUNT][Entity::CARD][Entity::NUMBER] = ltrim($cardNumber, '0');
        }

        return $input;
    }

    public function updatePayoutStatusManually(string $id, array $input)
    {
        /** @var Entity $payout */
        $payout = $this->repo->payout->findOrFail($id);

        (new Validator)->validateInput(Validator::PAYOUT_STATUS_MANUAL, $input);

        $payout = $this->updatePayoutAndFTAManually($payout, $input);

        return $payout->toArrayPublic();
    }

    /**
     * @param $item
     * @param Validator $validator
     * @param $batchId
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateInputFields($item, Validator $validator, $batchId): void
    {
        $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;
        // fail if idempotency key not present
        $validator->validateIdempotencyKey($idempotencyKey, $batchId);

        $payoutId = $item[Entity::PAYOUT][Entity::ID] ?? null;
        // fail if payout id is not present.
        $validator->validatePayoutId($payoutId);

        $payoutUpdateAction = $item[BatchHelper::PAYOUT_UPDATE_ACTION] ?? null;
        // fail if update action is not present
        $validator->validateUpdateAction($payoutUpdateAction);
    }

    protected function postCreationProcessingForCompositePayout(Entity $compositePayout)
    {
        $this->trace->info(
            TraceCode::COMPOSITE_PAYOUT_CREATED,
            [
                'payout_id'       => $compositePayout->getId(),
                'fund_account_id' => $compositePayout->fundAccount->getId(),
                'contact_id'      => $compositePayout->fundAccount->source->getId(),
            ]);

        // Setting $composite field for payout entity to deny unsetting of fund_account field in a
        // strictPrivateAuth composite payout request
        $compositePayout->setComposite(true);

        if ($this->compositePayoutSaveOrFail === true)
        {
            $compositePayout = $compositePayout->load('fundAccount.contact');
        }
        // Setting $composite field for fund_account entity to deny unsetting of contact field in a
        // strictPrivateAuth composite payout request
        $compositePayout->fundAccount->setComposite(true);

        return $compositePayout;
    }

    public function getFreePayoutsAttributes(string $balanceId)
    {
        Base\UniqueIdEntity::verifyUniqueId($balanceId, true);

        $response = $this->core->getFreePayoutsAttributes($balanceId);

        return $response;
    }

    /**
     * @param array $input
     * @param Base\PublicCollection $payouts
     * @return array
     */
    protected function mergePendingPayoutsViaWorkflowService(array $input, Base\PublicCollection $payouts)
    {
        $pendingPayoutsViaWfs = [];

        if ((isset($input[Payout\Entity::PENDING_ON_ROLES])) ||
            (isset($input[Entity::PENDING_ON_ME])))
        {
            // Here we unset PENDING_ON_ROLES and set PENDING_ON_ROLES_VIA_WFS
            // Then we fetch again, this will use Payout\Fetch and Payout\Repository
            // to fetch the payouts by joining with WFS related tables
            if (isset($input[Payout\Entity::PENDING_ON_ROLES]))
            {
                $input[Entity::PENDING_ON_ROLES_VIA_WFS] = $input[Entity::PENDING_ON_ROLES];
                unset($input[Entity::PENDING_ON_ROLES]);
            }

            if (isset($input[Entity::PENDING_ON_ME]))
            {
                $input[Entity::PENDING_ON_ME_VIA_WFS] = $input[Entity::PENDING_ON_ME];
                unset($input[Entity::PENDING_ON_ME]);
            }

            $pendingPayoutsViaWfs = $this->repo->payout->fetchMultiple($input, $this->merchant->getId(), false);
        }

        $uniquePayouts = [];
        foreach ($pendingPayoutsViaWfs as $pendingPayout)
        {
            if (in_array($pendingPayout->getId(), $uniquePayouts, true) === false)
            {
                $uniquePayouts[] = $pendingPayout->getId();

                $payouts->add($pendingPayout);
            }
        }

        $payoutsArr = $payouts->toArrayPublic();

        $payoutItems = & $payoutsArr['items'];

        // Sort payouts by created_at desc
        usort($payoutItems, function($a, $b)
        {
            return $b[Entity::CREATED_AT] - $a[Entity::CREATED_AT];
        });

        return $payoutsArr;
    }

    public function postBulkPayoutsAmountType(array $input)
    {
        $merchantIds = $input[Entity::MERCHANT_IDS];

        foreach ($merchantIds as $merchantId)
        {
            try
            {
                // Find Or Fail automatically takes care of validation and this is the only validation we need.
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                $this->setAmountTypeForPayouts($merchant, Entity::PAISE);

                $this->trace->info(
                    TraceCode::PAYOUT_BULK_AMOUNT_TYPE_UPDATE_SUCCESSFUL,
                    [
                        'merchant_id' => $merchantId
                    ]);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->error(
                    TraceCode::PAYOUT_BULK_AMOUNT_TYPE_UPDATE_FAILED,
                    [
                        'merchant_id'   => $merchantId,
                        'error'         => $throwable->getMessage(),
                    ]);
            }
        }

        return ['success' => true];
    }

    protected function setAmountTypeForPayouts($merchant, $type)
    {
        $this->getSettingsAccessor($merchant)
             ->upsert(Batch\Constants::TYPE, $type)
             ->save();
    }

    public function getAmountTypeForPayouts($merchant)
    {
        return $this->getSettingsAccessor($merchant)
                    ->get(Batch\Constants::TYPE);
    }

    public function getSettingsAccessor(Merchant\Entity $merchant): Settings\Accessor
    {
        return Settings\Accessor::for($merchant, Settings\Module::PAYOUT_AMOUNT_TYPE, Constants\Mode::LIVE);
    }

    // This function allows update from Paise to Rupees only. There is no way to go back.
    public function updateBulkPayoutsAmountType()
    {
        // There is no input required. We just need the MID.
        $this->setAmountTypeForPayouts($this->merchant, Entity::RUPEES);

        return ['success' => true];
    }

    public function updatePayoutStatusManuallyInBatch(array $input)
    {
        if ($input[Entity::STATUS] === 'fav_failed')
        {
            $FAVService = new FundAccountValidation\Service;

            return $FAVService->manualUpdateFavToFailedState($input[Entity::PAYOUT_IDS]);
        }

        (new Validator)->validateInput(Validator::PAYOUT_BULK_STATUS_UPDATE_MANUAL, $input);

        $payouts = $this->repo->payout->findMany($input[Entity::PAYOUT_IDS]);

        $failedIds = [];
        $processedIds = [];

        foreach ($payouts as $payout)
        {
            try
            {
                $this->updatePayoutAndFTAManually($payout, $input);

                $processedIds[] = $payout->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_BULK_MANUAL_STATUS_UPDATE_EXCEPTION,
                    [
                        'payout_id'         => $payout->getId(),
                        'failure_reason'    => $e->getMessage(),
                    ]);

                $failedIds[] = ["{$payout->getPublicId()} - {$e->getMessage()}"];
            }
        }

        return [
            'total_count'   => count($payouts),
            'processed_ids' => $processedIds,
            'failed_ids'    => $failedIds,
        ];
    }

    protected function updatePayoutAndFTAManually(Entity $payout, array $input) : Entity
    {
        $payout = $this->repo->transaction(
            function() use ($payout, $input)
            {
                /** @var Entity $payout */
                $payout = $this->core->updatePayoutStatusManually($payout, $input);

                $this->core->updateFTAOfPayoutManually($payout, $input);

                return $payout;
            });

        return $payout;
    }

    public function processDispatchForOnHoldPayouts()
    {
        $eventNotificationConfig = (new Admin\Service)->getConfigKey([
            'key' => Admin\ConfigKey::RX_EVENT_NOTIFICAITON_CONFIG_FTS_TO_PAYOUT
        ]);

        $beneBankDownList = array_keys($eventNotificationConfig['BENEFICIARY']);

        $this->trace->info
        (
            TraceCode::BENE_BANK_DOWN_LIST_FOR_ON_HOLD_PAYOUT,
            [
                'bene_banks_down' => $beneBankDownList,
            ]
        );

        $payoutIdsToProcess = $this->repo->payout->getOnHoldPayoutsWithBeneBankUp($beneBankDownList);

        $merchantIdsForAutoCancel = $this->repo->payout->getMerchantIdsWithAtleastOneOnHoldPayout();

        $payoutIdsToFail = [];

        if ($merchantIdsForAutoCancel != null and count($merchantIdsForAutoCancel) > 0)
        {
            $fetchLimitCount = floor(self::ON_HOLD_FETCH_LIMIT / count($merchantIdsForAutoCancel));

            foreach ($merchantIdsForAutoCancel as $merchantId)
            {
                $slaValue = $this->core->getMerchantSlaForOnHoldPayouts($merchantId);

                $payoutIdsToFailForMerchant = $this->repo->payout->getOnHoldPayoutsForMerchantIdForOnHoldAtGreaterThanSla($merchantId, $slaValue, $fetchLimitCount);

                $payoutIdsToFail = array_merge($payoutIdsToFail, $payoutIdsToFailForMerchant);
            }
        }

        $this->trace->info
        (
            TraceCode::PAYOUT_ON_HOLD_TO_BE_DISPATCHED,
            [
                'payout_ids_to_process' => $payoutIdsToProcess,
                'payout_ids_to_auto_cancel' => $payoutIdsToFail,
            ]
        );

        $payoutIdsToProcess = array_unique(array_merge($payoutIdsToProcess, $payoutIdsToFail));

        $this->core->dispatchOnHoldPayouts($payoutIdsToProcess);

        $response =
            [
                'onhold_payout_ids_to_process' => $payoutIdsToProcess,
            ];

        $this->trace->info
        (
            TraceCode::PAYOUT_ON_HOLD_TO_BE_DISPATCHED,
            $response
        );

        try
        {
            $this->payoutServiceOnHoldCronClient->sendOnHoldCronViaMicroservice();
        }
        catch (\Exception $exception)
        {
            $this->trace->info(
                TraceCode::ON_HOLD_CRON_VIA_MICROSERVICE_FAILED,
                [
                    'exception' => $exception->getMessage(),
                ]
            );
        }
        return $response;
    }

    public function processSchedulePayoutOnPayoutService($input)
    {
        (new Validator)->validateInput(Validator::PROCESS_SCHEDULED_PAYOUTS, $input);

        return $this->core->initiateScheduledPayoutsViaPayoutService($input);
    }

    public function retryPayoutsOnPayoutService($input)
    {
        (new Validator)->validateInput(Validator::RETRY_PAYOUTS_ON_SERVICE, $input);

        return $this->core->retryPayoutsOnPayoutService($input);
    }

    /**
     * THIS FUNCTION IS MEANT ONLY FOR HIGH TPS EXTERNAL MERCHANTS.
     *
     * DO NOT!!!! I REPEAT, DO NOT ONBOARD ANY INTERNAL APPS ON THIS CODE.
     *
     * @param array                   $input
     *
     * @param Merchant\Balance\Entity $balance
     * @param array                   $metadata
     *
     * @return array
     */
    protected function newCompositePayoutFlow(array $input, Merchant\Balance\Entity $balance, array $metadata = []): array
    {
        $startTime = microtime(true);

        $input = $this->trimSpaces($input);

        // We figure out the trace input at this place, we shall also pass this around,
        // so that we don't have to redo this process repeatedly for the downstream logs
        $traceData = $this->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::NEW_PAYOUT_COMPOSITE_CREATE_REQUEST, $traceData + ['save_or_fail_flag' => $this->compositePayoutSaveOrFail, 'metadata' => $metadata]);

        // TODO: Update this with a single validator to validate Contact, Fund Account and Payout data at once
        (new Validator)->validateInput(Validator::FUND_ACCOUNT_PAYOUT_COMPOSITE, $input);

        $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
            'step'              => 'composite_validation',
            'time_taken'        => (microtime(true) - $startTime) * 1000,
            'save_or_fail_flag' => $this->compositePayoutSaveOrFail
        ]);

        $startTime = microtime(true);

        $contactMetadata = [];

        if (array_key_exists(Entity::CONTACT, $metadata) === true)
        {
            $contactMetadata = $metadata[Entity::CONTACT];
        }

        try
        {
            $contact = $this->createContactForNewCompositePayoutFlow($input, $traceData, $contactMetadata);
        }
        catch (\Throwable $exception)
        {
            $contact = $this->handleExceptionAndFindEntity($exception, 'contact', $contactMetadata);
        }

        $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
            'step'              => 'composite_contact_creation',
            'time_taken'        => (microtime(true) - $startTime) * 1000,
            'save_or_fail_flag' => $this->compositePayoutSaveOrFail
        ]);

        $startTime = microtime(true);

        $fundAccountMetaData = [];

        if (array_key_exists(Entity::FUND_ACCOUNT, $metadata) === true)
        {
            $fundAccountMetaData = $metadata[Entity::FUND_ACCOUNT];
        }

        try
        {
            $fundAccount = $this->createFundAccountForNewCompositePayoutFlow($input, $contact, $traceData, $fundAccountMetaData);
        }
        catch (\Throwable $exception)
        {
            $fundAccount = $this->handleExceptionAndFindEntity($exception, 'fund_account', $fundAccountMetaData);
        }

        $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
            'step'              => 'composite_fund_account_creation',
            'time_taken'        => (microtime(true) - $startTime) * 1000,
            'save_or_fail_flag' => $this->compositePayoutSaveOrFail
        ]);

        $startTime = microtime(true);

        $payoutMetadata = [];

        if (array_key_exists(Entity::PAYOUT, $metadata) === true)
        {
            $payoutMetadata = $metadata[Entity::PAYOUT];
        }

        try
        {
            $payout = $this->createPayoutForNewCompositePayoutFlow($input, $fundAccount, $balance, $payoutMetadata);
        }
        catch (\Throwable $exception)
        {
            $payout = $this->handleExceptionAndFindEntity($exception, 'payout', $payoutMetadata);
        }

        $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
            'step'              => 'composite_payout_creation',
            'time_taken'        => (microtime(true) - $startTime) * 1000,
            'save_or_fail_flag' => $this->compositePayoutSaveOrFail,
            'metadata'          => $payoutMetadata,
            'payout_id'         => $payout->getId(),
            'contact_id'        => $contact->getId(),
            'fund_account_id'   => $fundAccount->getId(),
        ]);

        return [$payout, $contact, $fundAccount];
    }

    // Check for DB error of duplicate entry and fetch entity from master if required.
    protected function handleExceptionAndFindEntity($exception, string $entityName, array $metadata)
    {
        $this->trace->traceException(
            $exception,
            Trace::ERROR,
            TraceCode::PAYOUT_ENTITY_CREATION_FAILURE_IN_INGRESS_TO_EGRESS,
            [
                'entity'   => $entityName,
                'metadata' => $metadata
            ]);

        $id = array_pull($metadata, Entity::ID, '');

        if ($this->checkDuplicatePrimaryKeyError($exception->getMessage(), $entityName, $id) === false)
        {
            throw $exception;
        }

        return $this->repo->$entityName->findOrFailOnMaster($id);
    }

    // Checks for DB error of duplicate entry with same primary key.
    protected function checkDuplicatePrimaryKeyError(string $errorMsg, string $entityName, string $entityId)
    {
        $errorPattern = sprintf("/1062 Duplicate entry '%s' for key '%ss.PRIMARY'/", $entityId, $entityName);

        return (preg_match($errorPattern, $errorMsg) === 1);
    }

    protected function createContactForNewCompositePayoutFlow(array $input, array $traceData, array $contactMetadata): Contact\Entity
    {
        [$contactInput, $contactTraceData] = $this->getInputForContactCreationFromCompositePayoutPayload($input, $traceData);

        return (new Contact\Service)->createForCompositePayout($contactInput,
                                                               $contactTraceData,
                                                               $this->merchant,
                                                               $this->compositePayoutSaveOrFail,
                                                               $contactMetadata);
    }

    protected function createFundAccountForNewCompositePayoutFlow(array $input,
                                                                  Contact\Entity $contact,
                                                                  array $traceData,
                                                                  array $fundAccountMetaData = []): FundAccount\Entity
    {
        [$fundAccountInput, $faTraceData] = $this->getInputForFundAccountCreationFromCompositePayoutPayload($input,
                                                                                         $contact->getPublicId(),
                                                                                         $traceData);

        return (new FundAccount\Service)->createForCompositePayout($fundAccountInput,
                                                                   $contact,
                                                                   $faTraceData,
                                                                   $this->merchant,
                                                                   $this->compositePayoutSaveOrFail,
                                                                   $fundAccountMetaData);

    }

    protected function createPayoutForNewCompositePayoutFlow(array $input,
                                                             FundAccount\Entity $fundAccount,
                                                             Merchant\Balance\Entity $balance,
                                                             array $payoutMetadata): Entity
    {
        $payoutInput = $this->getInputForPayoutCreationForNewCompositePayoutFlow($input,
                                                                                 $fundAccount->getPublicId());

        return $this->core->createPayoutToFundAccountForCompositePayout($payoutInput,
                                                                        $this->merchant,
                                                                        $fundAccount,
                                                                        $balance,
                                                                        $this->compositePayoutSaveOrFail,
                                                                        $payoutMetadata);
    }

    protected function getInputForContactCreationFromCompositePayoutPayload(array $input, array $traceData): array
    {
        return [$input[Entity::FUND_ACCOUNT ][Entity::CONTACT], $traceData[Entity::FUND_ACCOUNT][Entity::CONTACT]];
    }

    protected function getInputForFundAccountCreationFromCompositePayoutPayload(array $input,
                                                                                string $contactId,
                                                                                array $traceData): array
    {
        // Abstract out the Fund Account object
        $fundAccountInput = $input[Entity::FUND_ACCOUNT];
        $fundAccountTraceData = $traceData[Entity::FUND_ACCOUNT];

        // Unset the nested contact object from the outer fund account object
        unset($fundAccountInput[Entity::CONTACT]);
        unset($fundAccountTraceData[Entity::CONTACT]);

        // Add contactId to the fund account object
        $fundAccountInput[FundAccount\Entity::CONTACT_ID] = $contactId;
        $fundAccountTraceData[FundAccount\Entity::CONTACT_ID] = $contactId;

        return [$fundAccountInput, $fundAccountTraceData];
    }

    protected function getInputForPayoutCreationForNewCompositePayoutFlow(array $input,
                                                                          string $fundAccountId): array
    {
        unset($input[Entity::FUND_ACCOUNT]);

        $input[Payout\Entity::FUND_ACCOUNT_ID] = $fundAccountId;

        return $input;
    }

    public function updatePayoutEntry($payoutId, $input)
    {
        return $this->core->updatePayoutEntry($payoutId, $input);
    }

    protected function checkIfPayoutIsAllowed(bool $isCompositePayout, array $input)
    {
        if (($this->merchant->isFeatureEnabled(Features::ALLOW_NON_SAVED_CARDS) === true) and
            (($isCompositePayout === false) and
             (isset($input[Entity::FUND_ACCOUNT_ID]) === true) and
             ($this->core->isPayoutsToFundAccountAllowed($input[Entity::FUND_ACCOUNT_ID]) === false)))
        {
            $this->trace->error(TraceCode::STANDALONE_PAYOUT_TO_CARDS_NOT_ALLOWED,
                                [
                                    'is_composite_payout' => $isCompositePayout,
                                    'payout_mode'         => $input[Payout\Entity::MODE] ?? null
                                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_STANDALONE_PAYOUT_TO_CARDS_NOT_ALLOWED);
        }
    }

    public function axisCCPayoutAnalytics()
    {

        if ($this->app['basicauth']->authCreds->checkIfOrgAxisCC() === false)
        {
            //Currently this should be called only for axis cc merchant.
            // So throwing error if condition is not met
            $this->trace->info(
                TraceCode::PAYOUT_AXIS_CC_GET_PAYOUT_ANALYTICS_REQUEST,
                [
                    'is_axis' => false,
                ]
            );

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $response = $this->core->fetchPayoutAnalyticsfromPayoutsService($this->merchant);

        return $response;
    }

    public function trackPayoutsFetchEvent(array $input, $payout = null)
    {
        $merchantId = $this->merchant->getId();
        $user   = $this->auth->getUser();
        $role   = $this->auth->getUserRole();;

        $userId = null;
        //For outh user will be there for normal private auth user won't be there
        if (isset($user) === true )
        {
            $userId        = $user->getId();
        }

        //tracking slack app related events
        $eventAttribute = [
            'merchant_id'   => $merchantId,
            'request'       => $this->app['api.route']->getCurrentRouteName(),
            'user_id'       => $userId,
            'user_role'     => $role,
            'channel'       => $this->auth->getSourceChannel(),
            'filters'       => $input
        ];

        $this->app['diag']->trackPayoutsFetchEvent(EventCode::PAYOUT_FETCH_REQUESTS,
            $payout,
            null,
            $eventAttribute);
    }

    public function getPayoutStatusReasonMap(): array
    {
        return StatusReasonMap::$payoutStatusToReasonMap;
    }

    public function getHolidayDetails(array $input): array
    {
        $app  = \App::getFacadeRoot();

        $auth = $app['basicauth'];
        $merchantId = $auth->getMerchant()->getId();

        $input[Entity::MERCHANT_ID] = $merchantId;

        $tempInput = $input;

        $balance = $this->processAccountNumber($tempInput);

        $channel = $balance->getChannel();

        $this->trace->info(
            TraceCode::FTS_HOLIDAY_DEBUG,
            [
                "account_type" => $balance->getAccountType(),
                "merchant" => $merchantId,
                "channel" => $channel
            ]
        );

        if ($balance->isAccountTypeDirect() === true)
        {
            if ($channel === 'rbl')
            {
                $input[Entity::CHANNEL] = 'rbl';
            }
            else if ($channel === 'icici')
            {
                $input[Entity::CHANNEL] = 'icici';
            }
        }
        else if ($balance->isAccountTypeShared() === true)
        {
            $input[Entity::CHANNEL] = 'icici';
        }

        unset($input[Entity::ACCOUNT_NUMBER]);

        /** @var \RZP\Services\FTS\FundTransfer $transferService */
        $transferService = App::getFacadeRoot()['fts_fund_transfer'];

        $response = $transferService->getHolidayDetails($input);

        return $response;
    }

    public function createPayoutViaLedgerCronJob(array $blacklistIds, array $forcedMerchantIds, int $limit)
    {
        $this->core->createPayoutViaLedgerCronJob($blacklistIds, $forcedMerchantIds, $limit);
    }

    public function updateMerchantOnHoldSlas(array $input)
    {
        (new Validator)->validateMerchantSlasForOnHoldPayouts($input);

        $adminService = new Admin\Service;

        $merchantSlaConfigList = $adminService->getConfigKey([
            'key' => Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA
        ]);

        $this->repo->transaction(
            function () use ($input, & $merchantSlaConfigList, $adminService) {
                foreach ($input as $sla => $merchantIds) {
                    foreach ($merchantIds as $merchantId) {
                        try
                        {
                            $merchantEntity = $this->repo->merchant->findOrFail($merchantId);
                        }
                        catch (DbQueryException $exception)
                        {
                            throw new Exception\BadRequestException(
                                ErrorCode::BAD_REQUEST_ERROR,
                                null,
                                null,
                                "merchantId: $merchantId is not found in database"
                            );
                        }

                        $accessor = Settings\Accessor::for($merchantEntity, Settings\Module::PAYOUTS);

                        $accessor->upsert(self::PAYOUTS_ON_HOLD_SLA_SETTINGS_KEY, $sla);

                        $accessor->save();

                        $merchantSlaConfigList[$merchantId] = $sla;
                    }
                }

                $adminService->setConfigKeys([
                    Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA => $merchantSlaConfigList
                ]);
            }
        );

        return ['success' => true];
    }
}
