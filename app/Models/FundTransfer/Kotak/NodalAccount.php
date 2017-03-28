<?php

namespace RZP\Models\FundTransfer\Kotak;

use Carbon\Carbon;
use Excel;
use Mail;
use RZP\Exception;
use RZP\Mail\Settlement as SettlementMail;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base;
use RZP\Models\BankAccount;
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

    protected $summary;

    public function __construct()
    {
        // Date format is DD/MM/YYYY in human representation
        $this->date = Carbon::today('Asia/Kolkata')->format('d/m/Y');

        $this->hour = Carbon::now('Asia/Kolkata')->hour;

        $this->queue = \Queue::getFacadeRoot();

        $this->initSummary();
    }

    protected function initSummary()
    {
        $this->summary['total']['amount'] = 0;
        $this->summary['total']['count'] = 0;

        $this->summary['NEFT']['amount'] = 0;
        $this->summary['NEFT']['count'] = 0;

        $this->summary['IFT']['amount'] = 0;
        $this->summary['IFT']['count'] = 0;

        $this->summary['RTGS']['amount'] = 0;
        $this->summary['RTGS']['count'] = 0;
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    public function generateSettlementFile($entities, $h2h = true)
    {
        $textData = $excelData = [];

        $row = 2; // row number

        foreach ($entities as $entity)
        {
            list($version, $paymentRefNo, $source) = $this->getPaymentRefNoAndVersion($entity);

            $merchant = $source->merchant;

            $ba = $merchant->bankAccount;

            //
            // @note: Convert the amount to string for text file otherwise
            //        sometimes float becomes recurring decimal in text file.
            //        However in excel keep it as integer since it helps in
            //        mathematical operations directly
            //

            $amount = $source->getAmount() / 100;

            $type = $this->getPaymenType($ba, $amount);

            $this->updateSummary($type, $amount);

            $array = [
                Headings::CLIENT_CODE             => 'RAZORNODAL',
                Headings::PRODUCT_CODE            => 'MERPAY',
                Headings::PAYMENT_TYPE            => $type,
                Headings::PAYMENT_REF_NO          => $paymentRefNo,
                Headings::PAYMENT_DATE            => $this->date,
                Headings::DR_AC_NO                => static::$nodalAccountNumber,
                Headings::AMOUNT                  => $amount,
                Headings::BANK_CODE_INDICATOR     => 'M',
                Headings::BENEFICIARY_CODE        => $ba->getBeneficiaryCode(),
                Headings::CREDIT_NARRATION        => 'RAZORPAY SETTLEMENT',
                Headings::PAYMENT_DETAILS_1       => 'RAZORPAY PAYMENT',
                Headings::MERCHANT_ID             => $merchant->getPublicId(),
                Headings::BANK_ACCOUNT_ID         => $ba->getId(),
                Headings::BATCH_FUND_TRANSFER_ID  => $entity->getBatchFundTransferId(),
                Headings::SOURCE_ID               => $source->getPublicId(),
                Headings::VERSION                 => $version,
            ];

            $array = $this->getAllFields($array);

            $textDataArray = $array;
            $textDataArray['Amount'] = (string) $amount;

            array_push($textData, $textDataArray);

            $row++;

            array_push($excelData, $array);
        }

        $urlExcel = $this->writeToExcelFile($excelData, $this->getFileToWriteNameWithoutExt());

        $txt = $this->generateText($textData);

        if ($h2h === true)
        {
            $name = $this->getH2HFileName();

            $urlText = $this->writeToTextFileH2H($name, $txt);
        }

        $urlText = $this->writeToTextFile($txt);

        $this->sendKotakSettlementMail();

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

    protected function getPaymentRefNoAndVersion($entity)
    {
        $version = Attempt\Version::V1;

        if ($entity instanceof Attempt\Entity)
        {
            $version = Attempt\Version::V2;

            $source = $entity->source;

            $paymentRefNo = $entity->getPublicId();
        }
        else if ($entity instanceof Settlement\Entity)
        {
            $source = $entity;

            $paymentRefNo = $source->getPublicId();
        }
        else
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid entity for Settlement-file generation: ' . get_class($entity));
        }

        return [$version, $paymentRefNo, $source];
    }

    protected function getPaymenType(BankAccount\Entity $ba, $amount)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if (($ifscFirstFour === 'KKBK') or
            ($ifscFirstFour === 'VYSA'))
        {
            $type = 'IFT';
        }
        else if (($amount >= self::RTGS_AMOUNT) and
                 ($this->hour <= 14))
        {
            $type = 'RTGS';
        }
        else
        {
            $type = 'NEFT';
        }

        return $type;
    }

    protected function updateSummary($type, $amount)
    {
        $this->summary['total']['count']++;
        $this->summary['total']['amount'] += $amount;

        $this->summary[$type]['amount'] += $amount;
        $this->summary[$type]['count']++;
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

    protected function sendKotakSettlementMail()
    {
        $data = [
            'summary' => $this->summary,
        ];

        $fileName = $this->getFileToWriteNameWithoutExt();
        $path = $this->getStorageDir();
        $fullpath = $path . '/'. $fileName;

        $data['file'] = $fullpath;

        $kotakSettlementMail = new SettlementMail\KotakSettlement($data);

        Mail::send($kotakSettlementMail);
    }

    protected function sendKotakPayoutsMail($fileName, $count, $amounts)
    {
        $amounts['total'] = sprintf('%.2f', $amounts['total']);

        $data = compact('amounts', 'count');

        $data['file'] = $this->getFullFilePath($fileName);

        $kotakPayoutMail = new SettlementMail\KotakPayout($data);

        Mail::send($kotakPayoutMail);
    }

    // @codingStandardsIgnoreStart
    protected function getH2HFileName()
    {
        $name = 'RAZORNODAL\$\$'. Carbon::now('Asia/Kolkata')->format('dmYHis') . '.txt';

        return $name;
    }
    // @codingStandardsIgnoreEnd
}
