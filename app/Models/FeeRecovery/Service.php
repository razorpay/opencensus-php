<?php

namespace RZP\Models\FeeRecovery;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function createRecoveryPayout(array $input)
    {
        $response = $this->core()->createFeeRecoveryPayout($input);

        return $response;
    }

    public function recoveryPayoutCron(array $input)
    {
        $feeRecovery = $this->core()->recoveryPayoutCron($input);

        return $feeRecovery;
    }

    public function createManualRecovery(array $input)
    {
        $response = $this->core()->createManualRecovery($input);

        return $response;
    }

    /**
     * Creates retry entry into the fee_recovery table corresponding to a Payout/Reversal.
     * This function gets invoked at payout initiation and reversal creation.
     * Max five fee_recovery payout can be processed at one time.
     *
     * @param array $input
     * @return array
     */
    public function createRecoveryRetryPayoutManually(array $input)
    {
        (new Validator())->validateInput('create_fee_recovery_retry_payouts', $input);

        $previousRecoveryPayoutsId = $input[Entity::PREVIOUS_RECOVERY_PAYOUT_ID];

        $feeRecovery = $this->core()->recreateFeeRecoveryPayout($previousRecoveryPayoutsId, true);

        if ($feeRecovery instanceof Base\PublicEntity)
        {
            return $feeRecovery->toArrayPublic();
        }
        else
        {
            return $feeRecovery;
        }
    }

    public function processFeeRecoveryBalanceCron()
    {
        return $this->core()->processFeeRecoveryBalanceCron();
    }

    public function updateFeeRecoveryScheduleAdmin(array $input)
    {
        $response = $this->core()->updateFeeRecoveryScheduleAdmin($input);

        return $response;
    }

    public function calculateFeeRecoveryAmountAdmin(array $input)
    {
        (new Validator())->validateInput('calculate_fee_recovery_amount', $input);

        $response = $this->core()->calculateFeeRecoveryAmountAdmin($input[Entity::BALANCE_ID], $input[Entity::FROM], $input[Entity::TO]);

        return $response;
    }

    public function createRecoveryPayoutJobAdmin(array $input)
    {
        (new Validator())->validateInput('create_recovery_payout_job_admin', $input);

        $response = $this->core()->createRecoveryPayoutJobAdmin($input);

        return $response;
    }
}
