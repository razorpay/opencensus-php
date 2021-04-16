<?php

namespace RZP\Models\Batch\Processor;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

use RZP\Constants\Mode;
use RZP\Models\Batch\Header;
use RZP\Models\FileStore\Type;
use RZP\Models\Payout\BatchHelper;
use RZP\Models\Payout as PayoutModel;
use RZP\Models\Merchant\RazorxTreatment;

class TallyPayout extends Base
{
    const TOTAL_PAYOUT_AMOUNT = "total_payout_amount";

    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        return Header::getHeadersForFileTypeAndBatchType($this->inputFileType, $this->batch->getType());
    }

    /**
     * Adds the total payout amount to the response.
     * @param array $entries
     * @return array
     */
    protected function getValidatedEntriesStatsAndPreview(array $entries): array
    {
        $response = parent::getValidatedEntriesStatsAndPreview($entries);

        $totalPayoutAmount = (int) (array_sum(array_column($entries, Header::PAYOUT_AMOUNT_RUPEES)) * 100);

        $response += [self::TOTAL_PAYOUT_AMOUNT => $totalPayoutAmount];

        return $response;
    }
}
