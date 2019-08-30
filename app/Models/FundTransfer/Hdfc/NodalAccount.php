<?php

namespace RZP\Models\FundTransfer\Hdfc;

use App;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base\PublicCollection;
use RZP\Models\BankAccount\Entity as BankEntity;
use RZP\Models\FundTransfer\Mode as TransferMode;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Mail\Settlement\Settlement as SettlementMail;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;

class NodalAccount extends NodalBase\FileProcessor
{
    use FileHandlerTrait;

    const SIGNED_URL_DURATION = '1440';

    protected $date;

    protected $emptyRow;

    public function __construct(string $purpose)
    {
        parent::__construct($purpose);

        $this->date     = Carbon::today(Timezone::IST)->format('d/m/Y');

        $this->emptyRow = $this->getEmptyArray();
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
            //if BA is not present for attempt
            // marking FTA as failed, if source is settlement
            if($this->markFailedIfBANotExists($entity) === true)
            {
                continue;
            }

            $record = $this->getSettlementRow($entity);

            array_push($textData, $record);
        }

        $this->trace->info(TraceCode::FTA_ROWS_FETCHED_FOR_FILE);

        $text = $this->generateText($textData, ',', true);

        $this->trace->info(TraceCode::FTA_DATA_CREATED_FOR_FILE);

        $textFileEntity = $this->createSettlementFiles($text, $h2h);

        $this->trace->info(TraceCode::FTA_FILE_CREATED_IN_S3);

        $this->sendSettlementMail($textFileEntity);

        $this->trace->info(TraceCode::FTA_FILE_EMAIL_SENT);

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
            $mode = $this->getTransferMode($amount, $bankAccount->merchant);
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

        $entity->setMode($mode);

        $this->repo->save($entity);

        $this->updateSummary($mode, $amount);

        $record = $this->emptyRow;

        $beneName = $this->normalizeString($ba->getBeneficiaryName(), 200);

        $beneAddress = $this->normalizeString($ba->getBeneficiaryAddress1(), 70);

        // `Payment detail 1` is sent with settlement id
        // `Payment detail 2` os sent with batch id
        // Payment detail 1 & 2 will be sent in the reverse file
        $record[Headings::TRANSACTION_TYPE]              = $type;
        $record[Headings::BENEFICIARY_CODE]              = $ba->getId();
        $record[Headings::BENEFICIARY_ACCOUNT_NUMBER]    = $ba->getAccountNumber();
        $record[Headings::INSTRUMENT_AMOUNT]             = number_format($amount, 2, '.', '');
        $record[Headings::BENEFICIARY_NAME]              = $beneName;

        $record[Headings::BENE_ADDRESS_1]                = $beneAddress;
        $record[Headings::BENE_ADDRESS_2]                = 'NA';
        $record[Headings::BENE_ADDRESS_3]                = 'NA';

        $record[Headings::CUSTOMER_REFERENCE_NUMBER]     = $entity->getId();
        $record[Headings::PAYMENT_DETAILS_1]             = $entity->getId();
        $record[Headings::PAYMENT_DETAILS_2]             = $source->getBatchFundTransferId();
        $record[Headings::TRANSACTION_DATE]              = $this->date;
        $record[Headings::IFC_CODE]                      = $ba->getIfscCode();
        $record[Headings::BENE_BANK_NAME]                = $ba->getBankName();

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
            'uid'   => '10007',
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
        $date = Carbon::now(Timezone::IST)->format('dm');

        $serialNo = $this->getFileSerialNo(Channel::HDFC);

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
        $settlementCount = $this->repo->batch_fund_transfer->getSettlementBatchCountOfDay($channel);

        $count = $settlementCount + 1;

        return str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    protected function sendSettlementMail(FileStore\Creator $textFileEntity)
    {
        $data = [
            'channel'   => $this->channel,
            'summary'   => $this->summary,
            'file_data' => $this->getFileData($textFileEntity)
        ];

        $settlementMail = new SettlementMail($data);

        Mail::queue($settlementMail);
    }

    /**
     * Currently csv file is generated like excel in `creator`. Which will not consider delimiter being part of string.
     * Also base on observation its understood that file should not contain special characters.
     * So this method will take care of those things.
     *
     * {@inheritdoc}
     */
    protected function normalizeString($string, int $length = 0, string $default = 'NA'): string
    {
        if (empty($string) === true)
        {
            return $default;
        }

        $normalizedString = preg_replace("/\r\n|\r|\n|,|'/", ' ', $string);

        if ($length > 0)
        {
            $normalizedString = substr($normalizedString, 0, $length);
        }

        return $normalizedString;
    }
}
