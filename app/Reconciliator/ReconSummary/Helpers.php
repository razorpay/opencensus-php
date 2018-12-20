<?php

namespace RZP\Reconciliator\ReconSummary;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Entity as ConstantEntity;


class Helpers
{
    public static function getClassName(string $entity): string
    {
        return __NAMESPACE__ . '\\' . $entity . 'ReconStatusSummary';
    }

    public static function addExtraColumns(&$entry)
    {
        //
        // Initializing 'recon_count_percentage' to 100 % , bcoz
        // if total_count is 0, we want the recon_count_percentage
        // to remain as 100 %. If recon_count > 0 then recon_count_percentage
        // will be set to actual non zero percentage value.
        //
        $entry['recon_count_percentage'] = 100;

        if ($entry['total_count'] > 0)
        {
            $entry['recon_count_percentage'] = floatval(number_format(($entry['recon_count'] / $entry['total_count']) * 100, 2));
        }

        //
        // Note: In case of emandate payments, amount can be 0, resulting total amount 0.
        //
        $entry['recon_amount_percentage'] = 100;

        if ($entry['total_amount'] > 0)
        {
            $entry['recon_amount_percentage'] = floatval(number_format(($entry['recon_amount'] / $entry['total_amount']) * 100, 2));
        }
    }

    public static function getFormattedDate($timestamp, $format = 'jS F, Y')
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format($format);
    }

    public static function formatSheetColumns(& $entry, $entityName)
    {
        if ($entityName === ConstantEntity::PAYMENT)
        {
            $entry['created_at'] = self::getFormattedDate($entry['created_at'], 'jS F, Y H:m:s');
        }
        else
        {
            $entry['processed_at'] = self::getFormattedDate($entry['processed_at'], 'jS F, Y H:m:s');

        }
        $entry['payment_amount']            = $entry['payment_amount']/100;
        $entry['payment_captured_at']       = $entry['payment_captured_at'] ? self::getFormattedDate($entry['payment_captured_at'],'jS F, Y H:m:s') : '';
        $entry['payment_authorized_at']     = $entry['payment_authorized_at'] ? self::getFormattedDate($entry['payment_authorized_at'],'jS F, Y H:m:s') : '';
        $entry['payment_amount_refunded']   = $entry['payment_amount_refunded']/100;

        if (isset($entry['refund_amount']) === true)
        {
            $entry['refund_amount']  = $entry['refund_amount']/100;
        }

        unset($entry['row_number']);
    }

    public static function getFormattedSummary(array $summary)
    {
        $formattedSummary = [];

        foreach ($summary as $entry)
        {
            self::addExtraColumns($entry);

            $formattedSummary[$entry['date']][] =  $entry;
        }

        sortMultiDimensionalArray($formattedSummary, Constants::RESULT_SORT_KEY, SORT_DESC);

        $metadataEntryDateWise = self::setDateWiseStats($formattedSummary);

        // as we want the metaData entry to be on the top in the final result,
        // we add it after sorting is done
        foreach ($formattedSummary as $date => &$dateWiseEntries)
        {
            array_unshift($dateWiseEntries, $metadataEntryDateWise[$date]);
        }

        return $formattedSummary;
    }

    public static function setDateWiseStats(& $formattedSummary):array
    {
        $metadataEntryDateWise = [];
        foreach ($formattedSummary as $date => $dateWiseEntries)
        {
            //
            // For each date, calculate metadata across all gateways
            //
            // initialize each of the params to 0, and
            // iterate over the params and add them up.
            // Here we will ignore 'METHOD' during calculations
            // and later set that to (string) "All"
            //
            $metadataEntry = array_fill_keys(Constants::AGGREGATE_PARAMS, 0);

            foreach ($dateWiseEntries as $entry)
            {
                foreach (Constants::AGGREGATE_PARAMS as $param)
                {
                    if (($param === Constants::GATEWAY) or ($param === Constants::METHOD))
                    {
                        continue;
                    }

                    //
                    // Here the columns 'TXN_COUNT_CONTRIBUTION_PERCENTAGE' and
                    // 'TXN_AMOUNT_CONTRIBUTION_PERCENTAGE' are not yet present
                    // in the $entry row and these are added later in the code in
                    // function call addGatewaysContributionColumn(), so we need
                    // to put 'isset' condition
                    //
                    if (isset($entry[$param]) === false)
                    {
                        continue;
                    }

                    $metadataEntry[$param] += $entry[$param];
                }
            }

            // set the percentage column for the date
            self::addExtraColumns($metadataEntry);

            $metadataEntry['date'] = $date;
            $metadataEntry[Constants::GATEWAY] = "All";
            $metadataEntry[Constants::METHOD]  = "All";

            //
            // Now we have the metadata entry for the date, calculate
            // the individual gateway's contribution (%).
            //
            self::addGatewaysContributionColumn($formattedSummary[$date], $metadataEntry);

            $metadataEntryDateWise[$date] =  $metadataEntry;
        }

        return $metadataEntryDateWise;
    }

    /**
     * Adds two columns indicating how much individual gateway
     * contributed towards total count and total amount for a day.
     *
     * @param $dateWiseFormattedSummary
     * @param $metadataEntry
     */
    public static function addGatewaysContributionColumn(& $dateWiseFormattedSummary, & $metadataEntry)
    {
        if (($metadataEntry[Constants::TOTAL_COUNT] === 0) or
            ($metadataEntry[Constants::TOTAL_AMOUNT] === 0))
        {
            return;
        }

        // iterate over each gateway, and calculate values
        foreach ($dateWiseFormattedSummary as $index => &$entry)
        {
            $entry[Constants::TXN_COUNT_CONTRIBUTION_PERCENTAGE] = floatval(number_format(
                             ($entry[Constants::TOTAL_COUNT] / $metadataEntry[Constants::TOTAL_COUNT]) * 100,
                            2));

            $entry[Constants::TXN_AMOUNT_CONTRIBUTION_PERCENTAGE] = floatval(number_format(
                ($entry[Constants::TOTAL_AMOUNT] / $metadataEntry[Constants::TOTAL_AMOUNT]) * 100,
                2));
        }
    }
}
