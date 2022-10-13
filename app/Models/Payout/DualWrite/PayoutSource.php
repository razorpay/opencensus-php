<?php

namespace RZP\Models\Payout\DualWrite;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\PayoutSource\Entity;

class PayoutSource extends Base
{
    public function dualWritePSPayoutSources(string $payoutId)
    {
        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_SOURCES_INIT,
            ['payout_id' => $payoutId]
        );

        /** @var array $psPayoutSources */
        $psPayoutSources = $this->getAPIPayoutSourcesFromPayoutService($payoutId);

        /** @var PublicCollection $apiPayoutSources */
        $apiPayoutSources = $this->repo->payout_source->getPayoutSourcesByPayoutId($payoutId);

        /**
         * @var  $id                    string
         * @var  $apiPayoutSource Entity
         */
        foreach ($apiPayoutSources as $apiPayoutSource)
        {
            $id = $apiPayoutSource->getId();

            if (array_key_exists($id, $psPayoutSources) === true)
            {
                $apiPayoutSource->setRawAttributes($psPayoutSources[$id]->getAttributes());

                $this->repo->payout_source->saveOrFail($apiPayoutSource);

                unset($psPayoutSources[$id]);
            }
        }

        foreach ($psPayoutSources as $psPayoutSource)
        {
            $this->repo->payout_source->saveOrFail($psPayoutSource);
        }

        $this->trace->info(
            TraceCode::PAYOUT_SERVICE_DUAL_WRITE_PAYOUT_SOURCES_DONE,
            ['payout_id' => $payoutId]
        );
    }

    public function getAPIPayoutSourcesFromPayoutService(string $payoutId)
    {
        $payoutServiceSources = $this->repo->payout_source->getPayoutServiceSources($payoutId);

        if (count($payoutServiceSources) === 0)
        {
            return [];
        }

        $psPayoutSources = [];

        foreach ($payoutServiceSources as $payoutServiceSource)
        {
            // converts the stdClass object into associative array.
            $this->attributes = get_object_vars($payoutServiceSource);

            $this->processModifications();

            $entity = new Entity;

            $entity->setRawAttributes($this->attributes, true);

            // Explicitly setting the connection.
            $entity->setConnection($this->mode);

            // This will ensure that updated_at columns are not overridden by saveOrFail.
            $entity->timestamps = false;

            $psPayoutSources[$entity->getId()] = $entity;
        }

        return $psPayoutSources;
    }
}
