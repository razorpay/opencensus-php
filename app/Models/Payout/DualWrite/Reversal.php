<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Reversal\Entity;

class Reversal extends Base
{
    protected $columnConversions = [
        'fees'            => Entity::FEE,
        Entity::PAYOUT_ID => Entity::ENTITY_ID
    ];

    public function dualWritePSReversal(string $payoutId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_REVERSAL_INIT,
            ['payout_id' => $payoutId]
        );

        $psReversal = $this->getAPIReversalFromPayoutService($payoutId);

        if (empty($psReversal) === true)
        {
            return null;
        }

        /** @var Entity $apiReversal */
        $apiReversal = $this->repo->reversal->findReversalForPayout($payoutId);

        if (empty($apiReversal) === true)
        {
            $this->repo->reversal->saveOrFail($psReversal);

            return $psReversal;
        }

        $apiReversal->setRawAttributes($psReversal->getAttributes());

        $this->repo->saveOrFail($apiReversal);

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_REVERSAL_DONE,
            ['payout_id' => $payoutId]
        );

        return $apiReversal;
    }

    public function getAPIReversalFromPayoutService(string $id)
    {
        $payoutServiceReversal = $this->repo->reversal->getPayoutServiceReversalByPayoutId($id);

        if (count($payoutServiceReversal) === 0)
        {
            return null;
        }

        $psReversal = $payoutServiceReversal[0];

        // converts the stdClass object into associative array.
        $this->attributes = get_object_vars($psReversal);

        $this->processModifications();

        $reversal = new Entity;

        $reversal->setRawAttributes($this->attributes, true);

        $reversal->setEntityType('payout');

        if (empty($reversal->getNotesJson()) === true)
        {
            $reversal->setNotes([]);
        }

        // Explicitly setting the connection.
        $reversal->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $reversal->timestamps = false;

        return $reversal;
    }
}
