<?php

namespace RZP\Models\Settlement\Kotak;

use Carbon\Carbon;
use Excel;
use Mail;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\MailTags;
use RZP\Models\Transaction;

class NodalAccount
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Settlement';

    protected static $nodalAccountNumber = '7911547334';

    // RTGS if amount is more that 10L
    const RTGS_AMOUNT = 1000000.00;

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

        $this->hour = Carbon::now('Asia/Kolkata')->hour;

        $this->queue = \Queue::getFacadeRoot();

        $this->mail = \Mail::getFacadeRoot();
    }

    public function generateSettlementFile($settlements, $h2h = true)
    {
        $textData = array();
        $excelData = array();

        $row = 2; // row number

        $totalAmount = $neftAmount = $iftAmount = $rtgsAmount = 0;
        $neftCount   = $iftCount   = $rtgsCount = 0;

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
            else if (($amount >= self::RTGS_AMOUNT) and
                     ($this->hour <= 14))
            {
                $type = 'RTGS';
                $rtgsAmount += $amount;
                $rtgsCount++;
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
                'Beneficiary_Code'      => $ba->getBeneficaryCode(),
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
        $amounts['rtgs'] = $rtgsAmount;

        $count['total'] = $settlements->count();
        $count['neft']  = $neftCount;
        $count['ift']   = $iftCount;
        $count['rtgs']   = $rtgsCount;

        $urlExcel = $this->writeToExcelFile($excelData, $this->getFileToWriteNameWithoutExt());

        $txt = $this->generateText($textData);

        if ($h2h === true)
        {
            $name = $this->getH2HFileName();

            $urlText = $this->writeToTextFileH2H($name, $txt);
        }

        $urlText = $this->writeToTextFile($txt);

        $this->sendKotakSettlementMail($count, $amounts);

        return [$urlText, $urlExcel];
    }

    public function getPayoutsFile(Base\PublicCollection $payouts)
    {
        $textData = [];

        $totalAmount = 0;

        foreach ($payouts as $payout)
        {
            $merchant = $payout->merchant;

            $ba = $payout->destination;

            $amount = $payout->getAmount() / 100;

            $totalAmount += $amount;

            $array = [
                'Client_Code'           => 'RAZORNODAL',
                'Product_Code'          => 'REFUND',
                'Payment_Type'          => 'IMPS',
                'Payment_Ref_No.'       => $payout->getPublicId(),
                'Payment_Date'          => $this->date,
                'Dr_Ac_No'              => static::$nodalAccountNumber,
                'Amount'                => (string) $amount,
                'Bank_Code_Indicator'   => 'M',
                'Beneficiary_Name'      => $ba->getBeneficiaryName(),
                'IFSC Code'             => $ba->getIfscCode(),
                'Beneficiary_Acc_No'    => $ba->getAccountNumber(),
                'Credit_Narration'      => 'RAZORPAY SETTLEMENT',
                'Payment Details 1'     => 'RAZORPAY PAYOUTS',
                'Payment Details 2'     => $merchant->getPublicId(),
                'Payment Details 3'     => $ba->getId()
            ];

            $array = $this->getAllFields($array);

            $textDataArray = $array;

            $textData[] = $textDataArray;
        }

        $amounts['total'] = $totalAmount;

        $count['total'] = $payouts->count();

        $txt = $this->generateText($textData);

        $name = $this->getH2HFileName();

        $urlText = $this->writeToTextFileH2H($name, $txt);

        self::$fileToWriteName = 'Kotak_Payout';

        $name = $this->getFileToWriteName();

        $fullpath = $this->saveLocally($name, $txt);

        $this->sendKotakPayoutsMail($name, $count, $amounts);

        return $urlText;
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

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_SETTLEMENT_FILES);
        });
    }

    protected function sendKotakPayoutsMail($fileName, $count, $amounts)
    {
        $amounts['total'] = sprintf('%.2f', $amounts['total']);

        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Kotak IMPS payouts files for $today";

        $data = compact('amounts', 'count', 'subject');

        $data['file'] = $this->getFullFilePath($fileName);

        Mail::send('emails.admin.payout', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Payouts');

            $message->subject($data['subject']);

            $message->to($emails);

            $message->attach($data['file']);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_PAYOUT_SUMMARY);
        });
    }

    // @codingStandardsIgnoreStart
    protected function getH2HFileName()
    {
        $name = 'RAZORNODAL\$\$'. Carbon::now('Asia/Kolkata')->format('dmYHis') . '.txt';

        return $name;
    }
    // @codingStandardsIgnoreEnd
}
