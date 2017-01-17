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

    public function create($input)
    {
        //
        // Prevent duplicate creation of the same error model.
        // Basically, since we pass an empty 'to', it means, this is for an unscheduled
        // maintenance. In case of a scheduled maintenance, the 'to' param is set
        // and this will return null. For an unscheduled one, in case there already
        // does exist a record for the same gateway, issuer and method, do not create
        // additional ones.

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        $alreadyPresent = $this->verifyIfExists($input);

        if (empty($alreadyPresent) === false)
        {
            return $alreadyPresent;
        }

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        $downWindow = (new Entity)->build($input);

        $this->repo->saveOrFail($downWindow);

        return $downWindow;
    }

    public function edit($downWindow, $input)
    {
        $downWindow->edit($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_EDIT, $input);

        return $downWindow;
    }

    public function delete($downWindow)
    {
        $id = $downWindow->getId();

        $this->repo->gateway_absence->deleteOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_DELETE, ['id' => $id]);

        return $downWindow;

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

    public function verifyIfExists(array $input)
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

        return $absentees->first();

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
}
