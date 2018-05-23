<?php

namespace RZP\Reconciliator\ReconSummary;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class Helpers
{
    public static function getClassName(string $entity): string
    {
        return __NAMESPACE__ . '\\' . $entity . 'ReconStatusSummary';
    }

    public static function addExtraColumns(&$entry)
    {
        $entry['recon_count_percentage'] = number_format(($entry['recon_count'] / $entry['total_count']) * 100, 2);

        //
        // Note: In case of emandate payments, amount can be 0, resulting total amount 0. In this case,
        // if all reconciled transactions are of 0 amount, recon_count_percentage will be nonzero
        // but recon_amount_percentage will be 0.
        //
        $entry['recon_amount_percentage'] = 0;

        if ($entry['total_amount'] > 0)
        {
            $entry['recon_amount_percentage'] = number_format(($entry['recon_amount'] / $entry['total_amount']) * 100, 2);
        }
    }

    public static function getFormattedDate($timestamp, $format = 'jS F, Y')
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format($format);
    }

    public static function formatSheetColumns(& $entry)
    {
        $entry['created_at']                = self::getFormattedDate($entry['created_at'],'jS F, Y H:m:s');
        $entry['payment_amount']            = $entry['payment_amount']/100;
        $entry['payment_captured_at']       = self::getFormattedDate($entry['payment_captured_at'],'jS F, Y H:m:s');
        $entry['payment_authorized_at']     = self::getFormattedDate($entry['payment_authorized_at'],'jS F, Y H:m:s');
        $entry['payment_amount_refunded']   = $entry['payment_amount_refunded']/100;

        if (isset($entry['refund_amount']) === true)
        {
            $entry['refund_amount']  = $entry['refund_amount']/100;
        }

        unset($entry['row_number']);
    }

}