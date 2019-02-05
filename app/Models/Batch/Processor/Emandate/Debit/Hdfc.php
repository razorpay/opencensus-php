<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Netbanking\Base as NetbankingBase;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;

class Hdfc extends Base
{
    protected $gateway = Gateway::NETBANKING_HDFC;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            self::PAYMENT_ID            => $row[ Headings::TRANSACTION_REF_NO ],
            self::ACCOUNT_NUMBER        => $row[ Headings::ACCOUNT_NO ],
            self::GATEWAY_ERROR_MESSAGE => $row[ Headings::REJECTION_REMARKS ],
            self::GATEWAY_RESPONSE_CODE => $row[ Headings::STATUS ],
            self::AMOUNT                => $row[ Headings::AMOUNT],
        ];
    }

    /**
     * @param array $content
     * @return array
     * @throws Exception\GatewayErrorException
     */
    protected function getGatewayAttributes(array $content): array
    {
        $gatewayStatus = strtolower($content[self::GATEWAY_RESPONSE_CODE]);

        return [
            NetbankingBase\Entity::RECEIVED       => true,
            NetbankingBase\Entity::ERROR_MESSAGE  => $content[self::GATEWAY_ERROR_MESSAGE],
            NetbankingBase\Entity::STATUS         => $gatewayStatus,
        ];
    }

    /**
     * @param array $content
     * @return bool
     * @throws Exception\GatewayErrorException
     */
    protected function isAuthorized(array $content): bool
    {
        return Netbanking\Hdfc\Status::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE]);
    }

    protected function getApiErrorCode(array $content): string
    {
        $errorDescription = $content[self::GATEWAY_ERROR_MESSAGE];

        return Netbanking\Hdfc\ErrorCode::getApiErrorCode($errorDescription);
    }


    /**
     * - Trims empty rows and/or columns from xlsx/csv parsed rows.
     * - Trims additional headers of validated file, if applies.
     * Overriding temporarily
     * @param  array $entries
     * @return array
     */
    protected function cleanParsedEntries(array $entries): array
    {
        $totalEntries = count($entries);

        // CSV: Removes first dictionary if it's the header itself
        if ((empty($entries) === false) and (array_keys($entries[0]) === array_values($entries[0])))
        {
            array_shift($entries);
        }

        // The output file that is given to merchants in the `batches/validate` api has two extra columns named `Error
        // Code` and `Error Description`. For batch create, if the merchant passes a `file_id`, the downloaded file has
        // these two columns, whose entries are removed below.
        if ($this->inputFileType === FileStore\Type::BATCH_VALIDATED)
        {
            $entries = array_map(
                function ($entry)
                {
                    array_forget($entry, [Batch\Header::ERROR_CODE, Batch\Header::ERROR_DESCRIPTION]);

                    return $entry;
                },
                $entries);
        }

        // Excel: Removes empty trailing rows
        $entries = array_filter($entries, function ($v)
        {
            return (empty(array_filter($v)) === false);
        });

        //
        // Excel: Removes empty(not all additional columns) trailing columns
        // Notes: Input file can have 0 to max 15 notes columns in the format: notes[key_1], notes[key_2]
        //        Puts formatted notes key value pair in entry for consumption by other components(in validation,
        //        processors of specific type etc)
        //
        foreach ($entries as & $entry)
        {
            $index = 0;

            foreach ($entry as $key => $value)
            {
                // Excel: Empty trailing columns comes as sequentially indexed key and null values
                if ((($key === $index++) or ($key === '')) and ($value === null))
                {
                    unset($entry[$key]);
                }
                // If key is of notes pattern pushes the key value pair in a entry's notes & unset current key
                else if (preg_match(Batch\Header::NOTES_REGEX, $key, $matches) === 1)
                {
                    unset($entry[$key]);
                    $entry[Batch\Header::NOTES][$matches[1]] = $value;
                }
            }
        }

        $stats        = ['total_entries' => $totalEntries, 'total_cleaned_entries' => $totalEntries - count($entries)];
        $tracePayload = $this->batch->toArrayTrace([], $stats);

        $this->trace->debug(TraceCode::BATCH_PROCESS_ENTRIES_CLEANED, $tracePayload);

        return $entries;
    }
}
