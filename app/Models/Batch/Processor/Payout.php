<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Header;

class Payout extends Base
{

    const TOTAL_PAYOUT_AMOUNT = "total_payout_amount";

    public function addSettingsIfRequired(& $input)
    {
        if (isset($input["config"]) === true) {
            $config = $input["config"];

            $input["config"] = $config;
        }
    }

    /**
     * Adds the total payout amount the response
     * @param array $entries
     * @return array
     */
    protected function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $response = parent::getValidatedEntriesStatsAndPreview($entries);

        $totalApprovedAmount = array_sum(array_column($entries, Header::PAYOUT_AMOUNT));

        $response += [self::TOTAL_PAYOUT_AMOUNT => $totalApprovedAmount];

        return $response;
    }
}
