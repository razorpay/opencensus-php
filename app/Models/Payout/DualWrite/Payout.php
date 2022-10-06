<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use RZP\Trace\TraceCode;
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

        if (empty($apiPayout) === false)
        {
            $payout = $apiPayout->setRawAttributes($payout->getAttributes());
        }

        $payout->setSavePayoutServicePayoutFlag(true);

        (new PayoutLogs)->dualWritePSPayoutLogs($payout);

        $this->repo->payout->saveOrFail($payout);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_DONE,
            ['payout_id' => $id]
        );

        return $payout;
    }

    public function getAPIPayoutFromPayoutService(string $id)
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

        $payout->setRawAttributes($this->attributes, true);

        $payout->setIsPayoutService(1);

        // Explicitly setting the connection.
        $payout->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $payout->timestamps = false;

        return $payout;
    }
}
