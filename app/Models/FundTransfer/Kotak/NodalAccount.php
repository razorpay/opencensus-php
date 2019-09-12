<?php

namespace RZP\Models\FundTransfer\Kotak;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Excel;
use Mail;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Models\Settlement;
use RZP\Models\BankAccount;
use RZP\Models\FundTransfer;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Mail\Settlement\Settlement as SettlementMail;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;

class NodalAccount extends NodalBase\FileProcessor
{
    use FileHandlerTrait;

    protected static $fileToWriteName = 'Kotak_Settlement';

    protected static $nodalAccountNumber = '7911547334';

    // IMPS if amount is less that 1L
    const IMPS_AMOUNT = 100000.00;

    protected $summary;

    protected $app;

    public function __construct(string $purpose)
    {
        parent::__construct($purpose);

        // Date format is DD/MM/YYYY in human representation
        $this->date = Carbon::today(Timezone::IST)->format('d/m/Y');

        $this->hour = Carbon::now(Timezone::IST)->hour;

        $this->app = App::getFacadeRoot();
    }

    public static function getHeadings()
    {
        return Headings::getRequestFileHeadings();
    }

    public function generateFundTransferFile(Base\PublicCollection $attempts, $h2h = true): FileStore\Creator
    {
        $textData = [];

        foreach ($attempts as $attempt)
        {
            //if BA is not present for attempt
            // marking FTA as failed, if source is settlement
            if($this->markFailedIfBANotExists($attempt) === true)
            {
                continue;
            }

            if ($attempt->isRefund() === true)
            {
                list($amount, $row) = $this->getRefundRow($attempt);
            }
            else
            {
                list($amount, $row) = $this->getSettlementRow($attempt);
            }

            $textDataArray = $row;

            $textDataArray['Amount'] = (string) $amount;

            array_push($textData, $textDataArray);
        }

        $txt = $this->generateText($textData);

        $textFileEntity = $this->createSettlementFiles($txt, $h2h);

        $this->sendSettlementMail($textFileEntity);

        return $textFileEntity;
    }

    /**
     * Fetched the Nodal Account balance
     *
     * @return array
     * [
     *  {account_number} => {account_balance}
     * ]
     */
    public function getAccountBalance(): array
    {
        return (new Balance())->getAccountBalance();
    }

    protected function getSettlementRow(Attempt\Entity $entity) : array
    {
        list($version, $paymentRefNo, $source) = $this->getPaymentRefNoAndVersion($entity);

        $merchant = $entity->merchant;

        $ba = $entity->bankAccount;

        //
        // @note: Convert the amount to string for text file otherwise
        //        sometimes float becomes recurring decimal in text file.
        //        However in excel keep it as integer since it helps in
        //        mathematical operations directly
        //

        $amount = $source->getAmount() / 100;

        $type = $this->getPaymentType($ba, $amount, $entity);

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

        return [$amount, $array];
    }

    protected function getRefundRow(Attempt\Entity $attempt): array
    {
        list($version, $paymentRefNo, $source) = $this->getPaymentRefNoAndVersion($attempt);

        $merchant = $attempt->merchant;

        $ba = $attempt->bankAccount;

        $amount = $source->getAmount() / 100;

        $type = $this->getPaymentType($ba, $amount, $attempt);

        $this->updateSummary($type, $amount);

        $array = [
            Headings::CLIENT_CODE             => 'RAZORNODAL',
            Headings::PRODUCT_CODE            => 'REFUND',
            Headings::PAYMENT_TYPE            => $type,
            Headings::PAYMENT_REF_NO          => $paymentRefNo,
            Headings::PAYMENT_DATE            => $this->date,
            Headings::INSTRUMENT_DATE         => $this->date,
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

        return [$amount, $array];
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
        // Settlements are not done via IMPS
        if (($amount <= self::IMPS_AMOUNT) and
            ($attempt->getSourceType() !== Type::SETTLEMENT))
        {
            $type = FundTransfer\Mode::IMPS;
        }
        else
        {
            // Check RTGS time and minimum
            $type = $this->getTransferMode($amount, $ba->merchant);
        }

        // Mode will be present only for attempts of type Refund
        if ($attempt->getMode() != null)
        {
            $type = $attempt->getMode();
        }

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        // For Kotak beneficiaries, none of the
        // above logic matters, we only do IFT
        if (($ifscFirstFour === 'KKBK') or
            ($ifscFirstFour === 'VYSA'))
        {
            $type = FundTransfer\Mode::IFT;
        }

        return $type;
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

    protected function createSettlementFiles($textData, bool $h2h)
    {
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

        return $textFile;
    }

    protected function sendSettlementMail(FileStore\Creator $textFileEntity)
    {
        $textFileEntity = $textFileEntity->get();

        $data = [
            'summary'  => $this->summary,
            'channel'  => $this->channel,
            'textFile' => $textFileEntity['local_file_path']
        ];

        $settlementMail = new SettlementMail($data);

        Mail::queue($settlementMail);
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
