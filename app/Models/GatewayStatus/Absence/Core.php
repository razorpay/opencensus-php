<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create($input)
    {
        $downWindow = (new Entity)->build($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_CREATE, $input);

        return $downWindow;
    }

    public function edit($downWindow, $input)
    {
        $downWindow->edit($input);

        $this->repo->saveOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_EDIT, $input);

        return $downWindow;
    }

    protected function getFormattedCheckoutDataRecord(Merchant\Entity $merchant, Entity $absent)
    {
        // in case we have a terminal id, we need to ensure the corresponding merchant
        // alone receives this data. Else, nothing to send here
        $terminal = $absent->terminal;

        if ($terminal !== null)
        {
            $terminalMerchant = $terminal->merchant;

            if ($terminalMerchant->getId() !== $merchant->getId())
            {
                return [];
            }
        }

        $data = [
            'issuer' => $absent->getIssuer(),
            'card_type' => $absent->getCardType(),
            'network' => $absent->getNetwork(),
            'reason_code' => $absent->getReasonCode(),
            'partial' => $absent->getPartial(),
            'scheduled' => $absent->getScheduled(),
        ];

        return $data;
    }

    public function getFormattedCheckoutData(Merchant\Entity $merchant)
    {
        // set the from time to current time. For all practical
        // purposes, this is usually not set by input.
        $input = [Entity::FROM => time()];

        $absentees = $this->repo->gateway_absence->fetchAbsent($input);

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

}
