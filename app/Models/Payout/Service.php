<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Payout;
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

        $this->processAccountNumber($input);

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
        $payouts = $this->repo->payout->fetch($input, $this->merchant->getId());

        return $payouts->toArrayPublic();
    }

    public function processFailedPayouts(array $input)
    {
        $data = (new Core)->retryFailedPayouts($input);

        return $data;
    }

    protected function processAccountNumber(array & $input)
    {
        //
        // If the account number is not present, we don't care about anything.
        // The payout would happen from the merchant's primary balance.
        //
        if (isset($input[Entity::ACCOUNT_NUMBER]) === false)
        {
            return;
        }

        //
        // If an account number is present, it means that the merchant
        // should be enabled on business banking and we have to convert
        // to balance_id.
        //
        /** @var Merchant\Validator $merchantValidator */
        $merchantValidator = $this->merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
    }
}
