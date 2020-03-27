<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Card;
use RZP\Models\Payout;
use RZP\Models\Contact;
use RZP\Models\Pricing;
use RZP\Models\Reversal;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\FundAccount;
use RZP\Http\RequestHeader;
use RZP\Models\Admin\Permission;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Payout\BatchHelper as PayoutBatchHelper;
use RZP\Models\FundAccount\Service as FundAccountService;
use RZP\Models\FundAccount\BatchHelper as FundAccountHelper;

use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    use Base\Traits\ProcessAccountNumber;

    /**
     * @var FundAccountService
     */
    protected $fundAccountService;

    /**
     * @var Contact\Core
     */
    protected $contactCore;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Payout\Core;

        $this->contactCore = new Contact\Core;

        $this->fundAccountService = new FundAccountService;
    }

    public function fundAccountPayout(array $input): array
    {
        // Only allow access over strictly private auth, for proxy auth: OTP auth flow is mandated.
        if ($this->auth->isStrictPrivateAuth() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        // Only allowed for Rx payouts, mandates account number
        $this->processAccountNumber($input);

        $payout = $this->repo->transaction(
            function () use ($input)
            {
                $isCompositePayout = false;

                (new Validator)->setStrictFalse()
                               ->validateInput(Validator::BEFORE_CREATE_FUND_ACCOUNT_PAYOUT, $input);

                if (isset($input[Entity::FUND_ACCOUNT]) === true)
                {
                    $isCompositePayout = true;

                    $input = $this->getPayoutInputForCompositeRequest($input);
                }

                /** @var Entity $payout */
                $payout = $this->core->createPayoutToFundAccount($input, $this->merchant);

                if ($isCompositePayout === true)
                {
                    $this->trace->info(
                        TraceCode::COMPOSITE_PAYOUT_CREATED,
                        [
                            'payout_id'       => $payout->getId(),
                            'fund_account_id' => $payout->fundAccount->getId(),
                            'contact_id'      => $payout->fundAccount->source->getId(),
                        ]);

                    // Setting $composite field for payout entity to deny unsetting of fund_account field in a
                    // strictPrivateAuth composite payout request
                    $payout->setComposite(true);

                    $payout = $payout->load('fundAccount.contact');

                    // Setting $composite field for fund_account entity to deny unsetting of contact field in a
                    // strictPrivateAuth composite payout request
                    $payout->fundAccount->setComposite(true);
                }

                return $payout;
            }
        );

        return $payout->toArrayPublic();
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

    public function bulkApproveFundAccountPayouts(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_BULK_APPROVE_REQUEST, ['input' => $input]);

        (new Validator)->validateInput('bulk_approve', $input);

        $this->user->validateInput('verify_otp', array_only($input, [User\Entity::OTP, User\Entity::TOKEN]));

        (new User\Core)->verifyOtp($input + ['action' => 'approve_payout_bulk'], $this->merchant, $this->user);

        $payouts = $this->repo->payout->findManyByPublicIdsAndMerchant($input[Entity::PAYOUT_IDS], $this->merchant);

        foreach ($payouts as $payout)
        {
            $payout->getValidator()->validatePayoutStatusForApproveOrReject();
        }

        $failedIds = [];

        foreach ($payouts as $payout)
        {
            try
            {
                $payout = (new Core)->approvePayout($payout, $input);
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
            'total_count' => count($input[Entity::PAYOUT_IDS]),
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

        $payouts = $this->repo->payout->findManyByPublicIdsAndMerchant($input[Entity::PAYOUT_IDS], $this->merchant);

        foreach ($payouts as $payout)
        {
            $payout->getValidator()->validatePayoutStatusForApproveOrReject();
        }

        $failedIds = [];

        foreach ($payouts as $payout)
        {
            try
            {
                $payout = (new Core)->rejectPayout($payout, $input);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::PAYOUT_APPROVE_REJECT_EXCEPTION,
                    [
                        'payout_id' => $payout->getId(),
                    ]);

                $failedIds[] = $payout->getId();
            }
        }

        return [
            'total_count' => count($input[Entity::PAYOUT_IDS]),
            'failed_ids'  => $failedIds,
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

        $payout = $this->core->createPayoutToFundAccount($payoutInput, $this->merchant);

        return $payout->toArrayPublic();
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
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $payout->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateToAccountNumberForBankingIfApplicable($input);

        $payouts = $this->repo->payout->fetch($input, $this->merchant->getId());

        return $payouts->toArrayPublic();
    }

    public function processReversedPayout(string $id)
    {
        $payout = $this->repo->payout->findByPublicId($id);

        $newPayout = (new Core)->retryReversedPayout($payout);

        return $newPayout->toArrayPublic();
    }

    public function getPurposes(): array
    {
        return (new Purpose)->getAll($this->merchant);
    }

    public function postPurpose(array $input): array
    {
        (new Validator)->validateInput('create_purpose', $input);

        $purposeObj = new Purpose;

        $purposeObj->addNewCustom($input[Entity::PURPOSE], $input[Entity::PURPOSE_TYPE], $this->merchant);

        return $purposeObj->getAll($this->merchant);
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

    /**
     * Return a summary of workflows for RazorpayX dashboard consumption.
     *
     * Works ONLY for create_payout workflows right now.
     *
     * @return array
     */
    public function getWorkflowSummary(): array
    {
        $permissionId = $this->repo
                             ->permission
                             ->retrieveIdsByNamesAndOrg(Permission\Name::CREATE_PAYOUT, Org\Entity::RAZORPAY_ORG_ID)
                             ->first();

        $workflowRules = $this->repo
                              ->workflow_payout_amount_rules
                              ->fetchBankingWorkflowSummaryForPermissionId($permissionId, $this->merchant->getId());

        $data = [];

        foreach ($workflowRules as $wfRule)
        {
            $wfRuleData = $wfRule->toArray();

            $hasWorkflow = (empty($wfRuleData['workflow_id']) === false);

            $data[] = array_only($wfRuleData, ['min_amount', 'max_amount', 'workflow_id']) + [
                    'has_workflow' => $hasWorkflow,
                    'steps'        => Entity::serializeWorkflowSteps($wfRuleData['workflow']['steps'] ?? []),
                ];
        }

        return $data;
    }

    public function getDashboardSummary(): array
    {
        $queued = $this->getQueuedPayoutsSummary();

        $pending   = [];
        $scheduled = [];

        if ($this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS) === true)
        {
            $pending = $this->getPendingPayoutsSummary();
        }

        $completeSummary = $this->getCompleteSummary($pending, $queued, $scheduled);

        return $completeSummary;
    }

    public function processDispatchForQueuedPayouts(array $input)
    {
        $merchantIdsWhitelist = $input['merchant_ids'] ?? [];
        $merchantIdsBlacklist = $input['merchant_ids_not'] ?? [];
        $from                 = $input['from'] ?? null;
        $to                   = $input['to'] ?? null;

        $queuedPayouts = $this->repo->payout->fetchQueuedPayouts($merchantIdsWhitelist,
                                                                 $merchantIdsBlacklist,
                                                                 $from,
                                                                 $to);

        $summary = $this->core->processDispatchForQueuedPayouts($queuedPayouts);

        return $summary;
    }

    public function cancelPayout(string $payoutId)
    {
        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($payoutId, $this->merchant);

        $payout = $this->core->cancelPayout($payout);

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

        // if any merchant wants to skip duplicate check
        // and unique create contact and fund account everytime
        $createDuplicate = $this->shouldCreateDuplicateForFundAccountAndContact();

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

                $this->repo->transaction(function() use ($createDuplicate,
                                                         & $item,
                                                         & $payoutBatch,
                                                         & $batchId,
                                                         & $idempotencyKey,
                                                         $validator)
                {
                    $idempotencyKey = $item[Entity::IDEMPOTENCY_KEY] ?? null;

                    $validator->validateIdempotencyKey($idempotencyKey, $batchId);

                    $result = $this->repo->payout->fetchByIdempotentKey($item[Entity::IDEMPOTENCY_KEY],
                                                                        $this->merchant->getId(),
                                                                        $batchId);

                    if ($result !== null)
                    {
                        $this->trace->info(TraceCode::PAYOUT_EXIST_WITH_SAME_IDEMPOTENCY_KEY,
                                            ['input' => $result->toArrayPublic(),
                                             Entity::IDEMPOTENCY_KEY => $item[Entity::IDEMPOTENCY_KEY]]);

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

        $this->trace->info(TraceCode::BATCH_SERVICE_PAYOUT_BULK_REQUEST, $payoutBatch->toArrayWithItems());

        return $payoutBatch->toArrayWithItems();
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

    protected function getQueuedPayoutsSummary()
    {
        $queuedPayoutsSummary = [];

        $merchantId = $this->merchant->getId();

        $queuedPayouts = $this->repo->payout->fetchQueuedPayouts([$merchantId]);

        $groupedQueuedPayouts = $queuedPayouts->groupBy(Entity::BALANCE_ID);

        foreach ($groupedQueuedPayouts as $balanceId => $queuedPayouts)
        {
            $currentBalance = $queuedPayouts->first()->balance->getBalance();

            $totalAmount = $totalFees = 0;

            $bankingAccountId = $queuedPayouts->first()->bankingAccount->getPublicId();

            foreach ($queuedPayouts as $payout)
            {
                $totalAmount += $payout->getAmount();

                list($fees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

                $totalFees += $fees;
            }

            $queuedPayoutsSummary[$bankingAccountId][Status::QUEUED] = [
                'balance'       => $currentBalance,
                'count'         => count($queuedPayouts),
                'total_amount'  => $totalAmount,
                'total_fees'    => $totalFees,
            ];
        }

        return $queuedPayoutsSummary;
    }

    protected function getPendingPayoutsSummary()
    {
        $user = $this->auth->getUser();

        $pending = $this->repo->payout->fetchPayoutsPendingOnUser($user, $this->merchant);

        $groupedPendingPayouts = $pending->groupBy(Entity::BALANCE_ID);

        $pendingPayoutsSummary = [];

        foreach ($groupedPendingPayouts as $balanceId => $payouts)
        {
            $bankingAccountId = $payouts->first()->bankingAccount->getPublicId();

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
        $bankingAccountList = $this->merchant->activeBankingAccounts;

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

        return $completeSummary;
    }

    protected function processEntryForPayoutForFundAccount(array $entry,
                                                           FundAccount\Entity $fundAccount,
                                                           string $batchId): Entity
    {
        $input = PayoutBatchHelper::getPayoutInput($entry, $fundAccount->toArrayPublic(), $this->merchant);

        return $this->core->createPayoutToFundAccount($input, $this->merchant, $batchId);
    }

    // ToDo https://razorpay.atlassian.net/browse/RX-849
    protected function shouldCreateDuplicateForFundAccountAndContact()
    {
        $merchant = $this->merchant;

        $variant  = $this->app['razorx']->getTreatment($merchant->getId(),
                                                       Merchant\RazorxTreatment::X_CONTACT_AND_FUND_ACCOUNT_CREATION,
                                                       $this->mode);

        $flag = ($variant === 'create_duplicate') ? true : false;

        return $flag;
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
    protected function getPayoutInputForCompositeRequest(array $input): array
    {
        $traceRequest = $this->unsetSensitiveCardDetails($input);

        $this->trace->info(TraceCode::PAYOUT_COMPOSITE_CREATE_REQUEST, $traceRequest);

        (new Validator)->validateInput(Validator::FUND_ACCOUNT_PAYOUT_COMPOSITE, $input);

        $compositeInput = $this->repo->transaction(
            function () use ($input)
            {
                $contactData = $this->createContactForCompositePayout($input);

                $contactId = $contactData[Contact\Entity::ID];

                $fundAccountData = $this->createFundAccountForCompositePayout($input, $contactId);

                $fundAccountId = $fundAccountData[FundAccount\Entity::ID];

                $payoutInput = $this->getInputForPayoutCreateFromComposite($input, $fundAccountId);

                return $payoutInput;
            });

        return $compositeInput;
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
}
