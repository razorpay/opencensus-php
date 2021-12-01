<?php

namespace RZP\Models\FundTransfer\Axis;

use Mail;
use Config;
use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Models\Base;
use RZP\Encryption\Type;
use RZP\Models\FileStore;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\BankAccount;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Encryption\AESEncryption;
use RZP\Models\FundTransfer\Mode;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Mail\Settlement\Settlement as SettlementMail;
use RZP\Models\FundTransfer\Base\Initiator as NodalBase;

class NodalAccount extends NodalBase\FileProcessor
{
    const SIGNED_URL_DURATION = '1440';

    const MODE_MAPPING = [
        Mode::NEFT    => 'N',
        Mode::RTGS    => 'R',
        Mode::IMPS    => 'M',
        Mode::IFT     => 'I',
    ];

    const BEAM_FILE_TYPE = 'settlement';

    protected $secret = null;

    protected $iv = null;

    protected $date = null;

    protected $data = null;

    protected $queue = null;

    protected $id = null;

    public function __construct(string $purpose)
    {
        parent::__construct($purpose);

        $this->date = Carbon::today(Timezone::IST)->format('n/j/y');

        $this->id = Base\UniqueIdEntity::generateUniqueId();

        $this->secret = Config::get('nodal.axis.secret');

        $this->iv = base64_decode(Config::get('nodal.axis.iv'));
    }

    public function generateFundTransferFile($entities, $h2h = true): FileStore\Creator
    {
        $rows = $this->getRows($entities);

        $this->trace->info(TraceCode::FTA_ROWS_FETCHED_FOR_FILE);

        list($excelFile, $rzpFile) = $this->createFile($rows);

        $this->trace->info(TraceCode::FTA_FILE_CREATED_IN_S3);

        $fileData = $this->getFileData($rzpFile);

        $this->sendAxisTransferMail($fileData);

        $this->trace->info(TraceCode::FTA_FILE_EMAIL_SENT);

        //
        // Pushing to Beam after sending the email
        // such that current settlement processing
        // doesn't get affected by Beam errors.
        //
        $this->sendFile($excelFile);

        $this->trace->info(TraceCode::FTA_FILE_SEND_VIA_BEAM);

        return $excelFile;
    }

    protected function createFile(array $values): array
    {
        $fileName = 'axis/outgoing/' . $this->id;

        $metadata = $this->getH2HMetadata();

        $creator = new FileStore\Creator;

        $rowCount = count($values);

        //
        // We need to set column format of columns D
        // from 3rd row onwards to required date format
        //
        $colFormat = [
            'D2:D' . $rowCount => 'dd/mm/yyyy',
            'F2:G' . $rowCount => 'dd/mm/yyyy',
            'G2:G' . $rowCount => 'dd/mm/yyyy',
        ];

        $rzpFile = $creator->extension(FileStore\Format::XLSX)
                           ->content($values)
                           ->name($fileName)
                           ->store(FileStore\Store::S3)
                           ->type(FileStore\Type::FUND_TRANSFER_DEFAULT)
                           ->metadata($metadata)
                           ->headers(false)
                           ->columnFormat($colFormat)
                           ->save();

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::XLSX)
                        ->content($values)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FUND_TRANSFER_H2H)
                        ->metadata($metadata)
                        ->headers(false)
                        ->columnFormat($colFormat)
                        ->encrypt(Type::AES_ENCRYPTION, [
                            AESEncryption::MODE   => AES::MODE_CBC,
                            AESEncryption::IV     => $this->iv,
                            AESEncryption::SECRET => $this->secret,])
                        ->encode()
                        ->save();

        return [$file, $rzpFile];
    }

    protected function getFileData($file)
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

    protected function getH2HMetadata()
    {
        // To be changed perhaps
        return [
            'gid'   => '10000',
            'uid'   => '10003',
            'mtime' => Carbon::now()->timestamp,
            'mode'  => '33188'
        ];
    }

    protected function getRows($entities): array
    {
        $rows = [];

        $rows[] = Headings::getRequestFileHeadings();

        foreach ($entities as $entity)
        {
            //if BA is not present for attempt
            // marking FTA as failed, if source is settlement
            if($this->markFailedIfBANotExists($entity) === true)
            {
                continue;
            }

            $source = $entity->source;

            $amount = ($source->getAmount() / 100);

            $ba = $entity->bankAccount;

            $beneCode = $entity->bankAccount->getId();

            // currently kotak is registered with below benecode, so we override
            // the benecode until the new one gets registered.
            if ($beneCode === '9KnioczXfED3wz')
            {
                $beneCode = 'RZRNAXISCARD';
            }

            $rows[] = $this->getTrasactionRow($amount, $beneCode, $ba, $entity);
        }

        return $rows;
    }

    protected function getTrasactionRow($amount, $accountId, BankAccount\Entity $ba, Base\Entity $entity): array
    {
        $mode = $this->getPaymentType($amount, $ba);

        $entity->setMode($mode);

        $this->repo->save($entity);

        $this->updateSummary($mode, $amount);

        $mode = self::MODE_MAPPING[$mode];

        $formattedAmount = (float) sprintf('%0.2f', $amount);

        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(strtotime($this->date));

        $transactionValues = [
            $mode,
            '917020041206002',
            $accountId,
            $excelDate,
            $formattedAmount,
            $excelDate,
            $excelDate,
            $entity->getId(),
        ];

        return $transactionValues;
    }

    protected function getPaymentType($amount, BankAccount\Entity $ba)
    {
        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if ($ifscFirstFour === 'UTIB')
        {
            return Mode::IFT;
        }

        $mode = $this->getTransferMode($amount, $ba->merchant);

        return $mode;
    }

    protected function sendAxisTransferMail(array $fileData)
    {
        $data = [
            'channel'    => $this->channel,
            'summary'    => $this->summary,
            'file_data'  => $fileData,
            'body'       => json_encode($this->data, JSON_PRETTY_PRINT),
            'recipients' => 'axis.nodal.transfers@razorpay.com'
        ];

        $settlementMail = new SettlementMail($data);

        Mail::queue($settlementMail);
    }

    /**
     * @param FileStore\Creator $file
     * Send file to bank through Beam
     */
    protected function sendFile(FileStore\Creator $file)
    {
        $fileInfo = [$file->getFullFileName()];

        $bucketConfig = $this->getBucketConfig(FileStore\Type::FUND_TRANSFER_H2H, $this->env);

        $data =  [
            Service::BEAM_PUSH_FILES   => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => BeamConstants::AXIS_SETTLEMENT_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [15, 30, 45, 60, 90, 120, 150, 180];

        $batchFTAId = null;

        //Batchfta can be null in case of test mode
        if(empty($this->batchFundTransfer) === false)
        {
            $batchFTAId = $this->batchFundTransfer->getId();
        }

        $mailInfo = [
            'fileInfo'              => $fileInfo,
            'channel'               => $this->channel,
            'filetype'              => self::BEAM_FILE_TYPE,
            'subject'               => 'Axis Settlement File Send Failure',
            'recipient'             => Constants::MAIL_ADDRESSES[Constants::SETTLEMENT_ALERTS],
            'batchFundTransferId'   => $batchFTAId,
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }
}
