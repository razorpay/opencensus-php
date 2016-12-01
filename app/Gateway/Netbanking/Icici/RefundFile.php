<?php

namespace RZP\Gateway\Netbanking\Icici;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\FileStore;

class RefundFile extends Base\RefundFile
{
    protected static $fileToWriteName = 'Icici_Netbanking_Refunds';

    const EMAIL_BODY = 'Please forward the ICICI Netbanking refunds file to UBPS operations team';

    // The columns of the file
    protected static $headers = [
        'Sr No',
        'Payee id',
        'SPID',
        'Bank Reference No.',
        'Transaction Date',
        'Transaction Amount',
        'Refund Amount',
        'Transaction Id',
        'Reversal/Cancellation',
        'Remarks'
    ];

    public function generate($input)
    {
        // Calling method from below
        $data = $this->getRefundData($input);

        // Gets $filetoWriteName
        $fileName = $this->getFileToWriteNameWithoutExt();

        // Gets the path to the excel file
        $urlExcel = $this->writeToExcelFile($data, $fileName);

        // Creating a file with excel format
        $creator = $this->createFile(
            FileStore\Format::XLSX,
            $data,
            $fileName,
            FileStore\Type::ICICI_NETBANKING_REFUND);

        // Have to understand how this works exactly
        $fileData = [
            'file_path' => $this->getExcelFullFilePath(),
            'body' => self::EMAIL_BODY,
        ];

        $this->sendRefundEmail($fileData);

        return $urlExcel;
    }

    protected function getRefundData($input)
    {
        $i = 1; // Serial Number starts at 1

        // for test cases we pick up data from Fixtures / Entity / Terminal.php

        foreach ($input['data'] as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'], 'Asia/Kolkata')->format('jS F Y');

            $data[] = array(
                'Sr No'                  => $i++, // assign then increment
                'Payee id'               => $row['terminal']['gateway_merchant_id'],
                'SPID'                   => $row['terminal']['gateway_merchant_id2'], // test ------------ for all sub merchants
                'Bank Reference No.'     => $row['gateway']['bank_payment_id'],
                'Transaction Date'       => $date,
                'Transaction Amount'     => $row['payment']['amount'] / 100,
                'Refund Amount'          => $row['refund']['amount'] / 100,
                'Transaction Id'         => $row['payment']['id'],
                'Reversal/Cancellation'  => 'C', // R and C -- need a logic to get this - for now saying C
                'Remarks'                => '', // empty for now
            );
        }

        return $data;
    }
}
