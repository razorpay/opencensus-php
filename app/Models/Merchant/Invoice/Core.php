<?php

namespace RZP\Models\Merchant\Invoice;

use App;
use File;
use Mail;

use Carbon\Carbon;
use Monolog\Logger;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Services\UfhService;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\FeeModel;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Merchant\Balance;
use RZP\Jobs\EInvoice\XEInvoice;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice\EInvoice;
use RZP\Models\Report\Types\InvoiceReport;
use RZP\Models\BankingAccount\AccountType;
use RZP\Jobs\AdjustmentInvoiceEntityCreate;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Report\Types\BankingInvoiceReport;
use RZP\Jobs\EInvoice\PgEInvoice as PgEInvoiceJob;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Jobs\MerchantInvoice as MerchantInvoiceJob;
use RZP\Models\Merchant\Invoice\EInvoice\PgEInvoice;
use RZP\Mail\Report\RazorpayX\MerchantBankingInvoice;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\Merchant\Invoice\EInvoice\DocumentTypes;
use RZP\Models\Merchant\Preferences as MerchantPreferences;
use RZP\Mail\Merchant\MerchantInvoiceExecutionReport as MerchantInvoiceExecutionReport;

class Core extends Base\Core
{
    use FileHandlerTrait;

    const STORE_TYPE = 'file';

    const DASHBOARD_FILE_URL = '%sufh/file/%s';

    public function create(array $input, Merchant\Entity $merchant, Balance\Entity $balance = null): Entity
    {
        $invoiceEntity = new Entity;

        $invoiceEntity->merchant()->associate($merchant);

        $invoiceEntity->build($input);

        // TODO: Balance should be sent from every caller of this function.
        // Remove `null` default in function argument.
        if (empty($balance) === true)
        {
            $balance = $merchant->primaryBalance;
        }

        $invoiceEntity->balance()->associate($balance);

        $balanceType = $balance->getType();

        $invoiceEntity->generateInvoiceNumber($input['month'], $input['year'], $balanceType);

        $this->repo->saveOrFail($invoiceEntity);

        return $invoiceEntity;
    }

    public function queueCreateInvoiceEntities(array $input)
    {
        RuntimeManager::setMaxExecTime(900);

        (new Validator)->validateInput('create_queue', $input);

        $previousMonth = Carbon::now(Timezone::IST)->subMonth();

        $year  = $previousMonth->year;

        $month = $previousMonth->month;

        if ((isset($input['month']) === true) and (isset($input['year']) === true))
        {
            $year  = $input['year'];

            $month =  $input['month'];
        }

        //
        // in case merchant id is given in the request then dont have to spawn the k8s job
        // can directly queue the mid and generate the invoice
        //
        if (isset($input['merchant_ids']) === true)
        {
            $merchantIds = $input['merchant_ids'];

            $this->processMerchantInvoice($this->mode, $year, $month, $merchantIds);
        }
        else
        {
            $year = (string) $year;

            $month = (string) $month;

            $this->app->k8s_client->createInvoiceJob($this->mode, $year, $month);
        }
    }

    public function createAdjustmentInvoiceEntity(Adjustment\Entity $adjustment, array $input): Entity
    {
        $currentDate = Carbon::now(Timezone::IST);

        $merchant = $adjustment->merchant;

        $gstin = $merchant->getGstin();

        $params = [
            Entity::MONTH           => $currentDate->month,
            Entity::YEAR            => $currentDate->year,
            Entity::GSTIN           => $gstin,
            Entity::TYPE            => Type::ADJUSTMENT,
            Entity::DESCRIPTION     => $input[Entity::DESCRIPTION],
            Entity::AMOUNT          => ($input[Entity::AMOUNT] ?? 0),
            Entity::TAX             => ($input[Entity::TAX] ?? 0),
        ];

        $invoiceEntity = $this->create($params, $merchant, $adjustment->balance);

        return $invoiceEntity;
    }

    public function dispatchForAdjustmentInvoiceEntityCreate(array $input)
    {
        (new Validator)->validateInput('bulk_create', $input);

        $force = (bool) $input['force'];

        $mode = $this->mode;

        $successCount = 0;

        $failureCount = 0;

        $totalCount = 0;

        foreach ($input['invoice_entities'] as $row)
        {
            $totalCount++;

            try
            {
                $balanceId = (isset($row[Entity::BALANCE_ID]) === true) ? $row[Entity::BALANCE_ID] : "";

                AdjustmentInvoiceEntityCreate::dispatch($row[Entity::MERCHANT_ID], $row[Entity::MONTH], $row[Entity::YEAR],
                    $row[Entity::AMOUNT], $row[Entity::TAX], $row[Entity::DESCRIPTION],
                    $balanceId, $force, $mode);

                $successCount++;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::ADJUSTMENT_INVOICE_ENTITY_CREATION_SKIPPED,
                    [
                        'merchant'    => $row[Entity::MERCHANT_ID],
                        'month'       => $row[Entity::MONTH],
                        'year'        => $row[Entity::YEAR],
                    ]);

                $failureCount++;
            }
        }

        $this->trace->info(
            TraceCode::ADJUSTMENT_INVOICE_ENTITY_CREATION_RESPONSE,
            [
                'success_count'  => $successCount,
                'failure_count'  => $failureCount,
                'total_count'    => $totalCount
            ]);
    }

    public function createMultipleInvoiceEntities(string $merchantId, int $month, int $year,
                                                  int $amount, int $tax, string $description,
                                                  string $balanceId = null, bool $force = false)
    {
        if ($force === false)
        {
            $existingAdjustmentInvoices = $this->repo
                                               ->merchant_invoice
                                               ->fetchDataOfTypeAdjustmentInvoice($merchantId, $month, $year);

            if ($existingAdjustmentInvoices->count() > 0)
            {
                 $this->trace->info(
                     TraceCode::ADJUSTMENT_INVOICE_ENTITY_CREATION_SKIPPED,
                     [
                         'merchant'    => $merchantId,
                         'month'       => $month,
                         'year'        => $year,
                     ]);

                 return;
            }
        }

        /** @var Merchant\Entity $merchant */
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);

        $type = Type::ADJUSTMENT;

        $gstin = $merchant->getGstin();

        $balance = $merchant->primaryBalance;

        if (empty($balanceId) === false)
        {
            $balance = $this->repo->balance->findOrFailById($balanceId);
        }

        $params = [
            Entity::MONTH           => $month,
            Entity::YEAR            => $year,
            Entity::TYPE            => $type,
            Entity::GSTIN           => $gstin,
            Entity::AMOUNT          => $amount,
            Entity::TAX             => (int) round($amount * $tax/100),
            Entity::DESCRIPTION     => $description
        ];

        $this->trace->info(
            TraceCode::ADJUSTMENT_INVOICE_ENTITY_CREATION_REQUEST,
            [
                'merchant'       => $merchantId,
                'balance_id'     => $balance->getId(),
                'params'         => $params,
            ]);

        $this->create($params, $merchant, $balance);

    }

    public function updateGstinForInvoice(array $input, Merchant\Entity $merchant): int
    {
        $currentGstin = $merchant->getGstin();

        $invoiceNumber = trim($input[Entity::INVOICE_NUMBER]);

        $merchantId = $merchant->getId();

        $this->trace->info(TraceCode::INVOICE_GSTIN_UPDATE_REQUEST, [
            'merchant_id'  => $merchantId,
            'invoice_number' => $invoiceNumber,
            'current_gstin' => $currentGstin,
        ]);

        $entities = $this->repo->merchant_invoice->fetchByInvoiceNumber($merchantId, $invoiceNumber);

        $count = $entities->count();

        if ($count === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_MERCHANT_INVOICE_NUMBER,
                null,
                [
                    'merchant_id' => $merchantId,
                    'invoice_number' => $invoiceNumber
                ]);
        }

        $this->repo->transaction(function() use ($entities, $currentGstin)
        {
            foreach ($entities as $entity)
            {
                $oldEntity = clone($entity);
                $entity->setGstin($currentGstin);

                $this->app['workflow']
                    ->setEntityAndId($entity->getEntity(), $entity->getId())
                    ->handle($oldEntity, $entity);

                $this->repo->saveOrFail($entity);
            }
        });

        (new PgEInvoice())->updateGstinForEinvoice($merchantId, $invoiceNumber, $currentGstin);

        return $count;
    }

    public function generateInvoiceReport($input)
    {
        $data = (new BankingInvoiceReport)->getInvoiceReport($input);

        $invoiceEntity = (new Repository)->findByIdAndMerchantId($data[Entity::ID], $this->merchant->getId());

        $XEInvoiceCore = (new Merchant\Invoice\EInvoice\XEInvoice());
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($this->merchant->getId(), ['merchantDetail']);
        $date = Carbon::createFromDate($input[Entity::YEAR], $input[Entity::MONTH], 1, Timezone::IST);

        if($XEInvoiceCore->shouldGenerateEInvoice($merchant, $date->getTimestamp()) === true)
        {
            $data[BankingInvoiceReport::E_INVOICE_DETAILS] = $XEInvoiceCore->getEInvoiceDataForPdf($this->merchant->getId(),
                $input[Entity::MONTH], $input[Entity::YEAR], EInvoice\Types::BANKING);
        }

        $hasTaxInvoice = isset($data[BankingInvoiceReport::ROWS][EInvoice\DocumentTypes::INV]);

        $hasCreditNote = isset($data[BankingInvoiceReport::ROWS][EInvoice\DocumentTypes::CRN]);

        if ($hasCreditNote && $hasTaxInvoice)
        {
           $hasEInvoiceData = !empty($data[BankingInvoiceReport::E_INVOICE_DETAILS][DocumentTypes::CRN]);

           if ($hasEInvoiceData === false)
           {
               $invoiceAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::INV]
               [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

               $creditNoteAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::CRN]
               [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

               if ($creditNoteAmount > $invoiceAmount)
               {
                  unset($data[BankingInvoiceReport::ROWS][EInvoice\DocumentTypes::CRN]);
                  $this->trace->info(TraceCode::EINVOICE_REQUIRED_FOR_X, [
                       'merchant_id'  => $this->merchant->getId(),
                       'month' => $input['month'],
                       'year' => $input['year'],
                   ]);
               }
           }
        }

        if(($input['month'] >= 7 and $input['year'] >= 2021) or ($input['year'] >= 2022))
        {
            foreach($data[BankingInvoiceReport::ROWS] as $type => $lineItem)
            {
                $accounts = array_except($lineItem, BankingInvoiceReport::COMBINED);

                $virtualAccountInvoiceAmount = 0;
                $rblAccountInvoiceAmount = 0;

                foreach ($accounts as $account => $attributes)
                {
                    if($attributes[BankingInvoiceReport::ACCOUNT_TYPE] === Balance\AccountType::DIRECT
                        and $attributes[BankingInvoiceReport::CHANNEL] === Balance\Channel::RBL)
                    {
                        if($attributes[BankingInvoiceReport::AMOUNT] > $rblAccountInvoiceAmount)
                        {
                            $rblAccountInvoiceAmount = $attributes[Entity::AMOUNT];
                        }
                    }
                    else if($attributes[BankingInvoiceReport::ACCOUNT_TYPE] === Balance\AccountType::SHARED)
                    {
                        if($attributes[BankingInvoiceReport::AMOUNT] > $virtualAccountInvoiceAmount)
                        {
                            $virtualAccountInvoiceAmount = $attributes[Entity::AMOUNT];
                        }
                    }
                }

                if($rblAccountInvoiceAmount > 0)
                {
                    if($virtualAccountInvoiceAmount === 0){
                        $data[BankingInvoiceReport::ROWS][$type][BankingInvoiceReport::SELLER_ENTITY] = 'RSPL';
                    }
                    else{
                        return [
                            null,
                            $data,
                            'Error:PDF not generated',
                        ];
                    }
                }
                else{
                    $data[BankingInvoiceReport::ROWS][$type][BankingInvoiceReport::SELLER_ENTITY] = 'RZPL';
                }
            }
        }
        else
        {
            foreach($data[BankingInvoiceReport::ROWS] as $type => $lineItem)
            {
                $data[BankingInvoiceReport::ROWS][$type][BankingInvoiceReport::SELLER_ENTITY] = 'RSPL';
            }
        }

        $pathToTemporaryFile = (new PdfGenerator)->generateBankingInvoice($data);

        $fileAccessUrl = $this->uploadViaUfh($pathToTemporaryFile, $invoiceEntity);

        return [
            $fileAccessUrl,
            $data,
            null,
        ];
    }

    public function sendInvoiceEmail($fileId, $data , $emailAddresses)
    {
        $fileAccessUrl = $this->getDashboardFileAccessUrl($fileId);

        $this->trace->info(
            TraceCode::MERCHANT_BANKING_INVOICE_EMAIL_SEND_REQUEST,
            [
                'file_id'             =>  $fileId,
                'file_access_url'     =>  $fileAccessUrl,
                'data'                =>  $data,
                'email_addresses'     =>  $emailAddresses,
            ]);


        $mailable = new MerchantBankingInvoice($fileAccessUrl,
                                               $data,
                                               $emailAddresses);

        \Mail::queue($mailable);
    }

    protected function uploadViaUfh(string $pathToTemporaryFile, Entity $entity)
    {
        $ufhService = $this->app['ufh.service'];

        $uploadedFileInstance = $this->getUploadedFileInstance($pathToTemporaryFile);

        $response = $ufhService->uploadFileAndGetUrl($uploadedFileInstance,
                                                     $name = File::name($pathToTemporaryFile),
                                                     self::STORE_TYPE,
                                                     $entity);

        $this->trace->info(
            TraceCode::UFH_RESPONSE,
            [
                'merchant_invoice_id'   => $entity->getId(),
                'ufh_response'          => $response,
            ]);

        return $response;
    }

    protected function getUploadedFileInstance(string $path)
    {
        $name = File::name($path);

        $extension = File::extension($path);

        $originalName = $name . '.' . $extension;

        $mimeType = File::mimeType($path);

        $size = File::size($path);

        $error = null;

        // Setting as Test, because UploadedFile expects the file instance to be a temporary uploaded file, and
        // reads from Local Path only in test mode. As our requirement is to always read from local path, so
        // creating the UploadedFile instance in test mode.

        $test = true;

        $object = new UploadedFile($path, $originalName, $mimeType, $error, $test);

        return $object;
    }

    protected function getDashboardFileAccessUrl(string $fileId = null)
    {
        return sprintf(self::DASHBOARD_FILE_URL, $this->config['applications.dashboard.url'], $fileId);
    }

    public function processMerchantInvoice($mode, $year, $month, $merchantIds = [])
    {
        //
        // merchant_ids_excluded is an array of merchant ids coming from input,
        // for which invoice shouldn't be generated.
        //
        $redis = $this->app->redis->Connection('mutex_redis');

        $merchantIdsExcluded = $redis->LRANGE(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, 0, -1);

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_CREATE_REQUEST,
            [
                'month'                 => $month,
                'year'                  => $year,
                'merchant_ids'          => $merchantIds,
                'merchant_ids_excluded' => $merchantIdsExcluded,
                'mode'                  => $mode
            ]);

        $endTimestamp =  $this->getPatchedLastDay($month, $year)
                                ->getTimestamp();

        $batch = 10000;

        $skip = 0;

        $i = 0;

        do
        {
            $merchantIdsToEnqueue = $this->repo
                                         ->merchant
                                         ->fetchActivatedMerchantsBeforeTimestamp(
                                             $batch,
                                             $skip,
                                             $endTimestamp,
                                             $merchantIds,
                                             $merchantIdsExcluded);

            $count = count($merchantIdsToEnqueue);

            $skip += $count;

            foreach ($merchantIdsToEnqueue as $merchantId)
            {
                MerchantInvoiceJob::dispatch(
                    $merchantId,
                    $month,
                    $year,
                    $mode)
                    // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                    ->delay($i++ % 901);
            }
        } while($batch === $count);

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_DISPATCH_COUNT,
            [
                'count' => $skip,
            ]);


        $skip = 0;
        $i = 0;

        do{
            $merchantIdsWithCaRblActivated = $this->repo
                ->banking_account
                ->fetchMerchantsWithCaRblAccount(
                    $batch,
                    $skip,
                    \RZP\Models\BankingAccount\Channel::RBL,
                    AccountType::CURRENT,
                    $merchantIds,
                    $merchantIdsExcluded);

            $count = count($merchantIdsWithCaRblActivated);

            $skip += $count;

            foreach($merchantIdsWithCaRblActivated as $merchantId){
                MerchantInvoiceJob::dispatch(
                    $merchantId,
                    $month,
                    $year,
                    $mode)
                    // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                    ->delay($i++ % 901);
            }

        }while($batch === $count);

        $this->trace->info(
            TraceCode::MERCHANT_NOT_ACTIVATED_DISPATCH_COUNT,
            [
                'count' => $skip,
            ]);
    }

    /**
     * verify if the invoice is generated correctly for all the eligible merchant
     * else raise an slack alert and log the missing ids
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function verify(int $year, int $month): array
    {
        [$result, $activeMerchants] = $this->repo->merchant_invoice->verify($year, $month);

        if ($result->isEmpty() === true)
        {
            return [];
        }

        $this->trace->error(
            TraceCode::MERCHANT_INVOICE_CREATION_SKIPPED,
            [
                'count'        => $result->count(),
                'merchant_ids' => $result->getIds(),
            ]);

        (new SlackNotification)->send(
            'merchant_invoice_alert',
            [
                'total_invoice_skipped' => $result->count(),
            ],
            null,
            $result->count());

        $merchantIds = [];

        $result->each(
            function($merchant) use (& $merchantIds)
            {
                $merchantIds[] = [ 'Merchant_id' => $merchant->getId()];
            });

        $totalInvoiceCreated = $activeMerchants - $result->count();

        try
        {
            $fileName = $this->createCsvFile($merchantIds, 'merchant_invoice_summary_' . $month . '_' . $year,
                                        null, 'files/report');

            $data = [
                'month'                    => $month,
                'year'                     => $year,
                'total_merchants'          => $activeMerchants,
                'total_invoice_created'    => $totalInvoiceCreated,
                'total_invoice_skipped'    => $result->count(),
                'attachment'               => $fileName,
            ];

            $email = new MerchantInvoiceExecutionReport($data);

            Mail::send($email);

            unlink($fileName); // nosemgrep : php.lang.security.unlink-use.unlink-use

            return $result->getIds();
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR
            );

            throw $e;
        }
    }

    protected function getPgDocumentCount($documentData) : int
    {
        $documentCount = 0;
        foreach ($documentData as $documentType => $data)
        {
            if(sizeof($data) !== 0)
            {
                $documentCount++;
            }
        }

        return $documentCount;
    }

    public function dispatchForPgEInvoice($data, $month, $year, $merchantId)
    {
        $params = [
            EInvoice\Entity::MONTH          => $month,
            EInvoice\Entity::YEAR           => $year,
            EInvoice\Entity::GSTIN          => $data['gstin'],
            EInvoice\Entity::INVOICE_NUMBER => $data['invoice_number'],
        ];

        $documentData = $data[InvoiceReport::PAGES];

        $documentCount = $this->getPgDocumentCount($documentData);

        PgEInvoiceJob::dispatch($this->mode, $merchantId, $documentCount,
            $data[InvoiceReport::PAGES], $params);

        $this->trace->info(TraceCode::EINVOICE_JOB_DISPATCH_FOR_PG,
            [
                'merchant_id'   => $merchantId,
                'params'        => $params,
            ]);
    }

    public function dispatchForXEInvoice($data, $month, $year, $merchantId)
    {
        $params = [
            Einvoice\Entity::MONTH          => $month,
            Einvoice\Entity::YEAR           => $year,
            Einvoice\Entity::GSTIN          => $data['gstin'],
            Einvoice\Entity::INVOICE_NUMBER => $data['invoice_number'],
        ];

        foreach($data[BankingInvoiceReport::ROWS] as $type => $lineItems)
        {
            if(!$this->hasNonzeroLineItem($lineItems))
            {
                unset($data[BankingInvoiceReport::ROWS][$type]);
            }
        }

        XEInvoice::dispatch($this->mode, $merchantId,$data[BankingInvoiceReport::ROWS], $params);
    }

    public function hasNonzeroLineItem($lineItems)
    {
        if($lineItems[BankingInvoiceReport::COMBINED][BankingInvoiceReport::AMOUNT] === 0 and
            $lineItems[BankingInvoiceReport::COMBINED][BankingInvoiceReport::TAX_TOTAL] === 0)
        {
            return false;
        }
        return true;
    }

    public function getPgInvoiceData($merchant, $month, $year, $invoiceBreakup) : array
    {
        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);

        $isGstApplicable = Calculator\Tax\IN\Utils::isGstApplicable($date->getTimestamp());

        $input = [
            'month'           => $month,
            'year'            => $year,
            'gst_applicable'  => $isGstApplicable,
            'merchant_id'     => $merchant->getId(),
        ];

        $data = (new InvoiceReport())->getpgInvoiceTemplateDate($input, $merchant, $invoiceBreakup);

        return [$date, $isGstApplicable, $data];
    }

    public function getPgInvoiceBreakupGroupedData($input, $merchant)
    {
        $invoiceBreakup = $this->repo
                               ->merchant_invoice
                               ->fetchInvoiceReportData($merchant->getId(), $input['month'], $input['year']);

        [$date, $isGstApplicable, $data] = $this->getPgInvoiceData($merchant, $input['month'], $input['year'], $invoiceBreakup);

        return $data;
    }

    public function getTemplateDataForPgInvoice($merchant, $month, $year, $invoiceBreakup, $eInvoiceData = []): array
    {
        [$date, $isGstApplicable, $data] = $this->getPgInvoiceData($merchant, $month, $year, $invoiceBreakup);

        $data['merchant'] = $merchant;

        $data['merchant_id'] = $merchant->getId();

        $data['dates'] = [
                'startDate'   => $this->getPatchedFirstDay($month, $year)->format('d/m/y'),
                'billingDate' => $this->getPatchedLastDay($month, $year)->format('d/m/y'),
                'endDate'     => $this->getPatchedLastDay($month, $year)->format('d/m/y'),
        ];

        $data['invoice_id'] = $merchant->getId() . '/' . $date->addMonth()->format('m/y');

        $merchantDetails = $merchant->merchantDetail;

        $merchantDetails['isYesBankMerchant'] = $this->isYesBankMerchant($merchantDetails);

        $merchantDetails['submitted'] = (int) ($merchantDetails['submitted'] ?? 0);

        $merchantDetails['locked'] = (int) ($merchantDetails['locked'] ?? 0);

        $merchantDetails['activated'] = (int) ($merchantDetails['activated'] ?? 0);

        $merchantDetails['activation_flow'] = $merchantDetails['activation_flow'] ?? null;

        $gst = (empty($merchantDetails['gstin']) === false) ? $merchantDetails['gstin'] :
            ((empty($merchantDetails['p_gstin']) === false) ? $merchantDetails['p_gstin'] : '');

        $data['gst'] = $gst;
        $data['isGstApplicable'] = $isGstApplicable;

        $data['merchant_details'] = $merchantDetails;

        $state_code = $data['merchant_details']['business_registered_state'];
        if (empty($state_code) === false)
        {
            $data['merchant_details']['business_registered_state'] = IndianStates::getStateNameByCode($state_code);
        }

        $data['einvoice_data'] = $eInvoiceData;

        $feeModel = $merchant->getFeeModel();

        if(empty($feeModel) === false)
        {
            $data['is_postpaid'] = ($feeModel === FeeModel::POSTPAID);
        }

        return $data;
    }

    public function getXEInvoiceData($month, $year, $merchant)
    {
        $input = [
            'month'           => $month,
            'year'            => $year,
        ];

        return (new BankingInvoiceReport())->getInvoiceReportForEInvoice($input, $merchant);
    }

    public function isYesBankMerchant($merchantDetails)
    {
        $ifscCode = $merchantDetails['bank_branch_ifsc'];

        $yesIfsc = substr( $ifscCode, 0, 4 );

        return ((strcasecmp($yesIfsc, "YESB") === 0) === true) ;
    }

    public function getSignedUrlForPgInvoice($year, $month, $merchantId)
    {
        $name = (new PdfGenerator())->getNameForMerchantPgInvoice($year, $month, $merchantId);

        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);

        $shouldGeneratePgEInvoice = (new PgEInvoice())->shouldGenerateEInvoice($this->merchant, $date->getTimestamp());

        $shouldGenerateRevisedPgInvoice = (new PgEInvoice())->shouldGenerateRevisedInvoice($this->merchant->getId(), $month, $year);

        if($shouldGenerateRevisedPgInvoice === true)
        {
            $name = (new PdfGenerator())->getNameForMerchantPgRevisedInvoice($year, $month, $merchantId);
        }

        $file = $this->repo
                     ->file_store
                     ->getFileWithNameAndMerchantIdAndName($merchantId, $name, FileStore\Type::MERCHANT_INVOICE);

        if (empty($file) === true)
        {
            if(($shouldGeneratePgEInvoice === true) or ($shouldGenerateRevisedPgInvoice === true))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Razorpay was unable to generate an invoice either due to incorrect GSTIN and/or Address PIN or due to some technical error. Please try again in some time.');
            }

            $invoiceBreakup = $this->repo
                                   ->merchant_invoice
                                   ->fetchInvoiceReportData($merchantId, $month, $year);

            $file = (new PdfGenerator())->generatePgInvoice($merchantId, $month, $year, $invoiceBreakup);
        }

        return (new FileStore\Accessor())->getSignedUrlOfFile($file);
    }

    private function getPatchedFirstDay($month, $year)
    {
        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);
        if ($month == 01 and $year == 2021) {
            return $date->firstOfMonth()->subDays(1)->startOfDay();
        }
        else {
            return $date;
        }
    }

    private function getPatchedLastDay($month, $year)
    {
        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);
        if ($month == 12 and $year == 2020) {
            return $date->lastOfMonth()->subDays(1)->endOfDay();
        }
        else {
            return $date->endOfMonth();
        }
    }

    public function createPgMerchantInvoicePdf($merchantIds, $month, $year)
    {
        $result = [
            'success_mids' => [],
            'failed_mids'  => []
        ];

        foreach ($merchantIds as $merchantId)
        {
            $name = (new PdfGenerator)->getNameForMerchantPgInvoice($year, $month, $merchantId);

            $file = $this->repo
                ->file_store
                ->getFileWithNameAndMerchantIdAndName($merchantId, $name, FileStore\Type::MERCHANT_INVOICE);

            if (empty($file) == false)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_INVOICE_PDF_CREATION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'year'        => $year,
                        'month'       => $month,
                        'reason'      => 'merchant invoice file store entry is already present'
                    ]);

                $result['failed_mids'][] = $merchantId;
            }
            else
            {
                try
                {
                    $invoiceBreakup = $this->repo
                        ->merchant_invoice
                        ->fetchInvoiceReportData($merchantId, $month, $year);

                    (new PdfGenerator)->generatePgInvoice($merchantId, $month, $year, $invoiceBreakup);

                    $result['success_mids'][] = $merchantId;
                }
                catch (\Throwable $e)
                {
                    $result['failed_mids'][] = $merchantId;

                    $this->trace->traceException(
                        $e,
                        null,
                        TraceCode::MERCHANT_INVOICE_PDF_CREATION_FAILED,
                        [
                            'merchant_id' => $merchantId,
                            'year'        => $year,
                            'month'       => $month,
                            'reason'      => $e->getMessage(),
                        ]);
                }
            }
        }

        return $result;
    }
}
