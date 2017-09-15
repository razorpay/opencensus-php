<?php

namespace RZP\Models\Gateway\Downtime;

use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment;

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

    /**
     * Fetches downtime information at the current time
     * @param  array  $methods Array of methods for which to fetch downtime
     *                         If empty, then downtime for all methods are returned
     * @return Collection      Collection of downtimes
     */
    public function getPublicGatewayDowntimeData(array $methods = []): Base\PublicCollection
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
        $downtimes = $this->repo->gateway_downtime
                                ->fetchDowntimesWithoutTerminal($input, $methods);

        return $downtimes;
    }

    public function fetchMostRecentActive(array $input)
    {
        return $this->repo->gateway_downtime->fetchMostRecentActive($input);
    }

    /**
     * Gets the list of relevant downtimes from database
     *
     * @param  array        $terminals Set of all terminals
     * @param  array        $input     Array containing payment, merchant enttties
     * @return PublicCollection collection of applicable downtimes
     */
    public function getApplicableDowntimesForPayment(
                        array $terminals,
                        array $input): Base\PublicCollection
    {
        $params = $this->getDowntimeFetchParams($terminals, $input);

        $downtimes = $this->repo
                          ->gateway_downtime
                          ->fetchApplicableDowntimesForPayment($params);

        return $downtimes;
    }

    /**
     * Forms the query params for fetching downtimes,
     * based on payment and list of terminal gateways.
     *
     * Common fields :
     * 1. `method`  : netbanking/card/emi
     * 2. `gateway` : list of terminal gateways & `all`
     * 3. `partial` : false (since we do not want to filter
     *                       downtimes with low success rate)
     *
     * Fields for netbanking : (not implemented, keeping for ref)
     * 1. `method` : netbanking
     * 2. `issuer` : bank name
     *
     * Fiels for Card/Emi :
     * 1. `method`    : [card, emi]
     * 2. `network`   : card network & `all`
     * 3. `card_type` : card type & `all`
     * 4. `issuer`    : if present & `all`, else, only `all`
     *
     * @param $terminals array
     * @param $input     array
     * @return $params   array
     */
    protected function getDowntimeFetchParams(array $terminals, array $input): array
    {
        $payment = $input['payment'];

        $gateways = $this->getTerminalGateways($terminals);

        $gateways[] = Entity::ALL;

        $now = Carbon::now()->getTimestamp();

        $params = [
            Entity::GATEWAY => $gateways,
            Entity::PARTIAL => false,
            Entity::END     => $now,
            Entity::BEGIN   => $now,
        ];

        if ($payment->isMethodCardOrEmi() === true)
        {
            $this->fillCardDetails($params, $payment);

            return $params;
        }

        return [];
    }

    /**
     * Sets card related data in params
     *
     * @param $params by reference
     * @param $payment Payment\Entity
     */
    protected function fillCardDetails(array & $params, Payment\Entity $payment)
    {
        $params[Entity::METHOD] = [Payment\Method::CARD, Payment\Method::EMI];

        $params[Entity::NETWORK] = [$payment->card->getNetworkCode(),
                                    Entity::ALL,
                                    Entity::UNKNOWN,
                                    strtolower(Entity::UNKNOWN)];

        $params[Entity::CARD_TYPE] = [$payment->card->getType(),
                                      Entity::ALL,
                                      Entity::UNKNOWN,
                                      strtolower(Entity::UNKNOWN)];

        $params[Entity::ISSUER] = [Entity::ALL,
                                   Entity::UNKNOWN,
                                   strtolower(Entity::UNKNOWN)];

        $issuer = $payment->card->getIssuer();

        if (empty($issuer) === false)
        {
            $params[Entity::ISSUER][] = $issuer;
        }
    }

    /**
     * Gets the list of gateways from the given terminals
     *
     * Sometimes the list might have duplicates,
     * as more than one terminal can have same gateway
     * Hence, we use `array_unique` before returning
     *
     * @param $terminals array of Terminal\Entity
     * @return $terminalGateways array
     */
    protected function getTerminalGateways(array $terminals) : array
    {
        $gateways = array_pluck($terminals, 'gateway');

        $gateways = array_values(array_unique($gateways));

        return $gateways;
    }
}
