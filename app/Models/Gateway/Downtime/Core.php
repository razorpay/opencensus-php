<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;

class Core extends Base\Core
{
    /**
     * Prevent duplicate creation of the same error model.
     * Basically, since we pass an empty 'to', it means, this is for an unscheduled
     * maintenance. In case of a scheduled maintenance, the 'to' param is set.
     * For an unscheduled one, in case there already does exist a record for the
     * same gateway, issuer and method, update the unscheduled with scheduled. In
     * case there already does exist a scheduled one, and the current one is unscheduled,
     * do not replace. Essentially, the scheduled one precedes the unscheduled.
     *
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input)
    {
        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_CREATE, $input);

        $downtime = $this->repo->gateway_downtime->fetchUnique($input);

        if ($downtime !== null)
        {
            $downtime->edit($input, 'edit_duplicate');
        }
        else
        {
            $downtime = (new Entity)->build($input);
        }

        $this->repo->saveOrFail($downtime);

        return $downtime;
    }

    public function edit(string $id, array $input)
    {
        $downtime = $this->repo->gateway_downtime->findOrFailPublic($id);

        $this->trace->info(TraceCode::GATEWAY_DOWNTIME_EDIT, $input);

        $downtime->edit($input);

        $this->repo->saveOrFail($downtime);

        return $downtime;
    }

    public function getFormattedGatewayDowntimeCheckoutData(Merchant\Entity $merchant)
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [
            Entity::BEGIN => time(),
        ];

        // Currently we are fetching only downtimes with null terminal id
        // as only a particular gateway terminal having a systemic downtime hasn't
        // been encountered yet. Will need to modify this later when we deal with
        // such downtimes
        $downtimes = $this->repo->gateway_downtime->fetchDowntimesWithoutTerminal($input);

        return $downtimes->toArrayExternal();
    }

    public function fetchMostRecentActive(array $input)
    {
        return $this->repo->gateway_downtime->fetchMostRecentActive($input);
    }
}
