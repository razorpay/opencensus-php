<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
use RZP\Models\Pricing;
use RZP\Models\Reversal;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Feature\Constants as Features;

use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Payout\Core;
    }

    public function fundAccountPayout(array $input): array
    {
        // Only allow access over strictly private auth, for proxy auth: OTP auth flow is mandated.
        if ($this->auth->isStrictPrivateAuth() === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_FORBIDDEN);
        }

        $this->processAccountNumber($input);

        $payout = $this->core->createPayoutToFundAccount($input, $this->merchant);

        return $payout->toArrayPublic();
    }

    public function approveFundAccountPayout(string $id, array $input): array
    {
        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        $this->user->validateInput('verifyOtp', array_only($input, [User\Entity::OTP, User\Entity::TOKEN]));

        (new User\Core)->verifyOtp($input + ['action' => 'approve_payout'], $this->merchant, $this->user);

        $payout = (new Core)->approvePayout($payout);

        return $payout->toArrayPublic();
    }

    public function bulkApproveFundAccountPayouts(array $input)
    {
        (new Validator)->validateInput('bulk_approve', $input);

        $this->user->validateInput('verify_otp', array_only($input, [User\Entity::OTP, User\Entity::TOKEN]));

        (new User\Core)->verifyOtp($input + ['action' => 'approve_payout'], $this->merchant, $this->user);

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
                $payout = (new Core)->approvePayout($payout);
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

    public function rejectFundAccountPayout(string $id): array
    {
        /** @var Entity $payout */
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant);

        $payout->getValidator()->validatePayoutStatusForApproveOrReject();

        $payout = (new Core)->rejectPayout($payout);

        return $payout->toArrayPublic();
    }

    public function bulkRejectFundAccountPayout(array $input)
    {
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
                $payout = (new Core)->rejectPayout($payout);
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
     * @param  array $input
     *
     * @return array
     */
    public function fundAccountPayoutWithOtp(array $input): array
    {
        $this->user->validateInput('verifyOtp', array_only($input, ['otp', 'token']));

        (new User\Core)->verifyOtp($input + ['action' => 'create_payout'], $this->merchant, $this->user);

        $payoutInput = array_except($input, ['otp', 'token']);

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

        //Here value true specifies payout on demand mode enabled
        $input[Entity::TYPE] = Entity::ON_DEMAND;

        $payout = (new Payout\Core)->createPayoutToMerchant($input, $this->merchant);

        return $payout->toArrayPublic();
    }

    public function fetch(string $id, array $input): array
    {
        $payout = $this->repo->payout->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $payout->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $this->processAccountNumber($input);

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

    public function getQueuedPayoutsSummary()
    {
        $merchantId = $this->merchant->getId();

        $currentBalance = $this->merchant->bankingBalance;

        $queuedPayouts = $this->repo->payout->fetchQueuedPayouts([$merchantId]);

        $totalAmount = $totalFees = 0;

        foreach ($queuedPayouts as $payout)
        {
            $totalAmount += $payout->getAmount();

            list($fees, $tax, $feesSplit) = (new Pricing\Fee)->calculateMerchantFees($payout);

            $totalFees += $fees;
        }

        return [
            'balance'       => $currentBalance,
            'count'         => count($queuedPayouts),
            'total_amount'  => $totalAmount,
            'total_fees'    => $totalFees,
        ];
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

        $workflows = $this->repo
                          ->workflow
                          ->fetchBankingWorkflowSummaryForPermissionId($permissionId, $this->merchant->getId());

        return $workflows->toArray();
    }

    public function getDashboardSummary(): array
    {
        $queued = $this->getQueuedPayoutsSummary();

        $pending   = [];
        $scheduled = [];

        if ($this->merchant->isFeatureEnabled(Features::PAYOUT_WORKFLOWS) === true)
        {
            $user = $this->auth->getUser();

            $pending = $this->repo->payout->fetchSummaryOfPayoutsPendingOnUser($user);
        }

        return [
            'queued'    => $queued,
            'pending'   => $pending,
            'scheduled' => $scheduled,
        ];
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
     * We are allowing Fund Account payouts only on RX.
     * In RX, we always mandate account number.
     *
     * @param array $input
     */
    protected function processAccountNumber(array & $input)
    {
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
    }
}
