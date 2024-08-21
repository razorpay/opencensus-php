<?php

namespace RZP\Models\Payout;

use App;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

use Monolog\Logger;
use RZP\Constants\Entity as EntityConstant;
use RZP\Constants\Mode;
use RZP\Constants\Product;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Constants;
use RZP\Exception\LogicException;
use RZP\Http\Route;

use RZP\Models\Vpa;
use RZP\Error\Error;
use RZP\Models\Base;
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
use RZP\Services\UfhService;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Workflow;
use RZP\Models\Settings;
use RZP\Models\Admin\Org;
use RZP\Trace\Tracer;
use RZP\Traits\TrimSpace;
use RZP\Http\OAuthScopes;
use RZP\Models\FundAccount;
use RZP\Http\RequestHeader;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Mail\Payout\Attachments;
use RZP\Services\PayoutService;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\DbQueryException;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\PayoutAttachmentEmail;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Mail\User\BulkPayoutSummary;
use RZP\Models\BankingAccountService;
use RZP\Models\Base\PublicCollection;
use RZP\Error\PublicErrorDescription;
use RZP\Models\User\Core as UserCore;
use RZP\Exception\ServerErrorException;
use RZP\Exception\BadRequestException;
use RZP\Models\PayoutOutbox\RequestType;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Workflow\Service\Adapter;
use RZP\Jobs\ApprovedPayoutDistribution;
use RZP\Models\PartnerBankHealth\Events;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Exception\ServerNotFoundException;
use RZP\Models\Merchant\Account as Account;
use RZP\Models\Payout\Batch as PayoutsBatch;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\PayoutsDetails as PayoutDetails;
use RZP\Jobs\PayoutPostCreateProcessLowPriority;
use RZP\Models\Application\ApplicationMerchantMaps;
use RZP\Services\Mock\UfhService as MockUfhService;
use RZP\Models\PayoutsStatusDetails\StatusReasonMap;
use RZP\Models\PayoutSource\Core as PayoutSourceCore;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\Feature\Constants as FeatureConstant;
use RZP\Models\Payout\BatchHelper as PayoutBatchHelper;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\PayoutSource\Entity as PayoutSourceEntity;
use RZP\Models\FundAccount\Service as FundAccountService;
use RZP\Services\RazorpayLabs\SlackApp as SlackAppService;
use RZP\Models\FundAccount\BatchHelper as FundAccountHelper;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Models\Payout\Batch\Constants as BatchPayoutConstants;
use RZP\Models\FundAccount\Validation as FundAccountValidation;
use RZP\Models\Workflow\Service\Config\Service as WorkflowConfigService;
use Throwable;

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

    /**
     * @var PayoutService\StatusReasonMap
     */
    protected $payoutStatusReasonMapApiServiceClient;

    protected $slackAppService;

    protected $workflowMigration;

    protected $payoutDetailsCore;

    protected $workflowConfigService;

    protected $userCore;

    protected $batch;

    protected const IS_VALID_PURPOSE = "is_valid_purpose";

    protected const PAYOUTS_ON_HOLD_SLA_SETTINGS_KEY = "payouts_on_hold_sla";

    protected const ON_HOLD_FETCH_LIMIT = 5000;

    protected const PARTNER_BANK_ON_HOLD_FETCH_LIMIT = 5000;

    protected const PAYOUT_NOTIFICATION_COUNT = 5;

    protected $compositePayoutSaveOrFail = true;

    const LOW_BALANCE_LIMIT_FOR_TEST_ACCOUNT = 3000000;

    const FILE      = 'file';
    const FILE_SIZE = 'file_size';
    const ENTITY    = 'entity';
    const MODES     = 'modes';

    const EXISTING_PAYOUT_BEHAVIOUR_RETURN_SAME = 'return_same';

    const EXISTING_PAYOUT_BEHAVIOUR_RETURN_ERROR = 'return_error';

    const EXISTING_PAYOUT_BEHAVIOUR_RETURN_NEW = 'return_new';

    const DISPATCH_LIMIT_FOR_PAYOUTS_SCHEDULED_POST_APPROVAL = 25000;

    /**
     * @var PayoutService\OnHoldCron
     */
    protected $payoutServiceOnHoldCronClient;

    /**
     * @var PayoutService\PayoutsCreateFailureProcessingCron
     */
    protected $payoutServiceCreateFailureProcessingCronClient;

    /**
     * @var PayoutService\PayoutsUpdateFailureProcessingCron
     */
    protected $payoutServiceUpdateFailureProcessingCronClient;

    /**
     * @var PayoutService\OnHoldSLAUpdate
     */
    protected $payoutServiceOnHoldSLAUpdateClient;

    /**
     * @var PayoutService\BulkPayout
     */
    protected $payoutServiceBulkPayoutsClient;

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

        $this->payoutServiceCreateFailureProcessingCronClient = $this->app[PayoutService\PayoutsCreateFailureProcessingCron::PAYOUTS_CREATE_FAILURE_PROCESSING_CRON];

        $this->payoutServiceUpdateFailureProcessingCronClient = $this->app[PayoutService\PayoutsUpdateFailureProcessingCron::PAYOUTS_UPDATE_FAILURE_PROCESSING_CRON];

        $this->payoutServiceOnHoldSLAUpdateClient = $this->app[PayoutService\OnHoldSLAUpdate::PAYOUT_SERVICE_ON_HOLD_SLA_UPDATE];

        $this->payoutStatusReasonMapApiServiceClient = $this->app[PayoutService\StatusReasonMap::PAYOUT_SERVICE_STATUS_REASON_MAP];

        $this->payoutServiceBulkPayoutsClient = $this->app[PayoutService\BulkPayout::PAYOUT_SERVICE_BULK_PAYOUTS];

        $this->payoutDetailsCore = new PayoutDetails\Core();

        $this->userCore = new User\Core();

        $this->batch = new Batch\Service();
    }

    public function fetchPayoutsDetailsForDcc($input) : array
    {
        (new Validator)->validateInput(Validator::DATA_CONSISTENCY_CHECKER_PAYOUTS_DETAIL_FETCH, $input);

        return $this->core->fetchPayoutsDetailsForDcc($input);
    }

    public function initiatePayoutsConsistencyCheck()
    {
        return $this->core->initiatePayoutsConsistencyCheck();
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

    public function deductCreditsViaPayoutService(array $input)
    {
        return $this->core->deductCreditsViaPayoutService($input);
    }

    public function fetchPricingInfoForPayoutService(array $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_FETCH_PRICING_INFO_REQUEST,
            [
                'input' => $input,
            ]);

        try
        {
            (new Validator)->validateInput(Validator::PAYOUT_SERVICE_FETCH_PRICING_INFO, $input);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FETCH_PRICING_INFO_FOR_MICROSERVICE_FAILED,
                [
                    'input' => $input,
                ]
            );

            return [
                Entity::ERROR            => $exception->getMessage(),
                Error::PUBLIC_ERROR_CODE => strval($exception->getCode()),
            ];
        }

        return $this->core->fetchPricingInfoForPayoutService($input);
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

    public function createFundManagementPayout(array $input)
    {
        // Validation on the Job Name
        $jobName = app('worker.ctx')->getJobName() ?? null;

        if ($jobName !== PayoutConstants::FUND_MANAGEMENT_PAYOUT_INITIATE)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $merchantId = $input[Entity::MERCHANT_ID];

        $channel = $input[Entity::CHANNEL];

        $fmpUniqueIdentifier = $input[PayoutConstants::FMP_UNIQUE_IDENTIFIER];

        $payoutCreationPayload = $input[PayoutConstants::PAYOUT_CREATE_INPUT];

        $requestTime = microtime(true);

        $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CREATE_REQUEST, [
            'input'       => $payoutCreationPayload,
            'merchant_id' => $merchantId,
            'channel'     => $channel,
            'time'        => $requestTime,
        ]);

        $fundManagementPayout = $this->app['api.mutex']->acquireAndReleaseStrict(
            'create_fund_management_payout_' . $fmpUniqueIdentifier . '_' . $merchantId . '_' . $channel,
            function() use ($merchantId, $payoutCreationPayload) {

                /* @var Merchant\Entity $merchant */
                $merchant = $this->repo->merchant->findOrFail($merchantId);

                // Setting Merchant Id in Basic Auth
                $this->app['basicauth']->setMerchant($merchant);

                $this->merchant = $merchant;

                return $this->createFundAccountManagementCompositePayout($payoutCreationPayload, true);
            },
            120,
            ErrorCode::BAD_REQUEST_ANOTHER_FUND_MANAGEMENT_PAYOUT_CREATION_IN_PROGRESS
        );

        $responseTime = microtime(true);

        $this->trace->info(TraceCode::FUND_MANAGEMENT_PAYOUT_CREATED, [
            'payout_data'   => $fundManagementPayout,
            'merchant_id'   => $merchantId,
            'channel'       => $channel,
            'time'          => $responseTime,
            'response_time' => $responseTime - $requestTime,
        ]);
    }

    public function createFundAccountManagementCompositePayout(array $input, bool $internal = false): array
    {
        $this->validateFundAccountManagementCompositePayoutInput($input);

        (new Validator)->setStrictFalse()
                       ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

        $input = $this->createContactAndFundAccountAndGetPayoutInputForCompositeRequest($input);

        $payout = $this->core->createPayoutToFundAccount($input, $this->merchant, null, $internal);

        if ($payout->getIsPayoutService() === true)
        {
            if (array_key_exists(Entity::FUND_ACCOUNT_ID, $payout->payoutServiceResponse) === true)
            {
                $fundAccountId = $payout->payoutServiceResponse[Entity::FUND_ACCOUNT_ID];

                $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

                $payout->fundAccount()->associate($fundAccount);

                $payout = $this->postCreationProcessingForCompositePayout($payout);

                $payout->payoutServiceResponse[Entity::FUND_ACCOUNT] = $payout->fundAccount->toArrayPublic();
            }
        }
        else
        {
            $payout = $this->postCreationProcessingForCompositePayout($payout);
        }

        if ($payout->getIsPayoutService() === true)
        {
            return $payout->payoutServiceResponse;
        }

        return $payout->toArrayPublic();
    }

    public function validateFundAccountManagementCompositePayoutInput($input)
    {
        // Only Accept Composite Payout
        if (isset($input[Entity::FUND_ACCOUNT]) === false)
        {
            $this->trace->error(TraceCode::FUND_MANAGEMENT_VANILLA_PAYOUT_CREATION_NOT_ALLOWED, [
                'merchant_id' => $this->merchant,
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $basDetails = $this->repo->banking_account_statement_details->getDirectBasDetailEntityByMerchantAndBalanceId(
            $this->merchant->getId(), $input[Entity::BALANCE_ID]);

        // Don't Allow FMPs with balance Id belonging to wrong merchant_id
        if (isset($basDetails) === false)
        {
            $this->trace->error(TraceCode::FMP_CREATION_INVALID_SOURCE_ERROR, [
                'merchant_id'    => $this->merchant->getId(),
                'balance_id'     => $input[Entity::BALANCE_ID],
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN, null, [
                'merchant_id'    => $this->merchant->getId(),
                'balance_id'     => $input[Entity::BALANCE_ID],
            ]);
        }

        $bankAccount   = $input[Entity::FUND_ACCOUNT][FundAccount\Type::BANK_ACCOUNT] ?? null;
        $accountNumber = $bankAccount[BankAccount\Entity::ACCOUNT_NUMBER] ?? null;
        $ifsc          = $bankAccount[BankAccount\Entity::IFSC] ?? null;
        $name          = $bankAccount[BankAccount\Entity::NAME] ?? null;

        // Don't Allow FMPs with invalid Bank Account Details
        if ((isset($accountNumber) === false) and
            (isset($ifsc) === false) and
            (isset($name) === false))
        {
            $this->trace->error(TraceCode::FMP_CREATION_INVALID_BANK_ACCOUNT_DETAILS_ERROR, [
                'merchant_id'    => $this->merchant->getId(),
                'account_number' => $accountNumber,
                'ifsc'           => $ifsc,
                'name'           => $name
            ]);

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN, null, [
                'merchant_id'    => $this->merchant->getId(),
                'account_number' => $accountNumber,
                'ifsc'           => $ifsc,
                'name'           => $name
            ]);
        }

        $bankAccount = $this->repo->bank_account->findLatestBankAccountByAccountNumber(
            $accountNumber, $ifsc, $name, BankAccount\Type::VIRTUAL_ACCOUNT, $this->merchant->getId());

        // Don't Allow FMPs for account numbers not belonging to the merchant due to security concerns
        if (isset($bankAccount) === false)
        {
            $destinationBasDetails = $this->repo->banking_account_statement_details->fetchActiveAccountsByAccountNumberAndMerchantId(
                $this->merchant->getId(), $accountNumber);

            if (count($destinationBasDetails) === 0)
            {
                $this->trace->error(TraceCode::FMP_CREATION_INVALID_BANK_ACCOUNT_DETAILS_ERROR, [
                    'merchant_id'    => $this->merchant->getId(),
                    'account_number' => $accountNumber,
                    'ifsc'           => $ifsc,
                    'name'           => $name
                ]);

                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN, null, [
                    'merchant_id'    => $this->merchant->getId(),
                    'account_number' => $accountNumber,
                    'ifsc'           => $ifsc,
                    'name'           => $name,
                ]);
            }
        }
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

        $traceData = $this->unsetSensitiveCardDetails($input);

        $this->trace->info(
            TraceCode::PAYOUT_CREATE_REQUEST,
            [
                'input' => $traceData,
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
        $balance = $this->processAccountNumber($input, true);

        (new Validator)->setStrictFalse()
                       ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

        (new Validator)->validateAndUpdateCardMode($input);

        $isCompositePayout = false;

        if (isset($input[Entity::FUND_ACCOUNT]) === true)
        {
            $isCompositePayout = true;
        }

        $this->checkIfPayoutIsAllowed($isCompositePayout, $input, $internal, $balance);

        if ($isCompositePayout === true)
        {
            $startTime = microtime(true);

            $newFlowFlag = $this->merchant->isFeatureEnabled(Features::HIGH_TPS_COMPOSITE_PAYOUT);

            $highTpsIngress = $this->merchant->isFeatureEnabled(Features::HIGH_TPS_PAYOUT_INGRESS);

            $asyncIngressFlag = $this->merchant->isFeatureEnabled(Features::PAYOUT_ASYNC_INGRESS);

            $this->compositePayoutSaveOrFail = !$asyncIngressFlag;

            $this->trace->info(TraceCode::PAYOUT_OPTIMIZATION_FOR_COMPOSITE_TIME_TAKEN, [
                'step'       => 'feature_enabled_check',
                'time_taken' => (microtime(true) - $startTime) * 1000,
            ]);

            if (($highTpsIngress === true) or ($newFlowFlag === true))
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

            $compositePayoutInputCreateStartTime = millitime();

            $input = $this->createContactAndFundAccountAndGetPayoutInputForCompositeRequest($input);

            $compositePayoutInputCreateEndTime = millitime();

            $this->trace->histogram(
                Metric::COMPOSITE_PAYOUT_CONTACT_FUND_ACCOUNT_CREATE_DURATION,
                $compositePayoutInputCreateEndTime - $compositePayoutInputCreateStartTime);

            if (($compositePayoutInputCreateEndTime - $compositePayoutInputCreateStartTime) >= 250)
            {
                $this->trace->info(TraceCode::COMPOSITE_PAYOUT_CONTACT_FUND_ACCOUNT_CREATE_DURATION, [
                    'time' => ($compositePayoutInputCreateEndTime - $compositePayoutInputCreateStartTime)
                ]);
            }
        }

        $payout = $this->core->createPayoutToFundAccount($input, $this->merchant, null, $internal, $balance);

        if ($isCompositePayout === true)
        {
            if ($payout->getIsPayoutService() === true)
            {
                if (array_key_exists(Entity::FUND_ACCOUNT_ID, $payout->payoutServiceResponse) === true)
                {
                    $fundAccountId = $payout->payoutServiceResponse[Entity::FUND_ACCOUNT_ID];

                    $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

                    $payout->fundAccount()->associate($fundAccount);

                    $payout = $this->postCreationProcessingForCompositePayout($payout);

                    $payout->payoutServiceResponse[Entity::FUND_ACCOUNT] = $payout->fundAccount->toArrayPublic();
                }
            }
            else
            {
                $payout = $this->postCreationProcessingForCompositePayout($payout);
            }
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

        if ($payout->getIsPayoutService() === true)
        {
            return $payout->payoutServiceResponse;
        }

        return $payout->toArrayPublic();
    }

    /**
     *Composite Payout Creation after verifying user's otp for the action.
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function postCompositePayoutWithOtp(array $input, bool $internal = false) : array
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        $otpInput = $this->addVpaToVerifyOtp($input);

        (new User\Core)->verifyOtp($otpInput + ['action' => 'create_composite_payout_with_otp'],
            $this->merchant,
            $this->user,
            $this->mode === Constants\Mode::TEST);

        return $this->postCompositePayout($input, $internal);
    }

    /**
     * Composite Payout Creation for internal apps
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function postCompositePayoutInternal(array $input)
    {
        if ($this->app['basicauth']->isXperienceApp())
        {
            /** @var \RZP\Models\Merchant\Balance\Entity $inputBalance  */
            $inputBalance = $this->repo->balance->findOrFailById($input[Entity::BALANCE_ID]);

            $input[Entity::ACCOUNT_NUMBER] = $inputBalance->getAccountNumber();

            unset($input[Entity::BALANCE_ID]);
        }

        return $this->postCompositePayout($input);
    }

    /**
     * Composite Payout Creation
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function postCompositePayout(array $input, bool $internal = false): array
    {
        (new Validator)->setStrictFalse()
            ->validateInput(Validator::FUND_ACCOUNT_PAYOUT_COMPOSITE, $input);

        $input = $this->addReferenceIdForCompositePayout($input);

        $input = array_except($input, ['otp', 'token']);

        $payoutInput = $input;

        $requestTime = microtime(true);

        $this->trace->info(
            TraceCode::COMPOSITE_PAYOUT_CREATE_REQUEST,
            [
                'input'     => $payoutInput,
                'time'      => $requestTime,
            ]);

        // Only ProxyAuth and xperience internal auth are supported for this flow
        if (($this->auth->isProxyAuth() === false) &&
            ($this->app['basicauth']->isXperienceApp() === false))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        // Only allowed for Rx payouts, mandates account number
        // TODO: Cache the Balance ID
        $balance = $this->processAccountNumber($input);

        (new Validator)->setStrictFalse()
            ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

        $isCompositePayout = false;

        if (isset($input[Entity::FUND_ACCOUNT]) === true)
        {
            $isCompositePayout = true;
        }
        else
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $this->checkIfPayoutIsAllowed($isCompositePayout, $input, $internal, $balance);

        $input = $this->createContactAndFundAccountAndGetPayoutInputForCompositeRequest($input);

        $payout = $this->core->createPayoutToFundAccount($input, $this->merchant, null, $internal);

        if ($payout->getIsPayoutService() === true)
        {
            if (array_key_exists(Entity::FUND_ACCOUNT_ID, $payout->payoutServiceResponse) === true)
            {
                $fundAccountId = $payout->payoutServiceResponse[Entity::FUND_ACCOUNT_ID];

                $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $this->merchant);

                $payout->fundAccount()->associate($fundAccount);

                $payout = $this->postCreationProcessingForCompositePayout($payout);

                $payout->payoutServiceResponse[Entity::FUND_ACCOUNT] = $payout->fundAccount->toArrayPublic();
            }
        }
        else
        {
            $payout = $this->postCreationProcessingForCompositePayout($payout);
        }

        $responseTime = microtime(true);

        $this->trace->info(
            TraceCode::COMPOSITE_PAYOUT_CREATE_RESPONSE,
            [
                'input'         => $input,
                'payout_id'     => $payout->getId(),
                'is_composite'  => $isCompositePayout,
                'time'          => $responseTime,
                'response_time' => $responseTime - $requestTime,
            ]);

        if ($payout->getIsPayoutService() === true)
        {
            return $payout->payoutServiceResponse;
        }

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
               $this->auth->isAccountsReceivableApp() or
               $this->auth->isBusinessReportingApp() or
               $this->auth->isVendorPaymentApp() or
               $this->auth->isSettlementsApp() or
               $this->auth->isXPayrollApp() or
               $this->auth->isScroogeApp() or
               $this->auth->isChargeCollectionsApp() or
               $this->auth->isCapitalCollectionsApp() or
               $this->auth->isFTSApp() or
               $this->auth->isXperienceApp();
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

    public function isChargeCollectionsApp(): bool
    {
        return $this->auth->isChargeCollectionsApp();
    }

    public function isBatchApp(): bool
    {
        return $this->auth->isBatchApp();
    }

    public function isXperienceApp(): bool
    {
        return $this->auth->isXperienceApp();
    }

    public function isRemitterDetailsExpectedInPayload(): bool
    {
        if ($this->isAllowedInternalApp() === false)
        {
            return false;
        }

        return (
            ($this->auth->isPayoutLinkApp() === false) and
            ($this->auth->isAccountsReceivableApp() === false) and
            ($this->auth->isBusinessReportingApp() === false) and
            ($this->auth->isVendorPaymentApp() === false) and
            ($this->auth->isSettlementsApp() === false) and
            ($this->auth->isScroogeApp() === false) and
            ($this->auth->isChargeCollectionsApp() === false) and
            ($this->auth->isCapitalCollectionsApp() === false) and
            ($this->auth->isFTSApp() === false) and
            ($this->auth->isXperienceApp() === false)
        );
    }

    public function approveIciciCaFundAccountPayout(array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_ICICI_CA_APPROVE_REQUEST, ['input' => $input]);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($input['payout_id'], $this->merchant);

        if (Payout\Core::checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa($payout->balance, $this->merchant) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ENABLED_FOR_2FA_PAYOUT,
                null,
                null,
                'merchant is not enabled for ICICI 2FA payout flow'
            );
        }

        $payoutValidator =  $payout->getValidator();

        $payoutValidator->setStrictFalse()->validateInput(Validator::APPROVE_ICICI_CA_PAYOUT_RULES, $input);

        $payoutValidator->validatePayoutStatusForApproveOrReject();

        $payout = (new Core)->approveIciciCAPayout($payout, $input);

        return $payout->toArrayPublic();

    }

    public function approveFundAccountPayout(string $id, array $input): array
    {
        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        $isPartnerApproval = $this->isXPartnerApproval();

        $this->trace->info(TraceCode::PAYOUT_APPROVE_REQUEST, [
            'id' => $id, 'input' => $input, 'isPartnerApproval' => $isPartnerApproval
        ]);

        try
        {
            /** @var Entity $payout */
            $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);
        }
        catch (\Throwable $exception)
        {
            try
            {
                $payout = $this->handlePayoutServicePayoutForWorkflowAction($id);
            }
            catch (\Throwable $psException)
            {
                throw $exception;
            }

            if (empty($payout) === true)
            {
                throw $exception;
            }
        }

        $payoutValidator =  $payout->getValidator();

        $payoutValidator->validatePayoutStatusForApproveOrReject();

        if ($isPartnerApproval === true)
        {
            $user = optional($auth->getUser())->getId() ?? null;

            $this->trace->info(TraceCode::PAYOUT_PARTNER_APPROVE_REQUEST, [
                'id'       => $id,
                'input'    => $input,
                'user'     => $user,
                'merchant' => $auth->getMerchantId()
            ]);

            $payoutValidator->validatePayoutForApprovalViaOAuth();

            $payoutValidator->validateInput(Validator::PARTNER_PAYOUT_APPROVAL_RULES, $input);

            $input['user_comment'] = $input['remarks'] ?? '';
        }
        else
        {
            $payoutValidator->setStrictFalse()->validateInput(Validator::APPROVE_PAYOUT_RULES, $input);

            $this->user->validateInput('verifyOtp', array_only($input, [User\Entity::OTP, User\Entity::TOKEN]));

            (new User\Core)->verifyOtp($input + ['action' => 'approve_payout', 'payout_id' => $id], $this->merchant, $this->user);
        }

        $payout = (new Core)->approvePayout($payout, $input);

        return $payout->toArrayPublic();
    }

    private function handlePayoutServicePayoutForWorkflowAction(string $payoutId)
    {
        try
        {
            $merchantId = $this->merchant->getId();

            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                RazorxTreatment::WORKFLOW_ACTION_WITH_DB_DUAL_WRITE_PAYOUTS_SERVICE,
                $this->mode,
                Payout\Entity::RAZORX_RETRY_COUNT
            );

            $this->trace->info(
                TraceCode::WORKFLOW_ACTION_WITH_DB_DUAL_WRITE_PAYOUTS_SERVICE,
                [
                    'variant'       => $variant,
                    'mode'          => $this->mode,
                    'merchant_id'   => $merchantId,
                ]);

            if (strtolower($variant) === 'on')
            {
                $this->dualWritePayout($payoutId);

                /** @var Entity $payout */
                $payout = $this->repo->payout->findByPublicIdAndMerchant($payoutId, $this->merchant);

                return $payout;
            }

            return [];
        }
        catch (\Throwable $exception)
        {
            /** @var Route $route */
            $route = $this->app['api.route'];

            $routeName = $route->getCurrentRouteName();

            $this->trace->count(Payout\Metric::PAYOUT_SERVICE_WORKFLOW_ACTION_FAILED, [
                Constants\Metric::LABEL_ROUTE_NAME => $routeName,
                Constants\Metric::LABEL_MESSAGE    => $exception->getMessage(),
            ]);

            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_WORKFLOW_ACTION_FAILED,
                [
                    'payout_id' => $payoutId
                ]);

            // We want to throw original exception here
            throw $exception;
        }
    }

    protected function dualWritePayout(string $id)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

        $data = [
            'payout_id' => $id,
            'timestamp' => $currentTime,
        ];

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_INIT,
            $data
        );

        (new Payout\Core)->processDualWrite([
            'payout_id' => $id,
            'timestamp' => $currentTime,
        ]);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_COMPLETE,
            $data);
    }

    public function processActionOnFundAccountPayoutInternal(string $id, bool $approved, array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_WORKFLOW_ACTION_REQUEST,
            ['id' => $id, 'approved' => $approved, 'input' => $input]);

        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicId($id);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        if ($this->schedulePayoutProcessingForP2PIfApplicable($payout) === false)
        {
            if ($this->shouldProcessBulkApproveAsync($payout->getMerchantId()))
            {
                $payload = [
                    'input' => $input,
                    'payout_id' => $id,
                    'is_approved' => $approved,
                ];

                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_JOB_DISPATCH, ['payload' => $payload]);

                ApprovedPayoutDistribution::dispatch($this->mode, $payload, $payout->getMerchantId());

                $this->trace->info(TraceCode::PAYOUT_ASYNC_APPROVE_JOB_DISPATCH_SUCCESS, ['payload' => $payload]);
            }
            else
            {
                $payout = (new Core)->processActionOnPayout($approved, $payout, $input);
            }
        }

        return $payout->toArrayPublic();
    }

    public function sendPendingPayoutAndPayoutLinkApprovalEmails()
    {
        $startAt = millitime();

        $approverList = $this->repo->payout->fetchMerchantUserDataHavingPendingPayouts();

        $this->trace->info(TraceCode::PENDING_APPROVAL_EMAILS_MERCHANT_QUERY_DURATION, [
            'query_execution_time' => millitime() - $startAt,
            'approver_list'         => $approverList
        ]);

        $payoutLinksDataFetchStartAt = millitime();

        /*
         {
            "mid1": {
                "owner": {
                    "payout_links_count": 5,
                    "payout_links_amount": 50,
                },
                "admin": {
                    "payout_links_count": 2,
                    "payout_links_amount": 20,
                }
            },
            "mid2": {
                "owner": {
                    "payout_links_count": 5,
                    "payout_links_amount": 50,
                },
                "admin": {
                    "payout_links_count": 2,
                    "payout_links_amount": 20,
                }
            }
        }
        */
        $merchantPendingPayoutLinksMeta = $this->app['payout-links']->getPendingPayoutLinksMetaForEmail();

        $payoutLinksApproverList = (empty($merchantPendingPayoutLinksMeta) === false) ?
            $this->getUsersDataForPendingPayoutLinks($merchantPendingPayoutLinksMeta) : new PublicCollection();

        $this->trace->info(
            TraceCode::PENDING_APPROVAL_EMAILS_MERCHANT_QUERY_DURATION,
            [
                'data_fetch_time'           => millitime() - $payoutLinksDataFetchStartAt,
                'pending_payout_links_data' => $merchantPendingPayoutLinksMeta,
                'approver_list'             => $payoutLinksApproverList
            ]);

        return $this->core->prepareTemplateAndDispatchEmail($approverList, $payoutLinksApproverList, $merchantPendingPayoutLinksMeta);
    }

    public function sendPendingPayoutApprovalReminder($input): array
    {
        $this->trace->info(TraceCode::PAYOUTS_PENDING_APPROVAL_SEND_PUSH_NOTIFICATION, [
            'input' => $input,
        ]);

        (new Validator)->validateInput('pending_payout_approval_reminder', $input);

        $includeMerchantIds = [];

        $excludeMerchantIds = [];

        if (array_key_exists(PayoutConstants::INCLUDE_MERCHANT_IDS, $input))
        {
            $includeMerchantIds = $input[PayoutConstants::INCLUDE_MERCHANT_IDS];
        }

        if (array_key_exists(PayoutConstants::EXCLUDE_MERCHANT_IDS, $input))
        {
            $excludeMerchantIds = $input[PayoutConstants::EXCLUDE_MERCHANT_IDS];
        }

        $startAt = millitime();

        $approveList = $this->repo->payout->fetchMerchantUserDataHavingPendingPayouts($includeMerchantIds, $excludeMerchantIds);

        $this->trace->info(TraceCode::PENDING_APPROVAL_REMINDER_MERCHANT_QUERY_DURATION, [
            'query_execution_time' => millitime() - $startAt,
            'approve_list'         => $approveList
        ]);

        return $this->core->getPendingPayoutsDataAndDispatchEvents($approveList);
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
        catch (\WpOrg\Requests\Exception $exception)
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
        $auth = $this->app['basicauth'];

        $isPartnerApproval = $this->isXPartnerApproval();

        $this->trace->info(TraceCode::PAYOUT_REJECT_REQUEST, ['id' => $id, 'isPartnerApproval' => $isPartnerApproval]);

        try
        {
            /** @var Entity $payout */
            $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);
        }
        catch (\Throwable $exception)
        {
            try
            {
                $payout = $this->handlePayoutServicePayoutForWorkflowAction($id);
            }
            catch (\Throwable $psException)
            {
                throw $exception;
            }

            if (empty($payout) === true)
            {
                throw $exception;
            }
        }

        $payoutValidator = $payout->getValidator();

        $payoutValidator->validatePayoutStatusForApproveOrReject();

        if ($isPartnerApproval === true)
        {
            $user = $auth->getUser() !== null ? $auth->getUser()->getId() : null;

            $this->trace->info(TraceCode::PAYOUT_PARTNER_REJECT_REQUEST, [
                'id'       => $id,
                'input'    => $input,
                'user'     => $user,
                'merchant' => $auth->getMerchantId()
            ]);

            $payoutValidator->validateInput(Validator::PARTNER_PAYOUT_APPROVAL_RULES, $input);

            $payoutValidator->validatePayoutForApprovalViaOAuth();

            $input['user_comment'] = $input['remarks'] ?? '';
        }

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

    public function ownerBulkRejectPayouts(array $input)
    {
        $this->trace->info(TraceCode::OWNER_BULK_REJECT_PAYOUT_REQUEST, ['input' => $input]);

        (new Validator)->validateInput(Validator::OWNER_BULK_REJECT_PAYOUTS, $input);

        $payouts = $this->repo->payout->findManyByPublicIdsAndMerchant($input[Entity::PAYOUT_IDS], $this->merchant);

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
                    TraceCode::OWNER_BULK_REJECT_PAYOUT_EXCEPTION,
                    [
                        'payout_id'         => $payout->getId(),
                        'failure_reason'    => $e->getMessage(),
                    ]);

                $failedIds[] = $payout->getId();
            }
        }

        return [
            'total_count'   => count($payouts),
            'processed_ids' => $processedIds,
            'failed_ids'    => $failedIds,
        ];
    }

    public function fetchPendingPayoutsSummary(array $input)
    {
        $this->trace->info(TraceCode::FETCH_PENDING_PAYOUTS_REQUEST, ['input' => array_except($input, ['account_numbers'])]);

        (new Validator)->validateInput(Validator::FETCH_PENDING_PAYOUTS_SUMMARY, $input);

        $pendingPayouts = $this->repo->payout->findPendingPayoutsSummaryForAccountNumbers($input[PayoutConstants::ACCOUNT_NUMBERS], $this->merchant->getMerchantId());

        $pendingPayoutsSummary = array();

        foreach ($pendingPayouts as $pendingPayout)
        {
            $pendingPayoutsSummary[] = [
                'id' => $pendingPayout->getPublicId(),
                'amount' => $pendingPayout->getAmount(),
            ];
        }

        return $pendingPayoutsSummary;
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
     * Adds vpa to reference_id in contact to avoid creation of duplicate contacts
     * @param array $input
     *
     * @return array
     */
    public function addReferenceIdForCompositePayout(array $input): array
    {
        $fundAccountVpa = $input[Entity::FUND_ACCOUNT][FundAccount\Entity::VPA];

        if (isset($fundAccountVpa[Vpa\Entity::ADDRESS]) === true)
        {
            // trimming it to use first 40 characters as refernce_id column validator allows only 40 characters
            $input[Entity::FUND_ACCOUNT][FundAccount\Entity::CONTACT][Contact\Entity::REFERENCE_ID] = substr($fundAccountVpa[Vpa\Entity::ADDRESS],0,40);
        }

        return $input;
    }


    /**
     * Adds VPA on input for Otp Verfication
     * @param array $input
     *
     * @return array
     */
    public function addVpaToVerifyOtp(array $input): array
    {
        $fundAccountVpa = $input[Entity::FUND_ACCOUNT][FundAccount\Entity::VPA];

        if (isset($fundAccountVpa[Vpa\Entity::ADDRESS]) === true)
        {
            $input[FundAccount\Entity::VPA] = $fundAccountVpa[Vpa\Entity::ADDRESS];
        }

        return $input;
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
        $balance = $this->processAccountNumber($payoutInput);

        (new Validator)->setStrictFalse()
                       ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT_WITH_OTP, $input);

        if (isset($payoutInput[Entity::ORIGIN]) === false)
        {
            $payoutInput[Entity::ORIGIN] = Entity::DASHBOARD;
        }

        $this->checkIfIciciDirectAccountPayoutShouldBeAllowed($input, false, $balance);

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

            if ($payout->getIsPayoutService() === true)
            {
                return $payout->payoutServiceResponse;
            }

            return $payout->toArrayPublic();
        }
    }

    /**
     * Creates a payout in the pending state and initiates only an OTP request to FTS for
     * ICICI CA payouts. The transfer request to FTS is not initiated.
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function fundAccountPayout2faForIciciCa(array $input): array
    {
        // Only allowed for Rx payouts, mandates account number
        $balance = $this->processAccountNumber($input);

        $this->checkIfDirectAccountIsActive($balance);

        (new Validator)->setStrictFalse()
            ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

        if (isset($input[Entity::ORIGIN]) === false)
        {
            $input[Entity::ORIGIN] = Entity::DASHBOARD;
        }

        $payout = $this->core->createPayoutAndTriggerIciciOtp($input, $this->merchant, $balance);

        return $payout->toArrayPublic();
    }

    /**
     * Sends an OTP creation request to FTS for ICICI 2FA payouts
     *
     * @param array $input
     * @return array
     * @throws BadRequestException
     */

    public function otpSendForIciciCa2fa(array $input): array
    {
        (new Validator)->validateInput(Validator::PAYOUT_2FA_OTP_SEND_REQUEST, $input);

        $payoutId = $input[Payout\Entity::PAYOUT_ID];

        $this->trace->info(TraceCode::PAYOUT_2FA_OTP_SEND_REQUEST,
            [
                Entity::PAYOUT_ID   => $payoutId,
                Entity::MERCHANT_ID => $this->merchant->getId()
            ]);

        $payout = $this->repo->payout->findByPublicIdAndMerchant($payoutId, $this->merchant);

        if ($payout->getStatus() !== Payout\Status::PENDING)
        {
            $this->trace->info(TraceCode::PAYOUT_2FA_OTP_REQUEST_NOT_ALLOWED,
                [
                    Entity::PAYOUT_ID    => $payoutId,
                    Entity::STATUS       => $payout->getStatus(),
                    Entity::MERCHANT_ID  => $this->merchant->getId()
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ALLOWED_TO_TRIGGER_2FA_OTP,
                null,
                null,
                'Merchant is not allowed to trigger OTP for 2FA payout'
            );
        }

        if (Payout\Core::checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa($payout->balance, $this->merchant) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_ENABLED_FOR_2FA_PAYOUT,
                null,
                null,
                'merchant is not enabled for ICICI 2FA payout flow'
            );
        }

        try
        {
            $this->core->triggerIciciOtpForPayoutViaFts($payout);
        }
        catch (Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::CRITICAL,
                TraceCode::PAYOUT_2FA_OTP_REQUEST_TO_FTS_FAILED,
                [
                    Entity::PAYOUT_ID   => $payoutId,
                    Entity::MERCHANT_ID => $this->merchant->getId()
                ]);

            $this->trace->count(Metric::FTS_OTP_CREATION_FAILURES_COUNT);
        }

        return ['success' => true];
    }

    private function shouldCreateUndoablePayout()
    {
        if ($this->auth->isXDashboardApp() === false) {
            return false;
        }

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
        // currently keeping this feature under razorx
        if ($this->shouldSkipPayrollEntries())
        {
            $input[Entity::SOURCE_TYPE_EXCLUDE] = PayoutSourceEntity::XPAYROLL;
        }

        $payout = null;

        if ($this->core->shouldFetchPayoutByIdViaMicroservice($input) === true)
        {
            try
            {
                $payout = $this->core->fetchByIdFromPayoutsService($id, $input);
            }

            catch (\Exception $e)
            {
                if ($e->getMessage() !== PublicErrorDescription::BAD_REQUEST_INVALID_ID)
                {
                    throw $e;
                }
            }
        }

        if (empty($payout) === true)
        {
            $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant, $input);

            //tracking slack app related events
            $this->trackPayoutsFetchEvent($input, $payout);

            return $payout->toArrayPublic();
        }

        return $payout;
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

        if ($this->shouldUnsetAccountNUmber())
        {
            if ($this->merchant->isFeatureEnabled(Features::ENABLE_SMART_ROUTING) === true)
            {
                if (isset($input[Entity::REFERENCE_ID]) === true)
                {
                    // unset account number to avoid smart routing failures
                    unset($input[Entity::BALANCE_ID]);
                }
            }
        }

        $payoutServiceInput = $input;

        if ($this->core->shouldFetchPayoutsViaMicroserviceAndUpdateInputAccordingly($payoutServiceInput) === true)
        {
            $payouts = $this->core->fetchMultipleFromPayoutsService($payoutServiceInput);

            return $payouts;
        }

        // For pending_on_roles filter we are skipping the fetch from old wf system.
        // Because for the CAC merchants workflow won't be present in old system.
        // And if the filter applied on custom role this will fail to find role in Admin/Roles table
        // hence flow will break if we don't skip.

        $isCacEnabled = $this->merchant->isCACEnabled();

        $payouts = new Base\PublicCollection;

        if ((
            ((isset($input[Payout\Entity::PENDING_ON_ROLES])) ||
                (isset($input[Entity::PENDING_ON_ME])))
                && $isCacEnabled === true
            ) === false)
        {
            $payouts = $this->repo->payout->fetchMultiple($input, $this->merchant, $useMasterConnection);
        }

        // Since pending payouts can be on both the api workflow system and workflow service
        // therefore we need to fetch and merge payouts from both systems
        $payoutsArr = $this->mergePendingPayoutsViaWorkflowService($input, $payouts);

        if (empty($payoutsArr) == true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_GET_EMPTY_RESPONSE,
                [
                    Entity::MERCHANT_ID       => $this->merchant->getId(),
                    'is_reference_id_present' => array_key_exists(Entity::REFERENCE_ID, $input),
                    'useMasterConnection'     => $useMasterConnection
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

    public function shouldUnsetAccountNUmber()
    {
        $unsetAccountNumberExperimentVariant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::RX_UNSET_ACCOUNT_NUMBER,
            $this->mode);

        return strtolower($unsetAccountNumberExperimentVariant) === 'on';
    }

    public function processReversedPayout(string $id)
    {
        $payout = $this->repo->payout->findByPublicId($id);

        $newPayout = (new Core)->retryReversedPayout($payout);

        return $newPayout->toArrayPublic();
    }

    public function processDispatchForPayoutsAutoExpiry(array $input)
    {
        $from = Carbon::now(Timezone::IST)->startOfDay()->subMonths(3)->getTimestamp();

        $merchantIdsToExclude = $input['excluded_merchant_ids'] ?? [];

        $targetMerchantIds = $input['merchant_ids'] ?? [];

        $pendingPayoutIds = $this->repo->payout->getPayoutsBeforeTimestampForStatus(Status::PENDING, $from, $merchantIdsToExclude, $targetMerchantIds);

        $queuedPayoutIds = $this->repo->payout->getPayoutsBeforeTimestampForStatus(Status::QUEUED, $from, $merchantIdsToExclude, $targetMerchantIds);

        $payoutIdsToDispatch = array_merge($pendingPayoutIds, $queuedPayoutIds);

        $this->core->dispatchPayoutsForAutoExpiry($payoutIdsToDispatch);

        $response =
            [
                'payout_ids_to_expire' => $pendingPayoutIds,
            ];

        $this->trace->info(
            TraceCode::PAYOUT_AUTO_EXPIRY_DISPATCH_COMPLETE,
            $response
            );

        return $response;
    }

    public function dispatchStuckPayouts(array $input)
    {
        return $this->core->dispatchStuckPayouts($input);
    }

    public function processInitiateForScheduledPayouts($input)
    {
        (new Validator)->validateInput(Validator::PROCESS_SCHEDULED_PAYOUTS, $input);

        $balanceIdsWhitelist = $input[Entity::BALANCE_IDS] ?? [];
        $balanceIdsBlacklist = $input[Entity::BALANCE_IDS_NOT] ?? [];

        $dispatchPayoutsScheduledPostApproval = $input[Entity::DISPATCH_PAYOUTS_SCHEDULED_POST_APPROVAL] ?? false;

        if ($dispatchPayoutsScheduledPostApproval)
        {
            $p2pSchedulePostApprovalMerchantList = (new Admin\Service)->getConfigKey([
                'key' => Admin\ConfigKey::P2P_SCHEDULE_POST_APPROVAL_MERCHANT_LIST
            ]);

            if (empty($p2pSchedulePostApprovalMerchantList) === true)
            {
                return [
                    'total_payout_count'                    => 0,
                    'dispatched_payout_count'               => 0,
                    'dispatched_payout_amount'              => 0,
                    'no_dispatch_for_payout_service_count'  => 0
                ];
            }

            $scheduledPayoutList = $this->repo->payout->getScheduledPayoutsToBeProcessedForMerchants(
                $p2pSchedulePostApprovalMerchantList,
                Status::SCHEDULED,
                self::DISPATCH_LIMIT_FOR_PAYOUTS_SCHEDULED_POST_APPROVAL
            );
        }
        else
        {
            $scheduledPayoutList = $this->repo->payout->getScheduledPayoutsToBeProcessed($balanceIdsWhitelist, $balanceIdsBlacklist);
        }

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

            $isCacEnabled = $this->merchant->isCACEnabled();

            $newConfig = (new WorkflowMigration())->convertOldSummaryIntoNew($this->merchant, $skipFetchFromWfs, $returnOld, $isCacEnabled);

            $this->app['trace']->info(
                TraceCode::WORKFLOW_CONFIG_MIGRATE_PAYLOAD,
                [
                    'payload' => $newConfig
                ]
            );

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

    public function getWorkflowSummaryByType($input)
    {
        // For test mode, we haven't enabled workflows yet
        // therefore returning empty array
        if ($this->mode === Constants\Mode::TEST)
        {
            return [];
        }

        (new Validator)->validateInput(Validator::WFS_CONFIG_FETCH, $input);

        return $this->core->getFetchWorkflowSummary(false, $input[Entity::CONFIG_TYPE]);
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
        $this->trace->info(TraceCode::BATCH_SUBMITTED_PAYOUTS_CRON_REQUEST);

        Tracer::startSpanWithAttributes(Constants\HyperTrace::BATCH_SUBMITTED_PAYOUTS_CRON_REQUEST,
                                        [
                                            'mode' => $this->app['rzp.mode'],
                                        ]);

        $merchantIds = $this->repo->payout->fetchMIDsWithBatchSubmittedPayouts();

        $this->core->processInitiateForBatchSubmittedPayouts($merchantIds);

        try
        {
            $this->payoutServiceBulkPayoutsClient->initiateBatchSubmittedCronViaMicroservice();
        }
        catch (\Exception $exception)
        {
            $this->trace->info(
                TraceCode::INITIATE_BATCH_SUBMITTED_CRON_VIA_MICROSERVICE_FAILED,
                [
                    'exception' => $exception->getMessage(),
                ]
            );
        }

        return $merchantIds;
    }

    public function cancelPayout(string $payoutId, $input)
    {
        (new Validator)->validateInput(Validator::CANCEL_PAYOUT, $input);

        /** @var Entity $payout */
        $payout = $this->core->getAPIModelPayoutFromPayoutService(Entity::stripSignWithoutValidation($payoutId));

        if (empty($payout) === true)
        {
            $payout = $this->repo->payout->findByIdAndMerchant($payoutId, $this->merchant);
        }

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
        $this->trace->info(
            TraceCode::CREATE_BULK_PAYOUT_INPUT,
            [
                'input' => $input
            ]);

        if ($this->merchant->isFeatureEnabled(Features::PAYOUT_SERVICE_ENABLED) === false)
        {
            return $this->createBulkPayoutForAPI($input);
        }
        else
        {
            $merchantID = $this->merchant->getId();

            $variant = $this->app->razorx->getTreatment(
                $merchantID,
                RazorxTreatment::BULK_PAYOUT_CA_VA_SEGREGATION_PAYOUTS_SERVICE,
                $this->mode,
                Payout\Entity::RAZORX_RETRY_COUNT
            );

            $this->trace->info(
                TraceCode::BULK_PAYOUT_CA_EXPERIMENT_VALUE,
                [
                    'variant'     => $variant,
                    'mode'        => $this->mode,
                    'merchant_id' => $merchantID,
                ]);

            if (strtolower($variant) === 'on')
            {
                // Merchant onboarded on both CA and VA
                return $this->handleBulkCreationForPSEnabledMerchant($input, $merchantID);
            }
            else
            {
                $merchantEnabledOnCA = $this->checkIfMerchantIsEnabledOnDirectAccount($merchantID);

                if ($merchantEnabledOnCA === true)
                {
                    $this->trace->error(TraceCode::CA_MERCHANT_IN_BULK_PAYOUT_VA_FLOW,
                                        [
                                            'input'   => $input,
                                            'variant' => $variant,
                                        ]);

                    throw new ServerErrorException(
                        'CA merchant should not come into Bulk Payout VA flow',
                        ErrorCode::SERVER_ERROR_CA_MERCHANT_IN_BULK_PAYOUT_VA_FLOW,
                        [
                            'input'   => $input,
                            'variant' => $variant,
                        ]
                    );
                }

                // Merchant onboarded only on VA
                return $this->payoutServiceBulkPayoutsClient->createBulkPayoutViaMicroservice($input);
            }
        }
    }

    private function getBalancesForBulkInput(array $input, $merchantID)
    {
        $accountNumbers = array_column($input, PayoutBatchHelper::RAZORPAYX_ACCOUNT_NUMBER);

        $accountNumbers = array_map('trim', $accountNumbers);

        $uniqueAccountNumbers = array_unique($accountNumbers);

        $balances = $this->repo->balance->
        getBalancesForAccountNumbersForTypeBanking($uniqueAccountNumbers, $merchantID);

        $balanceArray = $balances->toArray();

        $balanceIDs = array_column($balanceArray, Entity::ID);

        $this->trace->info(
            TraceCode::BALANCES_FOR_BULK_PAYOUT_INPUT,
            [
                Entity::BALANCE_IDS => $balanceIDs
            ]);

        return $balances;
    }

    private function getPayoutServiceAndApiInput(array $input, $merchantID)
    {
        $balances = $this->getBalancesForBulkInput($input, $merchantID);

        $accountNumbersAccountTypeMap = [];

        foreach ($balances as $balance)
        {
            $accountNumber = $balance->getAccountNumber();

            $accountNumbersAccountTypeMap[$accountNumber] = $balance->getAccountType();
        }

        $apiInput = [];

        $psInput = [];

        foreach($input as $item)
        {
            $accountNumber = trim($item[PayoutBatchHelper::RAZORPAYX_ACCOUNT_NUMBER]) ?? null;

            if ($accountNumbersAccountTypeMap[$accountNumber] == Merchant\Balance\AccountType::SHARED)
            {
                $psInput[] = $item;
            }
            else if ($accountNumbersAccountTypeMap[$accountNumber] == Merchant\Balance\AccountType::DIRECT)
            {
                $apiInput[] = $item;
            }
            else
            {
                // Considering this as default case. In case of invalid account number we are going to send
                // it to API for processing. This will add correct error response for corresponding invalid
                // account number row and send it to batch service.
                $apiInput[] = $item;
            }
        }

        return array($psInput, $apiInput);
    }

    private function checkIfMerchantIsEnabledOnDirectAccount($merchantID)
    {
        $balances = $this->repo->balance->
        getBalancesByMerchantIDForTypeBanking($merchantID);

        $balanceArray = $balances->toArray();

        $balanceIDs = array_column($balanceArray, Entity::ID);

        $this->trace->info(
            TraceCode::BALANCES_FOR_BULK_PAYOUT_MERCHANT,
            [
                Entity::BALANCE_IDS => $balanceIDs,
            ]);

        if (empty($balanceArray) === true)
        {
            $this->trace->error(TraceCode::BALANCE_RECORDS_NOT_AVAILABLE_FOR_MERCHANT,
                                [
                                    Entity::MERCHANT_ID => $merchantID,
                                    Entity::BALANCE_IDS => $balanceIDs,
                                ]);

            throw new ServerErrorException(
                'Balance records are not available for the merchant',
                ErrorCode::SERVER_ERROR_BALANCE_RECORDS_NOT_AVAILABLE_FOR_MERCHANT,
                [
                    Entity::MERCHANT_ID => $merchantID,
                    Entity::BALANCE_IDS => $balanceIDs,
                ]
            );
        }

        foreach ($balances as $balance)
        {
            $accountType = strtolower($balance->getAccountType());

            if ($accountType === Merchant\Balance\AccountType::DIRECT)
            {
                return true;
            }
        }

        return false;
    }

    private function handleBulkCreationForPSEnabledMerchant(array $input, $merchantID)
    {
        list($psInput, $apiInput) = $this->getPayoutServiceAndApiInput($input, $merchantID);

        $this->trace->info(
            TraceCode::BULK_PAYOUTS_API_PS_INPUT,
            [
                'ps_input'  => $psInput,
                'api_input' => $apiInput,
            ]);

        $finalResponse = new Base\PublicCollection;

        /**
         * TODO Proceed with Direct Account Payout creation if Payout Service creation fails
         * JIRA Link: https://razorpay.atlassian.net/browse/XPE-644
         *
         * We don't want to proceed with direct account payout creation incase if PS
         * doesn't send 2xx. Hence we return exception received from PS to batch service
         * so that whole input will be retried.
         */
        try
        {
            if (empty($psInput) === false)
            {
                $psResponse = $this->payoutServiceBulkPayoutsClient->
                createBulkPayoutViaMicroservice($psInput);

                if (isset($psResponse['items']) === true)
                {
                    $psPayouts = $psResponse['items'];

                    foreach ($psPayouts as $psPayout)
                    {
                        $finalResponse->push($psPayout);
                    }
                }
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::BULK_PAYOUT_CREATION_PS_FAILED,
                [
                    'input'     => $input,
                    'ps_input'  => $psInput,
                    'api_input' => $apiInput,
                ]);

            throw $exception;
        }

        try
        {
            if (empty($apiInput) === false)
            {
                $apiResponse = $this->createBulkPayoutForAPI($apiInput);

                if (isset($apiResponse['items']) === true)
                {
                    $apiPayouts = $apiResponse['items'];

                    foreach ($apiPayouts as $apiPayout)
                    {
                        $finalResponse->push($apiPayout);
                    }
                }
            }
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::BULK_PAYOUT_CREATION_API_FAILED,
                [
                    'input'     => $input,
                    'ps_input'  => $psInput,
                    'api_input' => $apiInput,
                ]);

            /**
             * TODO Proceed with Direct Account Payout creation if Payout Service creation fails
             * JIRA Link: https://razorpay.atlassian.net/browse/XPE-644
             *
             *  We want batch service to retry if input contains only direct account payouts.
             *  If it is mix of shared and direct, we don't want to retry for direct since it
             *  can cause a lot of retries that can cause issues in system.
             */
            if (empty($psInput) === true)
            {
                throw $exception;
            }
        }

        try
        {
            return $finalResponse->toArrayWithItems();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::BULK_PAYOUT_FAILED_POST_CREATION,
                [
                    'input'          => $input,
                    'final_response' => $finalResponse,
                ]);
        }
    }

    private function createBulkPayoutForAPI(array $input)
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

        $mutex = $this->app['api.mutex'];

        $mutexLockTimeout = 180;

        foreach ($input as $item)
        {
            $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

            $batchPayoutIdempotencyKeyRollout = $this->app->razorx->getTreatment(
                $this->merchant->getId(),
                RazorxTreatment::BATCH_PAYOUTS_IKEY_ROLLOUT,
                $this->mode);

            $this->trace->info(
                TraceCode::BULK_PAYOUTS_IKEY_ROLLOUT_EXPERIMENT,
                [
                    'feature'        => RazorxTreatment::BATCH_PAYOUTS_IKEY_ROLLOUT,
                    'output'         => $batchPayoutIdempotencyKeyRollout
                ]);

            if (strtolower($batchPayoutIdempotencyKeyRollout) === 'on')
            {
                $idempotencyKeyString = "batch_payout_" . $this->merchant->getId() . "_" . $idempotencyKey;

                $fundAccountID = $item[FundAccountHelper::FUND_ACCOUNT][FundAccountHelper::ID] ?? null;

                if($fundAccountID == null)
                {
                    $fundAccountID = $this->fundAccountService->getFundAccountIDFromAccountInput($item, $this->merchant);

                    $this->trace->info(
                        TraceCode::BULK_PAYOUTS_FUND_ACCOUNT_RETRIEVED,
                        [
                            Entity::FUND_ACCOUNT_ID => $fundAccountID,
                            'input'          => $item
                        ]);
                }
                else
                {
                    try
                    {
                        $fundAccount = $this->fundAccountService->checkFundAccountExistence($fundAccountID);

                        $fundAccountID = $fundAccount->getId();
                    }
                    catch (Exception\BaseException $exception)
                    {
                        $this->trace->traceException($exception,
                                                     Trace::INFO,
                                                     TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST
                        );

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

                        $this->trace->count(Metric::BULK_PAYOUTS_PROCESSING_BAD_REQUEST_ERROR, [
                            Constants\Metric::LABEL_ERROR_CODE => $exception->getCode(),
                        ]);

                        continue;
                    }
                }

                $fetchExistingPayout = function() use ($fundAccountID, $item, $idempotencyKey, $batchId)
                {
                    // checking for the payout presence in the last 24 hours since ikey becomes trivial after 24 hours.
                    return $this->repo->payout->fetchBulkByFundAccountIDAndIdempotencyKey($idempotencyKey,
                                                                     $this->merchant->getId(), $fundAccountID, 24
                    );
                };

                if (isset($fundAccountID))
                {
                    $existingPayoutBehaviour = self::EXISTING_PAYOUT_BEHAVIOUR_RETURN_ERROR;
                }
                else
                {
                    $existingPayoutBehaviour = self::EXISTING_PAYOUT_BEHAVIOUR_RETURN_NEW;
                }
            }
            else
            {
                $idempotencyKeyString = "batch_payout_" . $batchId . "_" . $idempotencyKey;

                $fetchExistingPayout = function() use ($item, $idempotencyKey, $batchId)
                {
                    return $this->repo->payout->fetchByIdempotentKey($idempotencyKey,
                                                                     $this->merchant->getId(),
                                                                     $batchId
                    );
                };

                $existingPayoutBehaviour = self::EXISTING_PAYOUT_BEHAVIOUR_RETURN_SAME;
            }

            $mutex->acquireAndRelease(
                $idempotencyKeyString,
                function() use ($fetchExistingPayout, $existingPayoutBehaviour, $item, $input, $idempotencyKey, $batchId, $validator, $payoutBatch, $createDuplicate)
                {
                    try
                    {
                        $this->trace->info(
                            TraceCode::BATCH_SERVICE_PAYOUT_BULK_REQUEST,
                            [
                                Entity::BATCH_ID => $batchId,
                                'input'          => $item
                            ]);

                        $validator->validateIdempotencyKey($idempotencyKey, $batchId);


                        if ($existingPayoutBehaviour != self::EXISTING_PAYOUT_BEHAVIOUR_RETURN_NEW)
                        {
                            $existingPayout = $fetchExistingPayout();

                            $this->trace->info(
                                TraceCode::BULK_PAYOUTS_EXISTING_PAYOUT_RETRIEVED,
                                [
                                    Entity::FUND_ACCOUNT_ID => $existingPayout,
                                    'input'          => $item
                                ]);
                        }
                        else
                        {
                            $existingPayout = null;
                        }

                        if ($existingPayout !== null)
                        {
                            $this->trace->info(TraceCode::PAYOUT_EXIST_WITH_SAME_IDEMPOTENCY_KEY,
                                               [
                                                   'input' => $existingPayout->toArrayPublic(),
                                                   Entity::IDEMPOTENCY_KEY => $item[Entity::IDEMPOTENCY_KEY],
                                               ]);

                            if ($existingPayoutBehaviour === self::EXISTING_PAYOUT_BEHAVIOUR_RETURN_ERROR)
                            {
                                $exceptionData = [
                                    Entity::BATCH_ID        => $batchId,
                                    Entity::IDEMPOTENCY_KEY => $idempotencyKey,
                                    'error'                 => [
                                        Error::DESCRIPTION       => 'Duplicate Payout with same idempotency key found: ' . $existingPayout->getId(),
                                        Error::PUBLIC_ERROR_CODE => ErrorCode::BAD_REQUEST_ERROR,
                                    ],
                                    Error::HTTP_STATUS_CODE => 400,
                                ];

                                if ($this->merchant->isFeatureEnabled(Features::PAYOUTS_BATCH))
                                {
                                    (new PayoutsBatch\Core())
                                        ->pushWebhookForPayoutCreationFailure($exceptionData, $item, $this->merchant);
                                }

                                $payoutBatch->push($exceptionData);
                            }
                            else
                            {
                                $payoutBatch->push($existingPayout->toArrayPublic() +
                                                   [Entity::IDEMPOTENCY_KEY => $existingPayout->getIdempotencyKey()]);
                            }
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
                                                     TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST
                        );

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

                        $this->trace->count(Metric::BULK_PAYOUTS_PROCESSING_BAD_REQUEST_ERROR, [
                            Constants\Metric::LABEL_ERROR_CODE => $exception->getCode(),
                        ]);
                    }
                    catch (\Throwable $throwable)
                    {
                        $this->trace->traceException($throwable,
                                                     Trace::CRITICAL,
                                                     TraceCode::BATCH_SERVICE_BULK_EXCEPTION
                        );

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

                        $this->trace->count(Metric::BULK_PAYOUTS_INTERNAL_SERVER_ERROR, [
                            Constants\Metric::LABEL_ERROR_CODE => $throwable->getCode(),
                        ]);
                    }
                },
                $mutexLockTimeout,
                ErrorCode::BAD_REQUEST_PAYOUT_OPERATION_FOR_MERCHANT_IN_PROGRESS);

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

    public function getTemplateFileForBulkPayouts($input)
    {
        (new Validator)->validateInput(Validator::PAYOUT_BULK_TEMPLATE_FILE, $input);

        $configKey = sprintf(
            PayoutConstants::BULK_TEMPLATE_CONFIG_KEY,
            $input[Entity::FILE_EXTENSION],
            $input[Entity::PAYOUT_METHOD],
            $input[Entity::BENEFICIARY_INFO]
        );

        $configKey = strtoupper($configKey);

        $templateFileId = env($configKey);

        $this->trace->info(
            TraceCode::PAYOUT_DOWNLOAD_TEMPLATE_REQUEST,
            [
                'config_key'           => $configKey,
                'template_file_id'     => $templateFileId
            ]);

        $ufhService = $this->getUfhService();

        $response = $ufhService->getSignedUrl($templateFileId, [], Account::SHARED_ACCOUNT);

        return [PayoutConstants::SIGNED_URL => $response[PayoutConstants::SIGNED_URL]];
    }

    public function processPayoutsBatch(string $batchId, array $input)
    {
        $settings = $input['config'];

        $verifyOtpInput = array_only($input, ['otp', 'token', 'total_payout_amount']);

        $verifyOtpInput = array_merge($verifyOtpInput, $settings);

        $verifyOtpInput = array_merge($verifyOtpInput, ['action' => 'create_payout_batch_v2']);

        (new UserCore())->verifyOtp($verifyOtpInput,
            $this->merchant,
            $this->user,
            $this->mode === Mode::TEST);

        $response = $this->app->batchService->processBatch($batchId, $input, $this->merchant);

        $batchPayoutSummaryEmailVariant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::BATCH_PAYOUTS_SUMMARY_EMAIL,
            $this->mode);

        if (strtolower($batchPayoutSummaryEmailVariant) == 'on')
        {
            $this->createBatchPayoutEmailSummaryReminder($response);
        }


        return $response;
    }

    private function createBatchPayoutEmailSummaryReminder($batchResponse)
    {
        $batchId = $batchResponse['id'];

        $merchantId = $this->merchant->getMerchantId();

        $mode = strtolower($this->mode);

        $reminderData = [
            'remind_at' => Carbon::now()->addMinutes(BatchPayoutConstants::PAYOUTS_BATCH_REMINDERS_CALLBACK_TIME)->timestamp, // T+2 hours
        ];

        $namespace  = BatchPayoutConstants::PAYOUTS_BATCH_NAMESPACE;

        $callbackUrl = sprintf(BatchPayoutConstants::PAYOUTS_BATCH_REMINDERS_CALLBACK_URL, $batchId, $merchantId, $mode);

        $request = [
            'namespace'     => $namespace,
            'entity_id'     => $batchId,
            'entity_type'   => 'batch',
            'reminder_data' => $reminderData,
            'callback_url'  => $callbackUrl,
        ];

        try
        {
            $this->trace->info(
                TraceCode::BULK_PAYOUTS_SUMMARY_EMAIL_REMINDER_CREATE_REQUEST,
                [
                    'create_reminder_callback' => [
                        'request' => $request,
                        'merchant_id' => $merchantId
                    ],
                ]);

            $this->app['reminders']->createReminder($request, '100000razorpay');
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_PAYOUTS_SUMMARY_EMAIL_REMINDER_CREATE_FAILED,
                [
                    'request'           => $request,
                    'merchant_id'       => $merchantId,
                ]);
        }
    }

    public function emailBatchPayoutsSummary(string $batchId, string $merchantId, string $mode): array
    {
        try
        {
            $this->trace->info(
                TraceCode::BULK_PAYOUT_SUMMARY_EMAIL,
                [
                    'reminder_callback_received' => [
                        'batch_id' => $batchId,
                        'merchant_id' => $merchantId,
                        'mode' => $mode
                    ],
                ]);

            list($mailData, $user) = $this->getBulkPayoutSummaryMailData($batchId, $merchantId, $mode);

            $this->trace->info(
                TraceCode::BULK_PAYOUT_SUMMARY_EMAIL,
                [
                    'mail_data' => $mailData,
                ]);

            $bulkPayoutSummaryMailable = new BulkPayoutSummary($mailData, $user);

            Mail::queue($bulkPayoutSummaryMailable);

            // throw a 400 response to stop further reminders from scheduling
            return [['error_code' => ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE], 400];
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::BULK_PAYOUT_SUMMARY_EMAIL_FAILED,
                [
                    'batch_id'          => $batchId,
                    'merchant_id'       => $merchantId,
                ]);

            // throw a 200 response to continue scheduling reminders
            return [[], 200];
        }
    }

    protected function getBulkPayoutSummaryMailData($batchId, $merchantId, $mode): array
    {
        $merchant = $this->repo->merchant->getMerchant($merchantId);

        $batchDetails = $this->batch->getBatchById('batch_' . $batchId, $merchant);

        $this->trace->info(
            TraceCode::BULK_PAYOUT_SUMMARY_EMAIL,
            [
                'batch_details' => $batchDetails,
            ]);

        $batchName = $batchDetails[BatchPayoutConstants::NAME];

        $batchCreatedAt = $batchDetails[BatchPayoutConstants::CREATED_AT];

        $batchCreatorId = $batchDetails[BatchPayoutConstants::CREATOR_ID];

        $totalCount = $batchDetails[BatchPayoutConstants::TOTAL_COUNT];

        $totalAmount = $batchDetails[BatchPayoutConstants::PROCESSED_AMOUNT];

        $debitAccountNumber = $batchDetails[BatchPayoutConstants::CONFIG][BatchPayoutConstants::ACCOUNT_NUMBER];

        $debitAccountName = $this->getAccountName($debitAccountNumber, $merchant, $mode);

        $payoutsSummary = $this->repo->payout->getPayoutsSummaryForBatchId($batchId, $mode);

        $user = $this->repo->user->getUserFromIdUsingMode($batchCreatorId, $mode);

        $userName = $user->getName();

        $payoutsStatusSummary = $this->getPayoutsStatusSummary($payoutsSummary);

        $this->trace->info(
            TraceCode::BULK_PAYOUT_SUMMARY_EMAIL,
            [
                'payouts_summary' => $payoutsStatusSummary,
            ]);

        /*
        $mailData will contain the data required to send the mail
        $mailData = [
            'batch_id' => 'batch_12345678901234',
            'batch_name' => 'This is a sample batch',
            'user_name' => 'I am Ironman',
            'total_amount' => 10000,
            'total_count' => 10,
            'source_account' => ICICI Bank,
            'current_time' => 'Tue, May 9, 2023 7:46 PM',
            'batch_created_at' => 'Tue, May 9, 2023 7:46 PM',
            'payout_status_count' => [
                'processing' => [
                    'total_amount' => 1000,
                    'total_count' => 1
                ],
                'processed' => [
                    'total_amount' => 9000,
                    'total_count' => 9
                ],
            ]
        ]
         * */

        $mailData = array();

        $mailData[BatchPayoutConstants::BATCH_ID] = 'batch_' . $batchId;

        $mailData[BatchPayoutConstants::BATCH_NAME] = $batchName;

        $mailData[BatchPayoutConstants::USER_NAME] = $userName;

        $mailData[BatchPayoutConstants::TOTAL_AMOUNT] = $totalAmount;

        $mailData[BatchPayoutConstants::TOTAL_COUNT] = $totalCount;

        $mailData[BatchPayoutConstants::SOURCE_ACCOUNT] = $debitAccountName;

        $mailData[BatchPayoutConstants::CURRENT_TIME] = Carbon::now(Timezone::IST)->toDayDateTimeString();

        $mailData[BatchPayoutConstants::BATCH_CREATED_AT] = Carbon::createFromTimestamp($batchCreatedAt, Timezone::IST)->toDayDateTimeString();

        $mailData[BatchPayoutConstants::PAYOUT_STATUS_COUNT] = $payoutsStatusSummary;

        return [$mailData, $user];
    }

    protected function getAccountName($accountNumber, $merchant, $mode): string
    {
        // If merchant is enabled on Account Sub-account feature, do not show the account number
        if ($merchant->isFeatureEnabled(Features::ASSUME_MASTER_ACCOUNT) === true ||
            $merchant->isFeatureEnabled(Features::ASSUME_SUB_ACCOUNT) === true)
        {
            return '';
        }

        $balance = $this->repo->balance->getBalanceByAccountNumber($accountNumber, $mode);

        $channel = $balance->getChannel();

        $accountType = $balance->getAccountType();

        $debitAccountName = PayoutConstants::RAZORPAYX_LITE;

        if ($accountType === AccountType::SHARED)
        {
            return $debitAccountName;
        }

        switch ($channel)
        {
            case Channel::AXIS:
                $debitAccountName = PayoutConstants::CHANNEL_AXIS_BANK;
                break;
            case Channel::ICICI:
                $debitAccountName = PayoutConstants::CHANNEL_ICICI_BANK;
                break;
            case Channel::RBL:
                $debitAccountName = PayoutConstants::CHANNEL_RBL_BANK;
                break;
            case Channel::YESBANK:
                $debitAccountName = PayoutConstants::CHANNEL_YES_BANK;
                break;
            default:
                $debitAccountName = $channel;
                break;
        }

        return $debitAccountName;
    }

    protected function getPayoutsStatusSummary($payoutsSummary): array
    {
        $payoutsStatusSummary = array();

        foreach ($payoutsSummary as $payoutSummary)
        {
            $amount = $payoutSummary->getAmount();

            $status = Status::getPublicStatusFromInternalStatus($payoutSummary->getStatus());

            if (array_key_exists($status, $payoutsStatusSummary))
            {
                $summary = $payoutsStatusSummary[$status];

                $totalAmount = $summary[BatchPayoutConstants::TOTAL_AMOUNT];

                $totalAmount = $totalAmount + $amount;

                $totalCount = $summary[BatchPayoutConstants::TOTAL_COUNT];

                $totalCount = $totalCount + 1;
            }
            else
            {
                $totalAmount = $amount;

                $totalCount = 1;
            }

            $payoutsStatusSummary[$status] = [
                BatchPayoutConstants::TOTAL_AMOUNT => $totalAmount,
                BatchPayoutConstants::TOTAL_COUNT  => $totalCount
            ];
        }

        return $payoutsStatusSummary;

    }

    public function getBatchRows(string $batchId, array $input)
    {
        return $this->app->batchService->getBatchEntries($batchId, $input, $this->merchant);
    }

    public function getScheduleSlotsForPayouts()
    {
        if ($this->merchant->isFeatureEnabled(FeatureConstant::PAYOUT_SERVICE_ENABLED))
        {
            return $this->core->getScheduleTimeSlotsViaPayoutService();
        }
        else
        {
            return Schedule::getTimeSlotsForScheduledPayouts();
        }
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

        $queuedPayouts = $this->repo->payout->fetchOptimisedQueuedAndOnHoldPayouts($merchantId);

        $this->trace->info(
            TraceCode::PAYOUT_SUMMARY_API_ANALYSIS,
            [
                'description' => 'completed fetching queued payouts',
            ]);

        foreach ($queuedPayouts as $payout)
        {
            $bankingAccountId = (new \RZP\Models\BankingAccount\Service())->fetchBankingAccountIdByBalanceId($payout['balance_id']);

            $summaryForQueuedReason = $this->processQueuedSummaryAggregate($payout);

            $queuedPayoutsSummary[$bankingAccountId][Status::QUEUED][$payout['queued_reason']] = $summaryForQueuedReason;
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

        $totalAmount = 0;

        foreach ($queuedPayoutsForReason as $payout)
        {
            $totalAmount += $payout->getAmount();
        }

        return [
            'balance'       => $currentBalance,
            'count'         => count($queuedPayoutsForReason),
            'total_amount'  => $totalAmount,
            'total_fees'    => 0,
        ];
    }

    protected function processQueuedSummaryAggregate($queuedPayouts)
    {
        return [
            'balance'       => $queuedPayouts['balance'],
            'count'         => $queuedPayouts['count'],
            'total_amount'  => $queuedPayouts['amount'],
            'total_fees'    => 0,
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
            $bankingAccountId = (new \RZP\Models\BankingAccount\Service())->fetchBankingAccountIdByBalanceId($balanceId);

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
     * setPendingOnUserFlagForPendingPayoutsViaWFS accepts an array of payouts and sets the pending_on_user flag
     * for the payouts using the WFS data dual written in API DB.
     * @param array $payoutArray
     */
    public function setPendingOnUserFlagForPendingPayoutsViaWFS(array & $payoutArray)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        /** @var Route $route */
        $route = app('api.route');

        $routeName = $route->getCurrentRouteName();

        // If route is not fetch_multiple or fetch_multiple_all or if the request is from Slack app, return
        if (!in_array($routeName, [Entity::PAYOUT_FETCH_MULTIPLE, Entity::PAYOUT_FETCH_MULTIPLE_ALL], true)
            or ($basicAuth->isSlackApp() ===  true))
        {
            return;
        }

        if (($basicAuth->isStrictPrivateAuth() === true) or
            ($basicAuth->isAdminAuth() === true))
        {
            return;
        }

        // Workflows are not enabled on test mode for now
        if ((app('rzp.mode') === Mode::TEST) or
            ($basicAuth->getUser() === null) or
            ($this->merchant === null) or
            ($this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS) === false))
        {
            return;
        }

        $pendingPayoutsViaWFS = [];

        // If the payout is on WFS flow or if the payout doesn't already have the pending_on_user flag set,
        // we fetch the flag from WFS tables in api DB. So, we will ignore only those payouts which are on API's workflow system.
        // Since only payouts pending on user will have these flags toggled to true later on, we will initially set the
        // flag to false for all payouts so that the pending_on_user param is returned for all payouts in the response
        foreach ($payoutArray as $index => $payout)
        {
            if (isset($payout[Entity::ID]) == true)
            {
                $payoutId = $payout[Entity::ID];

                Entity::verifyIdAndStripSign($payoutId);

                $workflowViaWorkflowService = $this->repo->workflow_entity_map->isPresent(Entity::PAYOUT, $payoutId);

                if (($workflowViaWorkflowService === true) or
                    (isset($payout[Entity::PENDING_ON_USER]) == false))
                {
                    $payout[Entity::PENDING_ON_USER] = false;

                    array_push($pendingPayoutsViaWFS, $payoutId);

                    $payoutArray[$index] = $payout;
                }
            }
        }

        if (empty($pendingPayoutsViaWFS) === true)
        {
            return;
        }

        $pendingPayoutIds = $this->repo->payout->filterPayoutsPendingOnUserViaWFS($pendingPayoutsViaWFS);

        $this->trace->info(
            TraceCode::PAYOUT_LIST_API_PAYOUTS_PENDING_ON_USER,
            [
                'description' => 'list of payouts pending on user',
                'payout_ids' => $pendingPayoutIds
            ]);

        foreach ($pendingPayoutIds as $pendingPayoutId)
        {
            foreach ($payoutArray as $index => $payout)
            {
                if (isset($payout[Entity::ID]) == true)
                {
                    $payoutId = $payout[Entity::ID];

                    Entity::verifyIdAndStripSign($payoutId);

                    if ($payoutId == $pendingPayoutId)
                    {
                        $payout[Entity::PENDING_ON_USER] = true;
                    }

                    $payoutArray[$index] = $payout;
                }
            }
        }
    }


    /**
     * @param string $isSummaryApiExperimentEnabled
     * @return array
     * @throws Exception\UserWorkflowNotApplicableException
     */
    protected function getPendingPayoutsSummary()
    {
        $pendingPayoutsSummary = [];

        // we are first checking if there is atleast one pending payout for merchant
        // and then only we will trigger the workflow query
        $allPendingPayouts = $this->repo->payout->checkIfPendingPayoutsExist($this->merchant->getId());

        if (count($allPendingPayouts) <= 0)
        {
            return $pendingPayoutsSummary;
        }

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

        foreach ($groupedPendingPayouts as $balanceId => $payouts)
        {
            $bankingAccountId = (new \RZP\Models\BankingAccount\Service())->fetchBankingAccountIdByBalanceId($balanceId);

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

        $this->checkIfPayoutIsAllowed(false, $input);

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

        $validator = new Validator();

        $validator->merchant = $this->merchant;

        $validator->validateInput(Validator::FUND_ACCOUNT_PAYOUT_COMPOSITE, $input);

        $contactData = $this->createContactForCompositePayout($input);

        $fundAccountData = $this->createFundAccountForCompositePayout($input, $contactData);

        $fundAccountId = $fundAccountData[FundAccount\Entity::ID];

        $payoutInput = $this->getInputForPayoutCreateFromComposite($input, $fundAccountId);

        return $payoutInput;
    }

    // @TODO: refactor this/move it to FundAccount entity.
    protected function unsetSensitiveCardDetails(array $input): array
    {
        $fundAccountInput = $input[Entity::FUND_ACCOUNT] ?? [];

        if ((isset($fundAccountInput[FundAccount\Entity::CARD]) === true) and
            (is_array($fundAccountInput[FundAccount\Entity::CARD]) === true))
        {
            $input = $this->trimCardNumberIfRequired($input);

            if (empty($fundAccountInput[FundAccount\Entity::CARD][Card\Entity::NUMBER]) === false)
            {
                $input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::IIN] =
                    substr($fundAccountInput[FundAccount\Entity::CARD][Card\Entity::NUMBER], 0, 6);
            }

            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::CVV]);
            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::NAME]);
            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::NUMBER]);
            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::EXPIRY_MONTH]);
            unset($input[Entity::FUND_ACCOUNT][FundAccount\Entity::CARD][Card\Entity::EXPIRY_YEAR]);
        }

        return $input;
    }

    protected function getInputForContactCreateFromComposite(array $input): array
    {
        return $input[Entity::FUND_ACCOUNT ][Entity::CONTACT];
    }

    protected function getInputForFundAccountCreateFromComposite(array $input, Contact\Entity $contactEntity): array
    {
        $fundAccountInput = $input[Entity::FUND_ACCOUNT];

        unset($fundAccountInput[Entity::CONTACT]);

        $fundAccountInput[FundAccount\Entity::CONTACT_ID] = $contactEntity->getPublicId();

        $fundAccountInput[FundAccount\Entity::CONTACT_ENTITY] = $contactEntity;

        return $fundAccountInput;
    }

    protected function getInputForPayoutCreateFromComposite(array $input, string $fundAccountId): array
    {
        $payoutInput = $input;

        unset($payoutInput[Entity::FUND_ACCOUNT]);

        $payoutInput[Payout\Entity::FUND_ACCOUNT_ID] = $fundAccountId;

        return $payoutInput;
    }

    protected function createContactForCompositePayout(array $input): Contact\Entity
    {
        $contactInput = $this->getInputForContactCreateFromComposite($input);

        $contactInput['isComposite'] = true;

        $contactResponse = (new Contact\Service)->create($contactInput);

        return $contactResponse[Contact\Entity::CONTACT_ENTITY];
    }

    protected function createFundAccountForCompositePayout(array $input, Contact\Entity $contactEntity): array
    {
        $fundAccountInput = $this->getInputForFundAccountCreateFromComposite($input, $contactEntity);

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
        try
        {
            $payout = $this->repo->payout->findOrFail($id);
        }
        catch (\Throwable $exception)
        {
            $payout = $this->core->getAPIModelPayoutFromPayoutService($id);

            if (empty($payout) === true)
            {
                throw $exception;
            }
        }

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
            $account = $compositePayout->fundAccount->account;

            $compositePayout = $compositePayout->load('fundAccount.contact');

            // Specifically adding here for card fund account to preserve card Meta Data, since
            // loading fundAccount.contact relation was overriding all the existing relations
            // This was causing us to make a network call to vault to again fetch card Meta Data
            // to populate fields in toArrayPublic() for payout
            if (($compositePayout->fundAccount->getAccountType() === Entity::CARD) and
                (isset($account) === true))
            {
                $compositePayout->fundAccount->account()->associate($account);
            }
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

    public function postFreePayoutMigration(array $input)
    {
        $this->trace->info(TraceCode::MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE_REQUEST,
                           [
                               'input' => $input,
                           ]);

        $action = $input['action'];

        switch ($action)
        {
            case "basd":
                $totalCount = 0;

                foreach ($input['data'] as $data)
                {
                    (new BasDetails\Core)->handleBasDetailsActions($data);

                    $totalCount++;
                }

                return [
                    'total_count' => $totalCount,
                ];

            case 'bas':
                $totalCount = 0;

                foreach ($input['data'] as $data)
                {
                    (new \RZP\Models\BankingAccountStatement\Service)->handleBasAdminActions($data);

                    $totalCount++;
                }

                return [
                    'total_count' => $totalCount,
                ];

            case 'ps_dual_write':
                $totalCount = 0;

                foreach ($input['data'] as $data)
                {
                    $this->payoutServiceDualWrite($data);

                    $totalCount++;
                }

                return [
                    'total_count' => $totalCount,
                ];
        }

        try
        {
            (new Validator)->validateInput(Validator::MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE, $input);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE_REQUEST_FAILED,
                [
                    'input' => $input,
                ]
            );

            throw $exception;
        }

        return $this->core->postFreePayoutMigration($input);
    }

    public function postFreePayoutRollback(array $input)
    {
        $this->trace->info(TraceCode::FREE_PAYOUT_ROLLBACK_REQUEST_FROM_PAYOUTS_SERVICE,
                           [
                               'input' => $input,
                           ]);

        try
        {
            (new Validator)->validateInput(Validator::ROLLBACK_FREE_PAYOUT_PAYOUTS_SERVICE, $input);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::MIGRATE_FREE_PAYOUT_PAYOUTS_SERVICE_REQUEST_FAILED,
                [
                    'input' => $input,
                ]
            );

            throw $exception;
        }

        return $this->core->postFreePayoutRollback($input);
    }

    public function payoutSourceUpdate(array $input)
    {
        return $this->core->payoutSourceUpdate($input);
    }

    public function statusDetailsSourceUpdate(array $input)
    {
        return $this->core->statusDetailsSourceUpdate($input);
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

            $pendingPayoutsViaWfs = $this->repo->payout->fetchMultiple($input, $this->merchant, false);
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

        $this->setPendingOnUserFlagForPendingPayoutsViaWFS($payoutItems);

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

        $payoutIds = $input[Entity::PAYOUT_IDS];

        $payouts = $this->repo->payout->findMany($payoutIds);

        $failedIds = [];
        $processedIds = [];

        // In this for loop we will update only API payouts.
        // API payout ids are removed from the payout ids list so that we have list of payout service payouts.
        /** @var Entity $payout */
        foreach ($payouts as $payout)
        {
            try
            {
                if ($payout->getIsPayoutService() === false)
                {
                    array_delete($payout->getId(), $payoutIds);
                }
                else
                {
                    continue;
                }

                $this->updatePayoutAndFTAManually($payout, $input);

                $processedIds[] = $payout->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::NON_PS_PAYOUT_BULK_MANUAL_STATUS_UPDATE_EXCEPTION,
                    [
                        'payout_id'         => $payout->getId(),
                        'failure_reason'    => $e->getMessage(),
                    ]);

                $failedIds[] = ["{$payout->getPublicId()} - {$e->getMessage()}"];
            }
        }

        $result = [
            'total_count'                  => count($payouts),
            'total_payout_service_payouts' => 0
        ];

        foreach ($payoutIds as $payoutId)
        {
            try
            {
                $payout = $this->core->getAPIModelPayoutFromPayoutService($payoutId);

                if (empty($payout) === true)
                {
                    continue;
                }

                $this->updatePayoutAndFTAManually($payout, $input);

                $result['total_payout_service_payouts']++;

                $processedIds[] = $payout->getId();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PS_PAYOUT_BULK_MANUAL_STATUS_UPDATE_EXCEPTION,
                    [
                        'payout_id'         => $payoutId,
                        'failure_reason'    => $e->getMessage(),
                    ]);

                $failedIds[] = ["{$payoutId} - {$e->getMessage()}"];
            }
        }

        $result['total_count'] += $result['total_payout_service_payouts'];

        $result[] = [
            'processed_ids'                => $processedIds,
            'failed_ids'                   => $failedIds,
        ];

        return $result;
    }

    protected function updatePayoutAndFTAManually(Entity $payout, array $input) : Entity
    {
        $oldStatus = $payout->getStatus();

        /** @var Entity $payout */
        $payout = $this->core->updatePayoutStatusManually($payout, $input);

        $this->core->updateFTAOfPayoutManually($payout, $input);

        $this->core->processTdsForPayout($payout, $oldStatus);

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

    public function processDispatchPartnerBankOnHoldPayouts(): array
    {
        try {

            $redis = $this->app['redis'];
            $downtimeRawQuery = "";
            $uptimeRawQuery = "";
            $payoutIdsToProcess = [];
            $payoutIdsToFail = [];
            $merchantIdsForAutoCancel = [];

            $downtimeKeys = $redis->hgetall(Core::PARTNER_BANK_HEALTH_REDIS_KEY);

            $this->trace->info
            (
                TraceCode::PARTNER_BANK_DOWN_LIST_FOR_ON_HOLD_PAYOUT,
                [
                    'partner_bank_downtime_info' => $downtimeKeys,
                ]
            );

            // generate raw query string
            foreach ($downtimeKeys as $key) {

                $key = json_decode($key);

                if ($key->status === Events::STATUS_UPTIME) {
                    $uptimeRawQuery = $this->generateRawQuery($uptimeRawQuery, $key);
                }

                if ($key->status === Events::STATUS_DOWNTIME) {
                    $downtimeRawQuery = $this->generateRawQuery($downtimeRawQuery, $key);
                }
            }

            // find hold payouts to process
            if (strlen($uptimeRawQuery) > 0) {

                $uptimeRawQuery = "( " . $uptimeRawQuery . " )";

                $payoutIdsToProcess = $this->repo->payout->getPartnerBankHoldPayoutsToProcess($uptimeRawQuery);

            }

            // find merchant id's having on hold payouts
            if (strlen($downtimeRawQuery) > 0) {

                $downtimeRawQuery = "( " . $downtimeRawQuery . " )";

                $merchantIdsForAutoCancel = $this->repo->payout->getMerchantIdsWithLeastOnePartnerBankOnHoldPayout($downtimeRawQuery);

            }

            // distibute processing of on hold payouts amongst merchants
            if ($merchantIdsForAutoCancel != null and count($merchantIdsForAutoCancel) > 0) {

                $fetchLimitCount = floor(self::PARTNER_BANK_ON_HOLD_FETCH_LIMIT / count($merchantIdsForAutoCancel));

                foreach ($merchantIdsForAutoCancel as $merchantId) {

                    $slaValue = $this->core->getMerchantSlaForOnHoldPayouts($merchantId, QueuedReasons::GATEWAY_DEGRADED);

                    $payoutIdsToFailForMerchant = $this->repo->payout->getPartnerBankOnHoldPayoutsForMerchantIdSlaBreached($merchantId,
                        $slaValue, $fetchLimitCount, QueuedReasons::GATEWAY_DEGRADED, $downtimeRawQuery);

                    $payoutIdsToFail = array_merge($payoutIdsToFail, $payoutIdsToFailForMerchant);
                }
            }

            $this->trace->info
            (
                TraceCode::PAYOUT_ON_HOLD_TO_BE_DISPATCHED,
                [
                    'payout_ids_to_process'        => $payoutIdsToProcess,
                    'payout_ids_to_auto_cancel'    => $payoutIdsToFail,
                    'merchant_ids_for_auto_cancel' => $merchantIdsForAutoCancel,
                    'downtime_query'               => $downtimeRawQuery,
                    'uptime_query'                 => $uptimeRawQuery,
                ]
            );

            $payoutIdsToProcess = array_unique(array_merge($payoutIdsToProcess, $payoutIdsToFail));

            $this->core->dispatchPartnerBankOnHoldPayouts($payoutIdsToProcess);

            $response =
                [
                    'onhold_payout_ids_to_process' => $payoutIdsToProcess,
                ];

            $this->trace->info
            (
                TraceCode::PARTNER_BANK_ON_HOLD_DISPATCH_LIST,
                $response
            );

            return $response;
        }
        catch (\Exception $exception) {
            $this->trace->info(
                TraceCode::PARTNER_BANK_ON_HOLD_DISPATCH_FAILED_WITH_EXCEPTION,
                [
                    'exception' => $exception->getMessage(),
                ]
            );
            return ['Process failed with exception'];
        }
    }

    public function payoutsServiceCreateFailureProcessingCron($input)
    {
        $this->trace->info
        (
            TraceCode::PAYOUTS_SERVICE_CREATE_FAILURE_PROCESSING_CRON_REQUEST,
            [
                'input' => $input,
            ]
        );

        (new Validator)->validateInput(Validator::PAYOUTS_SERVICE_CREATE_FAILURE_PROCESSING_CRON, $input);

        try
        {
            $this->payoutServiceCreateFailureProcessingCronClient->triggerCreateFailureProcessingViaMicroservice($input);
        }
        catch (\Exception $exception)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_SERVICE_CREATE_FAILURE_PROCESSING_CRON_FAILED,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }

        return [
            'success' => true,
        ];
    }

    public function payoutsServiceUpdateFailureProcessingCron($input)
    {
        $this->trace->info
        (
            TraceCode::PAYOUTS_SERVICE_UPDATE_FAILURE_PROCESSING_CRON_REQUEST,
            [
                'input' => $input,
            ]
        );

        (new Validator)->validateInput(Validator::PAYOUTS_SERVICE_UPDATE_FAILURE_PROCESSING_CRON, $input);

        try
        {
            $this->payoutServiceUpdateFailureProcessingCronClient->triggerUpdateFailureProcessingViaMicroservice($input);
        }
        catch (\Exception $exception)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_SERVICE_UPDATE_FAILURE_PROCESSING_CRON_FAILED,
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            throw $exception;
        }

        return [
            'success' => true,
        ];
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

        $contactType = $contactInput[Contact\Entity::TYPE] ?? null;

        if ((empty($contactType) === false) and (Contact\Type::isInInternal($contactType) === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                null,
                $input
            );
        }

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

    protected function checkIfPayoutIsAllowed(bool $isCompositePayout, array $input, bool $internal = false, Merchant\Balance\Entity $balance = null)
    {
        $payoutMode = $input[Payout\Entity::MODE] ?? null;

        if ($balance === null)
        {
            $balance = $this->repo->balance->findByPublicIdAndMerchant($input[Payout\Entity::BALANCE_ID], $this->merchant);
        }

        $this->checkIfDirectAccountIsActive($balance);

        $this->checkIfIciciDirectAccountPayoutShouldBeAllowed($input, $internal, $balance);

        if ($this->merchant->isFeatureEnabled(Features::ALLOW_NON_SAVED_CARDS) === true)
        {
            if ($isCompositePayout === false)
            {
                $this->core->isPayoutToFundAccountAllowed($input[Entity::FUND_ACCOUNT_ID], $payoutMode, $isCompositePayout);
            }
            else
            {
                if (isset($input[Entity::FUND_ACCOUNT][Entity::CARD][Card\Entity::INPUT_TYPE]) === true)
                {
                    $inputType = $input[Entity::FUND_ACCOUNT][Entity::CARD][Card\Entity::INPUT_TYPE];

                    if ((($inputType === Card\InputType::RAZORPAY_TOKEN) or
                         ($inputType === Card\InputType::SERVICE_PROVIDER_TOKEN)) and
                        ($payoutMode !== PayoutMode::CARD))
                    {
                        $this->trace->error(TraceCode::MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS,
                                            [
                                                'is_composite_payout' => $isCompositePayout,
                                                'payout_mode'         => $payoutMode,
                                                'is_tokenised'        => true
                                            ]);

                        throw new Exception\BadRequestException(
                            ErrorCode::BAD_REQUEST_MODE_NOT_SUPPORTED_FOR_PAYOUT_TO_TOKENISED_CARDS);
                    }
                }
            }
        }
    }

    public function checkIfDirectAccountIsActive(Merchant\Balance\Entity $balance = null)
    {
        if (is_null($balance))
        {
            return;
        }

        if ($balance->isAccountTypeDirect())
        {
            /** @var BasDetails\Entity $basd */
            $basd = $balance->bankingAccountStatementDetails;

            if ((!is_null($basd)) and
                (in_array($basd->getStatus(), BasDetails\Status::getStatusesForActiveCaFlows())))
            {
                return;
            }

            $this->trace->error(TraceCode::PAYOUTS_NOT_ALLOWED_DUE_TO_NO_BASD,
                [
                    'balance_id'  => $balance->getId(),
                    'merchant_id' => $balance->getMerchantId()
                ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'API payouts are not available for this account'
            );
        }
    }

    /**
     * Checks if a merchant is allowed to make a ICICI direct account payout
     *
     * @param array $input
     * @param bool $internal
     * @param Merchant\Balance\Entity $balance
     * @throws Exception\BadRequestException
     */
    protected function checkIfIciciDirectAccountPayoutShouldBeAllowed(array $input, bool $internal, Merchant\Balance\Entity $balance)
    {
        $shouldBlockNonBaasPayouts = Payout\Core::shouldBlockIciciDirectAccountPayoutsForNonBaasMerchants($balance, $this->merchant);

        if (($this->app['basicauth']->isProxyAuth() === true) and ($shouldBlockNonBaasPayouts === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Dashboard payouts are not available for this account'
            );
        }

        if (Payout\Core::checkIfMerchantIsAllowedForIciciDirectAccountPayoutWith2Fa($balance, $this->merchant) === false and
            $shouldBlockNonBaasPayouts === false)
        {
            return;
        }

        $isStrictPrivateAuth = $this->auth->isStrictPrivateAuth();

        $isVendorPaymentApp = $this->auth->isVendorPaymentApp();

        $isCapitalCollectionsApp = $internal === true and $this->auth->isCapitalCollectionsApp();

        // API payouts for ICICI 2FA enabled merchants are not allowed unless it is created from Vendor Payments
        // app or Capital collections app
        if ((($isStrictPrivateAuth === true or $this->isAllowedInternalApp() or $this->auth->isBatchApp()) and
            ($isVendorPaymentApp === false) and
            ($isCapitalCollectionsApp === false)) or ($shouldBlockNonBaasPayouts === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'API payouts are not available for this account'
            );
        }

        if (isset($input[Payout\Entity::SCHEDULED_AT]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'Scheduled payouts cannot be created using ICICI CA 2FA'
            );
        }
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
        $variant = $this->app->razorx->getTreatment(
            $this->merchant->getId(),
            RazorxTreatment::STATUS_REASON_MAP_VIA_PS,
            $this->mode);

        if ($this->merchant->isFeatureEnabled(Features::PAYOUT_SERVICE_ENABLED) and strtolower($variant) === 'on')
        {
            return $this->payoutStatusReasonMapApiServiceClient->GetPayoutStatusReasonMapViaMicroService();
        }

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
            else if ($channel === 'axis')
            {
                $input[Entity::CHANNEL] = 'axis';
            }
            else if ($channel === 'yesbank')
            {
                $input[Entity::CHANNEL] = 'yesbank';
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

    public function createPayoutViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        $this->core->createPayoutViaLedgerCronJob($blacklistIds, $whitelistIds, $limit);
    }

    public function updateMerchantOnHoldSlas(array $input)
    {
        $this->trace->info(
            TraceCode::ON_HOLD_SLA_UPDATE_API_REQUEST,
            [
                'input' => $input,
            ]
        );

        (new Validator)->validateMerchantSlasForOnHoldPayouts($input);

        $adminService = new Admin\Service;

        $merchantSlaConfigList = $adminService->getConfigKey([
            'key' => Admin\ConfigKey::RX_ON_HOLD_PAYOUTS_MERCHANT_SLA
        ]);

        $success = false;

        $this->repo->transaction(
            function () use ($input, & $merchantSlaConfigList, $adminService, & $success) {
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

                $success = true;
            }
        );

        $this->trace->info(
            TraceCode::ON_HOLD_SLA_UPDATE_API_RESPONSE,
            [
                'success' => $success
            ]
        );

        if ($success === true)
        {
            $configList = [];

            foreach ($input as $sla => $merchantIds)
            {
                foreach ($merchantIds as $merchantId)
                {
                    $configList['merchants_sla'][$merchantId] = $sla;
                }
            }

            try
            {
                $this->payoutServiceOnHoldSLAUpdateClient->onHoldSLAUpdateToMicroservice($configList);
            }
            catch (\Exception $exception)
            {
                $this->trace->info(
                    TraceCode::ON_HOLD_SLA_UPDATE_TO_MICROSERVICE_FAILED,
                    [
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }

        return ['success' => $success];
    }

    public function decrementFreePayoutsForPayoutsService($input)
    {
        return $this->core->decrementFreePayoutsForPayoutsService($input);
    }

    /**
     * Gets the signed URL for the attachment added against the payout
     * @param string $payoutId
     * @param string $attachmentId
     *
     * @return array|string[]
     * @throws BadRequestValidationFailureException
     * @throws Exception\ServerErrorException
     */
    public function getAttachmentSignedUrl(string $payoutId, string $attachmentId)
    {
        $payoutId = Entity::verifyIdAndStripSign($payoutId);

        // validate the attachment ID against the payout
        $payoutDetailsEntity = $this->payoutDetailsCore->getPayoutDetailsById($payoutId);

        $attachments = $payoutDetailsEntity->getAttachmentsAttribute();

        $found = false;

        foreach ($attachments as $attachment)
        {
            if ($attachment[PayoutDetails\Entity::ATTACHMENTS_FILE_ID] === $attachmentId)
            {
                $found = true;

                break;
            }
        }

        if (!$found)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_ATTACHMENT_NOT_LINKED_TO_PAYOUT,
                PayoutDetails\Entity::ATTACHMENTS_FILE_ID,
                $attachmentId
            );
        }


        return $this->payoutDetailsCore
            ->getAttachmentSignedUrl($attachmentId);
    }

    public function uploadAttachment(array $input): array
    {
        (new Validator)->validateInputForPayoutAttachment($input);

        $inputFile = $input[self::FILE];

        // only files <= 5MB (5*1024*1024) can be uploaded as attachments
        if ($inputFile->getSize() > 5242880)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ATTACHMENT_SIZE,
                self::FILE_SIZE,
                $inputFile->getSize(),
                'File size greater than 5MB cannot be uploaded'
            );
        }

        if ($inputFile->getSize() == 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_EMPTY_FILE_UPLOADED,
                self::FILE_SIZE,
                $inputFile->getSize(),
                'Empty file uploaded'
            );
        }

        return $this->payoutDetailsCore->uploadAttachment($inputFile,
            $input['file']->getClientOriginalName(),
            $this->merchant);
    }

    /**
     * Update attachment against the payout
     * All users can update attachment in non-final/non-processing state
     * Only Owner and Admin can update attachment in final or processing state
     * @param string $payoutId
     * @param array  $input
     *
     * @return array
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function updateAttachments(string $payoutId, array $input): array
    {
        $payoutId = Entity::verifyIdAndStripSign($payoutId);

        $this->trace->info(TraceCode::UPDATE_PAYOUT_ATTACHMENTS_INPUT, [
            'payout_id' => $payoutId,
            'input'     => $input,
        ]);

        // updating attachments on payouts is only allowed for vanilla payouts
        $payoutSource = (new PayoutSourceCore())->getPayoutSource($payoutId);

        if ($payoutSource !== null)
        {
           throw new BadRequestException(
               ErrorCode::BAD_REQUEST_INVALID_PAYOUT_SOURCE_FOR_UPDATE,
               PayoutDetails\Entity::ATTACHMENTS,
               $payoutSource,
               'Invalid Payout Source for Update');
        }

        (new Validator)->validateAttachments($input);

        return $this->payoutDetailsCore
            ->updateAttachments($payoutId, $input);
    }

    public function payoutServiceRenameAttachments($payoutId, $input)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_RENAME_ATTACHMENTS,
            [
                'payout_id' => $payoutId,
                'input'     => $input,
            ]
        );

        $result = $this->payoutDetailsCore->renameAttachments($payoutId, $input[PayoutDetails\Entity::ATTACHMENTS]);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_RENAME_ATTACHMENTS_COMPLETE,
            [
                'payout_id' => $payoutId,
                'response'  => $result,
            ]
        );

        return ['result' => $result];
    }

    /**
     * Updates all payouts against Payout Link with the new attachments
     *
     * @param array $input
     *
     * @return string[]
     * @throws BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function bulkUpdateAttachments(array $input)
    {
        $validator = new Validator();

        $validator->validateInput(Validator::BULK_UPDATE_ATTACHMENTS, $input);

        $payoutIds = $input[PayoutDetails\Entity::PAYOUT_IDS];

        $validPayoutIds = array();

        $invalidPayoutIds = array();

        $payoutsSourceCore = new PayoutSourceCore();

        $allowedPayoutSources = [
            PayoutSourceEntity::PAYOUT_LINK,
            PayoutSourceEntity::PETTY_CASH
        ];

        foreach ($payoutIds as $payoutId)
        {
            try
            {
                $payoutSource = $payoutsSourceCore->getPayoutSource($payoutId);

                if (($payoutSource === null) or
                    (!in_array($payoutSource->getSourceType(), $allowedPayoutSources, true)))
                {
                    throw new BadRequestValidationFailureException(
                        ErrorCode::BAD_REQUEST_INVALID_PAYOUT_SOURCE_FOR_UPDATE,
                        PayoutSourceEntity::SOURCE_TYPE,
                        $payoutSource
                    );
                }

                array_push($validPayoutIds, $payoutId);
            }
            catch (BadRequestValidationFailureException $bex)
            {
                $this->trace->error(
                    TraceCode::BAD_REQUEST_PAYOUT_INVALID_SOURCE_TYPE,
                    [
                        'payout_id' => $payoutId,
                        'error'     => $bex->getMessage()
                    ]);

                array_push($invalidPayoutIds, $payoutId);
            }
        }

        if (!empty($invalidPayoutIds))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_UPDATE_PAYOUT_ATTACHMENTS,
                PayoutDetails\Entity::PAYOUT_IDS,
                $invalidPayoutIds,
                'Invalid Update Payout Attachments request'
            );
        }

        $validator->validateInput(Validator::UPDATE_REQUEST, $input[PayoutDetails\Entity::UPDATE_REQUEST]);

        $validator->validateAttachments($input[PayoutDetails\Entity::UPDATE_REQUEST]);

        return $this->payoutDetailsCore
            ->bulkUpdateAttachments($validPayoutIds, $input[PayoutDetails\Entity::UPDATE_REQUEST]);
    }

    /**
     * Updates Tax Payment Id against the Payout
     *
     * @param string $payoutId
     * @param array  $input
     *
     * @return array
     * @throws Exception\ServerErrorException
     */
    public function updateTaxPayment(string $payoutId, array $input)
    {
        $payoutId = Entity::verifyIdAndStripSign($payoutId);

        (new Validator)->validateInput(Validator::UPDATE_TAX_PAYMENT, $input);

        $taxPaymentId = Base\PublicEntity::stripDefaultSign($input[PayoutDetails\Entity::TAX_PAYMENT_ID]);

        return $this->payoutDetailsCore
            ->updateTaxPayment($payoutId, $taxPaymentId);
    }

    /**
     * Download attachments for the given time range
     *
     * @param array $input
     *
     * @return array
     * @throws Exception\ServerErrorException
     * @throws Throwable
     */
    public function downloadAttachments(array $input): array
    {
        // 1. Fetch Payouts for the given filter
        $merchantId = $this->merchant->getMerchantId();

        $shouldSendEmail = array_pull($input, PayoutConstants::SEND_EMAIL, false);

        if ($shouldSendEmail)
        {
            // validate receiver email ids
            $receiverEmailIds = array_pull($input, PayoutConstants::RECEIVER_EMAIL_IDS, []);

            if (sizeof($receiverEmailIds) === 0)
            {
                $this->trace->error(TraceCode::NO_RECEIVER_EMAIL_FOUND_FOR_PAYOUT_REPORT,
                    [
                        'input'    => $input,
                    ]);

                return [
                    PayoutConstants::ZIP_FILE_ID => '',
                    'message' => 'No receiver email found for Payout report'
                ];
            }
        }

        $payouts = $this->fetchMultiple($input);

        $payoutIds = $this->getPayoutIds($payouts);

        // 2. If no payouts, no attachment to download
        if (sizeof($payoutIds) == 0)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_NOT_FOUND_FOR_GIVEN_INPUT,
                [
                    'input' => $input,
                ]
            );

            return [PayoutConstants::ZIP_FILE_ID => ''];
        }

        // 3. Get attachments for the fetched payouts
        try
        {
            $attachmentIds = (new PayoutDetails\Core())->getAttachmentIdsByPayoutIds($payoutIds);
        }
        catch (Exception\ServerErrorException $e)
        {
            throw $e;
        }

        // 4. If no attachments, nothing to download
        if (sizeof($attachmentIds) == 0)
        {
            return [PayoutConstants::ZIP_FILE_ID => ''];
        }

        // 5. Send the attachment ids to UFH service and get the ZIP file id
        try
        {
            $ufhService = $this->getUfhService($merchantId);

            $zipFileId = $ufhService->downloadFiles($attachmentIds, $merchantId, PayoutConstants::PAYOUT_ATTACHMENT_PREFIX, PayoutConstants::PAYOUT_ATTACHMENTS);
        }
        catch (Exception\ServerErrorException $e)
        {
            throw $e;
        }

        if ($shouldSendEmail)
        {
            $variant = $this->app->razorx->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::PAYOUT_ATTACHMENT_EMAIL_VIA_SQS,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                $this->pushMessageToSQS($receiverEmailIds, $zipFileId, $merchantId);
            }
            else
            {
                //push to metro
                $this->pushMessageToMetro($receiverEmailIds, $zipFileId, $merchantId);
            }

            return [PayoutConstants::ZIP_FILE_ID => ''];
        }
        else
        {
            return [PayoutConstants::ZIP_FILE_ID => $zipFileId];
        }
    }

    public function emailAttachments(array $input): array
    {
        try
        {
            // preprocess the $input
            $message = $input['message'];

            $data = json_decode(base64_decode($message['data'], true), true);

            $this->processPayoutAttachmentEmail($data);

            // return 200 so that Metro considers a success push
            return [PayoutConstants::STATUS_CODE => 200];
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::PAYOUT_ATTACHMENT_SEND_MAIL_FAILURE,
                [
                    'exception' => $e->getMessage(),
                ]
            );
        }
        // ZIP file has not been not uploaded nor has failed
        // return 500 so that Metro retries again till the maxDeliveryAttempts are exhausted
        return [PayoutConstants::STATUS_CODE => 500];
    }

    public function processPayoutAttachmentEmail(array $data)
    {
        $this->trace->info(
            TraceCode::PAYOUT_ATTACHMENT_SEND_MAIL,
            [
                'data' => $data,
            ]
        );

        $zipFileId = $data[PayoutConstants::ZIP_FILE_ID];

        $recipientEmails = $data[PayoutConstants::EMAILS];

        $merchantId = $data[PayoutConstants::MERCHANT_ID];

        $ufhService = $this->getUfhService($merchantId);

        // get file details before getting signed URL
        // if the file entity has not been picked by the worker, then get signed URL API
        // will throw exception
        $fileDetails = $ufhService->getFileDetails($zipFileId, $merchantId);

        $this->trace->info(
            TraceCode::PAYOUT_ATTACHMENT_GET_DETAILS,
            [
                'file_details' => $fileDetails,
            ]
        );

        // ZIP uploaded to S3
        if (array_key_exists(PayoutConstants::STATUS, $fileDetails) && $fileDetails[PayoutConstants::STATUS] === PayoutConstants::FILE_UPLOADED)
        {
            $response = $ufhService->getSignedUrl($zipFileId, [], $merchantId);

            $this->trace->info(
                TraceCode::PAYOUT_ATTACHMENT_GET_SIGNED_URL,
                [
                    'response' => $response,
                ]
            );

            $this->triggerAttachmentsEmail($response, $recipientEmails);

            return;
        }

        // If for some reason, zip file upload to S3 failed
        if (array_key_exists(PayoutConstants::STATUS, $fileDetails) && $fileDetails[PayoutConstants::STATUS] === PayoutConstants::FILE_UPLOAD_FAILED)
        {
            $this->trace->info(
                TraceCode::PAYOUT_ATTACHMENT_UPLOAD_FAILED,
                [
                    'zipFileId' => $zipFileId,
                ]
            );

            // return 200 so that Metro considers a success push
            return;
        }

        throw new \Exception('File not ready');
    }

    /**
     * Gets the details for the attachment added against the payout report
     * @param string $attachmentId
     *
     * @return array|string[]
     * @throws ServerErrorException
     */
    public function getReportAttachmentDetails(string $attachmentId)
    {
        // TODO: Add validation on the attachmentId

        $merchantId = $this->merchant->getMerchantId();

        return $this->payoutDetailsCore->getAttachmentDetails($attachmentId, $merchantId);
    }

    /**
     * Gets the signed url for the attachment added against the payout report
     * This is used instead of `/ufh/file/${fileId}/get-signed-url` endpoint because
     * when all MIDs are onboarded to cloudfront dashboard will not have access
     * to payout S3 buckets.
     *
     * @param string $attachmentId
     *
     * @return array|string[]
     * @throws ServerErrorException
     */
    public function getReportAttachmentSignedUrl(string $attachmentId)
    {
        // TODO: Add validation on the attachmentId

        return $this->payoutDetailsCore->getAttachmentSignedUrl($attachmentId);
    }

    protected function triggerAttachmentsEmail($response, $recipientEmails)
    {
        // create mail object
        $mailData = array();

        $mailData[PayoutConstants::ATTACHMENT_FILE_URL] = $response[PayoutConstants::SIGNED_URL];

        $mailData[PayoutConstants::DISPLAY_NAME] = $response[PayoutConstants::DISPLAY_NAME];

        $mailData[PayoutConstants::EXTENSION] = $response[PayoutConstants::EXTENSION];

        $mailData[PayoutConstants::MIME] = $response[PayoutConstants::MIME];

        $mailObject = new Attachments($recipientEmails, $mailData);

        $this->trace->info(
            TraceCode::PAYOUT_ATTACHMENT_PUSH_TO_QUEUE,
            [
                'mailData' => $mailData,
            ]
        );

        // push to mail queue
        Mail::queue($mailObject);
    }

    protected function pushMessageToMetro(array $receiverEmailIds, string $zipFileId, string $merchantId)
    {
        $data = [
            'emails'         => $receiverEmailIds,
            'zip_file_id'    => $zipFileId,
            'merchant_id'    => $merchantId,
        ];

        $metroMessage = [
            'data' => json_encode($data, true),
            'attributes' => [
                'mode' => $this->app['rzp.mode'] ?? Mode::LIVE,
            ]
        ];

        $this->trace->info(TraceCode::PROCESSING_ATTACHMENT_FOR_PAYOUT_REPORTS, $data);

        try
        {
            $response = $this->app['metro']->publish(PayoutConstants::PAYOUT_ATTACHMENT_METRO_TOPIC, $metroMessage);

            $this->trace->info(TraceCode::ATTACHMENT_FOR_PAYOUT_REPORTS_METRO_MESSAGE_PUBLISHED,
                [
                    'topic'    => PayoutConstants::PAYOUT_ATTACHMENT_METRO_TOPIC,
                    'response' => $response,
                ]);

        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::ATTACHMENT_FOR_PAYOUT_METRO_MESSAGE_PUBLISH_ERROR,
                $data);

            throw $e;
        }
    }

    protected function pushMessageToSQS(array $receiverEmailIds, string $zipFileId, string $merchantId)
    {
        $data = [
            'emails'         => $receiverEmailIds,
            'zip_file_id'    => $zipFileId,
            'merchant_id'    => $merchantId,
        ];

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $this->trace->info(TraceCode::PROCESSING_ATTACHMENT_FOR_PAYOUT_REPORTS, $data);

        PayoutAttachmentEmail::dispatch($mode, $data);
    }

    protected function getUfhService($merchantId = null)
    {
        $ufhServiceMock = $this->app['config']->get('applications.ufh.mock');

        if ($ufhServiceMock === true)
        {
            $ufhService = new MockUfhService($this->app, null);
        }
        else
        {
            $ufhService = new UfhService($this->app, $merchantId);
        }

        if (is_null($ufhService) == true)
        {
            $this->trace->info(
                TraceCode::PAYOUT_UFH_SERVICE_NULL,
                [
                    'ufh_service' => $ufhService,
                ]
            );

            throw new ServerErrorException(
                'Could not get UFH Client',
                ErrorCode::SERVER_ERROR_INVALID_UFH_CLIENT,
                null
            );
        }

        return $ufhService;
    }


    /**
     * Return the payout ids of the given payouts
     * @param array $payouts
     * @return array
     */
    protected function getPayoutIds(array $payouts): array
    {
        $payoutIds = array();

        foreach ($payouts['items'] as $payout)
        {
            array_push($payoutIds, substr($payout[Entity::ID], 5));
        }

        return $payoutIds;
    }

    public function createTestPayoutsForDetectingDowntimeYESB(array $input)
    {
        $this->trace->info(TraceCode::TEST_PAYOUTS_YESB_CRON_REQUEST);

        $testPayoutInput = $input;
        $merchantId      = Account::FUND_LOADING_DOWNTIME_DETECTION_SOURCE_ACCOUNT_MID;
        $this->merchant  = $this->core->addMerchantForTestPayouts($merchantId);

        $this->trace->info(TraceCode::TEST_PAYOUT_FOR_DETECTING_FUND_LOADING_DOWNTIME_CREATE_REQUEST,
            ['input' => $testPayoutInput,]);

        $balance = $this->processAccountNumber($testPayoutInput);

        $modes = array_pull($testPayoutInput,self::MODES);

        foreach ($modes as $mode)
        {
            $testPayoutInput[Payout\Entity::MODE] = $mode;

            (new Validator)->setStrictFalse()
                ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $testPayoutInput);

            $payout = $this->core->createPayoutToFundAccount($testPayoutInput, $this->merchant);

            $response = [
                Payout\Entity::ID               => 'pout_' . $payout->getId(),
                Payout\Entity::MODE             => $payout->getMode(),
                Payout\Entity::STATUS           => $payout->getStatus(),
                Payout\Entity::FUND_ACCOUNT_ID  => $payout->getFundAccountId(),
                Payout\Entity::NARRATION        => $payout->getNarration(),
                Payout\Entity::CREATED_AT       => $payout->getCreatedAt(),
            ];

            $finalResponse[] = $response;
        }

        $this->trace->info(TraceCode::TEST_PAYOUT_FOR_DETECTING_FUND_LOADING_DOWNTIME_CREATED,
            ['response' => $finalResponse]);

        return $finalResponse;
    }

    public function createTestPayoutsForDetectingDowntimeICICI(array $input)
    {
        $this->trace->info(TraceCode::TEST_PAYOUTS_ICICI_CRON_REQUEST);

        $testPayoutInput  = $input;
        $merchantId       = Account::FUND_LOADING_DOWNTIME_DETECTION_SOURCE_ACCOUNT_MID;
        $this->merchant   = $this->core->addMerchantForTestPayouts($merchantId);

        $this->trace->info(TraceCode::TEST_PAYOUT_FOR_DETECTING_FUND_LOADING_DOWNTIME_CREATE_REQUEST,
            ['input' => $testPayoutInput,]);

        $balance = $this->processAccountNumber($testPayoutInput);

        if($balance->getBalance() < self::LOW_BALANCE_LIMIT_FOR_TEST_ACCOUNT)
        {
          $this->trace->info(TraceCode::LOW_BALANCE_ALERT_FOR_TEST_PAYOUTS,
                ['merchant_id' => $balance->getMerchantId(),
                  'balance'    => $balance->getBalance()
                ]);
        }

        $modes = array_pull($testPayoutInput,self::MODES);

        foreach ($modes as $mode)
        {
            $testPayoutInput[Payout\Entity::MODE] = $mode;

            (new Validator)->setStrictFalse()
                ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $testPayoutInput);


            $payout = $this->core->createPayoutToFundAccount($testPayoutInput, $this->merchant);

            $response = [
                Payout\Entity::ID => 'pout_' . $payout->getId(),
                Payout\Entity::MODE => $payout->getMode(),
                Payout\Entity::STATUS => $payout->getStatus(),
                Payout\Entity::FUND_ACCOUNT_ID => $payout->getFundAccountId(),
                Payout\Entity::NARRATION => $payout->getNarration(),
                Payout\Entity::CREATED_AT => $payout->getCreatedAt(),
            ];

            $finalResponse[] = $response;
        }
        $this->trace->info(TraceCode::TEST_PAYOUT_FOR_DETECTING_FUND_LOADING_DOWNTIME_CREATED,
            ['response' => $finalResponse]);

        return $finalResponse;
    }

    public function checkTestPayoutsStatus(array $input)
    {
        $this->trace->info(TraceCode::STATUS_OF_TEST_PAYOUTS_CRON_REQUEST);

        return $this->core->checkStatusOfTestPayouts($input);
    }

    public function addBalanceToSourceForTestMerchant($input)
    {
        $this->trace->info(TraceCode::ADD_BALANCE_TO_SOURCE_FOR_TEST_PAYOUTS_CRON_REQUEST,
            ['input' => $input]);

        $merchantId      = Account::FUND_LOADING_DOWNTIME_DETECTION_DESTINATION_ACCOUNT_MID;
        $this->merchant  = $this->core->addMerchantForTestPayouts($merchantId);

        $sourceMid = Account::FUND_LOADING_DOWNTIME_DETECTION_SOURCE_ACCOUNT_MID;
        $count     = $this->repo->payout->fetchCountOfProcessedPayoutsInLast24Hours($sourceMid);

        $input[Payout\Entity::AMOUNT] = $count * 100;

        $balance = $this->processAccountNumber($input);

        (new Validator)->setStrictFalse()
            ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

        $payout = $this->core->createPayoutToFundAccount($input, $this->merchant);

        $response = [
            'message' => 'Balance added to source account successfully',
        ];

        return $response;
    }

    public function payoutServiceRedisKeySet($input)
    {
        return $this->core->payoutServiceRedisKeySet($input);
    }

    public function payoutServiceDualWrite($input)
    {
        return $this->core->payoutServiceDualWrite($input);
    }

    public function payoutUpdateByBASRecon($input)
    {
        return $this->core->payoutUpdateByBASRecon($input);
    }

    public function payoutServiceDeleteCardMetaData($input)
    {
        (new Validator())->validateInput(Validator::DELETE_CARD_META_DATA_FOR_PAYOUT_SERVICE, $input);

        return $this->core->payoutServiceDeleteCardMetaData($input);
    }

    public function initiateDataMigration(array $input): array
    {
        $response = $this->core->initiateDataMigration($input);

        return $response;
    }

    public function payoutServiceDataMigrationRedisCleanUp(array $input): array
    {
        $response = $this->core->psDataMigrationRedisCleanUp($input);

        return $response;
    }

    public function payoutServiceMailAndSms(array $input)
    {
        try
        {
            $this->core->payoutServiceMailAndSms($input);
        }
        catch (\throwable $exception)
        {
            $this->trace->traceException($exception);

            if ($exception->getMessage() === "The selected entity is invalid.")
            {
                return ['message' => $exception->getMessage()];
            }

            if ($exception->getCode() === ErrorCode::BAD_REQUEST_INVALID_PAYOUT_NOTIFICATION_TYPE)
            {
                return ['message' => 'The selected type is invalid.'];
            }

            throw $exception;
        }

        return ['message' => 'success'];
    }

    protected function getUsersDataForPendingPayoutLinks(array $merchantPendingPayoutLinksMeta): Base\PublicCollection
    {
        $merchantIdToRolesMapping = array(); // [M1 => [Role1, Role2], M2 => [Role1, Role2], M3 => [Role1, Role2]]

        foreach ($merchantPendingPayoutLinksMeta as $merchantId => $rolesPendingLinksData)
        {
            $merchantIdToRolesMapping[$merchantId] = array_keys($rolesPendingLinksData);
        }

        return $this->userCore->getBankingUsersForMerchantRoles($merchantIdToRolesMapping);
    }

    private function shouldProcessBulkApproveAsync(string $merchantId)
    {
        $bulkApprovalAsyncExperimentVariant = $this->app->razorx->getTreatment(
            $merchantId,
            RazorxTreatment::PAYOUT_BULK_APPROVE_ASYNC,
            Constants\Mode::LIVE);

        return (strtolower($bulkApprovalAsyncExperimentVariant) === 'on');
    }

    private function generateRawQuery(string $rawQuery, $key): string
    {
        if (strlen($rawQuery) > 0) {
            $rawQuery = $rawQuery." OR ";
        }

        return $rawQuery . " (`payouts`.`channel` = '" . strtolower($key->channel) . "' AND `payouts`.`mode` = '" . $key->mode . "') ";

    }

    public function validatePayoutsBatch(array $input): array
    {
        // compute the batch type based on the file
        $input['type'] = 'payout';

        $batch = (new Batch\Entity)->build($input, 'validate');

        $batch->merchant()->associate($this->merchant);

        $processor = Batch\Processor\Factory::get($batch);

        $batchFile = $input['file'];

        $partial = UniqueIdEntity::generateUniqueId();

        $fileIdentifier = pathinfo($batchFile->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = $partial . '/' . $fileIdentifier;

        $extension = strtolower($batchFile->getClientOriginalExtension());

        if (empty($extension) === false)
        {
            $fileName .= '.'. $extension;
        }

        $localDir  = storage_path('files/filestore') . '/' . Batch\Entity::INPUT_FILE_PREFIX;

        $movedFile = $batchFile->move($localDir, $fileName);

        $entries = $processor->processBatchFile($movedFile->getPathname());

        if (sizeof($entries) === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "File upload failed, atleast 1 filled row required"
            );
        }

        $batchType = $this->core()->getBatchType($entries);

        $response = [];

        $statusCode = 200;

        if ($batchType === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "File upload failed, file format doesn't follow any of the templates"
            );
        }

        // redirect to old flow if old template
        if ($batchType === BatchPayoutConstants::PAYOUTS)
        {
            // return 301 response
            $statusCode = 301;

            return [$response, $statusCode];
        }

        try
        {
            $input['type'] = $batchType;

            $input['file'] = $movedFile;

            $response = $this->app->batchService->validateFile($input, $this->merchant);
        }
        catch (ServerNotFoundException $exception)
        {
            // Either Batch Microservice is down or not found
            $this->trace->error(TraceCode::BATCH_SERVER_FAILED, ['error' => $exception->getMessage()]);

            $statusCode = 500;
        }

        return [$response, $statusCode];
    }

    /**
     * This function should not be a part of this class.
     * Ideally this should be at a central place that governs whether a token has acess to a resource
     * Since no new changes are being accepted in BasicAuth, adding it as a function here. Needs to be refactored.
     */
    private function isXPartnerApproval()
    {
        /** @var $auth BasicAuth */
        $auth = $this->app['basicauth'];

        if ($auth->getAccessTokenId() === null)
        {
            return false;
        }

        $scopes = $auth->getTokenScopes();

        if (empty($scopes) === true or (in_array(OAuthScopes::RX_PARTNER_READ_WRITE, $scopes, true) === false))
        {
            return false;
        }

        return $auth->getMerchant()->isFeatureEnabled(Features::ENABLE_APPROVAL_VIA_OAUTH) === true;
    }

    public function getPartnerBankStatus(): array
    {
        return $this->core->getPartnerBankStatus();
    }

    public function caFundManagementPayoutCheck($input)
    {
        $merchantIds = array_unique($input[Entity::MERCHANT_IDS]);

        return $this->core->caFundManagementPayoutCheck($merchantIds);
    }

    public function getCABalanceManagementConfig($merchantId): array
    {
        return $this->core->getCABalanceManagementConfig($merchantId);
    }

    public function updateCABalanceManagementConfig(array $input, string $merchantId): array
    {
        (new Validator())->validateInput(Validator::UPDATE_BALANCE_MANAGEMENT_CONFIG, $input);

        $this->core->updateCABalanceManagementConfig($merchantId, $input);

        return ['message' => 'Config for ' . $merchantId . ' updated successfully'];
    }

    private function schedulePayoutProcessingForP2PIfApplicable(Entity $payout)
    {
        if ($this->core->isPayoutApplicableForP2PDelay($payout) === false)
        {
            return false;
        }

        $this->core->schedulePayoutPostApproval($payout);

        return true;
    }

    /**
     * @throws BadRequestException
     */
    public function getSmartRoutingSummary($input): array
    {
        $response = [];
        try
        {
            (new Validator)->validateSmartRoutingSummaryInput($input);

            $mode = $input[Entity::MODE];

            $modeList = $mode === Entity::MODE_ALL ? Entity::PAYOUTS_SUMMARY_ALLOWED_MODES : [$mode];
            foreach ($modeList as $mode)
            {
                $input[Entity::MODE] = $mode;
                $response[$mode] = $this->core->payoutsSmartRoutingSummary($input);
            }

        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException($exception,
                Trace::ERROR,
                TraceCode::SMART_ROUTING_SUMMARY_FAILED,
                $input
            );

        }
        finally
        {
            //if value of all the mode in response is null throw Bad Request Exception
            $validResponse = false;
            foreach ($response as $mode => $value)
            {
                if ($value != null)
                {
                    $validResponse = true;
                    break;
                }
            }

            if(!$validResponse)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null
                );
            }
        }

        return $response;
    }

    /**
     * @throws BadRequestException
     */
    public function fetchSmartRoutingRulesForMerchantViaAdmin($input): array
    {
        $response = [];
        try
        {
            // Sample response: ["IMPS" => ["RBL", "ICICI", "SHARED"], "NEFT" => ["YESBANK", "SHARED", "RBL"], "UPI" => ["RBL"]]
            $response = $this->core->fetchSmartRoutingRulesForMerchant($input);
        }
        catch (Throwable $exception)
        {
            $this->trace->traceException($exception,
                 Trace::ERROR,
                 TraceCode::SMART_ROUTING_RULES_FETCH_FAILED,
                 $input
            );
        }
        finally
        {
            // if no priorities received for ANY of the modes, then throw bad request exception
            $validResponse = false;

            if(!(empty($response) === true)) {
                $validResponse = true;

                $priorityChannelsForIMPS = $response['IMPS'];
                $priorityChannelsForUPI = $response['UPI'];

                if(count($priorityChannelsForIMPS) === 0 && count($priorityChannelsForUPI) === 0) {
                    $validResponse = false;
                }
            }

            if(!$validResponse)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null
                );
            }
        }

        return $response;
    }

    /**
     * @throws BadRequestException
     */
    public function modifySmartRoutingRulesForMerchantViaAdmin($input): array
    {
        $response = [];
        try
        {
            (new Validator)->validateSmartRoutingRulesPayoutsModifyRequest($input);

            // Sample response: ["IMPS" => ["RBL", "ICICI", "SHARED"], "NEFT" => ["YESBANK", "SHARED", "RBL"], "UPI" => ["RBL"]]
            $response = $this->core->modifySmartRoutingRulesForMerchant($input);
        }
        catch (Throwable $exception)
        {
            $this->trace->traceException($exception, Trace::ERROR,
                 TraceCode::SMART_ROUTING_RULES_MODIFY_FAILED,
                 $input);
        }
        finally
        {
            // if no priorities received for ANY of the modes, then throw bad request exception
            $validResponse = false;

            if(!(empty($response) === true)) {
                $validResponse = true;

                $priorityChannelsForIMPS = $response['IMPS'];
                $priorityChannelsForUPI = $response['UPI'];

                if(count($priorityChannelsForIMPS) === 0 && count($priorityChannelsForUPI) === 0) {
                    $validResponse = false;
                }
            }

            if(!$validResponse)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null
                );
            }
        }

        return $response;
    }

    /**
     * @throws BadRequestException
     */
    public function fetchSmartRoutingRulesForMerchant($input): array
    {
        $response = [];
        try
        {
            $merchantID = $this->merchant->getId();

            $input[Entity::MERCHANT_ID] = $merchantID;

            // Sample response: ["IMPS" => ["RBL", "ICICI", "SHARED"], "NEFT" => ["YESBANK", "SHARED", "RBL"], "UPI" => ["RBL"]]
            $response = $this->core->fetchSmartRoutingRulesForMerchant($input);
        }
        catch (Throwable $exception)
        {
            $this->trace->traceException($exception,
                                         Trace::ERROR,
                                         TraceCode::SMART_ROUTING_RULES_FETCH_FAILED,
                                         $input
            );
        }
        finally
        {
            // if no priorities received for ANY of the modes, then throw bad request exception
            $validResponse = false;

            if(!(empty($response) === true)) {
                $validResponse = true;

                $priorityChannelsForIMPS = $response['IMPS'];
                $priorityChannelsForUPI = $response['UPI'];

                if(count($priorityChannelsForIMPS) === 0 && count($priorityChannelsForUPI) === 0) {
                    $validResponse = false;
                }
            }

            if(!$validResponse)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null
                );
            }
        }

        return $response;
    }

    /**
     * @throws BadRequestException
     */
    public function modifySmartRoutingRulesForMerchant($input): array
    {
        $response = [];
        try
        {
            $merchantID = $this->merchant->getId();

            $input[Entity::MERCHANT_ID] = $merchantID;

            (new Validator)->validateSmartRoutingRulesPayoutsModifyRequest($input);

            // Sample response: ["IMPS" => ["RBL", "ICICI", "SHARED"], "NEFT" => ["YESBANK", "SHARED", "RBL"], "UPI" => ["RBL"]]
            $response = $this->core->modifySmartRoutingRulesForMerchant($input);
        }
        catch (Throwable $exception)
        {
            $this->trace->traceException($exception, Trace::ERROR,
                                         TraceCode::SMART_ROUTING_RULES_MODIFY_FAILED,
                                         $input);
        }
        finally
        {
            // if no priorities received for ANY of the modes, then throw bad request exception
            $validResponse = false;

            if(!(empty($response) === true)) {
                $validResponse = true;

                $priorityChannelsForIMPS = $response['IMPS'];
                $priorityChannelsForUPI = $response['UPI'];

                if(count($priorityChannelsForIMPS) === 0 && count($priorityChannelsForUPI) === 0) {
                    $validResponse = false;
                }
            }

            if(!$validResponse)
            {
                throw new BadRequestException(
                    ErrorCode::BAD_REQUEST_ERROR,
                    null,
                    null
                );
            }
        }

        return $response;
    }
}
