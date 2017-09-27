<?php

namespace RZP\Models\FundTransfer\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Excel;
use Mail;

use App;
use RZP\Exception;
use RZP\Mail\Settlement as SettlementMail;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\FileStore;
use RZP\Models\FundTransfer;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base as NodalBase;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Constants\MailTags;
use RZP\Constants\Entity;
use RZP\Constants\Mode;
use RZP\Models\Transaction;

class NodalAccount extends NodalBase\NodalAccount
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Settlement';

    protected static $nodalAccountNumber = '7911547334';

    // IMPS if amount is less that 1L
    const IMPS_AMOUNT = 100000.00;

    protected $summary;

    protected $app;

    public function __construct()
    {
        parent::__construct();

        // Date format is DD/MM/YYYY in human representation
        $this->date = Carbon::today(Timezone::IST)->format('d/m/Y');

        $this->hour = Carbon::now(Timezone::IST)->hour;

        $this->queue = \Queue::getFacadeRoot();

        $this->app = App::getFacadeRoot();

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

            $merchant = $entity->merchant;

            $ba = $merchant->bankAccount;

            //
            // @note: Convert the amount to string for text file otherwise
            //        sometimes float becomes recurring decimal in text file.
            //        However in excel keep it as integer since it helps in
            //        mathematical operations directly
            //

            $amount = $source->getAmount() / 100;

            $type = $this->getPaymentType($ba, $amount, $entity->getSourceType());

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
                Headings::PAYMENT_DETAILS_1       => $source->getPublicId(),
                Headings::PAYMENT_DETAILS_2       => $merchant->getPublicId(),
                Headings::PAYMENT_DETAILS_3       => $version,
                Headings::PAYMENT_DETAILS_4       => $entity->getBatchFundTransferId(),
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

    public function generatePayoutsFile(Base\PublicCollection $payoutAttempts): string
    {
        $textData = [];

        $totalAmount = 0;

        foreach ($payoutAttempts as $attempt)
        {
            list($version, $paymentRefNo, $source) = $this->getPaymentRefNoAndVersion($attempt);

            $merchant = $attempt->merchant;

            $ba = $attempt->bankAccount;

            $amount = $source->getAmount() / 100;

            $totalAmount += $amount;

            $type = $this->getPaymentType($ba, $amount, $attempt->getSourceType());

            $array = [
                Headings::CLIENT_CODE             => 'RAZORNODAL',
                Headings::PRODUCT_CODE            => 'REFUND',
                Headings::PAYMENT_TYPE            => $type,
                Headings::PAYMENT_REF_NO          => $paymentRefNo,
                Headings::PAYMENT_DATE            => $this->date,
                Headings::DR_AC_NO                => static::$nodalAccountNumber,
                Headings::AMOUNT                  => (string) $amount,
                Headings::BANK_CODE_INDICATOR     => 'M',
                Headings::BENEFICIARY_NAME        => $ba->getBeneficiaryName(),
                Headings::IFSC_CODE               => $ba->getIfscCode(),
                Headings::BENEFICIARY_ACC_NO      => $ba->getAccountNumber(),
                Headings::CREDIT_NARRATION        => $attempt->getNarration() ?? 'RAZORPAY SETTLEMENT',
                Headings::PAYMENT_DETAILS_1       => $source->getPublicId(),
                Headings::PAYMENT_DETAILS_2       => $merchant->getPublicId(),
                Headings::PAYMENT_DETAILS_3       => $version,
                Headings::PAYMENT_DETAILS_4       => $attempt->getBatchFundTransferId(),
            ];

            $array = $this->getAllFields($array);

            $textDataArray = $array;

            $textData[] = $textDataArray;
        }

        $amounts['total'] = $totalAmount;

        $count['total'] = $payoutAttempts->count();

        $txt = $this->generateText($textData);

        $name = $this->getH2HFileName();

        $urlText = $this->writeToTextFileH2H($name, $txt);

        self::$fileToWriteName = 'Kotak_Payout';

        $name = $this->getFileToWriteName();

        $fullpath = $this->createTxtFile($name, $txt);

        $this->sendKotakPayoutsMail($name, $count, $amounts);

        return $urlText;
    }

    protected function getPaymentRefNoAndVersion($entity)
    {
        $version = Attempt\Version::V1;

        if ($entity instanceof Attempt\Entity)
        {
            $version = Attempt\Version::V3;

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

    protected function getPaymentType(BankAccount\Entity $ba, $amount, string $sourceType)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if (($ifscFirstFour === 'KKBK') or
            ($ifscFirstFour === 'VYSA'))
        {
            $type = 'IFT';
        }
        else if (($amount <= self::IMPS_AMOUNT) and
                 ($sourceType !== Entity::SETTLEMENT))
        {
            $type = 'IMPS';
        }
        else
        {
            $type = $this->getTransferMode($amount);
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
                                              ->type(FileStore\Type::FUND_TRANSFER_DEFAULT)
                                              ->save();

        // Create txt file in h2h only for live mode and h2h is true
        if (($this->getMode() === Mode::LIVE) and
            ($h2h === true))
        {
            $metadata = [
                'gid'   => '10000',
                'uid'   => '10001',
                'mtime' => Carbon::now()->getTimestamp(),
                'mode'  => '33188',
            ];

            $textFile = (new FileStore\Creator())->name('kotak/outgoing/' . $this->getH2HFileNameWithoutExt())
                                                 ->content($textData)
                                                 ->extension(FileStore\Format::TXT)
                                                 ->type(FileStore\Type::FUND_TRANSFER_H2H)
                                                 ->metadata($metadata)
                                                 ->save();
        }

        $textFile = (new FileStore\Creator())->name($this->getFileToWriteNameWithoutExt())
                                             ->content($textData)
                                             ->extension(FileStore\Format::TXT)
                                             ->type(FileStore\Type::FUND_TRANSFER_DEFAULT)
                                             ->save();

        return [$excelFile, $textFile];
    }

    protected function sendSettlementMail(
        FileStore\Creator $excelFileEntity,
        FileStore\Creator $textFileEntity)
    {
        // Don't send mail if mode is test and env is not dev or testing
        if (($this->getMode() === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return;
        }

        $summary = $this->summary;

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');
        $subject = "Kotak Settlement files for $today";

        $data = compact('summary', 'subject');

        $excelFileEntity = $excelFileEntity->get();
        $textFileEntity = $textFileEntity->get();

        $data['excelFile'] = $excelFileEntity['local_file_path'];
        $data['textFile'] = $textFileEntity['local_file_path'];

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

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y-H-i-s');

        $mode = $this->getMode();

        return static::$fileToWriteName.'_'.$mode.'_'.$time;
    }

    // @codingStandardsIgnoreStart
    protected function getH2HFileName()
    {
        $name = $this->getH2HFileNameWithoutExt() . '.txt';

        return $name;
    }

    protected function getH2HFileNameWithoutExt()
    {
        $name = 'RAZORNODAL_'. Carbon::now(Timezone::IST)->format('dmYHis');

        return $name;
    }
    // @codingStandardsIgnoreEnd
}
