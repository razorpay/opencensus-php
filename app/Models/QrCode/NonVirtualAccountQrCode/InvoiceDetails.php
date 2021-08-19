<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use Carbon\Carbon;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Models\QrCode\Constants as Constants;

class InvoiceDetails
{
    const GST_KEY_VALUE_DELIMITER = ':';

    const BUSINESS_GSTIN = 'business_gstin';
    const INVOICE_NUMBER = 'number';
    const INVOICE_DATE   = 'date';
    const GST_AMOUNT     = 'gst_amount';
    const CESS_AMOUNT    = 'cess_amount';
    const CUSTOMER_NAME  = 'customer_name';
    const SUPPLY_TYPE    = 'supply_type';

    const SUPPLY_TYPE_INTERSTATE = 'interstate';
    const SUPPLY_TYPE_INTRASTATE = 'intrastate';

    protected static $taxTagsMapping = [
        self::BUSINESS_GSTIN => Base\IntentParams::GSTIN,
        self::INVOICE_NUMBER => Base\IntentParams::INVOICE_NO,
        self::INVOICE_DATE   => Base\IntentParams::INVOICE_DATE,
        self::CUSTOMER_NAME  => Base\IntentParams::INVOICE_NAME,
    ];

    public static function getTaxDetails($qrCode)
    {
        if (empty($qrCode->getTaxInvoice()) === true)
        {
            return [];
        }

        $invoiceDetails = $qrCode->getTaxInvoice();

        $taxDetails = [];

        $gstBreakUp = [];

        foreach ($invoiceDetails as $key => $value)
        {
            switch ($key)
            {
                case InvoiceDetails::BUSINESS_GSTIN:

                    $taxDetails[Base\IntentParams::GSTIN] = $invoiceDetails[InvoiceDetails::BUSINESS_GSTIN];

                    break;

                case InvoiceDetails::INVOICE_NUMBER:

                    $taxDetails[Base\IntentParams::INVOICE_NO] = $invoiceDetails[InvoiceDetails::INVOICE_NUMBER];

                    break;

                case InvoiceDetails::INVOICE_DATE:

                    $taxDetails[Base\IntentParams::INVOICE_DATE] = Carbon::createFromTimestamp($invoiceDetails[InvoiceDetails::INVOICE_DATE])
                                                                         ->toDateTimeLocalString() . Constants::UTC_INDIA_OFFSET;

                    break;

                case InvoiceDetails::CUSTOMER_NAME:

                    $taxDetails[Base\IntentParams::INVOICE_NAME] = preg_replace('/[^A-Za-z0-9]/', '',
                                                                                $invoiceDetails[InvoiceDetails::CUSTOMER_NAME]);

                    break;

                case InvoiceDetails::GST_AMOUNT:

                    // Input in paise, so converting to Rs
                    $gstAmount = $invoiceDetails[InvoiceDetails::GST_AMOUNT] / 100;

                    $gstBreakUp[Base\IntentParams::GST] = Base\IntentParams::GST . ':' . $gstAmount;

                    if (array_key_exists(InvoiceDetails::SUPPLY_TYPE, $invoiceDetails) and
                        $invoiceDetails[InvoiceDetails::SUPPLY_TYPE] === InvoiceDetails::SUPPLY_TYPE_INTERSTATE)
                    {
                        $gstBreakUp[Base\IntentParams::IGST] = Base\IntentParams::IGST . self::GST_KEY_VALUE_DELIMITER . $gstAmount;
                    }
                    else
                    {
                        // CGST = SGST = GST/2
                        $gstBreakUp[Base\IntentParams::SGST] = Base\IntentParams::SGST . self::GST_KEY_VALUE_DELIMITER . ($gstAmount / 2);

                        $gstBreakUp[Base\IntentParams::CGST] = Base\IntentParams::CGST . self::GST_KEY_VALUE_DELIMITER . ($gstAmount / 2);
                    }

                    break;

                case InvoiceDetails::CESS_AMOUNT:

                    $gstBreakUp[Base\IntentParams::CESS] = Base\IntentParams::CESS . self::GST_KEY_VALUE_DELIMITER .
                                                           ($invoiceDetails[InvoiceDetails::CESS_AMOUNT] / 100);

                    break;
            }
        }

        if (empty($gstBreakUp) === false)
        {
            $taxDetails[Base\IntentParams::GST_BREAKUP] = implode('|', $gstBreakUp);
        }

        if (empty($taxDetails[Base\IntentParams::INVOICE_DATE]) === true)
        {
            $taxDetails[Base\IntentParams::INVOICE_DATE] = Carbon::now(Timezone::IST)->toDateTimeLocalString() . Constants::UTC_INDIA_OFFSET;
        }

        return $taxDetails;
    }
}
