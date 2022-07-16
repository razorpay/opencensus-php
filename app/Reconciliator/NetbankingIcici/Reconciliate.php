<?php

namespace RZP\Reconciliator\NetbankingIcici;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Reconciliator\RequestProcessor;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Currently Icici shares only payment report
     */
    const SUCCESS = [
        'razorpayreport'                                 => self::PAYMENT,
        'razorpaysireports'                              => self::PAYMENT,
        'razorpaybrokerreports'                          => self::PAYMENT,
        'razorpaysoftwarepvtltdreports'                  => self::PAYMENT,
        'razorpaydonationreports'                        => self::PAYMENT,
        'zest_money_sip'                                 => self::PAYMENT,
        'razorpaywalletreports'                          => self::PAYMENT,
        'consumer_durable_loan_booking_razorpay_reports' => self::PAYMENT,
        'razorpaygovttaxpaymentreports'                  => self::PAYMENT,
        'furlencorazorpaysi'                             => self::PAYMENT,
        'ofpr_daily_report'                              => self::REFUND,
    ];

    const EXCLUDE_FILE_STRING = 'success';

    const BLANK_FILE_SIZE = 100;

    /**
     * Currently Icici shares only payment report
     */
    const TYPE_TO_COLUMN_HEADER_MAP = [
        self::PAYMENT => self::PAYMENT_COLUMN_HEADER,
        self::REFUND  => self::REFUND_COLUMN_HEADER
    ];

    const PAYMENT_COLUMN_HEADER = [
        'ITC',
        'PRN',
        'BID',
        'Amount',
        'Date'
    ];

    const REFUND_COLUMN_HEADER = [
        'Payee id',
        'Payee Name',
        'Payment id',
        'ITC',
        'PRN',
        'Txn Amount',
        'Reversal Amount',
        'Reversal Date',
        'ReversalId',
        'Status',
        'Reason',
        'SPID',
        'Sub-merchant Name'
    ];

    private $recon_type;

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

        $this->recon_type = $typeName;

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
        //
        // We get many files of nb_icici, where the file
        // content has just the below line (or something similar)
        //
        // "Cannot open ../data/../work/reports/P000000001440.success.dat for READ. [No such file or directory]"
        //
        // Such files have size = 100. Verified from DB that
        // no other gateway has file size of 100 (since Jan 2018)
        // So skipping size 100 files.
        //
        if ($fileDetails['size'] === self::BLANK_FILE_SIZE)
        {
            $this->trace->info(
                TraceCode::RECON_FILE_SKIP,
                [
                    'info_code' => Base\InfoCode::RECON_SKIP_INVALID_BLANK_FILE,
                    'file_name' => $fileDetails['file_name'],
                    'size'      => $fileDetails['size'],
                    'gateway'   => RequestProcessor\Base::NETBANKING_ICICI,
                ]);

            return true;
        }

        if (strpos($fileDetails['file_name'], self::EXCLUDE_FILE_STRING) !== false)
        {
            return true;
        }

        return false;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        $type = $this->getTypeName($fileDetails['file_name']);

        if ($type === self::REFUND)
        {
            return [
                FileProcessor::LINES_FROM_TOP       => 1,
                FileProcessor::LINES_FROM_BOTTOM    => 0
            ];
        }
        else
        {
            return parent::getNumLinesToSkip($fileDetails);
        }
    }

    public function getDelimiter()
    {
        if ($this->recon_type === self::REFUND)
        {
            return '|';
        }
        else
        {
            return parent::getDelimiter();
        }
    }
}
