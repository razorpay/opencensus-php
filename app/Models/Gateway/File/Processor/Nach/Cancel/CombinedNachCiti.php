<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Cancel;

use Storage;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Gateway\File\Processor\FileHandler;

class CombinedNachCiti extends Base
{
    use FileHandler;

    const METHODS   = [Payment\Method::NACH, Payment\Method::EMANDATE];
    const GATEWAYS  = [Payment\Gateway::NACH_CITI, Payment\Gateway::ENACH_NPCI_NETBANKING];
    const ACQUIRER  = Payment\Gateway::ACQUIRER_CITI;
    const EXTENSION = FileStore\Format::ZIP;
    const FILE_TYPE = FileStore\Type::CITI_NACH_COMBINED_CANCEL;
    const S3_PATH   = 'citi/nach/input_file/';
    const FILE_NAME = 'MMS-CANCEL-CITI-CITI137268-{$date}-{$count}-INP';
    const ZIP_FILE  = 'RAZORP_CANCELLATION_{$utilityCode}_{$date}';
    const STEP      = 'cancel';

    protected $fileStore = [];
    protected $mailData  = [];

    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }
        try
        {
            $date = Carbon::now(Timezone::IST)->format('dmY');

            foreach ($data as $key => $xmls)
            {
                foreach ($xmls as $count => $xml)
                {
                    $count = str_pad(++$count, 6, '0', STR_PAD_LEFT);

                    $fileName = strtr(self::FILE_NAME, ['{$date}' => $date, '{$count}' => $count]);

                    $dirName = strtr(self::ZIP_FILE, ['{$utilityCode}' => $key, '{$date}' => $date]);

                    $xmlFilePath = $dirName . DIRECTORY_SEPARATOR . $fileName . '.xml';

                    Storage::put($xmlFilePath, $xml);
                }

                $this->generateZipFile($dirName);
            }

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE, [
                'id' => $this->gatewayFile->getId(),
            ], $e);
        }
    }

    public function sendFile($data)
    {
        $fileInfo = [];

        $files = $this->gatewayFile
            ->files()
            ->whereIn(FileStore\Entity::ID, $this->fileStore)
            ->get();

        foreach ($files as $file)
        {
            $fullFileName = $file->getName() . '.' . $file->getExtension();

            $fileInfo[] = $fullFileName;
        }

        $bucketConfig = $this->getBucketConfig(self::FILE_TYPE);

        $data = [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::CITIBANK_NACH_FILE_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'nach',
            'filetype'  => FileStore\Type::CITI_NACH_COMBINED_CANCEL,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $this->sendBeamRequest($data, [], $mailInfo, true);
    }
}
