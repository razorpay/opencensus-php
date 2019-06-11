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

        // Here value true specifies payout on demand mode enabled
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
