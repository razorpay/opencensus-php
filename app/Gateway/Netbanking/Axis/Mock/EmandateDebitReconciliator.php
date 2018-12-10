<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use Carbon\Carbon;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Timezone;

class EmandateDebitReconciliator extends Base\Mock\EmandateDebitReconciliator
{
    const HEADER_INVOICE_NO        = 'Txn Reference';
    const HEADER_BILL_DEBIT_DATE   = 'Execution Date';
    const HEADER_ORIGINATOR_ID     = 'Originator ID';
    const HEADER_MANDATE_REF_NO    = 'Mandate Ref/UMR';
    const HEADER_CUSTOMER_NAME     = 'Customer Name';
    const HEADER_DEBIT_ACCOUNT     = 'Customer Bank Account';
    const HEADER_DEBIT_BILL_AMOUNT = 'Paid In Amount';
    const HEADER_MIS_INFO_3        = 'MIS_INFO3';
    const HEADER_MIS_INFO_4        = 'MIS_INFO4';
    const HEADER_FILE_REF          = 'File_Ref';
    const HEADER_STATUS            = 'Status';
    const HEADER_REMARKS           = 'Return reason';
    const HEADER_RECORD_IDENTIFIER = 'Record Identifier';

    const STATUS_SUCCESS = 'Success';
    const STATUS_FAILURE = 'Rejected';

    protected $gateway = Payment\Gateway::NETBANKING_AXIS;

    /**
     * @override
     * @var string
     */
    protected $fileToWriteName = 'RAZORPAY_MIS';

    protected $columnHeaders = [
        self::HEADER_INVOICE_NO,
        self::HEADER_BILL_DEBIT_DATE,
        self::HEADER_ORIGINATOR_ID,
        self::HEADER_MANDATE_REF_NO,
        self::HEADER_CUSTOMER_NAME,
        self::HEADER_DEBIT_ACCOUNT,
        self::HEADER_DEBIT_BILL_AMOUNT,
        self::HEADER_MIS_INFO_3,
        self::HEADER_MIS_INFO_4,
        self::HEADER_FILE_REF,
        self::HEADER_STATUS,
        self::HEADER_REMARKS,
        self::HEADER_RECORD_IDENTIFIER,
    ];

    protected function getReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $data[] = $this->generateRowData($row);
        }

        return $data;
    }

    protected function generateRowData($input)
    {
        $payment = $input['payment'];

        $row = [
            $payment['id'],
            Carbon::now(Timezone::IST)->format('d-m-Y'),
            'RAZORPA',
            '111118159501136',
            'Test Customer',
            '1234567890',
            ($payment['amount'] / 100),
            '111118159501136',
            '3533',
            'RAZOR10072018A',
            self::STATUS_SUCCESS,
            '',
            'D',
        ];

        $this->content($row, 'row_data');

        return array_combine($this->columnHeaders, $row);
    }
}
