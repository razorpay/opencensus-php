<?php

namespace RZP\Models\FundTransfer\Yesbank;

use App;
use Excel;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Mail\Settlement as SettlementMail;
use RZP\Models\BankAccount;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\FundTransfer;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base as NodalBase;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\Settlement;

class NodalAccount extends NodalBase\NodalAccount
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'YESB_Settlement';

    protected static $nodalAccountNumber = '002261100000070';

    protected static $bankCodes = [IFSC::YESB];

    protected $summary;

    protected $app;

    protected $hour;

    protected $queue;

    protected $date;

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
        $h2h = false;

        $textData = $excelData = [];

        $row = 2; // row number

        foreach ($entities as $entity)
        {
            list($version, $paymentRefNo, $source) = $this->getPaymentRefNoAndVersion($entity);

            $merchant = $entity->merchant;

            $ba = $merchant->bankAccount;

            $amount = $source->getAmount() / 100;

            $type = $this->getPaymentType($ba, $amount, $entity);

            $this->updateSummary($type, $amount);

            $array = [
                Headings::BENEFICIARY_NAME        => $ba->getBeneficiaryName(),
                Headings::IFSC_CODE               => $ba->getIfscCode(),
                Headings::BENEFICIARY_ACC_NO      => $ba->getAccountNumber(),
                Headings::AMOUNT                  => $amount,
                Headings::BENEFICIARY_BANK        => $ba->getBankName()
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

    protected function getPaymentType(BankAccount\Entity $ba, $amount, Attempt\Entity $attempt)
    {
        // Check RTGS time and minimum
        $type = $this->getTransferMode($amount);

        // Mode will be present only for attempts of type Refund
        if ($attempt->getMode() != null)
        {
            $type = $attempt->getMode();
        }

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        // For YESB beneficiaries, none of the
        // above logic matters, we only do IFT
        if ($ifscFirstFour === 'YESB')
        {
            $type = FundTransfer\Mode::IFT;
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

            $textFile = (new FileStore\Creator())->name('yesbank/outgoing/' . $this->getH2HFileNameWithoutExt())
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
        $channel = 'Yesbank';

        $today = Carbon::now(Timezone::IST)->format('d-m-Y');
        $subject = "$channel Settlement files for $today";

        $data = compact('summary', 'subject', 'channel');

        $excelFileEntity = $excelFileEntity->get();
        $textFileEntity = $textFileEntity->get();

        $data['excelFile'] = $excelFileEntity['local_file_path'];
        $data['textFile'] = $textFileEntity['local_file_path'];

        $yesbankSettlementMail = new SettlementMail\KotakSettlement($data);

        Mail::send($yesbankSettlementMail);
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