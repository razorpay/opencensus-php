<?php

namespace RZP\Models\FundTransfer\Kotak;

use Carbon\Carbon;
use Excel;
use Mail;

use RZP\Exception;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\FileStore;
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

        $this->mail = \Mail::getFacadeRoot();

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

    public function generateSettlementFile($entities, $h2h = true): array
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

        $txt = $this->generateText($textData);

        list($excelFileEntity, $textFileEntity) = $this->createSettlementFiles($excelData, $txt, $h2h);

        $this->sendSettlementMail($excelFileEntity, $textFileEntity);

        return [$textFileEntity, $excelFileEntity];
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

    protected function createSettlementFiles($excelData, $textData, bool $h2h): array
    {
        // Create excel file
        $excelFile = (new FileStore\Creator())->name($this->getFileToWriteNameWithoutExt())
                                              ->content($excelData)
                                              ->extension(FileStore\Format::XLSX)
                                              ->type(FileStore\Type::FUND_TRANSFER_EXCEL)
                                              ->save();

        // Create txt file
        if ($h2h === true)
        {
            $metadata = [
                'gid'   => '10000',
                'uid'   => '10001',
                'mtime' => Carbon::now()->timestamp,
                'mode'  => '33188',
            ];

            $textFile = (new FileStore\Creator())->name('kotak/outgoing/' . $this->getH2HFileNameWithoutExt())
                                                 ->content($textData)
                                                 ->extension(FileStore\Format::TXT)
                                                 ->type(FileStore\Type::FUND_TRANSFER_TXT)
                                                 ->metadata($metadata)
                                                 ->save();
        }
        else
        {
            $textFile = (new FileStore\Creator())->name($this->getFileToWriteNameWithoutExt())
                                                 ->content($textData)
                                                 ->extension(FileStore\Format::TXT)
                                                 ->type(FileStore\Type::FUND_TRANSFER_TXT)
                                                 ->store(FileStore\Store::LOCAL)
                                                 ->save();
        }

        return [$excelFile, $textFile];
    }

    protected function sendSettlementMail(
        FileStore\Creator $excelFileEntity,
        FileStore\Creator $textFileEntity)
    {
        $summary = $this->summary;

        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');
        $subject = "Kotak Settlement files for $today";

        $data = compact('summary', 'subject');

        $excelFileEntity = $excelFileEntity->get();
        $textFileEntity = $textFileEntity->get();

        $data['excelFile'] = $excelFileEntity['local_file_path'];
        $data['textFile'] = $textFileEntity['local_file_path'];

        Mail::send('emails.admin.settlement', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Settlement');

            $message->subject($data['subject']);

            $message->to($emails);

            $message->attach($data['excelFile']);
            $message->attach($data['textFile']);

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
        $name = $this->getH2HFileNameWithoutExt() . '.txt';

        return $name;
    }

    protected function getH2HFileNameWithoutExt()
    {
        $name = 'RAZORNODAL_'. Carbon::now('Asia/Kolkata')->format('dmYHis');

        return $name;
    }
    // @codingStandardsIgnoreEnd
}
