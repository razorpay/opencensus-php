<?php

namespace RZP\Models\FundTransfer\Kotak;

use Carbon\Carbon;
use Excel;
use Mail;
use RZP\Exception;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base;
use RZP\Models\FundTransfer;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Constants\MailTags;
use RZP\Models\Transaction;

class NodalAccount
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Settlement';

    protected static $nodalAccountNumber = '7911547334';

    // RTGS if amount is more that 10L
    const RTGS_AMOUNT = 1000000.00;

    public function __construct()
    {
        // Date format is DD/MM/YYYY in human representation
        $this->date = Carbon::today('Asia/Kolkata')->format('d/m/Y');

        $this->hour = Carbon::now('Asia/Kolkata')->hour;

        $this->queue = \Queue::getFacadeRoot();

        $this->mail = \Mail::getFacadeRoot();
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    public function generateSettlementFile($entities, $h2h = true)
    {
        $textData = $excelData = [];

        $row = 2; // row number

        $totalAmount = $neftAmount = $iftAmount = $rtgsAmount = 0;
        $neftCount   = $iftCount   = $rtgsCount = 0;

        foreach ($entities as $entity)
        {
            $totalCount++;
            $version = Attempt\Version::V1;

            if ($entity instanceof Attempt\Entity)
            {
                $version = Attempt\Version::V2;

                $settlement = $entity->source;

                $paymentRefNo = $entity->getPublicId();
            }
            else if ($entity instanceof Settlement\Entity)
            {
                $settlement = $entity;

                $paymentRefNo = $settlement->getPublicId();
            }
            else
            {
                throw new Exception\InvalidArgumentException(
                    'Not a valid entity for Settlement-file generation: ' . get_class($entity));
            }

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

            $array = [
                Headings::CLIENT_CODE             => 'RAZORNODAL',
                Headings::PRODUCT_CODE            => 'MERPAY',
                Headings::PAYMENT_TYPE            => $type,
                Headings::PAYMENT_REF_NO          => $paymentRefNo,
                Headings::PAYMENT_DATE            => $this->date,
                Headings::DR_AC_NO                => static::$nodalAccountNumber,
                Headings::AMOUNT                  => $amount,
                Headings::BANK_CODE_INDICATOR     => 'M',
                Headings::BENEFICIARY_CODE        => $ba->getKotakBeneficaryCode(),
                Headings::CREDIT_NARRATION        => 'RAZORPAY SETTLEMENT',
                Headings::PAYMENT_DETAILS_1       => 'RAZORPAY PAYMENT',
                Headings::MERCHANT_ID             => $merchant->getPublicId(),
                Headings::BANK_ACCOUNT_ID         => $ba->getId(),
                Headings::BATCH_FUND_TRANSFER_ID  => $settlement->getBatchFundTransferId(),
                Headings::SOURCE_ID               => $settlement->getPublicId(),
                Headings::VERSION                 => $version,
            ];

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

        $count['total'] = $totalCount;
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
                Headings::CLIENT_CODE             => 'RAZORNODAL',
                Headings::PRODUCT_CODE            => 'REFUND',
                Headings::PAYMENT_TYPE            => 'IMPS',
                Headings::PAYMENT_REF_NO          => $payout->getPublicId(),
                Headings::PAYMENT_DATE            => $this->date,
                Headings::DR_AC_NO                => static::$nodalAccountNumber,
                Headings::AMOUNT                  => (string) $amount,
                Headings::BANK_CODE_INDICATOR     => 'M',
                Headings::BENEFICIARY_NAME        => $ba->getBeneficiaryName(),
                Headings::IFSC_CODE               => $ba->getIfscCode(),
                Headings::BENEFICIARY_ACC_NO       => $ba->getAccountNumber(),
                Headings::CREDIT_NARRATION        => 'RAZORPAY SETTLEMENT',
                Headings::PAYMENT_DETAILS_1       => 'RAZORPAY PAYOUTS',
                Headings::MERCHANT_ID             => $merchant->getPublicId(),
                Headings::BANK_ACCOUNT_ID         => $ba->getId(),
                Headings::BATCH_FUND_TRANSFER_ID  => $payout->getBatchFundTransferId(),
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
        $headings = self::getHeadings();

        $count = count($headings);

        return array_combine($headings, array_fill(0, $count, null));
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
