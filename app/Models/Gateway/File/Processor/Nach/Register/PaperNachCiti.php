<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Register;

use Mail;
use Storage;
use ZipArchive;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\FileStore\Utility;
use RZP\Gateway\Enach\Citi\Fields;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\RuntimeException;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Models\SubscriptionRegistration;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\Gateway\Nach\Base as NachMail;
use RZP\Services\Beam\Service as BeamService;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Gateway\File\Processor\Nach\Base;
use RZP\Gateway\Enach\Citi\NachRegisterFileHeadings as Headings;

class PaperNachCiti extends Base
{
    const STEP           = 'register';
    const FILE_NAME      = 'RAZORP_EMANDATE_{$utilityCode}_{$date}';
    const ZIP_FILE_NAME  = 'RAZORP_EMANDATE_{$utilityCode}_{$date}';
    const EXTENSION      = FileStore\Format::XLS;
    const FILE_EXTENSION = FileStore\Format::ZIP;
    const FILE_TYPE      = FileStore\Type::CITI_NACH_REGISTER;
    const GATEWAY        = Payment\Gateway::NACH_CITI;

    const UNTIL_CANCELLED = 'Until cancelled';

    protected $fileStore;

    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                        ->addHours(9)
                        ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
                      ->addHours(9)
                      ->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->fetchPendingNachRegistration(self::GATEWAY, $begin, $end);
        }
        catch (ServerErrorException $e)
        {
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_REGISTER_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids' => $paymentIds,
                'begin' => $begin,
                'end' => $end,
                'target' => $this->gatewayFile->getTarget(),
                'type'   => $this->gatewayFile->getType()
            ]);

        return $tokens;
    }

    public function generateData(PublicCollection $tokens)
    {
        return $tokens;
    }

    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }
        try
        {
            $this->DownloadS3ImagesFolder($data);

            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                // since file data is grouped based on utility code, it will be part of the file name
                $fileName = $this->getFileToWriteNameWithoutExt(['utilityCode' => $key]);

                $creator = new FileStore\Creator;

                $creator->extension(static::EXTENSION)
                        ->content($fileData)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(static::FILE_TYPE)
                        ->entity($this->gatewayFile)
                        ->metadata(static::FILE_METADATA)
                        ->save();

                $xlsFilePath = $creator->getFullFilePath();

                $xlsFileName = $creator->getFullFileName();

                $zipfileName = $this->getZipFileToWriteName($key, false);

                $zipFilePath = $this->getLocalSaveDir() . DIRECTORY_SEPARATOR . $zipfileName . '.zip';

                $zipFileName = $this->getZipFileToWriteName($key);

                $filePath = $key;

                $this->createZipFileWithData($filePath, $xlsFileName, $xlsFilePath, $zipFilePath);

                $zipCreator = new FileStore\Creator;

                $zipCreator->extension(static::FILE_EXTENSION)
                           ->localFilePath($zipFilePath)
                           ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[static::FILE_EXTENSION][0])
                           ->name($zipFileName)
                           ->store(FileStore\Store::S3)
                           ->type(static::FILE_TYPE)
                           ->entity($this->gatewayFile)
                           ->metadata(static::FILE_METADATA)
                           ->save();

                $file = $zipCreator->getFileInstance();

                $fileStoreIds[] = $file->getId();

                $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

                unlink($zipFilePath); // nosemgrep : php.lang.security.unlink-use.unlink-use
            }

            $this->fileStore = $fileStoreIds;

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::NACH_REGISTER_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }
        catch (\Throwable $e) {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ],
                $e);
        }
    }

    protected function getFileToWriteNameWithoutExt(array $data)
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date, '{$utilityCode}' => $data['utilityCode']]);

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function formatDataForFile($tokens)
    {
        $rows = [];
        $count = [];

        foreach ($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $utilityCode = $token->terminal->getGatewayMerchantId2();

            $subscriptionRegistration = $this->repo
                                             ->subscription_registration
                                             ->findByTokenIdAndMerchant($token->getId(), $token->merchant->getId());

            if(empty($subscriptionRegistration))
            {
                $this->trace->info(TraceCode::GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'payment_id'                => $paymentId,
                        'subscription_registration' => $subscriptionRegistration,
                    ]);

                continue;
            }

            $paperMandate = $subscriptionRegistration->paperMandate;

            $data = Fields::getNachRegistrationData($token, $paymentId, $token->merchant, $paperMandate);

            $startDate = Carbon::createFromTimestamp($data[Fields::START_TIMESTAMP], Timezone::IST)
                ->format('d/m/Y');


            if (empty($data[Fields::END_TIMESTAMP]) === false)
            {
                $endDate = Carbon::createFromTimestamp($data[Fields::END_TIMESTAMP], Timezone::IST)
                                    ->format('d/m/Y');
            }
            else
            {
                $endDate = self::UNTIL_CANCELLED;
            }

            if (isset($count[$utilityCode]) === true)
            {
                $count[$utilityCode] = $count[$utilityCode] + 1;
            }
            else
            {
                $count[$utilityCode] = 1;
            }

            $row = [
                Headings::SERIAL_NUMBER                 => $count[$utilityCode],
                Headings::CATEGORY_CODE                 => $data[Headings::CATEGORY_CODE],
                Headings::CATEGORY_DESCRIPTION          => $data[Headings::CATEGORY_DESCRIPTION],
                Headings::START_DATE                    => $startDate,
                Headings::END_DATE                      => $endDate,
                Headings::CLIENT_CODE                   => $data[Headings::CLIENT_CODE],
                Headings::MERCHANT_UNIQUE_REFERENCE_NO  => $data[Headings::MERCHANT_UNIQUE_REFERENCE_NO],
                Headings::CUSTOMER_ACCOUNT_NUMBER       => $data[Headings::CUSTOMER_ACCOUNT_NUMBER],
                Headings::CUSTOMER_NAME                 => $data[Headings::CUSTOMER_NAME],
                Headings::ACCOUNT_TYPE                  => $data[Headings::ACCOUNT_TYPE],
                Headings::BANK_NAME                     => $data[Headings::BANK_NAME],
                Headings::BANK_IFSC                     => $data[Headings::BANK_IFSC],
                Headings::AMOUNT                        => $this->getFormattedAmount($token->getMaxAmount()),
            ];

            $rows[$utilityCode][] = $row;

            $rowToTrace = $row;
            unset($rowToTrace[Headings::CUSTOMER_ACCOUNT_NUMBER]);

            $this->trace->info(TraceCode::NACH_REGISTER_REQUEST_ROW, ['row' => $rowToTrace]);
        }

        return $rows;
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

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo' => $fileInfo,
            'channel' => 'nach',
            'filetype' => FileStore\Type::CITI_NACH_REGISTER,
            'subject' => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SUBSCRIPTIONS_APPS]
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null) or
            ($beamResponse['failed'] !== null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'gateway_file' => $this->gatewayFile->getId(),
                    'target' => 'paper_nach_citi',
                    'type'   => $this->gatewayFile->getType()
                ]
            );
        }

        $mailData = $this->formatDataForMail($files);

        $type = static::GATEWAY . '_' . static::STEP;
        $mailable = new NachMail($mailData, $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
    }

    protected function getZipFileToWriteName($utilityCode, $withFullFilePath = true)
    {
        $begin = $this->gatewayFile->getBegin();

        $date = Carbon::createFromTimestamp($begin, Timezone::IST)->format('dmY');

        $zipfileName = strtr(static::ZIP_FILE_NAME,
            [
                '{$date}'        => $date,
                '{$utilityCode}' => $utilityCode,
            ]);

        if ($withFullFilePath === false)
        {
            $zipfileName = basename($zipfileName);
        }

        if ($this->isTestMode() === true)
        {
            $zipfileName .= '_' . $this->mode;
        }
        return $zipfileName;
    }

    protected function getLocalSaveDir(): string
    {
        $dirPath = storage_path('files/nach');

        if (file_exists($dirPath) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dirPath, 0777, true]);
        }

        return $dirPath;
    }

    protected function createZipFileWithData(string $filePath,
                                             string $xlsFileName,
                                             string $xlsFilePath,
                                             string $zipFilePath)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException(
                'Could not create Papernach zip file',
                [
                    'filename' => $zipFilePath
                ]);
        }

        $this->addImagesForUtilityCode($filePath, $zip);

        $zip->addFile($xlsFilePath, $xlsFileName);

        $zip->close();

        $this->deleteImageFolder($filePath);
    }

    public function addImagesForUtilityCode($utilityCode,  $zip)
    {
        $files = Storage::files($utilityCode);

        $basePath = storage_path('app') . '/';

        foreach ($files as $file)
        {
            $filePath = $basePath . $file;

            $zip->addFile($filePath, $file);
        }
    }

    protected function deleteImageFolder($folderPath)
    {
        try
        {
            $basePath = storage_path('app') . '/';

            $folderPath = $basePath . '/' . $folderPath;

            $itemIterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($folderPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($itemIterator as $item)
            {
                if ($item->isDir())
                {
                    rmdir($item->getPathname());
                }
                else
                {
                    unlink($item->getPathname()); // nosemgrep : php.lang.security.unlink-use.unlink-use
                }
            }

            rmdir($folderPath);
        }
        catch (\Exception $e)
        {
            $this->trace->critical(TraceCode::GATEWAY_NACH_FILE_DELETE_ERROR, ['file_path' => $folderPath]);
        }
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 0, '.', '');
    }

    protected function DownloadS3ImagesFolder($tokens)
    {
        $urls = [];

        foreach($tokens as $token)
        {
            $paymentId = $token['payment_id'];

            $utilityCode = $token->terminal->getGatewayMerchantId2();

            $payment = null;

            try
            {
                $payment = $this->repo->payment->findOrFail($paymentId);
            }
            catch (\Throwable $exception){}

            [$url, $formGenerationDate] = (new SubscriptionRegistration\Core())->getUploadedFileUrlByPaymentForNachMethod($payment);

            $filePath = $utilityCode . DIRECTORY_SEPARATOR . $paymentId .'.jpg';

            if(empty($url))
            {
                $this->trace->info(TraceCode::GATEWAY_FILE_ERROR_GENERATING_DATA,
                    [
                        'payment_id'    => $paymentId,
                        'url'           => $url,
                        'formCreateAt'  => $formGenerationDate
                    ]);
                continue;
            }

            try
            {
                $fileContents = file_get_contents($url);
            }
            catch (\Exception $e)
            {
                if (in_array($this->env, ['testing', 'testing_docker'], true) === true)
                {
                    $fileContents = 'dummy';
                }
                else
                {
                    throw $e;
                }
            }

            Storage::put($filePath, $fileContents);

            $urls[$paymentId] = $url;
        }
    }
}
