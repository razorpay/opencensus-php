<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Cancel;

use RZP\Mail\Gateway\Nach\Base as NachMail;
use Mail;
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

class CombinedNachIcici extends Base
{
    use FileHandler;

    const METHODS   = [Payment\Method::NACH, Payment\Method::EMANDATE];
    const GATEWAYS  = [Payment\Gateway::NACH_ICICI, Payment\Gateway::ENACH_NPCI_NETBANKING];
    const ACQUIRER  = Payment\Gateway::ACQUIRER_ICIC;
    const EXTENSION = FileStore\Format::ZIP;
    const FILE_TYPE = FileStore\Type::ICICI_NACH_COMBINED_CANCEL;
    const GATEWAY   = Payment\Gateway::NACH_ICICI;
    const S3_PATH   = 'icicibank/nach/input_file/';
    const FILE_NAME = 'MMS-CANCEL-ICIC-ICIC865719-{$date}-API0{$count}-INP';
    const ZIP_FILE  = 'MMS-CANCEL-ICIC-ICIC865719-{$date}-API000001-INP';
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
                $recordCount = 0;

                foreach ($xmls as $count => $xml)
                {
                    $count = str_pad(++$count, 5, '0', STR_PAD_LEFT);

                    $fileName = strtr(self::FILE_NAME, ['{$utilityCode}' => $key, '{$date}' => $date, '{$count}' => $count]);

                    $dirName = strtr(self::ZIP_FILE, ['{$utilityCode}' => $key, '{$date}' => $date]);

                    $xmlFilePath = $dirName . DIRECTORY_SEPARATOR . $fileName . '.xml';

                    Storage::put($xmlFilePath, $xml);

                    $recordCount++;
                }

                $this->generateZipFile($dirName);

                $fileNameForMail = basename($dirName) . '.' . self::EXTENSION;

                $this->mailData[$fileNameForMail]['count'] = $recordCount;

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
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::ICICI_ENACH_NB_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'nach',
            'filetype'  => FileStore\Type::ICICI_NACH_COMBINED_CANCEL,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $this->sendBeamRequest($data, [], $mailInfo, true);

        $type = self::GATEWAY . '_' . self::STEP;

        $mailable = new NachMail(['mailData' => $this->mailData], $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
    }
}
