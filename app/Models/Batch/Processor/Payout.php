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

class Payout extends Base
{
    const TOTAL_PAYOUT_AMOUNT = "total_payout_amount";

    protected $headers = [];

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

        if ($this->amountType === BatchHelper::PAISE)
        {
            $totalPayoutAmount = array_sum(array_column($entries, Header::PAYOUT_AMOUNT));
        }
        else
        {
            // Multiplying by 100 since amount is in rupees
            $totalPayoutAmount = (int) (array_sum(array_column($entries, Header::PAYOUT_AMOUNT_RUPEES)) * 100);
        }

        $response += [self::TOTAL_PAYOUT_AMOUNT => $totalPayoutAmount];

        return $response;
    }

    /**
     * Should return 1 during the validation flow since we wish to skip the first header row.
     *
     * Returns 0 during the creation flow, because a successfully validated file does not have the extra header row.
     *
     * @return int
     */
    protected function getNumRowsToSkipExcelFile()
    {
        if ($this->inputFileType === Type::BATCH_INPUT)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    protected function parseFirstRowAndGetHeadings(array & $rows, string $delimiter)
    {
        $this->headers = str_getcsv(current($rows), $delimiter);

        $headerRow = $rows[0];

        $this->setBatchPayoutsAmountType($headerRow);

        return $this->headers;
    }

    protected  function setBatchPayoutsAmountType(string $headerRow)
    {
        if (empty(strpos($headerRow, Header::PAYOUT_AMOUNT_RUPEES)) === false)
        {
            $this->amountType = BatchHelper::RUPEES;
        }
        else if (empty(strpos($headerRow, Header::PAYOUT_AMOUNT)) === false)
        {
            $this->amountType = BatchHelper::PAISE;
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BULK_PAYOUTS_PAYOUT_HEADER_MISMATCH,
                null,
                [
                    'headers' => $headerRow
                ]);
        }
    }

    protected function updateBatchHeadersIfApplicable(array &$headers, array $entries)
    {
        $variant  = $this->app['razorx']->getTreatment($this->merchant->getId(),
                                                       RazorxTreatment::BULK_PAYOUTS_IMPROVEMENTS_ROLLOUT,
                                                       Mode::LIVE,
                                                       3);

        if (strtolower($variant) === 'control')
        {
            $this->amountType = BatchHelper::PAISE;
        }

        if ($this->amountType === BatchHelper::PAISE)
        {
            $headers = array_diff($headers, [Header::PAYOUT_AMOUNT_RUPEES]);
        }
        else
        {
            $headers = array_diff($headers, [Header::PAYOUT_AMOUNT]);
        }
    }

    /**
     * In case of error in the validated file, we explicitly add the extra header and all the formatting since the
     * error file is to be sent back to the merchant.
     *
     * In case of no errors, we do not need to add any extra header or formatting.
     * We simply send the request to batch service without the extra header.
     *
     * @param $data
     * @param $name
     * @param array $columnFormat
     * @param string[] $sheetNames
     * @return mixed
     */
    protected function createExcelObject($data, $dir, $name, $extension, $columnFormat = [], $sheetNames = ['Sheet 1'])
    {
        $errorFlag = false;

        foreach ($data as $row)
        {
            if (empty($row[Header::ERROR_CODE]) === false)
            {
                $errorFlag = true;

                break;
            }
        }

        if ($errorFlag === true)
        {
            return (new PayoutModel\Bulk\Base)->createExcelObject($data, $dir, $name, $extension, $columnFormat, $sheetNames);
        }
        else
        {
            return parent::createExcelObject($data, $dir, $name, $extension, $columnFormat, $sheetNames);
        }
    }
}
