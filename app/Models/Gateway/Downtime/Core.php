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
            // Confirm te logic of verifyIfNeedsUpdate()
            $needsUpdate = $this->verifyIfNeedsUpdate($downtime, $input);

            if ($needsUpdate === true)
            {
                $downtime->edit($input, 'edit_duplicate');
            }
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

    public function delete(string $id)
    {
        $downtime = $this->repo->gateway_downtime->findOrFailPublic($id);

        $this->repo->gateway_downtime->deleteOrFail($downtime);

        $this->trace->info(
            TraceCode::GATEWAY_DOWNTIME_DELETE, ['id' => $downtime->getId()]);

        return $downtime;
    }

    // TODO: Need to relook at the format of the data sent to checkout
    public function getFormattedGatewayDowntimeCheckoutData(Merchant\Entity $merchant)
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [Entity::FROM => time()];

        $downtimes = $this->repo->gateway_downtime->fetch($input);

        $formatted = [];

        foreach ($downtimes as $downtime)
        {
            $method = $downtime->getMethod();

            // Shouldn't this be in collection class?
            $data = $this->getFormattedCheckoutDataRecord($merchant, $downtime);

            if (empty($data) === false)
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
                return [];
            }
        }

        $data = [
            Entity::ISSUER      => $downtime->getIssuer(),
            Entity::CARD_TYPE   => $downtime->getCardType(),
            Entity::NETWORK     => $downtime->getNetwork(),
            Entity::REASON_CODE => $downtime->getReasonCode(),
            Entity::PARTIAL     => $downtime->isPartial(),
            Entity::SCHEDULED   => $downtime->isScheduled(),
        ];

        return array_filter($data);
    }

    public function fetchMostRecentActive(array $input)
    {
        // $queryParams = [];

        // foreach ($this->uniqueCheckerKeys as $key)
        // {
        //     if (isset($input[$key]) === true)
        //     {
        //         $queryParams[$key] = $input[$key];
        //     }
        // }

        $downtimes = $this->repo->gateway_downtime->fetchMostRecentActive($input);

        return $downtimes->first();
    }

    protected function verifyIfNeedsUpdate(Entity $alreadyScheduled, array $input)
    {
        $scheduled = 0;

        if ((isset($input[Entity::SCHEDULED]) === true) and
            ($input[Entity::SCHEDULED] === '1'))
        {
            $scheduled = 1;
        }

        $returnStatus = false;

        // check for card methods. create a new one if needed right away
        // for netbanking and wallet, issuer is already taken care of by validator. Others
        // are irrelevant

        if ($alreadyScheduled->getMethod() === Method::CARD)
        {
            $network = $alreadyScheduled->getNetwork();

            $cardType = $alreadyScheduled->getCardType();

            $issuer = $alreadyScheduled->getIssuer();

            if (($this->isUnknownAllOrNull($network) === true) or
                ($this->isUnknownAllOrNull($cardType) === true) or
                ($this->isUnknownAllOrNull($issuer) === true))
            {
                $returnStatus = true;
            }
        }

        if (($alreadyScheduled->isScheduled() === false) and
            ($scheduled === 1))
        {
            $returnStatus = true;
        }

        return $returnStatus;
    }

    protected function isUnknownAllOrNull($value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::ALL, null], true);
    }
}
