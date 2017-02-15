<?php

namespace RZP\Reconciliator\Netbanking_Axis;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    // For now Axis sends only Payeespecific
    // refund and combined are left on here for structure
    const SUCCESS = [
        'Payeespecific' => self::PAYMENT,
        'refund'        => self::REFUND,
        'combined'      => self::COMBINED
    ];

    const PAYMENT_COLUMN_HEADERS = [
        'BID',
        'User Id',
        'User Name',
        'ITC No',
        'PRN No',
        'Amount',
        'Date',
        'Status'
    ];

    /*
     * Determines the type of reconciliation
     * based on the name of the file.
     * It can either be refund, payment or combined.
     * For now, only payment
     *
     * @param string $fileName
     * @return null | string
     */
    public function getTypeName($fileName)
    {
        $typeName = null;

        foreach (self::SUCCESS as $name => $type)
        {
            if (strpos($fileName, $name) !== false)
            {
                $typeName = $type;
            }
        }

        return $typeName;
    }

    public function getColumnHeadersForType($type)
    {
        $columnHeaders = [];

        if ($type === self::PAYMENT)
        {
            $columnHeaders = self::PAYMENT_COLUMN_HEADERS;
        }

        return $columnHeaders;
    }
}
