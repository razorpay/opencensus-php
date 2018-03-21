<?php

namespace RZP\Models\FundTransfer\Hdfc;

use App;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base\PublicCollection;
use RZP\Mail\Settlement as SettlementMail;
use RZP\Models\BankAccount\Entity as BankEntity;
use RZP\Models\FundTransfer\Mode as TransferMode;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;

class NodalAccount extends NodalBase\FileProcessor
{
    use FileHandlerTrait;

    const SIGNED_URL_DURATION = '1440';

    protected $date;

    protected $emptyRow;

    public function __construct()
    {
        parent::__construct();

        $this->initSummary();

        $this->date     = Carbon::today(Timezone::IST)->format('d/m/Y');

        $this->emptyRow = $this->getEmptyArray();
    }

    /**
     * Initialize the Settlement summary variables
     */
    protected function initSummary()
    {
        $this->summary = [
            'total' => [
                'amount'    => 0,
                'count'     => 0
            ],
            'NEFT'  => [
                'amount'    => 0,
                'count'     => 0
            ],
            'RTGS'  => [
                'amount'    => 0,
                'count'     => 0
            ],
            'IFT'   => [
                'amount'    => 0,
                'count'     => 0
            ],
        ];
    }

    /**
     * Generates Settlement file for HDFC
     *
     * @param PublicCollection $entities
     * @param bool $h2h
     *
     * @return FileStore\Creator
     */
    public function generateFundTransferFile(PublicCollection $entities, $h2h = true): FileStore\Creator
    {
        $textData = [];

        foreach ($entities as $entity)
        {
            $record = $this->getSettlementRow($entity);

            array_push($textData, $record);
        }

        $text = $this->generateText($textData, ',', true);

        $textFileEntity = $this->createSettlementFiles($text, $h2h);

        $this->sendSettlementMail($textFileEntity);

        return $textFileEntity;
    }

    protected function getTransactionType(BankEntity $bankAccount, float $amount): array
    {
        $ifsc = $bankAccount->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if ($ifscFirstFour === Constants::IFSC_IDENTIFIER)
        {
            $mode = TransferMode::IFT;
        }
        else
        {
            $mode = $this->getTransferMode($amount);
        }

        return [
            Constants::MODE_MAPPING[$mode],
            $mode
        ];
    }

    /**
     * Prepares settlement data to generate file.
     * Fields not participating in this method will be set to null
     *
     * @param Attempt\Entity $entity
     *
     * @return array
     */
    protected function getSettlementRow(Attempt\Entity $entity): array
    {
        $ba                 = $entity->bankAccount;

        $source             = $entity->source;

        $amount             = (string) ($source->getAmount() / 100);

        list($type, $mode)  = $this->getTransactionType($ba, $amount);

        $this->updateSummary($mode, $amount);

        $record = $this->emptyRow;

        // `Payment detail 1` is sent with settlement id
        // `Payment detail 2` os sent with batch id
        // Payment detail 1 & 2 will be sent in the reverse file
        $record[Headings::IFC_CODE]                      = $ba->getIfscCode();
        $record[Headings::TRANSACTION_TYPE]              = $type;
        $record[Headings::INSTRUMENT_AMOUNT]             = number_format($amount, 2, '.', '');
        $record[Headings::BENEFICIARY_NAME]              = substr($ba->getBeneficiaryName(), 0, 200);
        $record[Headings::BENEFICIARY_ACCOUNT_NUMBER]    = $ba->getAccountNumber();
        $record[Headings::CUSTOMER_REFERENCE_NUMBER]     = $entity->getId();
        $record[Headings::TRANSACTION_DATE]              = $this->date;
        $record[Headings::PAYMENT_DETAILS_1]             = $entity->getId();
        $record[Headings::PAYMENT_DETAILS_2]             = $source->getBatchFundTransferId();

        return $record;
    }

    /**
     * Gives an array with all fields required for settlement file generation.
     * All the fields are set to null
     * Generated array will be in the order acceptable from the bank.
     *
     * @return array
     */
    protected function getEmptyArray(): array
    {
        $headings = Headings::getRequestFileHeadings();

        $count = count($headings);

        return array_combine($headings, array_fill(0, $count, null));
    }

    protected function createSettlementFiles(string $textData, bool $h2h): FileStore\Creator
    {
        $fileDestination = $this->getSettlementFileDestination();

        $metadata = $this->getMetaData();

        // As HDFC expects the ascii file there wont be any extensions.
        // So here extension will be set to null by default
        $textFile = (new FileStore\Creator())->name($fileDestination)
                                             ->content($textData)
                                             ->headers(false)
                                             ->store(FileStore\Store::S3)
                                             ->type(FileStore\Type::FUND_TRANSFER_H2H)
                                             ->metadata($metadata)
                                             ->save();

        return $textFile;
    }

    protected function getFileData(FileStore\Creator $file): array
    {
        $fileInstance = $file->get();

        $signedFileUrl = $file->getSignedUrl(self::SIGNED_URL_DURATION)['url'];

        $fileData = [
            'file_path'  => $fileInstance['local_file_path'],
            'file_name'  => basename($fileInstance['local_file_path']),
            'signed_url' => $signedFileUrl,
        ];

        return $fileData;
    }

    protected function getMetaData(): array
    {
        return [
            'gid'   => '10000',
            'uid'   => '10001',
            'mtime' => Carbon::now(Timezone::IST)->getTimestamp(),
            'mode'  => '33188',
        ];
    }

    /**
     * Gets the file name according to requirement
     * Filename Format : <Domain>_<Client Code (4 Digit Client Code)> + <DDMM>.<SRL>
     *
     * @return string
     */
    protected function getSettlementFileDestination(): string
    {
        $date       = Carbon::now(Timezone::IST)->format('dm');

        $serialNo   = $this->getFileSerialNo(Channel::HDFC);

        return 'hdfc/outgoing/' . Constants::DOMAIN . '_' . Constants::CLIENT_CODE
               . '_' . Constants::CLIENT_CODE . $date . '.' . $serialNo;
    }

    /**
     * Gets the count of settlement file generated for HDFC
     * In a day up to 999 files can send, hence first file of the day will start from 001 to 999.
     *
     * @param string $channel
     * @return string
     */
    protected function getFileSerialNo(string $channel): string
    {
        $settlementCount    = $this->repo->batch_fund_transfer->getSettlementBatchCountOfDay($channel);

        $count              = $settlementCount;

        return str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    protected function updateSummary($type, $amount)
    {
        $this->summary['total']['count']++;
        $this->summary['total']['amount'] += $amount;

        $this->summary[$type]['amount'] += $amount;
        $this->summary[$type]['count']++;
    }

    protected function sendSettlementMail(FileStore\Creator $textFileEntity)
    {
        // Don't send mail if mode is test and env is not dev or testing
        if (($this->getMode() === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return;
        }

        $data               = $this->prepareDataForMail($textFileEntity);

        $settlementMail     = new SettlementMail\HdfcSettlement($data);

        Mail::send($settlementMail);
    }

    protected function prepareDataForMail(FileStore\Creator $textFileEntity): array
    {
        $channel             = Constants::NAME;

        $summary             = $this->summary;

        $data                = compact('summary', 'channel');

        $data['file_data']   = $this->getFileData($textFileEntity);

        return $data;
    }
}
