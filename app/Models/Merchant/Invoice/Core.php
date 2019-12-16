<?php

namespace RZP\Models\Merchant\Invoice;

use File;

use App;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Report\Types\BankingInvoiceReport;
use RZP\Jobs\MerchantInvoice as MerchantInvoiceJob;
use RZP\Mail\Report\RazorpayX\MerchantBankingInvoice;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use RZP\Models\Merchant\Preferences as MerchantPreferences;
use RZP\Jobs\MerchantInvoiceCorrection as MerchantInvoiceCorrectionJob;

class Core extends Base\Core
{
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
        // When true, payment transactions will be considered from the month
        // specified by `$invoiceDate` where created and captured in same month
        // All other transaction will we considered on created date
        //
        $isCorrection = (isset($input['correction']) === true) ?
                        (bool) $input['correction'] :
                        false;

        //
        // in case merchant id is given in the request then dont have to spawn the k8s job
        // can directly queue the mid and generate the invoice
        //
        if (isset($input['merchant_ids']) === true)
        {
            $merchantIds = $input['merchant_ids'];

            $this->processMerchantInvoice($this->mode, $year, $month, $merchantIds, $isCorrection);
        }
        else
        {
            $year = (string) $year;

            $month = (string) $month;

            $this->app->k8s_client->createInvoiceJob($this->mode, $year, $month);
        }
    }

    public function queueCorrectionInvoiceInvoice(array $input)
    {
        $this->trace->info(TraceCode::MERCHANT_INVOICE_CORRECTION_REQUEST, $input);

        (new Validator)->validateInput('correction_queue', $input);

        $invoiceDate = Carbon::createFromDate(
            $input['year'],
            $input['month'],
            1,
            Timezone::IST);

        $merchantIds = [];

        if (isset($input['merchant_ids']) === true)
        {
            $merchantIds = $input['merchant_ids'];
        }

        $batch  = 10000;

        $offset = 0;

        $defaultDelay = 0;

        do
        {
            $merchantIds = $this->repo
                                ->merchant
                                ->fetchActivatedMerchantsBeforeTimestamp(
                                    $batch,
                                    $offset,
                                    $invoiceDate->endOfMonth()->timestamp,
                                    $merchantIds);

            $count = count($merchantIds);

            $offset += $count;

            foreach ($merchantIds as $merchantId)
            {
                MerchantInvoiceCorrectionJob::dispatch(
                                                $merchantId,
                                                $invoiceDate->month,
                                                $invoiceDate->year,
                                                $this->mode)
                                            // Assign a delay between 0 and 900 so that tasks are distributed
                                            // over 15 minute period
                                            ->delay($defaultDelay++ % 901);
            }
        } while ($count === $batch);
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

    public function createMultipleInvoiceEntities(array $input)
    {
        (new Validator)->validateInput('bulk_create', $input);

        foreach ($input['invoice_entities'] as $row)
        {
            $this->trace->info(TraceCode::MERCHANT_INVOICE_BULK_CREATE, $row);

            $row[Entity::TYPE] = Type::ADJUSTMENT;

            /** @var Merchant\Entity $merchant */
            $merchant = $this->repo->merchant->findOrFail($row[Entity::MERCHANT_ID]);

            unset($row[Entity::MERCHANT_ID]);
            // TODO: Accept balance_id in invoice_entities passed as input on route merchant_invoice_add_bulk
            //Jira link : https://razorpay.atlassian.net/browse/RX-612
            $this->create($row, $merchant, $merchant->primaryBalance);
        }
    }

    public function updateGstinForInvoice(array $input, Merchant\Entity $merchant): int
    {
        $currentGstin = $merchant->getGstin();

        $invoiceNumber = trim($input[Entity::INVOICE_NUMBER]);

        $merchantId = $merchant->getId();

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

        return $count;
    }


    public function generateInvoiceReport($input)
    {
        $data = (new BankingInvoiceReport)->getInvoiceReport($input);

        $invoiceEntity = (new Repository)->findByIdAndMerchantId($data[Entity::ID], $this->merchant->getId());

        $pathToTemporaryFile = (new PdfGenerator)->generate($data);

        $fileAccessUrl = $this->uploadViaUfh($pathToTemporaryFile, $invoiceEntity);

        return [
            $fileAccessUrl,
            $data,
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

        $object = new UploadedFile($path, $originalName, $mimeType, $size, $error, $test);

        return $object;
    }

    protected function getDashboardFileAccessUrl(string $fileId = null)
    {
        return sprintf(self::DASHBOARD_FILE_URL, $this->config['applications.dashboard.url'], $fileId);
    }

    public function processMerchantInvoice($mode, $year, $month, $merchantIds = [], $isCorrection = false)
    {
        //
        // merchant_ids_excluded is an array of merchant ids coming from input,
        // for which invoice shouldn't be generated.
        //
        $merchantIdsExcluded = MerchantPreferences::NO_MERCHANT_INVOICE_MIDS;

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_CREATE_REQUEST,
            [
                'month'                 => $month,
                'year'                  => $year,
                'is_correction'         => $isCorrection,
                'merchant_ids'          => $merchantIds,
                'merchant_ids_excluded' => $merchantIdsExcluded,
                'mode'                  => $mode
            ]);

        $endTimestamp =  Carbon::createFromDate($year, $month, 1, Timezone::IST)
                                ->endOfMonth()
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
                                            $mode,
                                            $isCorrection)
                                            // Assign a delay between 0 & 900 so that tasks are distributed over 15 minute period
                                            ->delay($i++ % 901);
            }
        } while($batch === $count);

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_DISPATCH_COUNT,
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
     */
    public function verify(int $year, int $month): array
    {
        $result = $this->repo->merchant_invoice->verify($year, $month);

        if ($result->isEmpty() === true)
        {
            return [];
        }

        $this->trace->error(
            TraceCode::MERCHANT_INVOICE_CREATION_SKIPPED,
            [
                'count'        => $result->count(),
                'merchant_ids' => $result->toArray(),
            ]);

        (new SlackNotification)->send(
            'merchant_invoice_alert',
            [
                'total_invoice_skipped' => $result->count(),
            ],
            null,
            $result->count());

        return $result->toArray();
    }
}
