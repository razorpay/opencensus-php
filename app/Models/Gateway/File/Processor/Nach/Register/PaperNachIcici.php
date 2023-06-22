<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Register;

use Mail;
use Storage;
use Imagick;
use ZipArchive;
use DOMDocument;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Models\Customer\Token;
use RZP\Exception\RuntimeException;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Holidays;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\SubscriptionRegistration;
use RZP\Gateway\Enach\Base\CategoryCode;
use RZP\Mail\Gateway\Nach\Base as NachMail;
use RZP\Services\Beam\Service as BeamService;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\Gateway\File\Processor\Nach\Base;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Models\Gateway\File\Processor\FileHandler;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\Constants;
use RZP\Gateway\Enach\Npci\Physical\Icici\Registration\RequestFields;
use RZP\Models\Merchant\RazorxTreatment;

class PaperNachIcici extends Base
{
    use FileHandler;

    const STEP                   = 'register';
    const FILE_NAME              = 'MMS-CREATE-ICIC-ICIC865719-{$date}-{$code}';
    const EXTENSION              = FileStore\Format::ZIP;
    const FILE_TYPE              = FileStore\Type::ICICI_NACH_REGISTER;
    const GATEWAY                = Payment\Gateway::NACH_ICICI;
    const BASE_STORAGE_DIRECTORY = 'Icici/Nach/Register/';

    const IMAGE_SIZE_LIMIT = 99000;

    const UNTIL_CANCELLED = 'Until cancelled';

    protected $fileStore;

    protected $mailData;

    protected $zipFileSize = 50;

    public function __construct()
    {
        parent::__construct();

        $this->mailData = [];

        $this->fileStore = [];

        if ($this->isTestMode() === true)
        {
            $this->zipFileSize = 3;
        }
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('2048M');

        RuntimeManager::setTimeLimit(1200);
    }

    public function fetchEntities(): PublicCollection
    {
        if (Holidays::isWorkingDay(Carbon::now(Timezone::IST)) === false)
        {
            return new PublicCollection();
        }

        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
                        ->addHours(9)
                        ->getTimestamp();

        $begin = $this->getLastWorkingDay($begin);

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
            $count = 0;

            $key = Carbon::now()->getTimestamp();
            $mandateCreateDateEnabled = $this->isMandateCreateDateRazorxEnabled($key);

            foreach ($data as $token)
            {
                // files are grouped based on 50 registrations i.e 150 files
                $dirName = $this->prepareFilesForToken($token, $count, $mandateCreateDateEnabled);

                $count++;
            }

            // For the last set of files that need to be zipped
            if ((($count - 1) % $this->zipFileSize) !== ($this->zipFileSize - 1))
            {
                $this->generateZipFile($dirName);
                $this->deleteGeneratedFiles($dirName);
                $fileNameForMail = basename($dirName) . '.' . self::EXTENSION;
                $this->mailData[$fileNameForMail]['count'] = $count % $this->zipFileSize;
            }

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::NACH_REGISTER_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }
        catch (\Throwable $e)
        {
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

        $bucketConfig = $this->getBucketConfig(FileStore\Type::ICICI_NACH_REGISTER);

        $data = [
            BeamService::BEAM_PUSH_FILES         => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME       => BeamConstants::ICICI_ENACH_NB_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'nach',
            'filetype'  => FileStore\Type::ICICI_NACH_REGISTER,
            'subject'   => 'File Send failure',
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
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'target'        => 'paper_nach_icici',
                    'type'   => $this->gatewayFile->getType()
                ]
            );
        }

        $type = self::GATEWAY . '_' . self::STEP;

        $mailable = new NachMail(['mailData' => $this->mailData], $type, $this->gatewayFile->getRecipients());

        Mail::queue($mailable);
    }

    protected function deleteGeneratedFiles($folderPath)
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

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function prepareFilesForToken($token, $count, $mandateCreateDateEnabled): string
    {
        $fileNo = $count + 1;

        $dirNo = ((int) floor($count / $this->zipFileSize)) + 1;

        $fileCode = $this->getPaddedValue($fileNo, 6, '0', STR_PAD_LEFT);

        $dirCode = $this->getPaddedValue($dirNo, 6, '0', STR_PAD_LEFT);

        $date = Carbon::now(Timezone::IST)->format('dmY');

        $baseFileName = strtr(self::FILE_NAME, ['{$date}' => $date, '{$code}' => $fileCode]);

        $dirName = strtr(self::FILE_NAME, ['{$date}' => $date, '{$code}' => $dirCode]) . '-INP';

        $tiffFileName = $baseFileName . '_front.tiff';

        $jpgFileName = $baseFileName . '_detailfront.jpg';

        $formGenerationDate = $this->generateImages($token, $dirName, $tiffFileName, $jpgFileName);

        $this->generateXml($token, $dirName, $baseFileName, $formGenerationDate, $mandateCreateDateEnabled);

        // zip file can contain max 150 files (50 registrations - 1 xml, 2 images)
        if (($count % $this->zipFileSize) === ($this->zipFileSize - 1))
        {
            $this->generateZipFile($dirName);
            $this->deleteGeneratedFiles($dirName);
            $fileNameForMail = basename($dirName) . '.' . self::EXTENSION;
            $this->mailData[$fileNameForMail]['count'] = $this->zipFileSize;
        }

        return $dirName;
    }

    /*
     * Generates the image and returns the date when paper mandate form was generated
     */
    protected function generateImages($token, $dirName, $tiffName, $jpgName)
    {
        $paymentId = $token['payment_id'];

        $payment = null;

        try
        {
            $payment = $this->repo->payment->findOrFail($paymentId);
        }
        catch (\Throwable $exception){}

        [$url, $formGenerationDate] = (new SubscriptionRegistration\Core())->getUploadedFileUrlByPaymentForNachMethod($payment);

        $filePath  = $dirName . DIRECTORY_SEPARATOR;

        try
        {
            $jpgFileContents = file_get_contents($url);

            $image = new Imagick();

            $image->readImageBlob($jpgFileContents);

            $image->setCompression(Imagick::COMPRESSION_JPEG);

            $compressionValue = 75;

            $image->setCompressionQuality($compressionValue);

            $image->setImageFormat('tiff');

            $tiffFileContents = $image->getImageBlob();

            while ((strlen($tiffFileContents) > self::IMAGE_SIZE_LIMIT) and ($compressionValue > 25))
            {
                $compressionValue -= 5;

                $image->setCompressionQuality($compressionValue);

                $tiffFileContents = $image->getImageBlob();
            }
        }
        catch (\Exception $e)
        {
            if ($this->env === 'testing')
            {
                $jpgFileContents  = 'dummy';
                $tiffFileContents = 'dummy';
            }
            else
            {
                throw $e;
            }
        }

        Storage::put($filePath . $jpgName ,  $jpgFileContents);
        Storage::put($filePath . $tiffName, $tiffFileContents);

        return $formGenerationDate;
    }

    protected function generateXml($token, $dirName, $fileName, $formGenerationEpoch, $mandateCreateDateEnabled)
    {
        $merchant = $token->merchant;

        $paymentId = $token['payment_id'];

        $utilityCode = $token->terminal->getGatewayMerchantId2();

        $sponsorBankIfsc = $token->terminal->getGatewayAccessCode();

        $merchantCategory = $merchant->getCategory();

        $accountNumber = $token->getAccountNumber();

        $destinationBankFfsc = $token->getIfsc();

        $accountTypeMapping = $this->getAccountTypeMapping($token->getAccountType());

        $accountType = strtoupper($accountTypeMapping);

        $mandateCategoryCode = CategoryCode::getCategoryCodeFromMccForNach($merchantCategory);

        $customerName = $token['beneficiary_name'] ?? $token->customer->getName();
        $customerName = substr($customerName, 0, 40);

        $createdDate = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s');

        $startDate = $token->getStartTime();
        $firstCollectionDate = Carbon::createFromTimestamp($startDate, Timezone::IST)->format('Y-m-d');

        $label         = $merchant->getBillingLabel();
        $filteredLabel = preg_replace('/[^a-zA-Z]+/', '', $label);
        $creditorName  = str_limit($filteredLabel, 20, '');

        $maxAmount = $this->getFormattedAmount($token->getMaxAmount());

        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?> <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.009.001.01"/>');

        $mandateroot = $document->addChild(RequestFields::MANDATE_INIT_REQUEST);

        $grp = $mandateroot->addChild(RequestFields::GROUP_HEADER);

        $grp->addChild(RequestFields::MESSAGE_ID, $paymentId);

        $grp->addChild(RequestFields::CREATED_DATE_TIME, $createdDate);

        ((($grp->addChild(RequestFields::INSTRUCTING_AGENT))->addChild(RequestFields::FINANCIAL_INST_ID))->addChild(RequestFields::CLR_SYS_MEMBER_ID))->addChild(RequestFields::MEMBER_ID, $sponsorBankIfsc);

        ((($grp->addChild(RequestFields::INSTRUCTED_AGENT))->addChild(RequestFields::FINANCIAL_INST_ID))->addChild(RequestFields::CLR_SYS_MEMBER_ID))->addChild(RequestFields::MEMBER_ID, $destinationBankFfsc);

        $mandate = $mandateroot->addChild(RequestFields::MANDATE);

        $mandate->addChild(RequestFields::MANDATE_REQUEST_ID, $paymentId);

        $type = $mandate->addChild(RequestFields::TYPE);

        ($type->addChild(RequestFields::SVC_LEVEL))->addChild(RequestFields::PRTRY, $mandateCategoryCode);

        ($type->addChild(RequestFields::LCL_INSTRUMENT))->addChild(RequestFields::PRTRY, Constants::DEBIT);

        $occurences = $mandate->addChild(RequestFields::OCCURRENCES);

        $occurences->addChild(RequestFields::SEQUENCE_TYPE, Constants::RECURRING);

        $occurences->addChild(RequestFields::FREQUENCY, Constants::ADHOC);

        $occurences->addChild(RequestFields::FIRST_COLLECTION_DATE, $firstCollectionDate);

        if($mandateCreateDateEnabled === true){
            $formGenerationDate = Carbon::createFromTimestamp($formGenerationEpoch, Timezone::IST)->format('Y-m-d');
            ($occurences->addChild(RequestFields::DRTN))->addChild(RequestFields::FORM_DATE, $formGenerationDate);
        }

        $endDate = $token->getExpiredAt();
        if (empty($endDate) === false)
        {
            $endDate = Carbon::createFromTimestamp($endDate, Timezone::IST)->format('Y-m-d');

            $occurences->addChild(RequestFields::FINAL_COLLECTION_DATE, $endDate);
        }

        $maxAmount = $mandate->addChild(RequestFields::MAX_AMOUNT, $maxAmount);
        $maxAmount->addAttribute(RequestFields::CURRENCY, Constants::INR);

        $creditor = $mandate->addChild(RequestFields::CREDITOR);

        $creditor->addChild(RequestFields::NAME, $creditorName);

        $creditorAccount = $mandate->addChild(RequestFields::CREDITOR_ACCOUNT);

        (($creditorAccount->addChild(RequestFields::ID))->addChild(RequestFields::OTHER))->addChild(RequestFields::ID, $utilityCode);

        $creditorAgent = $mandate->addChild(RequestFields::CREDITOR_AGENT);

        (($creditorAgent->addChild(RequestFields::FINANCIAL_INST_ID))->addChild(RequestFields::CLR_SYS_MEMBER_ID))->addChild(RequestFields::MEMBER_ID, $sponsorBankIfsc);

        $debtor = $mandate->addChild(RequestFields::DEBTOR);

        $debtor->addChild(RequestFields::NAME, $customerName);

        $debtorAccount = $mandate->addChild(RequestFields::DEBTOR_ACCOUNT);

        (($debtorAccount->addChild(RequestFields::ID))->addChild(RequestFields::OTHER))->addChild(RequestFields::ID, $accountNumber);

        ($debtorAccount->addChild(RequestFields::TYPE))->addChild(RequestFields::PRTRY, $accountType);

        $debtorAgent = $mandate->addChild(RequestFields::DEBTOR_AGENT);

        (($debtorAgent->addChild(RequestFields::FINANCIAL_INST_ID))->addChild(RequestFields::CLR_SYS_MEMBER_ID))->addChild(RequestFields::MEMBER_ID, $destinationBankFfsc);

        $dom = new DOMDocument("1.0");

        $dom->preserveWhiteSpace = false;

        $dom->formatOutput = true;

        $dom->loadXML($document->asXML());

        $xmlFilePath  = $dirName . DIRECTORY_SEPARATOR . $fileName . '-INP.xml';

        Storage::put($xmlFilePath, $dom->saveXML());
    }

    public function getPaddedValue($value, $fieldLength, $padString, $padType)
    {
        $size = $fieldLength;

        $pad_str = str_pad($value, $size, $padString, $padType);

        return substr($pad_str, 0, $size);
    }

    protected function generateZipFile($zipFileTarget)
    {
        $files = Storage::files($zipFileTarget);

        $zipFileLocalName = basename($zipFileTarget);

        $zipFileLocalPath = $this->getLocalSaveDir() . DIRECTORY_SEPARATOR . $zipFileLocalName . '.zip';

        $zipFileS3Name = self::BASE_STORAGE_DIRECTORY . basename($zipFileTarget);

        $zip = new ZipArchive();

        if ($zip->open($zipFileLocalPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException(
                'Could not create Papernach zip file',
                [
                    'filename' => $zipFileLocalPath
                ]);
        }

        $basePath = storage_path('app') . '/';

        foreach ($files as $file)
        {
            $filePath = $basePath . $file;

            $zip->addFile($filePath, basename($file));
        }

        $zip->close();

        $zipCreator = new FileStore\Creator;

        $zipCreator->extension(static::EXTENSION)
            ->localFilePath($zipFileLocalPath)
            ->mime(FileStore\Format::VALID_EXTENSION_MIME_MAP[static::EXTENSION][0])
            ->name($zipFileS3Name)
            ->store(FileStore\Store::S3)
            ->type(static::FILE_TYPE)
            ->entity($this->gatewayFile)
            ->metadata(static::FILE_METADATA)
            ->save();

        $file = $zipCreator->getFileInstance();

        $this->fileStore[] = $file->getId();

        $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

        unlink($zipFileLocalPath); // nosemgrep : php.lang.security.unlink-use.unlink-use
    }

    public static function getAccountTypeMapping(string $accountType): string
    {
        $accountTypeMap = [
            Token\Entity::ACCOUNT_TYPE_SAVINGS     => 'savings',
            Token\Entity::ACCOUNT_TYPE_CURRENT     => 'current',
            Token\Entity::ACCOUNT_TYPE_CASH_CREDIT => 'cc',
            Token\Entity::ACCOUNT_TYPE_SB_NRE      => 'savings',
            Token\Entity::ACCOUNT_TYPE_SB_NRO      => 'savings',
        ];

        return $accountTypeMap[$accountType] ?? 'savings';
    }

    private function isMandateCreateDateRazorxEnabled($key): bool
    {
        $status = $this->app['razorx']->getTreatment($key,
            RazorxTreatment::ICICI_PNACH_MANDATE_CREATION_DATE_RAZORX, $this->mode);

        return (strtolower($status) === 'on');
    }
}
