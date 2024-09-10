<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payout\Entity;
use RZP\Exception\BadRequestException;

class Payout extends Base
{
    public function dualWritePSPayout(string $id)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_INIT,
            ['payout_id' => $id]
        );

        $payout = $this->getAPIPayoutFromPayoutService($id);

        if (empty($payout) === true)
        {
            return null;
        }

        /** @var Entity $apiPayout */
        $apiPayout = $this->repo->payout->find($id);

        $previous_status = null;
        $new_status = $payout->getStatus();

        if (empty($apiPayout) === false)
        {
            $previous_status = $apiPayout->getStatus();
            $payout = $apiPayout->setRawAttributes($payout->getAttributes());
        }

        $payout->setSavePayoutServicePayoutFlag(true);

        (new PayoutLogs)->dualWritePSPayoutLogs($payout);

        $this->repo->payout->saveOrFail($payout);

        $timestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_DONE,
            [
                'payout_id' => $id,
                'timestamp' => $timestamp,
                'previous_status' => $previous_status,
                'new_status' => $new_status,
            ]
        );

        return $payout;
    }

    public function getAPIPayoutFromPayoutService(string $id, bool $sync = false)
    {
        $payoutServicePayouts = $this->repo->payout->getPayoutServicePayout($id);

        if (count($payoutServicePayouts) === 0)
        {
            $this->trace->error(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_NOT_FOUND,
                [Entity::PAYOUT_ID => $id]
            );

            throw new BadRequestException(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_NOT_FOUND,
                Entity::PAYOUT_ID,
                [Entity::PAYOUT_ID => $id]
            );
        }

        $psPayout = $payoutServicePayouts[0];

        // converts the stdClass object into associative array.
        $this->attributes = get_object_vars($psPayout);

        $this->processModifications();

        $payout = new Entity;

        $payout->setRawAttributes($this->attributes, $sync);

        $payout->setIsPayoutService(1);

        // Explicitly setting the connection.
        $payout->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $payout->timestamps = false;

        return $payout;
    }
}
