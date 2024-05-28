<?php

namespace RZP\Models\Merchant\Invoice;

use Carbon\Carbon;

use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Exception\BaseException;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestException;
use RZP\Http\Request\Requests;
use RZP\Http\Response\StatusCode;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\UfhService;
use RZP\Models\Merchant\Balance;
use RZP\Jobs\MerchantInvoiceBackFill;
use RZP\Models\Merchant\Invoice\EInvoice;
use RZP\Models\Base\Traits\ProcessAccountNumber;
use RZP\Models\Merchant\Invoice\EInvoice\PgEInvoice;
use RZP\Models\Feature;

class Service extends Base\Service
{
    use ProcessAccountNumber;

    public function createInvoiceEntities(array $input)
    {
        return (new Core)->queueCreateInvoiceEntities($input);
    }

    public function createMultipleInvoiceEntities(array $input)
    {
        (new Core)->dispatchForAdjustmentInvoiceEntityCreate($input);
    }

    public function updateGstin(string $merchantId, array $input): array
    {
        (new Validator)->validateInput('edit_gstin', $input);

        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);

        $count = (new Core)->updateGstinForInvoice($input, $merchant);

        return ['count' => $count];
    }

    public function requestBankingInvoice(array $input)
    {
        (new Validator)->validateInput(Validator::BANKING_INVOICE_GENERATE, $input);

        $sendEmail = array_pull($input, Entity::SEND_EMAIL);

        $emailAddresses = array_pull($input, Entity::TO_EMAILS);

        list($ufhResponse, $data, $error) = $this->core()->generateInvoiceReport($input);

        $data = array_merge($data, $input);

        if($error != null)
        {
            return [
                'file_id' => null,
                'error_message' => $error,
            ];
        }

        if (boolval($sendEmail) === true)
        {
            $emailAddresses = $emailAddresses ?? $this->merchant->getEmail();

            $this->core()->sendInvoiceEmail($ufhResponse[UfhService::FILE_ID],
                                            $data,
                                            $emailAddresses);
            return [
                'file_id' => null,
            ];
        }
        else
        {
            return [
                'file_id' => $ufhResponse[UfhService::FILE_ID],
            ];
        }
    }

    public function adminActions(array $input)
    {
        $action = $input['action'];

        if ($action === 'updateEInvoiceLineItem')
        {
            $this->updateQrCodeUrlForMerchantEinvoice($input);
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::INVALID_ACTION);
        }
    }

    public function updateQrCodeUrlForMerchantEinvoice(array $input)
    {
        $XEInvoiceCore = (new EInvoice\XEInvoice());

        $XEInvoiceCore->updateQrCodeUrlForMerchantEinvoice($input);
    }

    public function fetchMultipleBankingInvoices(array $input)
    {
        $this->merchant->getValidator()->validateBusinessBankingActivated();

        $input[Entity::TYPE] = Type::RX_TRANSACTIONS;
        if ($this->isChargeCollectionsInvoicingExptEnabledForX($this->merchant->getId()))
        {
            $input[Entity::TYPE] = [Type::RX_TRANSACTIONS, Type::X_CHARGE_COLLECTIONS];
        }
        $invoices = $this->repo->merchant_invoice->fetch($input, $this->merchant->getId());

        $invoices = $invoices->toArrayPublic();

        $invoices = $this->getInvoicesGroupedByMonthAndYear($invoices);

        return [
            'entity' => 'collection',
            'count'  => count($invoices),
            'items'  => $invoices,
        ];
    }

    public function verify(array $input)
    {
        (new Validator)->validateInput(Validator::VERIFY, $input);

        $previousMonthTimestamp = Carbon::now(Timezone::IST)->subMonth(1);

        $year = $input['year'] ?? $previousMonthTimestamp->year;

        $month = $input['month'] ?? $previousMonthTimestamp->month;

        $data = (new Core)->verify($year, $month);

        return $data;
    }

    protected function getInvoicesGroupedByMonthAndYear(array $invoices): array
    {
        $template = [];

        $invoiceData = [];

        foreach ($invoices['items'] as $invoice)
        {
            $month = $invoice[Entity::MONTH];
            $year  = $invoice[Entity::YEAR];

            $template[$year][$month] = [
                Entity::AMOUNT => 0,
                Entity::TAX    => 0,
            ];
        }

        foreach ($invoices['items'] as $invoice)
        {
            $month = $invoice[Entity::MONTH];
            $year  = $invoice[Entity::YEAR];

            $template[$year][$month][Entity::AMOUNT] += $invoice[Entity::AMOUNT];
            $template[$year][$month][Entity::TAX] += $invoice[Entity::TAX];
        }

        foreach ($template as $year => $monthlyData)
        {
            foreach ($monthlyData as $month => $data)
            {
                $invoiceData[] = [
                    Entity::MONTH  => $month,
                    Entity::YEAR   => $year,
                    Entity::AMOUNT => $data[Entity::AMOUNT],
                    Entity::TAX    => $data[Entity::TAX],
                ];
            }
        }

        return $invoiceData;
    }

    public function pdfControl($input)
    {
        $strictB2c = false;

        if((isset($input['strict_b2c']) === true) and ($input['strict_b2c'] === '1'))
        {
            $strictB2c = true;
        }

        (new Validator())->validateInput('pdf_control', $input);

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_PDF_CONTROL_REQUEST,
            [
               'input' => $input
            ]);

        $result = [];

        switch ($input['action']){
            case Constants::ACTION_BACKFILL:
                $result = $this->backFillMerchantInvoiceB2cPDFs($input['merchant_ids'], $input['from_month'],
                    $input['from_year'], $input['to_year'], $input['to_month']);
                break;
            case Constants::ACTION_CREATE:
                $result =  $this->createPgMerchantInvoicePdf($input['merchant_ids'], $input['month'],
                    $input['year'], $strictB2c);
                break;
            case Constants::ACTION_DELETE:
                $result = $this->removeMerchantInvoicePdf($input['merchant_ids'], $input['month'], $input['year']);
                break;
        }

        return $result;
    }

    // This method is used for backfilling(storing in S3) the PG merchant invoices PDFs for the months on or before Dec-2020.
    public function backFillMerchantInvoiceB2cPDFs($merchantIds, $fromMonth, $fromYear, $toYear, $toMonth)
    {
        $fromDate = Carbon::createFromDate($fromYear, $fromMonth, 1, Timezone::IST);
        $toDate = Carbon::createFromDate($toYear, $toMonth, 1, Timezone::IST);

        $result = [];

        while($fromDate <= $toDate)
        {
            $month = $fromDate->month;
            $year  = $fromDate->year;
            $index  = $month.'-'.$year;

            $result[$index] = [
                'success_count' => 0,
                'failed_count' => 0,
            ];

            foreach ($merchantIds as $merchantId)
            {
                try
                {
                    MerchantInvoiceBackFill::dispatch($merchantId, $month, $year, $this->mode);

                    $result[$index]['success_count'] += 1;
                }
                catch (\Throwable $e)
                {
                    $result[$index]['failed_count'] += 1;

                    $this->trace->traceException(
                        $e,
                        TraceCode::MERCHANT_BACK_FILL_PG_INVOICE_DISPATCH_FAILED,
                        [
                            'merchant_id'   => $merchantId,
                            'month'         => $month,
                            'year'          => $year,
                        ]
                    );
                }
            }

            $fromDate->addMonth();
        }

        return $result;
    }

    public function createPgMerchantInvoicePdf($merchantIds, $month, $year, $strictB2c = false)
    {
        $result = [
            'success_mids' => [],
            'failed_mids'  => []
        ];

        foreach ($merchantIds as $merchantId)
        {
            $name = (new PdfGenerator)->getNameForMerchantPgInvoice($year, $month, $merchantId);

            $shouldGenerateRevisedPgInvoice = (new PgEInvoice())->shouldGenerateRevisedInvoice($merchantId, $month, $year);

            if($shouldGenerateRevisedPgInvoice === true)
            {
                $name = (new PdfGenerator())->getNameForMerchantPgRevisedInvoice($year, $month, $merchantId);
            }

            $file = $this->repo
                         ->file_store
                         ->getFileWithNameAndMerchantIdAndName($merchantId, $name, FileStore\Type::MERCHANT_INVOICE);

            $pgEInvoiceCore = (new PgEInvoice());

            $eInvoiceSuccess = $pgEInvoiceCore->isEinvoiceSuccess($merchantId, $month, $year, EInvoice\Types::PG);

            $invoiceBreakup = $this->repo
                                   ->merchant_invoice
                                   ->fetchInvoiceReportData($merchantId, $month, $year);

            if (((empty($file) === false) and ($eInvoiceSuccess === true)) or ($shouldGenerateRevisedPgInvoice === true))
            {
                $reason = 'merchant invoice file store entry is already present';

                if($shouldGenerateRevisedPgInvoice === true)
                {
                    $reason = 'action not allowed as merchant is eligible for revised invoices';
                }

                $this->trace->info(
                    TraceCode::MERCHANT_INVOICE_PDF_CREATION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'year'        => $year,
                        'month'       => $month,
                        'reason'      => $reason,
                    ]);

                $result['failed_mids'][] = $merchantId;
            }
            else
            {
                try
                {
                    if ((empty($file) === true) and ($eInvoiceSuccess === true))
                    {
                        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);

                        $this->checkForEinvoiceDataAndDispatch($merchant, $month, $year, $invoiceBreakup, false);

                        $result['success_mids'][] = $merchantId;
                    }
                    else if ($eInvoiceSuccess === false)
                    {
                        if(empty($file) === false)
                        {
                            $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);

                            $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);

                            if(($pgEInvoiceCore->shouldGenerateEInvoice($merchant, $date->getTimestamp()) === true) and
                                (Processor::hasTaxableAmount($invoiceBreakup) === true))
                            {
                                $this->checkForEinvoiceDataAndDispatch($merchant, $month, $year, $invoiceBreakup, true);

                                $result['success_mids'][] = $merchantId;
                            }
                            else
                            {
                                $result['failed_mids'][] = $merchantId;
                            }
                        }
                        else
                        {
                            $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);

                            $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);

                            if(($pgEInvoiceCore->shouldGenerateEInvoice($merchant, $date->getTimestamp()) === true) and
                                (Processor::hasTaxableAmount($invoiceBreakup) === true) and ($strictB2c === false))
                            {
                                $this->checkForEinvoiceDataAndDispatch($merchant, $month, $year, $invoiceBreakup, true);

                                $result['success_mids'][] = $merchantId;
                            }
                            else
                            {
                                (new PdfGenerator)->generatePgInvoice($merchantId, $month, $year, $invoiceBreakup);

                                $result['success_mids'][] = $merchantId;
                            }

                        }

                    }
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

    public function checkForEinvoiceDataAndDispatch($merchant, $month, $year, $invoiceBreakup, $updateGstin = false)
    {
        $merchantId = $merchant->getId();

        $invoiceCore = (new Core());
        [$date, $isGstApplicable, $data] = $invoiceCore->getPgInvoiceData($merchant, $month,
            $year, $invoiceBreakup);

        if($updateGstin === true)
        {
            $this->updateGstin($merchantId, [Entity::INVOICE_NUMBER => $data[Entity::INVOICE_NUMBER]]);
        }

        $invoiceCore->dispatchForPgEInvoice($data, $month, $year, $merchant->getId());
    }

    public function removeMerchantInvoicePdf($merchantIds, $month, $year)
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

            if (empty($file) === true)
            {
                $this->trace->info(
                    TraceCode::MERCHANT_INVOICE_PDF_DELETION_FAILED,
                    [
                        'merchant_id' => $merchantId,
                        'year'        => $year,
                        'month'       => $month,
                        'reason'      => 'merchant invoice file store entry is already deleted'
                    ]);

                $result['failed_mids'][] = $merchantId;
            }
            else
            {
                $this->repo
                     ->file_store
                     ->removeFileStoreEntryWithMerchantIdAndName($merchantId, $name, FileStore\Type::MERCHANT_INVOICE);

                $result['success_mids'][] = $merchantId;
            }
        }

        return $result;
    }

    /**
     * @throws BadRequestException
     */
    protected function addFeatureFlag($merchantId): void {
        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchantId,
            Feature\Entity::ENTITY_TYPE => EntityConstants::MERCHANT,
            Feature\Entity::NAMES       => [Feature\Constants::SHOW_INVOICE_REPORT],
            Feature\Entity::SHOULD_SYNC => true
        ];
        try
        {
            (new Feature\Service)->addFeatures($featureParams);
        }
        catch (BadRequestException $exception)
        {
            if ($exception->getCode() === ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED)
            {
                $this->trace->info(TraceCode::FEATURE_STALE_READ_SUCCESS, $featureParams);
            }
            else
            {
                throw $exception;
            }
        }
    }

    /**
     * @throws BadRequestException
     */
    protected function removeFeatureFlag($merchantId): void {
        $merchant = $this->repo->merchant->findOrFail($merchantId);
        if(!empty($merchant) && $merchant->isFeatureEnabled(Feature\Constants::SHOW_INVOICE_REPORT)) {
            try {
                $features = (new Feature\Service)->deleteEntityFeature(
                    'accounts',
                    $merchantId,
                    Feature\Constants::SHOW_INVOICE_REPORT,
                    [Feature\Entity::SHOULD_SYNC => true]);

            } catch(BadRequestException $exception) {
                if ($exception->getCode() === ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_NOT_EXIST)
                {
                    $this->trace->info(ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_NOT_EXIST, [
                        'merchantId' => $merchantId,
                        'feature' => Feature\Constants::SHOW_INVOICE_REPORT
                    ]);
                }
                else
                {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @throws BadRequestException
     */
    protected function toggleFeatureFlagIdentifier($input, $merchantId = null): void {
        switch ($input['action'])
        {
            case Constants::ADD_TO_SKIPPED_MIDS_LIST:
                if ($merchantId !== null) {
                    $this->addFeatureFlag($merchantId);
                }
                break;

            case Constants::REMOVE_FROM_SKIPPED_MIDS_LIST:
                $this->removeFeatureFlag($merchantId);
                break;
        }
    }

    protected function performInvoiceControlAction($mids, $res, $values, $input, $redis): array {
        foreach ($mids as $merchantId)
        {
            $skip = 0;

            try
            {
                switch ($input['action'])
                {
                    case Constants::ADD_TO_SKIPPED_MIDS_LIST:
                        if(in_array($merchantId, $values) === true)
                        {
                            $res['failed_mids'][] = $merchantId;

                            $this->trace->info(
                                TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_FAILED,
                                [
                                    'merchant_id' => $merchantId,
                                    'reason'      => 'merchant is already present in the skipped list',
                                ]);
                            $skip = 1;
                            break;
                        }

                        $redis->LPUSH(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, $merchantId);
                        $this->toggleFeatureFlagIdentifier($input, $merchantId);
                        break;

                    case Constants::REMOVE_FROM_SKIPPED_MIDS_LIST:
                        if(in_array($merchantId, $values) === false)
                        {
                            $res['failed_mids'][] = $merchantId;

                            $this->trace->info(
                                TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_FAILED,
                                [
                                    'merchant_id' => $merchantId,
                                    'reason'      => 'merchant is not present in the skipped list',
                                ]);

                            $skip = 1;
                            break;
                        }
                        $redis->LREM(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, 0, $merchantId);
                        $this->toggleFeatureFlagIdentifier($input, $merchantId);
                        break;
                }
                if($skip === 0)
                {
                    $res['success_mids'][] = $merchantId;
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    null,
                    TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_FAILED,
                    [
                       'merchant_id' => $merchantId,
                       'reason'      => 'failed to' . $input['action']. 'to redis skipped list'
                    ]);

                $res['failed_mids'][] = $merchantId;
            }
        }
        return $res;
    }
    public function generationControl($input)
    {
        (new Validator())->validateRequestInput($input);

        $redis = $this->app->redis->Connection('mutex_redis');

        $values = $redis->LRANGE(Constants::MERCHANT_INVOICE_SKIPPED_MIDS_KEY, 0, -1);

        if ($input['action'] === Constants::SHOW_SKIPPED_MIDS_LIST)
        {
            return $values;
        }

        if (!empty($input['isOrgIdSelected'])) {
            $result = [];
            foreach ($input['org_ids'] as $orgId) {
                $currObject = [
                    "orgId" => $orgId,
                    "success_mids" => [],
                    "failed_mids" => []
                ];

                $offset = 0;
                $index = 0;
                $associatedMids = [];
                while (true) {
                    $mids = $this->repo->merchant->fetchMerchantsByOrgIdFromTidb($orgId, $offset, 1000);
                    if (empty($mids) === true) {
                        break;
                    }
                    $associatedMids = array_merge($associatedMids, $mids);

                    $index++;

                    $offset = $index * 1000;
                }

                if(!empty($input['exempted_mids'])) {
                    $excludedMids = $input['exempted_mids'];
                    $associatedMids = array_diff($associatedMids, $excludedMids);
                }
                $currObject = $this->performInvoiceControlAction($associatedMids, $currObject, $values, $input, $redis);
                $result[] = $currObject;
            }
        } else {
            $result = [
                'failed_mids'  => [],
                'success_mids' => [],
            ];

            $result = $this->performInvoiceControlAction($input['merchant_ids'], $result, $values, $input, $redis);
        }

        $this->trace->info(
            TraceCode::MERCHANT_INVOICE_GENERATION_CONTROL_RESULT,
            [
               'input'  => $input,
               'result' => $result,
            ]);

        return $result;
    }

    private function isChargeCollectionsInvoicingExptEnabledForX($mid): bool
    {
        $properties = [
            'id' => $mid,
            'experiment_id' => $this->app['config']->get('app.charge_collections_invoicing_x_experiment_id'),
            'request_data'  => json_encode(['mid' => $mid]),
        ];
        return (new Merchant\Core())->isSplitzExperimentEnable($properties, 'enable');
    }
}
