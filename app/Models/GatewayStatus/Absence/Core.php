<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    protected $uniqueCheckerKeys = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::DOWNTIME_FROM
    ];

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
        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        $alreadyAvailable = $this->verifyIfExists($input);

        if (empty($alreadyAvailable) === false)
        {
            $needsUpdate = $this->verifyIfNeedsUpdate($alreadyAvailable, $input);

            if ($needsUpdate === true)
            {
                $editInput = $this->buildEditInput($input);

                return $this->edit($alreadyAvailable, $editInput);
            }

            return $alreadyAvailable;
        }

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        $downWindow = (new Entity)->build($input);

        $this->repo->saveOrFail($downWindow);

        return $downWindow;
    }

    public function edit(Entity $downWindow, array $input)
    {
        $downWindow->edit($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_EDIT, $input);

        return $downWindow;
    }

    public function delete(Entity $downWindow)
    {
        $id = $downWindow->getId();

        $this->repo->gateway_absence->deleteOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_DELETE, ['id' => $id]);

        return $downWindow;

    }

    public function getFormattedGatewayAbsenceCheckoutData(Merchant\Entity $merchant)
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [Entity::DOWNTIME_FROM => time()];

        $absentees = $this->repo->gateway_absence->fetch($input);

        $formatted = [];

        foreach ($absentees as $absent)
        {
            $method = $absent->getMethod();

            $data = $this->getFormattedCheckoutDataRecord($merchant, $absent);

            if (empty($data) === false)
            {
                $formatted[$method][] = $data;
            }
        }

        return $formatted;
    }

    public function fetchMostRecentActive(array $input)
    {
        $queryParams = [];

        foreach ($this->uniqueCheckerKeys as $key)
        {
            if (isset($input[$key]) === true)
            {
                $queryParams[$key] = $input[$key];
            }
        }

        $activeAbsentees = $this->repo->gateway_absence->fetchMostRecentActive($queryParams);

        return $activeAbsentees->first();
    }

    protected function verifyIfExists(array $input)
    {
        $queryParams = [];

        foreach ($this->uniqueCheckerKeys as $key)
        {
            if (isset($input[$key]) === true)
            {
                $queryParams[$key] = $input[$key];
            }
        }

        $absentees = $this->repo->gateway_absence->fetch($queryParams);

        return $absentees->firstOrFail();
    }

    protected function verifyIfNeedsUpdate(Entity $alreadyScheduled, array $input)
    {
        $scheduled = 0;

        if ((isset($input[Entity::SCHEDULED]) === true) and
            ($input[Entity::SCHEDULED] === "1"))
        {
            $scheduled = 1;
        }

        if ($alreadyScheduled->isScheduled() === $scheduled)
        {
            return false;
        }

        if (($alreadyScheduled->isScheduled() === true) and
            ($scheduled === 0))
        {
            return false;
        }

        if (($alreadyScheduled->isScheduled() === false) and
            ($scheduled === 1))
        {
            return true;
        }

        return false;
    }

    protected function buildEditInput(array $input)
    {
        $editInput = [
            Entity::SCHEDULED       => $input[Entity::SCHEDULED],
            Entity::DOWNTIME_FROM   => $input[Entity::DOWNTIME_FROM],
            Entity::DOWNTIME_TO     => $input[Entity::DOWNTIME_TO]
        ];

        if (isset($input[Entity::COMMENT]))
        {
            $editInput[Entity::COMMENT] = $input[Entity::COMMENT];
        }

        if (isset($input[Entity::PARTIAL]))
        {
            $editInput[Entity::PARTIAL] = $input[Entity::PARTIAL];
        }

        $editInput[Entity::SOURCE] = $input[Entity::SOURCE];

        return $editInput;
    }

    protected function getFormattedCheckoutDataRecord(Merchant\Entity $merchant, Entity $absent)
    {
        // in case we have a terminal id, we need to ensure the corresponding merchant
        // alone receives this data. Else, nothing to send here
        $terminalId = $absent->getTerminalId();

        if ($terminalId !== null)
        {
            $terminal = $absent->terminal;

            if ($terminal->getMerchantId() !== $merchant->getId())
            {
                return [];
            }
        }

        $data = [
            Entity::ISSUER      => $absent->getIssuer(),
            Entity::CARD_TYPE   => $absent->getCardType(),
            Entity::NETWORK     => $absent->getNetwork(),
            Entity::REASON_CODE => $absent->getReasonCode(),
            Entity::PARTIAL     => $absent->isPartial(),
            Entity::SCHEDULED   => $absent->isScheduled(),
        ];

        return array_filter($data);
    }
}
