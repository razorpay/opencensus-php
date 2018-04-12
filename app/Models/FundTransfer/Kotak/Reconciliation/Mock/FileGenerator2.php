<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Mock;

use App;
use Excel;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Kotak\NodalAccount;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class FileGenerator2
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    public function _construct()
    {
        $this->mode = \App::getFacadeRoot()['rzp.mode'];

        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Only test mode allowed');
        }
    }

    public function generateReconcileFile($input)
    {
        $setlFile = $this->getFile($input);

        if ($setlFile === null)
            return [];

        $data = $this->parseTextFile($setlFile);

        $data = $this->getReconciliationRows($data);

        $file = $this->writeToExcelFile($data, $this->getFileToWriteNameWithoutExt());

        Trace::info(TraceCode::SETTLEMENT_KOTAK_RECONCILE_FILE_GENERATED);

        return $file;
    }

    public static function getHeadings()
    {
        return NodalAccount::getHeadings();
    }

    protected function getReconciliationRows($data)
    {
        $recon = [];

        foreach ($data as $row)
        {
            $utr = random_integer(10);

            $ifsc = $row['IFSC Code'];
            $ift = false;
            $ifscFirstFour = substr($ifsc, 0, 4);
            if (($ifscFirstFour === 'KKBK') or
                ($ifscFirstFour === 'VYSA'))
            {
                $ift = true;
                $ifsc = '958';
            }

            $setl = $row['Payment_Ref_No.'];
            $setl = str_replace('_', ' ', $setl);

            $date = Carbon::createFromFormat('d/m/Y', $row['Payment_Date'], Timezone::IST);
            $date = $date->format('d M Y');

            $recon[] = array(
                'CLIENT CODE'           => $row['Client_Code'],
                'UPLOAD DATE'           => $date,
                'ACCOUNT NO'            => $row['Dr_Ac_No'],
                'MY PRODUCT CODE'       => $row['Product_Code'],
                'BANK PRODUCT'          => $row['Payment_Type'],
                'DEBIT DATE'            => $date,
                'AMOUNT'                => $row['Amount'],
                'BENEF ACCOUNT NUMBER'  => $row['Beneficiary_Acc_No'],
                'BENEF NAME'            => $row['Beneficiary_Name'],
                'UTR NO'                => 'KKBKH1' . $utr,
                'PAYABLE BRANCH'        => 'MUMBAI',
                'INSTRUMENT NO'         => '',
                'REFERENCE NUMBER'      => $setl,
                'STATUS'                => 'Presented and Paid',
                'PDTIFSCCODE'           => $ifsc,
            );
        }

        return $recon;
    }
}
