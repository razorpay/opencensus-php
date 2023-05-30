<?php

namespace RZP\Models\Batch\Processor;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder as PhpSpreadsheetDefaultValueBinder;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

use RZP\Constants\Mode;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\FileStore\Type;
use RZP\Models\Batch\Constants;
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
        // Rather than using the parent function, we have to now write this function ourselves so that we do not rely
        // on the error code column of the file. This way, we can simply rely on the error message
        $correctEntries = array_filter($entries, function($entry)
        {
            return (isset($entry[Header::ERROR_DESCRIPTION]) === false);
        });

        $maxRowsToParse = self::MAX_PARSED_ROWS;

        $previewData = array_slice($correctEntries, 0, $maxRowsToParse);

        $this->removeErrorColumnsFromEntries($previewData);

        $response = [
            Constants::PROCESSABLE_COUNT => count($correctEntries),
            Constants::ERROR_COUNT       => count($entries) - count($correctEntries),
            Constants::PARSED_ENTRIES    => $previewData,
        ];

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
        $headers = str_getcsv(current($rows), $delimiter);

        // deserialization of notes on headers is required as notes in entries as deserialized.
        $this->headers = $this->deserializeNotesInHeaders($headers);

        $headerRow = $rows[0];

        $this->setBatchPayoutsAmountType($headerRow);

        return $headers;
    }

    /**
     * We have now overloaded this function rather than using the base function, so that we can store the initial
     * headers provided to us in the input file. We later use the same headers to create the output file.
     *
     * Parses excel sheets at given path and returns array content.
     * Uses new phpoffice/phpspreadsheet package instead of maatwebsite/excel.
     *
     * @param  string $filePath
     * @param int $numRowsToSkip
     * @return array
     */
    protected function parseExcelSheetsUsingPhpSpreadSheet($filePath, $numRowsToSkip = 0): array
    {
        $fileType = SpreadsheetIOFactory::identify($filePath);

        $reader = SpreadsheetIOFactory::createReader($fileType);

        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new PhpSpreadsheetDefaultValueBinder);

        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        if ($spreadsheet->getSheetCount() !== 1)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
                null,
                null,
                "File upload failed, only 1 sheet allowed per file"
            );
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, false);

        $rows = array_slice($rows, $numRowsToSkip);

        $headers = array_values(array_shift($rows) ?? []);

        // deserialization of notes on headers is required as notes in entries as deserialized.
        $this->headers = $this->deserializeNotesInHeaders($headers);

        // No rows exists
        if (empty($headers) === true)
        {
            return [];
        }

        // Format rows as "heading key => value" kind of associative array
        foreach ($rows as & $row)
        {
            $row = array_combine($headers, array_values($row));
        }

        return $rows;
    }

    protected function deserializeNotesInHeaders(array $headers)
    {
        foreach ($headers as $key => $header)
        {
            if (preg_match(Header::NOTES_REGEX, $header, $matches) === 1)
            {
                if (array_search(Header::NOTES, $headers) === false)
                {
                    $headers[$key] = Header::NOTES;
                }
                else
                {
                    unset($headers[$key]);
                }
            }
        }

        return $headers;
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
        $currentHeaders = $this->headers;

        $headers = array_merge([Header::ERROR_DESCRIPTION], $currentHeaders);
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
            if (empty($row[Header::ERROR_DESCRIPTION]) === false)
            {
                $errorFlag = true;

                break;
            }
        }

        if ($errorFlag === true)
        {
            return (new PayoutModel\Bulk\ErrorFile)->createExcelObject($data, $dir, $name, $extension, $columnFormat, $sheetNames);
        }
        else
        {
            return parent::createExcelObject($data, $dir, $name, $extension, $columnFormat, $sheetNames);
        }
    }

    protected function saveSettings(array $input)
    {
        $config = $input[Entity::CONFIG] ?? [];

        // Temporary: For payout type batch captures user email to be used later to send processed file to.
        if (($this->batch->isPayoutType() === true) and
            ($this->app->basicauth->isStrictPrivateAuth() === false))
        {
            $user = $this->app->basicauth->getUser();
            $config['user'] = [
                'name'  => $user->getName(),
                'email' => $user->getEmail(),
            ];
        }

        if ((empty($config) === false) and
            ($this->app->basicauth->isStrictPrivateAuth() === false))
        {
            $this->settingsAccessor->upsert($config)->save();
        }
    }
}
