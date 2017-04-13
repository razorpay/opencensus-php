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

    // TODO: Need to relook at the format of the data sent to checkout
    public function getFormattedGatewayDowntimeCheckoutData(Merchant\Entity $merchant)
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [
            Entity::BEGIN => time(),
            Entity::PUBLIC => true
        ];

        $downtimes = $this->repo->gateway_downtime->fetch($input);

        $serializer = new ViewDataSerializer($downtimes, $merchant);

        $formatted = [];

        foreach ($downtimes as $downtime)
        {
            $method = $downtime->getMethod();

            $data = $this->getFormattedCheckoutDataRecord($merchant, $downtime);

            if ($data !== null)
            {
                $formatted[$method][] = $data;
            }
        }

        return $formatted;
    }

    protected function getFormattedCheckoutDataRecord(Merchant\Entity $merchant, Entity $downtime)
    {
        // in case we have a terminal id, we need to ensure the corresponding merchant
        // alone receives this data. Else, nothing to send here
        $terminalId = $downtime->getTerminalId();

        if ($terminalId !== null)
        {
            // Should we eager load this?
            $terminal = $downtime->terminal;

            if ($terminal->getMerchantId() !== $merchant->getId())
            {
                return null;
            }
        }

        return $downtime->toArrayCheckout();
    }

    public function fetchMostRecentActive(array $input)
    {
        return $this->repo->gateway_downtime->fetchMostRecentActive($input);
    }
}
