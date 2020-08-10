<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Models\Base;
use RZP\Models\Counter;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity;

class Service extends Base\Service
{
    const FREE_PAYOUT_UPDATE_MUTEX_LOCK_TIMEOUT = 60;

    public function updateFreePayout($id, $input)
    {
        Base\UniqueIdEntity::verifyUniqueId($id, true);

        $mutexResource = sprintf('UPDATE_FREE_PAYOUT_%s_%s',
            $id,
            $this->mode);

        return $this->app['api.mutex']->acquireAndRelease(
            $mutexResource,
            function() use ($id, $input) {

                /** @var Entity $balance */
                $balance = $this->repo->balance->findOrFailById($id);

                (new Validator)->validateInput(Validator::UPDATE_FREE_PAYOUTS_ATTRIBUTES, $input);

                $this->trace->info(
                    TraceCode::UPDATE_FREE_PAYOUTS_ATTRIBUTES_REQUEST,
                    [
                        'balance_id' => $balance->getId(),
                        'input' => $input,
                    ]
                );

                $updatePayoutsAttributes = $this->createCounterForBalance($balance, $input);

                $this->trace->info(
                    TraceCode::UPDATE_FREE_PAYOUTS_ATTRIBUTES_SUCCESS,
                    [
                        'balance_id' => $balance->getId(),
                        'updated_free_payouts_data' => $updatePayoutsAttributes,
                    ]
                );

                return $updatePayoutsAttributes;
            },
            self::FREE_PAYOUT_UPDATE_MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_FREE_PAYOUT_UPDATE_ANOTHER_OPERATION_IN_PROGRESS);
    }

    public function createCounterForBalance($balance, $input)
    {
        $updatePayoutsAttributes = [];

        $freePayoutObj = new FreePayout;

        if (isset($input[FreePayout::FREE_PAYOUTS_COUNT]) === true)
        {
            $freePayoutsCount = $input[FreePayout::FREE_PAYOUTS_COUNT];

            (new Counter\Core)->createIfNotExists($balance);

            $freePayoutObj->addNewAttribute($freePayoutsCount,
                $balance,
                FreePayout::FREE_PAYOUTS_COUNT);

            $updatePayoutsAttributes[FreePayout::FREE_PAYOUTS_COUNT] = $freePayoutsCount;
        }

        if (isset($input[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES]) === true)
        {
            $freePayoutsSupportedModes = $input[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES];

            if (is_array($freePayoutsSupportedModes) === false)
            {
                $freePayoutsSupportedModes = [];
            }

            $freePayoutObj->addNewAttribute($freePayoutsSupportedModes,
                $balance,
                FreePayout::FREE_PAYOUTS_SUPPORTED_MODES);

            $updatePayoutsAttributes[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES] = $freePayoutsSupportedModes;
        }

        return $updatePayoutsAttributes;
    }
}
