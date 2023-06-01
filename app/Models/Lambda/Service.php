<?php

namespace RZP\Models\Lambda;

use File;
use Request;
use RZP\Base\RuntimeManager;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorDescription;
use RZP\Excel\Import as ExcelImport;
use RZP\Exception;
use RZP\Exception\ServerErrorException;
use RZP\Jobs\MerchantCrossborderEmail;
use RZP\Mail\Base\Constants;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Batch;
use RZP\Models\Currency\Currency;
use RZP\Models\Gateway\File\Constants as GatewayConstants;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service as BeamService;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Document;
use RZP\Models\FundTransfer\Kotak;
use RZP\Reconciliator\FileProcessor;
use Symfony\Component\HttpFoundation;
use RZP\Models\Merchant\Document\Entity;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Models\Settlement\InternationalRepatriation\Entity as RepatEntity;
use RZP\Models\Settlement\InternationalRepatriation\Service as RepatService;
use RZP\Models\Transaction\Entity as TEntity;
use RZP\Models\Settlement\Entity as SEntity;
use Carbon\Carbon;

class Service extends Base\Service
{
    use Kotak\FileHandlerTrait;

    const MUTEX_RESOURCE = 'LAMBDA_REQUEST_%s';

    // 30 minutes
    const MUTEX_LOCK_TIMEOUT = 1800;

    protected $fileProcessor = null;

    protected $ufh;

    protected $mutex;

    const SFTP_BUCKET_TARGETS = [
        Batch\Constants::ENACH_NB_ICICI,
        Batch\Constants::ENACH_RBL,
    ];

    // skips extracting the zip file before creating the batch
    // the extraction is handled as part of batch processing
    const SKIP_INPUT_EXTRACT = [
        Batch\Type::NACH
    ];

    const RBL = "rbl";
    const ICICI = "icici";
    const NIUM = "NIUM";
    const LEDGER_TYPE_BOOK_FX = 'Book_FX';
    const LEDGER_TYPE_PAYOUTS = 'Payouts';
    const LEDGER_TYPE_RECEIVE = 'Receive';
    const NIUM_REPAT_FILE_TYPE = 'acct';

    const OPGSP_TRAN_REF_NO = 'opgsptranrefno';
    const REMITTANCE_CURRENCY = 'currency';

    const FIRSTDATA                     = "firstdata";
    const FIRSTDATA_DETAIL_FIRS_TYPE    = 'Det';
    const FIRSTDATA_SUMMARY_FIRS_TYPE   = 'Sum';
    const FIRS_FIRSTDATA_FILE           = 'firs_firstdata_file';
    const FIRS_FIRSTDATA_SUMMARY_FILE   = 'firs_firstdata_sum_file';

    protected static $headers = [
        'MID',
        'Merchant name',
        'Partner_Name',
        'Partner_Code',
        'TID',
        'TID Creation Date',
        'Date of Onboarding',
        'Legal Name',
        'DBA Name',
        'Machine Type',
        'Terminal Model',
        'Address1',
        'State',
        'City',
        'Location',
        'PIN Code',
        'Phone No. (Landline)',
        'Mobile No.',
        'Email Id',
        'Contact Person Name',
        'Merchant Category Code',
        'MCC Description',
        'Beneficiary Account NAME',
        'Beneficiary Account No',
        'Beneficiary Address',
        'IFSC Code',
        'Payment Mode',
        'Activate Date',
        'Current Status',
        'Business Website',
        'Purpose Code',
        'Purpose Code Description',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->fileProcessor = new FileProcessor;

        $this->mutex = $this->app['api.mutex'];

        $this->merchant = $this->repo->merchant->getSharedAccount();

        $this->ufh = (new UfhService($this->app));
    }

    public function processLambda(string $type, array $input)
    {
        $this->trace->info(
            TraceCode::LAMBDA_REQUEST,
            [
                'type'    => $type,
                'input'   => $input
            ]);

        $input['type'] = $type;

        $batches = $this->mutex->acquireAndRelease(
            sprintf(self::MUTEX_RESOURCE, strtoupper($type)),
            function () use ($input, $type)
            {
                list($file, $locationType) = $this->getFileDetails($input, $type);

                return $this->createBatches($input, $file, $locationType);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_LAMBDA_ANOTHER_OPERATION_IN_PROGRESS,
            5,
            2000,
            4000);

        return $batches->toArrayPublic();
    }

    protected function getFileDetails(array & $input, string $type,bool $configKey = false)
    {
        if (isset($input['key']) === true)
        {
            // TODO: add validation for key
            $key = urldecode($input['key']);

            $target = $input['gateway'] ?? null;

            if (($type === Batch\Type::NACH) or
                (in_array($target, self::SFTP_BUCKET_TARGETS, true)))
            {
                $filePath = $this->getH2HFileFromAws($key, true, 'sftp_bucket', 'ap-south-1',$configKey);
            }
            else
            {

                // Adding this to migrate the lambdas to indian region bucket
                // with old lambda bucket and region was not being passed
                // thus added this step to pass the bucket and region along with the new lambda
                // keeping the following config in order to support both the lmbdas old and new
                // to ease the migration process

                $bucketConfig = 'h2h_bucket';
                $bucketRegion = null;

                if(empty($input['bucket']) === false)
                {
                    $bucketConfig = $input['bucket'];
                    // doing this because the batch file creation will require this to be unset
                    // else in validation it will fail
                    unset($input['bucket']);
                }

                if (empty($input['region']) === false)
                {
                    $bucketRegion = $input['region'];
                    // doing this because the batch file creation will require this to be unset
                    // else in validation it will fail
                    unset($input['region']);
                }

                $filePath = $this->getH2HFileFromAws($key, true, $bucketConfig, $bucketRegion,$configKey);
            }

            $file = new HttpFoundation\File\File($filePath);

            $locationType = FileProcessor::STORAGE;

            unset($input['key']);
        }
        else if (isset($input['file']) === true)
        {
            $file = $input['file'];

            $locationType = FileProcessor::UPLOADED;

            unset($input['file']);
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid input, either bucket key or uploaded file is required');
        }

        return [$file, $locationType];
    }

    protected function createBatches($input, $file, $locationType)
    {
        $batches = new Base\PublicCollection;

        $files = [];

        if (($this->fileProcessor->isZipFile($file, $locationType) === true) and
            ((in_array($input['type'], self::SKIP_INPUT_EXTRACT, true) === false) and
            (($input['sub_type'] !== Batch\Constants::CANCEL) or ($input['gateway'] !== Batch\Constants::ENACH_RBL))))
        {
            // Gets the actual zip file's details first.
            $zipFileDetails = $this->fileProcessor->getFileDetails($file, $locationType);

            // Gets all files details present in the zip file.
            $files = $this->getFileDetailsFromZipFile($zipFileDetails);
        }
        else
        {
            $files[] = $file;
        }

        foreach ($files as $file)
        {
            $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

            $this->trace->info(TraceCode::LAMBDA_FILE_DETAILS, $fileDetails);

            //
            // `file` laravel validation rule which is being used
            // in batch validator expects either File or UploadedFile type
            //
            if ((($file instanceof HttpFoundation\File\File) === false) and
                (($file instanceof HttpFoundation\File\UploadedFile) === false))
            {
                $file = new HttpFoundation\File\File($fileDetails[FileProcessor::FILE_PATH]);
            }

            $input['file'] = $file;

            try
            {
                $batch = (new Batch\Core)->create($input, $this->merchant);

                $batches->push($batch);
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    null,
                    TraceCode::LAMBDA_BATCH_FAILURE,
                    [
                        'file_details' => $file
                    ]);
            }
        }

        return $batches;
    }

    protected function getFileDetailsFromZipFile(array $zipFileDetails)
    {
        // unzipFile unzips the file and stores it in a location.
        $unzippedFolderPath = $this->fileProcessor->unzipFile($zipFileDetails);

        return File::allFiles($unzippedFolderPath);
    }

    public function processLambdaFIRS(array $input, $mode = Mode::LIVE)
    {
        $this->trace->info(TraceCode::LAMBDA_REQUEST,
            [
                'input'   => $input
            ]);

        $configKey = true;

        list($file, $locationType) = $this->getFileDetails($input,'FIRS',$configKey);

        $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

        $file = new HttpFoundation\File\UploadedFile($fileDetails['file_path'],$this->fileProcessor->getFileName($file),$fileDetails['mime_type'],null,true);

        // additional check to ensure mode is set
        if (!isset($mode) or $mode !== Mode::LIVE or $mode !== Mode::TEST)
        {
            $mode = Mode::LIVE;
        }
        $document = $this->uploadFileAndSaveInMerchantDocument($file, $input, $mode);

        $documentMetaData = [
            Entity::ID => $document->getId(),
            Entity::FILE_STORE_ID => $document->getFileStoreId(),
            Entity::MERCHANT_ID => $document->getMerchantId(),
        ];

        return $documentMetaData;
    }

    protected function uploadFileAndSaveInMerchantDocument(HttpFoundation\File\UploadedFile $file, array $input, $mode = Mode::LIVE)
    {
        $filename = $file->getClientOriginalName();

        $ufhService = $this->app['ufh.service'];

       if($input['gateway'] === self::RBL)
       {
            list($company, $gatewayMerchantId, $date) = explode('_',$filename);

            $part = str_split($date,2);

            $terminal = $this->repo->terminal->findMerchantIdByGatewayMerchantIDAll($gatewayMerchantId);
            $merchantId = $terminal->getMerchantId();
            $merchant = $this->repo->merchant->find($merchantId);
            $storageFileName = 'FIRS/'.$merchantId.'/'.$part[1].'/'.$part[0].'/'.$filename;
            $type = 'firs_file';
            $documentDate = strtotime($part[0].'/'.date('d').'/'.$part[1]);

            $response = $ufhService->uploadFileAndGetResponse($file, $storageFileName, $type, $merchant);

            $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS,
                [
                    'success'           => isset($response[GatewayConstants::ID]),
                ]);

            /*
             * Commenting it out, because for now we are removing zip file generation logic
             * for RBL Files.
             *
             * $this->deleteExistingZipFile($merchantId,$part);
            */

            $document = (new Document\Core)->saveInMerchantDocument($response, $merchantId, $type, $documentDate);

            $this->triggerFIRSAvailableNotification($document, $mode);
       }

       if($input['gateway'] === self::ICICI)
       {
            list($tag, $referenceNumber, $utrNumberAndFileExtension) = explode('_',$filename);

            $utrNumberAndFileExtension = ltrim($utrNumberAndFileExtension);
            $utrNumberAndFileExtension = rtrim($utrNumberAndFileExtension);
            list($utrNumber, $fileExtension) = explode('.',$utrNumberAndFileExtension);

            $settlement =  $this->repo->settlement->findSettlementByUTR($utrNumber);
            $merchantId = $settlement->getMerchantId();
            $merchant = $this->repo->merchant->find($merchantId);

            $firs_date = date('m/d/Y', $settlement->getUpdatedAt());

            list($month,$date,$year) = explode('/',$firs_date);

            $storageFileName = 'FIRS/'.$merchantId.'/'.$year.'/'.$month.'/'.$filename;
            $type = 'firs_icici_file';
            $documentDate = strtotime($month.'/'.'01'.'/'.$year);

            $response = $ufhService->uploadFileAndGetResponse($file, $storageFileName, $type, $merchant);

            $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS,
                [
                    'success'           => isset($response[GatewayConstants::ID]),
                ]);

            $document = (new Document\Core)->saveInMerchantDocument($response, $merchantId, $type, $documentDate);
       }

       if ($input['gateway'] === self::FIRSTDATA)
       {
            // 15 Digit MID_FIRS_From_DDMMYY_ To_DDMMYY_POS_Det.pdf
            $tokens = explode('_',$filename);

            $gatewayMerchantId = trim($tokens[0]);
            $fromDate = trim($tokens[3]);
            $toDate = trim($tokens[5]);
            $filetypeAndFileExtension = trim($tokens[7]);

            $fromMonth = substr($fromDate,2,2);
            $fromYear = substr($fromDate,4,2);

            $toMonth = substr($toDate,2,2);
            $toYear = substr($toDate,4,2);

            $filetypeAndFileExtension = trim($filetypeAndFileExtension);
            list($fileType, $fileExtension) = explode('.',$filetypeAndFileExtension);

            if($fromMonth !== $toMonth || $fromYear !== $toYear || $fileExtension !== 'pdf' || 
                !($fileType === self::FIRSTDATA_SUMMARY_FIRS_TYPE || $fileType === self::FIRSTDATA_DETAIL_FIRS_TYPE))
            { 
                $this->trace->info(TraceCode::INVALID_FIRSTDATA_FIRS_FILE,[
                    'filename' => $filename
                ]);

                throw new Exception\BadRequestValidationFailureException(
                    'Invalid or Unsupported File Type');
            }

            $gatewayMerchantId = trim($gatewayMerchantId);
            
            // 33 is added as prefix before storing for all TIDs.
            $gatewayMerchantId = "33" . substr($gatewayMerchantId,7,8);
            $terminal = $this->repo->terminal->findMerchantIdByGatewayMerchantIDAll($gatewayMerchantId);
            $merchantId = $terminal->getMerchantId();
            $merchant = $this->repo->merchant->find($merchantId);
            $storageFileName = 'FIRS/'.$merchantId.'/'.$fromYear.'/'.$fromMonth.'/'.$filename;
            $documentDate = strtotime($fromMonth.'/'.date('d').'/'.$fromYear);

            if ($fileType == self::FIRSTDATA_DETAIL_FIRS_TYPE)
            {
                $type = self::FIRS_FIRSTDATA_FILE;
            }
            
            if ($fileType == self::FIRSTDATA_SUMMARY_FIRS_TYPE)
            {
                $type = self::FIRS_FIRSTDATA_SUMMARY_FILE;
            }

            $response = $ufhService->uploadFileAndGetResponse($file, $storageFileName, $type, $merchant);

            $this->trace->info(TraceCode::UPLOAD_FILE_DETAILS, [
                'success'           => (isset($response[UfhService::STATUS]) && $response[UfhService::STATUS] === FileProcessor::UPLOADED)
            ]);

            if(isset($response[UfhService::STATUS]) && $response[UfhService::STATUS] !== FileProcessor::UPLOADED)
            {
                throw new Exception\ServerErrorException('Unable to Upload FIRS File', ErrorCode::SERVER_ERROR);
            }

            $document = (new Document\Core)->saveInMerchantDocument($response, $merchantId, $type, $documentDate);

            $this->triggerFIRSAvailableNotification($document, $mode);
       }

        return $document;
    }

    protected function deleteExistingZipFile(string $merchantId, array $part)
    {
        $from = strtotime($part[0].'/01/'.$part[1]);
        $to = strtotime("+1 Month",$from)-1;

        $ufhService = $this->app['ufh.service'];

        $documentEntities = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeAndDate($merchantId,'firs_zip',$from,$to);

        foreach($documentEntities as $documentEntity)
        {
            if ($documentEntity != null)
            {
                $ufhService->deleteFile($documentEntity->getPublicFileStoreId(),$merchantId,'firs_zip');
                (new Document\Core)->deleteDocuments([$documentEntity->getFileStoreId()]);
            }
            break;
        }
    }

    public function processLambdaMerchantMasterFIRS(array $input)
    {
        $this->trace->info(TraceCode::LAMBDA_REQUEST,
            [
                'input'   => $input
            ]);

        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(3000);
        RuntimeManager::setMaxExecTime(6000);

        $configKey = true;

        list($file, $locationType) = $this->getFileDetails($input,'rbl_merchant_master',$configKey);

        $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

        $handler = fopen($fileDetails['file_path'],"r");

        $merchantDetailsMap = $this->fetchMerchantDetailsMapFromFile($handler);

        fseek($handler,0);
        $headers = fgetcsv($handler);
        $finalHeaders = self::$headers;
        $data=array();

        while (!feof($handler))
        {
            $row = fgetcsv($handler);
            if($row[0]=='')
                continue;
            $merchantBankDetail = $merchantDetailsMap[$row[0]];

            //Adding the Merchant Bank Details
            $row[23] = (string) $merchantBankDetail[DetailEntity::BANK_ACCOUNT_NAME];
            $row[24] = (string) $merchantBankDetail[DetailEntity::BANK_ACCOUNT_NUMBER];
            $row[25] = (string) $merchantBankDetail[DetailEntity::BANK_BENEFICIARY_ADDRESS1].' '.$merchantBankDetail[DetailEntity::BANK_BENEFICIARY_ADDRESS2].' '.$merchantBankDetail[DetailEntity::BANK_BENEFICIARY_ADDRESS3];
            $row[26] = (string) $merchantBankDetail[DetailEntity::BANK_BRANCH_IFSC];

            $line = array_combine($finalHeaders,array_slice($row,1));
            array_push($data,$line);
        }
        $month = date('m');
        $year = date('y');
        if($month==1)
        {
            $month = 12;
            $year = $year-1;
        }
        else{
            $month = $month -1;
        }
        $fileName = 'FIRS Merchants_'.$month.$year;
        fclose($handler);

        $creator = new FileStore\Creator;
        $creator->extension(FileStore\Format::XLSX)
            ->content($data)
            ->name($fileName)
            ->store(FileStore\Store::S3)
            ->type(FileStore\Type::RBL_MERCHANT_MASTER_FIRS)
            ->save();

        $this->pushFilesToSFTP($creator);

        $response = [
            $creator->getSignedUrl(),
        ];

        return $response;
    }

    protected function fetchMerchantDetailsMapFromFile( $fileHandler)
    {
        $headers = fgetcsv($fileHandler);
        $merchantIds=array();

        while(! feof($fileHandler))
        {
            $row = fgetcsv($fileHandler);
            if($row[0]=='')
                continue;
            array_push($merchantIds,$row[0]);
        }

        $distinctMerchantIds = array_unique($merchantIds);

        $splitIds = array_chunk($distinctMerchantIds,1000);
        $merchantDetailsMap=[];

        foreach ($splitIds as $setIds)
        {
            $merchantBankDetails = $this->repo->merchant_detail->findMerchantBankDetailsWithIds($setIds);
            foreach ($merchantBankDetails as $merchantDetail)
            {
                $merchantId = $merchantDetail[Entity::MERCHANT_ID];
                if(isset($merchantDetailsMap[$merchantId]) === false)
                {
                    $merchantDetailsMap[$merchantId]=[];
                }
                $merchantDetailsMap[$merchantId] = $merchantDetail;
            }
        }

        return $merchantDetailsMap;
    }

    protected function pushFilesToSFTP(FileStore\Creator $creator)
    {
        $bucketConfig = $creator->getBucketConfig(FileStore\Type::RBL_MERCHANT_MASTER_FIRS);

        $data =  [
            BeamService::BEAM_PUSH_FILES   => [$creator->getFullFileName()],
            BeamService::BEAM_PUSH_JOBNAME => BeamConstants::RBL_MERCHANT_MASTER_FIRS_JOB_NAME,
            BeamService::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
            BeamService::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        $timelines = [];

        $mailInfo = [
            'fileInfo'  => [$creator->getFullFileName()],
            'channel'   => 'RBL',
            'filetype'  => FileStore\Type::RBL_MERCHANT_MASTER_FIRS,
            'subject'   => 'File send failure',
            'recipient' => Constants::MAIL_ADDRESSES[Constants::CROSS_BORDER_TECH]
        ];

        $this->app['beam']->beamPush($data, $timelines, $mailInfo);
    }

    public function processLambdaSettlementRepatriation(array $input)
    {
        $this->trace->info(TraceCode::REPATRIATION_LAMBDA_REQUEST,
            [
                'input'   => $input
            ]);

        $configKey = true;

        list($file, $locationType) = $this->getFileDetails($input,'settlement_repatriation',$configKey);

        $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);

        $fileName = $file->getFilename();

        $this->trace->info(TraceCode::REPATRIATION_LAMBDA_REQUEST,
            [
                'fileNameDetails'   => $fileDetails,
                '$fileName'   => $fileName,
            ]);

        $fileNameDetails = explode('_',$fileName);

        $response =[];

        if($input['partner'] === self::NIUM &&
            empty($fileNameDetails) === false &&
            sizeof($fileNameDetails) >=2 &&
            $fileNameDetails[1] === self::NIUM_REPAT_FILE_TYPE){

            $handler = fopen($fileDetails['file_path'],"r");

            fseek($handler,0);
            $headers = fgetcsv($handler);

            $repatriationEntity = [];
            $settlementIdList = [];
            $transactions =[];
            $merchantIds = [];
            while (!feof($handler))
            {
                $row = fgetcsv($handler);
                if($row[1]=='')
                    continue;
                $ledgerType = $row[4];

                if(strcasecmp($ledgerType, self::LEDGER_TYPE_PAYOUTS) === 0){

                    $settledDate = strval($row[0]);

                    $settledAt = Carbon::createFromFormat('d/m/Y H:i:s', $settledDate, 'UTC')
                        ->setTimezone(Timezone::IST)->getTimestamp();;
                    $repatriationEntity[RepatEntity::SETTLED_AT] = $settledAt;
                    $repatriationEntity[RepatEntity::PARTNER_MERCHANT_ID] = $row[2];
                    $repatriationEntity[RepatEntity::PARTNER_SETTLEMENT_ID] = $row[3];
                    $repatriationEntity[RepatEntity::CURRENCY] ='INR';
                    $repatriationEntity[RepatEntity::CREDIT_CURRENCY] =$row[5];
                    $repatriationEntity[RepatEntity::PARTNER_TRANSACTION_ID]=$row[9];

                }
                elseif (strcasecmp($ledgerType, self::LEDGER_TYPE_BOOK_FX) === 0){

                    if($row[5] === 'INR')
                    {
                        $amount = $row[6];
                        $formattedAmount = number_format((float)$amount, 2, '.', '');
                        $repatriationEntity[RepatEntity::AMOUNT] = $formattedAmount * 100;
                    }else
                    {
                        $creditAmount = $row[7];
                        $formattedCreditAmount = number_format((float)$creditAmount, 2, '.', '');
                        $repatriationEntity[RepatEntity::CREDIT_AMOUNT] = $formattedCreditAmount * 100;
                    }
                }
                elseif (strcasecmp($ledgerType, self::LEDGER_TYPE_RECEIVE) === 0){

                    $transactionId = $row[3];

                    $transaction = $this->repo->transaction->findOrFail($transactionId);
                    $settlementId = $transaction->getSettlementId();

                    array_push($settlementIdList, $settlementId);
                    array_push($transactions, $transaction->getId());
                    array_push($merchantIds, $transaction->getMerchantId());

                }
            }

            $distinctSettlementIds = array_unique($settlementIdList);
            $isValidAmount = $this->reconcileRepatriationAmount($repatriationEntity[RepatEntity::AMOUNT],$distinctSettlementIds);

            if(!$isValidAmount){

                $this->trace->info(TraceCode::INVALID_REPATRIATION_AMOUNT, [
                    'fileDetails'           => $fileDetails,
                    'amount'                => $repatriationEntity[RepatEntity::AMOUNT],
                    'settlements'           => array_values($distinctSettlementIds),
                    'transactions'          => array_values($transactions)
                ]);

                $response['success'] = false;
                return $response;
            }

            $distinctMerchantId = array_unique($merchantIds);
            if(sizeof($distinctMerchantId) > 1) {

                $this->trace->info(TraceCode::REPATRIATION_FILE_NON_UNIQUE_MERCHANT, [
                    'fileDetails'           => $fileDetails,
                    '$distinctMerchantId'   => $distinctMerchantId
                ]);

                $response['success'] = false;
                return $response;

            }
            $repatriationEntity[RepatEntity::MERCHANT_ID] = $distinctMerchantId[0];

            if($repatriationEntity[RepatEntity::CREDIT_AMOUNT] >0 &&
                $repatriationEntity[RepatEntity::AMOUNT]>0){

                $forexRate = $repatriationEntity[RepatEntity::CREDIT_AMOUNT]/$repatriationEntity[RepatEntity::AMOUNT];
                $forexRateFormatted = number_format((float)$forexRate, 6, '.', '');

                $repatriationEntity[RepatEntity::FOREX_RATE] = $forexRateFormatted;
            }else
            {
                $this->trace->info(TraceCode::INVALID_REPATRIATION_AMOUNT, [
                    'fileDetails'           => $fileDetails,
                    'amount'                => $repatriationEntity[RepatEntity::AMOUNT],
                    'creditAmount'          => $repatriationEntity[RepatEntity::CREDIT_AMOUNT],
                    'settlements'           => array_values($distinctSettlementIds),
                    'transactions'          => array_values($transactions)
                ]);

                $response['success'] = false;
                return $response;
            }

            $repatriationEntity[RepatEntity::SETTLEMENT_IDS] = array_values($distinctSettlementIds);
            $repatriationEntity[RepatEntity::INTEGRATION_ENTITY] = $input['partner'];
            $repatriationEntity[RepatEntity::UPDATED_AT] = time();


            $this->saveRepatriationDetails($repatriationEntity);

            fclose($handler);
        }
        else{
            $this->trace->info(TraceCode::INVALID_REPATRIATION_FILE, $fileDetails);

            $response['success'] = false;
            return $response;
        }

        $this->trace->info(TraceCode::REPATRIATION_SUCESS, $fileDetails);
        $response['success'] = true;
        return $response;
    }

    public function processLambdaOpgspSettlementRepatriation(array $input)
    {
        try
        {
            $this->trace->info(TraceCode::OPGSP_REPATRIATION_LAMBDA_REQUEST, [
                'input' => $input
            ]);

            list($file, $locationType) = $this->getFileDetails($input, 'opgsp_settlement_repatriation', true);
            $fileDetails = $this->fileProcessor->getFileDetails($file, $locationType, false);
            $fileName = $file->getFilename();

            $this->trace->info(TraceCode::OPGSP_REPATRIATION_LAMBDA_REQUEST, [
                'fileNameDetails'   => $fileDetails,
                'fileName'         => $fileName,
            ]);

            if ($input['partner'] === self::ICICI)
            {
                $excelReader = (new ExcelImport(1))->toArray($fileDetails['file_path']);

                if (count($excelReader) >= 3)
                {
                    $this->trace->info(TraceCode::OPGSP_REPATRIATION_AMLOCK_FILE_FOUND, [
                        'input' => $input,
                        'fileNameDetails' => $fileDetails,
                        'fileName' => $fileName,
                    ]);
                }
                else if (count($excelReader) < 2)
                {
                    $this->trace->info(TraceCode::OPGSP_REPATRIATION_INVALID_FILE, [
                        'message' => 'sheet count is less than two',
                        'input' => $input,
                        'fileNameDetails' => $fileDetails,
                        'fileName' => $fileName,
                    ]);
                    return ['success' => false];
                }

                $consolidatedDetails = $excelReader[0];
                $transactionLevelDetails = $excelReader[1];

                if (count($consolidatedDetails) !== 1 or count($transactionLevelDetails) === 0)
                {
                    $this->trace->info(TraceCode::OPGSP_REPATRIATION_INVALID_FILE, [
                        'message' => 'repatriation sheet does not have appropriate number of entries',
                        'input' => $input,
                        'fileNameDetails' => $fileDetails,
                        'fileName' => $fileName,
                    ]);
                    return ['success' => false];
                }
                $consolidatedDetails = $consolidatedDetails[0];

                $totalSettlementAmount = 0;
                $totalINRAmount = 0;
                foreach ($transactionLevelDetails as $transaction)
                {
                    $totalSettlementAmount += $transaction['settlement_amount'];
                    $totalINRAmount += ($transaction['settlement_amount'] * $transaction['exchange_rate']);
                }
                $formattedCreditAmount = number_format((float)$totalSettlementAmount, 2, '.', '') * 100;
                $formattedAmount = number_format((float)$totalINRAmount, 2, '.', '') * 100;

                $settlementId = $consolidatedDetails[self::OPGSP_TRAN_REF_NO];
                if (!isset($settlementId) or strlen($settlementId) !== SEntity::ID_LENGTH)
                {
                    $this->trace->info(TraceCode::OPGSP_REPATRIATION_INVALID_FILE, [
                        'message' => 'Settlement ID not found in the file',
                        'settlement_id' => $settlementId,
                        'input' => $input,
                        'fileNameDetails' => $fileDetails,
                        'fileName' => $fileName,
                    ]);
                    return ['success' => false];
                }
                $settlement = $this->repo->settlement->findOrFail($settlementId);

                if ($formattedAmount != $settlement->getAmount())
                {
                    $this->trace->info(TraceCode::OPGSP_REPATRIATION_INVALID_AMOUNT, [
                        'fileDetails' => $fileDetails,
                        'amount' => $formattedAmount,
                        'settlement' => $settlementId,
                    ]);
                    return ['success' => false];
                }

                $repatriationEntity = [];

                $repatriationEntity[RepatEntity::SETTLED_AT] = $settlement->getUpdatedAt();
                $repatriationEntity[RepatEntity::CURRENCY] = Currency::INR;
                $repatriationEntity[RepatEntity::CREDIT_CURRENCY] = $consolidatedDetails[self::REMITTANCE_CURRENCY];
                $repatriationEntity[RepatEntity::PARTNER_TRANSACTION_ID] = $transactionLevelDetails[0]['track_number'];
                $repatriationEntity[RepatEntity::AMOUNT] = $formattedAmount;
                $repatriationEntity[RepatEntity::CREDIT_AMOUNT] = $formattedCreditAmount;
                $repatriationEntity[RepatEntity::MERCHANT_ID] = $settlement->getMerchantId();
                $repatriationEntity[RepatEntity::UPDATED_AT] = time();
                $repatriationEntity[RepatEntity::SETTLEMENT_IDS] = [$settlementId];
                $repatriationEntity[RepatEntity::INTEGRATION_ENTITY] = $input['partner'];

                if ($repatriationEntity[RepatEntity::CREDIT_AMOUNT] > 0 &&
                    $repatriationEntity[RepatEntity::AMOUNT] > 0)
                {
                    $forexRate = $repatriationEntity[RepatEntity::CREDIT_AMOUNT] / $repatriationEntity[RepatEntity::AMOUNT];
                    $forexRateFormatted = number_format((float)$forexRate, 6, '.', '');
                    $repatriationEntity[RepatEntity::FOREX_RATE] = $forexRateFormatted;
                }
                else
                {
                    $this->trace->info(TraceCode::OPGSP_REPATRIATION_INVALID_AMOUNT, [
                        'fileDetails' => $fileDetails,
                        'amount' => $repatriationEntity[RepatEntity::AMOUNT],
                        'creditAmount' => $repatriationEntity[RepatEntity::CREDIT_AMOUNT],
                        'settlement' => $settlementId,
                    ]);
                    return ['success' => false];
                }

                $this->saveRepatriationDetails($repatriationEntity);
            }
            else
            {
                $this->trace->info(TraceCode::OPGSP_REPATRIATION_INVALID_FILE, $fileDetails);
                return ['success' => false];
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(
                TraceCode::OPGSP_REPATRIATION_FAILED, [
                'input' => $input,
                'error_message' => $e->getMessage(),
            ]);
            $this->trace->traceException($e);
            return ['success' => false];
        }

        $this->trace->info(TraceCode::OPGSP_REPATRIATION_SUCCESS, $fileDetails);
        return ['success' => true];
    }

    // To save repatriation details into DB
    protected function saveRepatriationDetails($repatriationEntity){

        try{
            (new RepatService())->createInternationalRepatriation($repatriationEntity);
        }catch (\Throwable $e){
            $this->trace->error(
                TraceCode::REPATRIATION_DETAIL_SAVE_FAILED,
                ['repatriationEntity' => $repatriationEntity]
            );
            $this->trace->traceException($e);
            throw new ServerErrorException(PublicErrorDescription::SERVER_ERROR, ErrorCode::REPO_FAILED_TO_SAVE);
        }
    }

    protected  function reconcileRepatriationAmount($amount, $distinctSettlementIds): bool
    {

        $settlementAmount = 0;
        foreach ($distinctSettlementIds as $settlementId){

            $settlement =  $this->repo->settlement->findOrFail($settlementId);

            $settlementAmount = $settlementAmount + $settlement[SEntity::AMOUNT];

        }

        $response = false;
        if ($amount == $settlementAmount){
            $response = true;
        }

        return $response;
    }

    protected function triggerFIRSAvailableNotification($document, $mode) 
    {
        try
        {
            // set the mode
            if (isset($this->app['rzp.mode']) === false)
            {
                $this->app['rzp.mode'] = $mode;
            }

            $data = [
                'document_id' => $document->getId(),
                'action'      => MerchantCrossborderEmail::FIRS_AVAILABLE_NOTIFICATION,
                'mode'        => $this->app['rzp.mode'],
            ];
            
            $this->trace->info(TraceCode::FIRS_SEND_EMAIL_MESSAGE_DISPATCHED,
                [
                    'data' => $data,
                ]
            );

            // adding delay of 1 to 10 minutes to distribute load
            MerchantCrossborderEmail::dispatch($data)->delay(rand(60,1000) % 601);

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::FIRS_SEND_EMAIL_MESSAGE_DISPATCH_FAILED,
                [
                    'document_id' => $document->getId()
                ]);
        }
    }
}
