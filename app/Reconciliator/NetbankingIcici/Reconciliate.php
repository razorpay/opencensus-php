<?php

namespace RZP\Reconciliator\NetbankingIcici;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Currently Icici shares only payment report
     */
    const SUCCESS = [
        'razorpayreports'               => self::PAYMENT,
        'razorpaysireports'             => self::PAYMENT,
        'razorpaybrokerreports'         => self::PAYMENT,
        'razorpaysoftwarepvtltdreports' => self::PAYMENT,
        'razorpaydonationreports'       => self::PAYMENT,
        'zest_money_sip'                => self::PAYMENT,
    ];

    const EXCLUDE_FILE_STRING = 'success';

    /**
     * Currently Icici shares only payment report
     */
    const TYPE_TO_COLUMN_HEADER_MAP = [
        self::PAYMENT => self::PAYMENT_COLUMN_HEADER
    ];

    const PAYMENT_COLUMN_HEADER = [
        'ITC',
        'PRN',
        'BID',
        'Amount',
        'Date'
    ];

    /**
     * Determines the type of reconciliation
     * based on the name of the file.
     * It can either be refund, payment or combined.
     * For now, only payment.
     * we convert file name to lower case before sending
     *
     * Note : Here we return type as 'invalid_recon_type' for
     * unexpected MIS files. Doing this, as it is not practical to
     * keep adding new file names to exclude list each time we get
     * such files. This new tag ensures no slack alert for these
     * files, when recon type does not fall under VALID_RECON_TYPES.
     *
     * @param string $fileName
     * @return null | string
     */
    public function getTypeName($fileName)
    {
        $typeName = self::INVALID_RECON_TYPE;

        foreach (self::SUCCESS as $name => $type)
        {
            if (strpos($fileName, $name) !== false)
            {
                $typeName = $type;

                break;
            }
        }

        return $typeName;
    }

    public function getColumnHeadersForType($type)
    {
        if ($type === self::INVALID_RECON_TYPE)
        {
            //
            // We are getting few extra files from NB-icici (file data is blank),
            // which is not yet defined in SUCCESS const here. As of now we have
            // not put these files under exclude list.
            // For such unexpected files, recon type is being returned as 'invalid_recon_type'
            // in function getTypeName() above. So we are handling the undefined index error
            // here, when lookup in done in TYPE_TO_COLUMN_HEADER_MAP array.
            //
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'infoCode'              => Base\InfoCode::RECON_TYPE_NOT_FOUND,
                    'gateway'               => $this->gateway,
                ]
            );

            return [];
        }

        return self::TYPE_TO_COLUMN_HEADER_MAP[$type];
    }

    public function inExcludeList(array $fileDetails, array $inputDetails = [])
    {
        if (strpos($fileDetails['file_name'], self::EXCLUDE_FILE_STRING) !== false)
        {
            return true;
        }

        return false;
    }
}
