<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;

class Core extends Base\Core
{
    protected $editableForDuplicate = [
        Entity::DOWNTIME_FROM,
        Entity::DOWNTIME_TO,
        Entity::SOURCE
    ];

    protected $unsetForDuplicate = [
        Entity::ID,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
        Entity::GATEWAY,
        Entity::METHOD
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

        $gatewayAbsence = $this->repo->gateway_downtime->fetchUnique($input);

        if ($gatewayAbsence !== null)
        {
            list($needsUpdate, $fieldsToBeUpdated) = $this->verifyIfNeedsUpdate($gatewayAbsence, $input);

            if ($needsUpdate === true)
            {
                $editInput = $this->buildEditInput(
                    $input,
                    $gatewayAbsence,
                    $fieldsToBeUpdated);

                return $this->edit($gatewayAbsence, $editInput);
            }

            return $gatewayAbsence;
        }

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        $gatewayAbsence = (new Entity)->build($input);

        $this->repo->saveOrFail($gatewayAbsence);

        return $gatewayAbsence;
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

        $this->repo->gateway_downtime->deleteOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_DELETE, ['id' => $id]);

        return $downWindow;

    }

    public function getFormattedGatewayAbsenceCheckoutData(Merchant\Entity $merchant)
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [Entity::DOWNTIME_FROM => time()];

        $absentees = $this->repo->gateway_downtime->fetch($input);

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

        $activeAbsentees = $this->repo->gateway_downtime->fetchMostRecentActive($queryParams);

        return $activeAbsentees->first();
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

        $fieldsToBeUpdated = [];

        // check for card methods. create a new one if needed right away
        // for netbanking and wallet, issuer is already taken care of by validator. Others
        // are irrelevant

        if ($alreadyScheduled->getMethod() === Method::CARD)
        {
            $network = $alreadyScheduled->getNetwork();

            $cardType = $alreadyScheduled->getCardType();

            $issuer = $alreadyScheduled->getIssuer();

            if ($this->isUnknownOrAll($network) === true)
            {
                $fieldsToBeUpdated[] = Entity::NETWORK;

                $returnStatus = true;
            }

            if ($this->isUnknownOrAll($cardType) === true)
            {
                $fieldsToBeUpdated[] = Entity::CARD_TYPE;

                $returnStatus = true;
            }

            if ($this->isUnknownOrAll($issuer) === true)
            {
                $fieldsToBeUpdated[] = Entity::ISSUER;

                $returnStatus = true;
            }
        }

        if (($alreadyScheduled->isScheduled() === false) and
            ($scheduled === 1))
        {
            $fieldsToBeUpdated [] = Entity::SCHEDULED;

            $returnStatus = true;
        }

        return [$returnStatus, $fieldsToBeUpdated];
    }

    protected function isUnknownOrAll($value)
    {
        return in_array($value, [Entity::UNKNOWN, Entity::ALL], true);
    }

    protected function buildEditInput(array $input, Entity $alreadyAvailable, array $fieldsToBeUpdated)
    {
        $editInput = $alreadyAvailable->toArray();

        foreach ($this->editableForDuplicate as $key)
        {
            $editInput[$key] = $input[$key];
        }

        foreach ($fieldsToBeUpdated as $key)
        {
            $editInput[$key] = $input[$key];
        }

        if (isset($input[Entity::COMMENT]))
        {
            $editInput[Entity::COMMENT] = $input[Entity::COMMENT];
        }

        if (isset($input[Entity::PARTIAL]))
        {
            $editInput[Entity::PARTIAL] = $input[Entity::PARTIAL];
        }

        if (isset($input[Entity::TERMINAL_ID]))
        {
            $editInput[Entity::TERMINAL_ID] = $input[Entity::TERMINAL_ID];
        }

        if ($alreadyAvailable->getReasonCode() !== $input[Entity::REASON_CODE])
        {
            $editInput[Entity::REASON_CODE] = $input[Entity::REASON_CODE];
        }

        foreach($this->unsetForDuplicate as $key)
        {
            unset($editInput[$key]);
        }

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
