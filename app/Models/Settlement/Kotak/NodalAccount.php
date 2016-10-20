<?php

namespace RZP\Models\Settlement\Kotak;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Excel;

use Mail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;

class NodalAccount
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Settlement';

    protected static $nodalAccountNumber = '7911547334';

    public static $headings = array(
        'Client_Code',
        'Product_Code',
        'Payment_Type',
        'Payment_Ref_No.',
        'Payment_Date',
        'Instrument Date',
        'Dr_Ac_No',
        'Amount',
        'Bank_Code_Indicator',
        'Beneficiary_Code',
        'Beneficiary_Name',
        'Beneficiary_Bank',
        'IFSC Code',
        'Beneficiary_Acc_No',
        'Location',
        'Print_Location',
        'Instrument_Number',
        'Beneficiary_Address_1',
        'Beneficiary_Address_2',
        'Beneficiary_Address_3',
        'Beneficiary_Address_4',
        'Beneficiary_Email',
        'Beneficiary_Mobile',
        'Debit_Narration',
        'Credit_Narration',
        'Payment Details 1',
        'Payment Details 2',
        'Payment Details 3',
        'Payment Details 4',
        'Enrichment_1',
        'Enrichment_2',
        'Enrichment_3',
        'Enrichment_4',
        'Enrichment_5',
        'Enrichment_6',
        'Enrichment_7',
        'Enrichment_8',
        'Enrichment_9',
        'Enrichment_10',
        'Enrichment_11',
        'Enrichment_12',
        'Enrichment_13',
        'Enrichment_14',
        'Enrichment_15',
        'Enrichment_16',
        'Enrichment_17',
        'Enrichment_18',
        'Enrichment_19',
        'Enrichment_20');

    public function __construct()
    {
        // Date format is DD/MM/YYYY in human representation
        $this->date = Carbon::today('Asia/Kolkata')->format('d/m/Y');

        $this->queue = \Queue::getFacadeRoot();

        $this->mail = \Mail::getFacadeRoot();
    }

    public function generateSettlementFile($settlements, $h2h = true)
    {
        $textData = array();
        $excelData = array();

        $txt = '';

        $row = 2; // row number

        $totalAmount = $neftAmount = $iftAmount = 0;
        $neftCount   = $iftCount   = 0;

        foreach ($settlements as $settlement)
        {
            $merchant = $settlement->merchant;

            $ba = $merchant->bankAccount;

            //
            // @note: Convert the amount to string for text file otherwise
            //        sometimes float becomes recurring decimal in text file.
            //        However in excel keep it as integer since it helps in
            //        mathematical operations directly
            //

            $amount = $settlement->getAmount() / 100;
            $totalAmount += $amount;

            $type = 'NEFT';

            $ifsc = $ba->getIfscCode();

            $ifscFirstFour = substr($ifsc, 0, 4);

            if (($ifscFirstFour === 'KKBK') or
                ($ifscFirstFour === 'VYSA'))
            {
                $type = 'IFT';
                $iftAmount += $amount;
                $iftCount++;
            }
            else
            {
                $neftAmount += $amount;
                $neftCount++;
            }

            $array = array(
                'Client_Code'           => 'RAZORNODAL',
                'Product_Code'          => 'MERPAY',
                'Payment_Ref_No.'       => $settlement->getPublicId(),
                'Payment_Date'          => $this->date,
                'Dr_Ac_No'              => static::$nodalAccountNumber,
                'Amount'                => $amount,
                'Bank_Code_Indicator'   => 'M',
                'Beneficiary_Code'      => $ba->getKotakBeneficaryCode(),
                'Credit_Narration'      => 'RAZORPAY SETTLEMENT',
                'Payment Details 1'     => 'RAZORPAY PAYMENT',
                'Payment Details 2'     => $merchant->getPublicId(),
                'Payment Details 3'     => $ba->getId()
            );

            $array = $this->getAllFields($array);

            $textDataArray = $array;
            $textDataArray['Amount'] = (string) $amount;

            array_push($textData, $textDataArray);

            $row++;

            array_push($excelData, $array);
        }

        $amounts['total'] = $totalAmount;
        $amounts['neft'] = $neftAmount;
        $amounts['ift'] = $iftAmount;

        $count['total'] = $settlements->count();
        $count['neft']  = $neftCount;
        $count['ift']   = $iftCount;

        $urlExcel = $this->writeToExcelFile($excelData, $this->getFileToWriteNameWithoutExt());

        $txt = $this->generateText($textData);

        if ($h2h === true)
        {
            $urlText = $this->writeToTextFileH2H($txt);
        }

        $urlText = $this->writeToTextFile($txt);

        $this->sendKotakSettlementMail($count, $amounts);

        return [$urlText, $urlExcel];
    }

    public function generateSettlementFile2($settlements)
    {
        $textData = array();
        $excelData = array();

        $txt = '';

        $row = 2; // row number

        $totalAmount = $neftAmount = $iftAmount = 0;
        $neftCount   = $iftCount   = 0;

        foreach ($settlements as $settlement)
        {
            $merchant = $settlement->merchant;

            $ba = $merchant->bankAccount;

            //
            // @note: Convert the amount to string for text file otherwise
            //        sometimes float becomes recurring decimal in text file.
            //        However in excel keep it as integer since it helps in
            //        mathematical operations directly
            //

            $amount = $settlement->getAmount() / 100;
            $totalAmount += $amount;

            $type = 'NEFT';

            $ifsc = $ba->getIfscCode();

            $ifscFirstFour = substr($ifsc, 0, 4);

            if (($ifscFirstFour === 'KKBK') or
                ($ifscFirstFour === 'VYSA'))
            {
                $type = 'IFT';
                $iftAmount += $amount;
                $iftCount++;
            }
            else
            {
                $neftAmount += $amount;
                $neftCount++;
            }

            $array = array(
                'Client_Code'           => 'NODAL',
                'Product_Code'          => 'CMSPAY',
                'Payment_Type'          => $type,
                'Payment_Ref_No.'       => $settlement->getPublicId(),
                'Payment_Date'          => $this->date,
                'Dr_Ac_No'              => static::$nodalAccountNumber,
                'Amount'                => $amount,
                'Bank_Code_Indicator'   => 'M',
                'Beneficiary_Name'      => $ba->getBeneficiaryName(),
                'IFSC Code'             => $ifsc,
                'Beneficiary_Acc_No'    => $ba->getAccountNumber(),
                'Credit_Narration'      => 'RAZORPAY SETTLEMENT',
                'Payment Details 1'     => 'RAZORPAY PAYMENT',
                'Payment Details 2'     => $merchant->getPublicId(),
                'Payment Details 3'     => $ba->getId()
            );

            $array = $this->getAllFields($array);

            $textDataArray = $array;
            $textDataArray['Amount'] = (string) $amount;

            array_push($textData, $textDataArray);

            // Excel file has couple extra fields for calculating text data of that row.
            $array['Symbol'] = '~';
            $array['Text File'] = $this->getExcelTextFieldFormula($row);
            $array['Beneficiary_Acc_No'] = "'".$array['Beneficiary_Acc_No'];
            $row++;

            array_push($excelData, $array);
        }

        $amounts['total'] = $totalAmount;
        $amounts['neft'] = $neftAmount;
        $amounts['ift'] = $iftAmount;

        $count['total'] = $settlements->count();
        $count['neft']  = $neftCount;
        $count['ift']   = $iftCount;

        $urlExcel = $this->writeToExcelFile($excelData, $this->getFileToWriteNameWithoutExt());

        $txt = $this->generateText($textData);

        $urlText = $this->writeToTextFile($txt);

        $this->sendKotakSettlementMail($count, $amounts);

        return [$urlText, $urlExcel];
    }

    /**
     * Settlement file for kotak to be sent via h2h
     * Includes new beneficiary code
     *
     * @param array $settlements all settlements that need to be processed
     * @param array $txns all txns that need to be processed
     * @return array Array containing url of text and excel files generated.
     */
    public function generateSettlementFile3($settlements)
    {
        $textData = array();
        $excelData = array();

        $txt = '';

        $row = 2; // row number

        $totalAmount = $neftAmount = $iftAmount = 0;
        $neftCount   = $iftCount   = 0;

        foreach ($settlements as $settlement)
        {
            $merchant = $settlement->merchant;

            $ba = $merchant->bankAccount;

            //
            // @note: Convert the amount to string for text file otherwise
            //        sometimes float becomes recurring decimal in text file.
            //        However in excel keep it as integer since it helps in
            //        mathematical operations directly
            //

            $amount = $settlement->getAmount() / 100;
            $totalAmount += $amount;

            $type = 'NEFT';

            $ifsc = $ba->getIfscCode();

            $ifscFirstFour = substr($ifsc, 0, 4);

            if (($ifscFirstFour === 'KKBK') or
                ($ifscFirstFour === 'VYSA'))
            {
                $type = 'IFT';
                $iftAmount += $amount;
                $iftCount++;
            }
            else
            {
                $neftAmount += $amount;
                $neftCount++;
            }

            $array = array(
                'Client_Code'           => 'RAZORNODAL',
                'Product_Code'          => 'MERPAY',
                'Payment_Type'          => $type,
                'Payment_Ref_No.'       => $settlement->getPublicId(),
                'Payment_Date'          => $this->date,
                'Dr_Ac_No'              => static::$nodalAccountNumber,
                'Amount'                => $amount,
                'Bank_Code_Indicator'   => 'M',
                'Beneficiary_Code'      => $ba->getKotakBeneficaryCode(),
                'Beneficiary_Name'      => '',
                'Beneficiary_Bank'      => '',
                'IFSC Code'             => '',
                'Beneficiary_Acc_No'    => '',
                'Location'              => '',
                'Print_Location'        => '',
                'Instrument_Number'     => '',
                'Credit_Narration'      => 'RAZORPAY SETTLEMENT',
                'Payment Details 1'     => 'RAZORPAY PAYMENT',
                'Payment Details 2'     => $merchant->getPublicId(),
                'Payment Details 3'     => $ba->getId(),
            );

            $array = $this->getAllFields($array);

            $textDataArray = $array;
            $textDataArray['Amount'] = (string) $amount;

            array_push($textData, $textDataArray);

            // Excel file has couple extra fields for calculating text data of that row.
            $array['Symbol'] = '~';
            $array['Text File'] = $this->getExcelTextFieldFormula($row);
            $array['Beneficiary_Acc_No'] = "'".$array['Beneficiary_Acc_No'];
            $row++;

            array_push($excelData, $array);
        }

        $amounts['total'] = $totalAmount;
        $amounts['neft'] = $neftAmount;
        $amounts['ift'] = $iftAmount;

        $count['total'] = $settlements->count();
        $count['neft']  = $neftCount;
        $count['ift']   = $iftCount;

        $urlExcel = $this->writeToExcelFile($excelData, $this->getFileToWriteNameWithoutExt());

        $txt = $this->generateText($textData);

        $urlText = $this->writeToTextFile($txt);

        $this->sendKotakSettlementMail($count, $amounts);

        return [$urlText, $urlExcel];
    }

    protected function getEmptyArray()
    {
        $count = count(static::$headings);

        return array_combine(static::$headings, array_fill(0, $count, null));
    }

    protected function getAllFields($partialValues)
    {
        $dict = $this->getEmptyArray();

        foreach ($partialValues as $key => $value)
        {
            $dict[$key] = $value;
        }

        return $dict;
    }

    protected function getExcelTextFieldFormula($i)
    {
        $str = "=+A2&AX2&B2&AX2&C2&AX2&D2&AX2&E2&AX2&F2&AX2&G2&AX2&H2&AX2&I2&AX2&J2&AX2&K2&AX2&L2&AX2&M2&AX2&N2&AX2&O2&AX2&P2&AX2&Q2&AX2&R2&AX2&S2&AX2&T2&AX2&U2&AX2&V2&AX2&W2&AX2&X2&AX2&Y2&AX2&Z2&AX2&AA2";
        $str = str_replace('2', $i, $str);

        return $str;
    }



    protected function sendKotakSettlementMail($count, $amounts)
    {
        $amounts['neft'] = sprintf('%.2f', $amounts['neft']);
        $amounts['ift'] = sprintf('%.2f', $amounts['ift']);
        $amounts['total'] = sprintf('%.2f', $amounts['total']);

        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');
        $subject = "Kotak Settlement files for $today";

        $data = compact('amounts', 'count', 'subject');

        $fileName = $this->getFileToWriteNameWithoutExt();
        $path = $this->getStorageDir();
        $fullpath = $path . '/'. $fileName;

        $data['file'] = $fullpath;

        Mail::send('emails.admin.settlement', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Settlement');

            $message->subject($data['subject']);

            $message->to($emails);

            $file = $data['file'];

            $message->attach($file . '.xlsx');
            $message->attach($file . '.txt');
        });
    }
}
