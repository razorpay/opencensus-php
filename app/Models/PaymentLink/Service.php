<?php

namespace RZP\Models\PaymentLink;

use App;
use phpseclib\Crypt\AES;
use Razorpay\Trace\Logger as Trace;
use Request;
use RZP\Services;
use RZP\Models\Batch;
use RZP\Base\ConnectionType;
use RZP\Encryption\AESEncryption;
use RZP\Error\Error;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Batch\ResponseEntity;
use RZP\Models\PaymentLink\NocodeCustomUrl;
use RZP\Models\PaymentLink\Metric;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use Illuminate\Http\Request  as CurrentRequest;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Settings;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Models\Feature\Constants as Feature;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Models\PaymentLink\PaymentPageItem as PPI;
use RZP\Models\PaymentLink\CustomDomain\Plans as CDS_PLANS;


class Service extends Base\Service
{
    use Base\Traits\ServiceHasCrudMethods;

    protected $core;

    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->entityRepo = $this->repo->payment_link;
    }

    /**
     * {@inheritDoc}
     * Overridden as it expects in arguments & passes around $input to repository method
     */
    public function fetch(string $id, array $input): array
    {
        $entity = Tracer::inSpan(['name' => 'payment_page.get.find_entity'], function() use($id, $input)
        {
            return $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);
        });

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $this->modifyInputForFetch($input);

        $this->validateInputForFileUpload($input, $this->merchant);

        $entities = Tracer::inSpan(['name' => 'payment_page.fetch_pages'], function() use($input)
        {
            return $this->entityRepo->fetch($input, $this->merchant->getId());
        });

        return $entities->toArrayPublic();
    }

    protected function modifyInputForFetch(array & $input)
    {
        if ((isset($input[Entity::VIEW_TYPE]) === false) || (empty($input[Entity::VIEW_TYPE]) === true))
        {
            $input[Entity::VIEW_TYPE] = Entity::VIEW_TYPE_PAGE;
        }
    }

    public function validateInputForFileUpload($input,$merchant)
    {
        if ((isset($input[Entity::VIEW_TYPE]) === true) and
            ($input[Entity::VIEW_TYPE] === ViewType::FILE_UPLOAD_PAGE) and
            ($merchant->isFeatureEnabled(Feature::FILE_UPLOAD_PP) === false))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT
            );
        }
    }

    public function fetchWithDetailsForDashboard(string $id, array $input)
    {
        $entity = Tracer::inSpan(['name' => 'payment_page.get_details.find_entity'], function() use($id, $input)
        {
            return $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);
        });

        $data = $entity->toArrayPublic();

        Tracer::inSpan(['name' => 'payment_pages.get_details.fetch_setting_for_ppi'], function() use(&$data)
        {
            $this->fetchSettingForPPI($data);
        });

        $extra[Entity::SLUG] = Tracer::inSpan(['name' => 'payment_page.get_details.get_slug_from_short_url'], function() use($entity)
        {
            return $entity->getSlugFromShortUrl();
        });

        $extra[Entity::CAPTURED_PAYMENTS_COUNT] = Tracer::inSpan(['name' => 'payment_page.get_details.get_captured_payments'], function() use($entity)
        {
            return $this->getCapturedPaymentCount($entity);
        });

        $extra[Entity::SETTINGS] = Tracer::inSpan(['name' => 'payment_page.get_details.serialize'], function() use($entity)
        {
            return (new ViewSerializer($entity))->serializeSettingsWithDefaults();
        });

        return $data + $extra;
    }

    /**
     * @param \RZP\Models\PaymentLink\Entity $entity
     *
     * @return int
     */
    private function getCapturedPaymentCount(Entity $entity): int
    {
        $computedSettings = $entity->getComputedSettings()->toArray();

        $capturedPaymentCount = array_get($computedSettings, Entity::CAPTURED_PAYMENTS_COUNT);

        if ($capturedPaymentCount === null)
        {
            $capturedPaymentCount = $this->core->updateAndGetCapturedPaymentCount($entity);
        }

        return (int) $capturedPaymentCount;
    }

    public function create(array $input): array
    {
        $entity = Tracer::inSpan(['name' => 'payment_page.create'], function() use ($input) {
            return $this->core->create($input, $this->merchant, $this->user);
        });

        return Tracer::inSpan(['name' => 'payment_page.create.to_public'], function() use ($entity) {
            return $entity->toArrayPublic();
        });
    }

    public function sendNotification(string $id, array $input, $merchant = null)
    {
        $merchant = $this->merchant ?? $merchant;
        $paymentLink = Tracer::inSpan(['name' => 'payment_page.send_notification.find_payment_link'], function() use($id, $merchant)
        {
            return $this->repo->payment_link->findByPublicIdAndMerchant($id, $merchant);
        });

        Tracer::inSpan(['name' => 'payment_page.send_notification.core'], function() use($paymentLink, $input)
        {
            $this->core->sendNotification($paymentLink, $input);
        });
    }

    /**
     * @throws BadRequestValidationFailureException
     */
    public function sendNotificationToAllRecords(string $id, array $input)
    {

        (new Validator)->validateInput('validateSendNotificationToAllRecords', $input);

        $batchId = $input[PaymentPageRecord\Entity::BATCH_ID];

        $records = $this->repo->payment_page_record->findByPaymentPageIdAndBatchIdorFail($id, $batchId);

        if(in_array('sms',$input['notify_on']) === true)
        {
            $notifyInput['contacts'] = array_unique(array_column($records, PaymentPageRecord\Entity::CONTACT));
        }

        if(in_array('email',$input['notify_on']) === true)
        {
            $notifyInput['emails'] = array_unique(array_column($records, PaymentPageRecord\Entity::EMAIL));
        }

        if((isset($notifyInput['contacts']) === false)
            and isset($notifyInput['emails']) === false)
        {
            throw  new BadRequestValidationFailureException('Either email or contact should be present');
        }

        $this->sendNotification($id,$notifyInput);
    }

    public function expirePaymentLinks(): array
    {
        return $this->core->expirePaymentLinks();
    }

    public function deactivate(string $id): array
    {
        $paymentLink = Tracer::inSpan(['name' => 'payment_page.deactivate.find_payment_link'], function() use($id)
        {
            return $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);
        });

        $paymentLink = $this->core->deactivate($paymentLink);

        return $paymentLink->toArrayPublic();
    }

    public function activate(string $id, array $input): array
    {
        $paymentLink = Tracer::inSpan(['name' => 'payment_page.activate.find_payment_link'], function() use($id)
        {
            return $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);
        });

        $paymentLink = $this->core->activate($paymentLink, $input);

        return $paymentLink->toArrayPublic();
    }

    public function createSubscription(string $id, array $input): array
    {
        (new Validator)->validateInput('createSubscription', $input);

        $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

        return $this->core->createSubscription($paymentLink, $input, $this->merchant);
    }

    public function getButtonViewNameAndPayload(string $id, array $input, CurrentRequest $request, $viewType = null)
    {
        if ($viewType === ViewType::SUBSCRIPTION_BUTTON)
        {
            $view = 'payment_button.subscription';
        }
        else
        {
            $view = 'payment_button.index';
        }

        $payload = [
            'base_url'           => $this->app['config']['app']['url'],
            'environment'        => $this->app->environment(),
            'is_test_mode'       => ($this->mode === Mode::TEST),
            'payment_button_id'  => $id,
        ];

        $payload[Entity::REQUEST_PARAMS] = $input;

        if (empty($error = Request::get(Entity::ERROR)) === false)
        {
            $payload[Entity::ERROR] = $error;
        }

        if ($request->method() === 'POST')
        {
            $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

            (new Validator)->validatePageViewable($paymentLink);

            $buttonPayload = $this->core->getHostedViewPayload($paymentLink);

            $payload['button'] = $buttonPayload;

            $this->appendAmountIfPossible($id, $input, $payload);
        }

        $this->trace->count(
            Metric::PAYMENT_PAGE_VIEW_TOTAL,
            [
                'view_type' => $viewType,
            ]
        );

        return [$view, $payload];
    }

    /**
     * @param string $id
     * @param array  $input
     *
     * @return array
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function getViewNameAndPayload(string $id, array $input)
    {
        /** @var Entity $paymentLink */
        $paymentLink = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_FIND], function() use ($id) {
            return $this->repo->payment_link->findActiveByPublicId($id);
        });

        $route = $this->app['api.route']->getHost();

        $validator = new Validator;

        $validator->validatePaymentHandleAndHost($paymentLink, $route);

        Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_VALIDATE], function() use ($paymentLink, $validator) {
            $validator->validatePageViewable($paymentLink);
        });

        $viewPayload = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_GET_PAYLOAD], function() use
        ($paymentLink) {
            return $this->core->getHostedViewPayload($paymentLink);
        });

        $view = Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_GET_TEMPLATE], function() use ($paymentLink) {
            return $this->core->getHostedViewTemplate($paymentLink);
        });

        $this->core->addCustomAmountForPaymentHandleIfRequired($viewPayload, $route, $input);

        $this->trace->count(Metric::PAYMENT_PAGE_VIEW_TOTAL, $paymentLink->getMetricDimensions());

        return [$view, $viewPayload];
    }

    public function getHostedButtonDetails(string $id)
    {
        $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

        (new Validator)->validatePageViewable($paymentLink);

        return $this->core->getHostedViewPayload($paymentLink);
    }

    public function getHostedButtonPreferences(string $id)
    {
        $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

        (new Validator)->validatePageViewable($paymentLink);

        return $this->core->getHostedButtonPreferences($paymentLink);
    }

    /**
     * It uploads the images in S3 bucket and returns their location urls.
     *
     * @param array $input Includes images to be uploaded.
     *
     * @return array Image urls.
     * @throws \RZP\Exception\ServerErrorException
     */
    public function upload(array $input): array
    {
        Tracer::inSpan(['name' => 'payment_page.upload.validate'], function() use($input)
        {
            (new Validator)->validateInput('uploadImages', $input);
        });

        return $this->core->upload($input, $this->merchant);
    }

    public function appendAmountIfPossible(string $id, array $input, array & $payload)
    {
        if (isset($input['razorpay_payment_id']) === true)
        {
            $paymentLink = Tracer::inSpan(['name' => 'payment_page.append_amount.find_active_by_public_id'], function() use ($id) {
                return $this->repo->payment_link->findActiveByPublicId($id);
            });

            $paymentId = Tracer::inSpan(['name' => 'payment_page.append_amount.verify_id_and_strip_sign'], function() use (& $input) {
                return Payment\Entity::verifyIdAndStripSign($input['razorpay_payment_id']);
            });

            $payment = Tracer::inSpan(['name' => 'payment_page.append_amount.find_or_fail_public'], function() use ($paymentId) {
                return $this->repo->payment->findOrFailPublic($paymentId);
            });

            if ($paymentLink->getId() === $payment->getPaymentLinkId())
            {
                $payload[Entity::REQUEST_PARAMS][Entity::AMOUNT] = $payment->getAmount();
            }
        }
    }


    /**
     * @throws BadRequestException
     */
    public function fetchRecordsForPL($input, $paymentLinkId)
    {
        $paymentPage = $this->getPaymentLinkAndSetModeAndMerchant($paymentLinkId);

        $udfSchema = $paymentPage->getSettingsAccessor()->get(Entity::UDF_SCHEMA);

        $udfSchema = json_decode($udfSchema, true);

        $this->validateInputForFetchPL($input, $udfSchema);

        $priRefId = $input[PaymentPageRecord\Entity::PRIMARY_REF_ID];

        try{
            $paymentPageRecord = $this->repo->payment_page_record->findByPaymentPageAndPrimaryRefIdOrFail($paymentLinkId, $priRefId);
        }
        catch (\Throwable)
        {
            throw new BadRequestValidationFailureException(
                'Primary Reference Id\'s Mismatch.');
        }

        $valid = $this->checkSecondaryRefIds($input,$paymentPageRecord, $udfSchema);

        if($valid === false)
        {
            throw new BadRequestValidationFailureException(
                'Secondary Reference Id\'s Mismatch.');
        }

        $value = $udfSchema;

        $nameToTitle = [];

        foreach ($value as $val)
        {
            $nameToTitle[$val['name']] = $val['title'];
        }

        $names = array_column($value, 'name');

        $response = $this->buildResponse($paymentPageRecord, $names, $value, $nameToTitle, $input);

        return $response;

    }

    /*
     * This function transforms the i/p to generic format
     * where generic format is the one with pri__ref__id and
     * sec__ref__id's
     * */

    protected function transformInput($input, $udfSchema): array
    {
        $values = $udfSchema;

        $genericInput = [];

        //$genericInput can only have pri_ref_id and sec_ref_id's

        foreach ($values as $value)
        {
            if ((array_key_exists($value['title'], $input) === true)
                and (PaymentPageRecord\Entity::isRefId($value['name']) === true))
            {
                $genericInput[$value['name']] = $input[$value['title']];
            }
        }

        return $genericInput;
    }

    /*
     * This validates if the pri_ref_id is present and also
     * if sec_ref_id's are present in udf_schema and if required
     * is true then they should be present in $input else throws
     * the BadRequestValidationFailureException
     * */

    protected function validateInputForFetchPL($input, $udfSchema)
    {
        $validator = (new Validator);

        $validator->validateInput('fetchRecordsForPL',$input);

        //check secondary ref id's are mandatory

        $value = $udfSchema;

        $names = array_column($value, 'name');

        $nameToRequiredMap = [];

        foreach ($value as $val)
        {
            if ((PaymentPageRecord\Entity::isSecondaryRefId($val['name']) === true)
                and ($val['required'] === true))
            {
                $nameToRequiredMap[$val['name']] = $val['required'];
            }
        }

        $diff = array_diff_key($nameToRequiredMap ,$input);

        if(count($diff) !== 0)
        {
            throw new BadRequestValidationFailureException(
                'Secondary Reference Id\'s missing.');
        }

    }

    /*
     * This function fetches the secondary ref id's titles from
     * udfSchema and then gets the value for those from other_details of
     * payment_page_record and then verifies it against the input specified
     * */

    protected function checkSecondaryRefIds($input,$paymentPageRecord, $udfSchema): bool
    {
        unset($input[PaymentPageRecord\Entity::PRIMARY_REF_ID]);

        $value = $udfSchema;

        $valueMap = [];

        $paymentPageRecord = $paymentPageRecord->toArray();

        $details = json_decode($paymentPageRecord['other_details'],true);

        foreach ($value as $val)
        {
            if((PaymentPageRecord\Entity::isSecondaryRefId($val['name']) === true)
                and ($val['required'] === true))
            {
                $valueMap[$val['name']] = $details[$val['name']];
            }
        }

        if($input == $valueMap)
            return true;

        return false;
    }

    protected function buildResponse($paymentPageRecord, $keys, $udfSchema, $nameToTitle, $input)
    {
        $response = [];

        $data = [];

        $fields = [
            PaymentPageRecord\Entity::EMAIL,
            PaymentPageRecord\Entity::PHONE,
            PaymentPageRecord\Entity::PRIMARY_REF_ID
        ];

        $intersection = array_values(array_uintersect($keys,$fields,'strcasecmp'));

        foreach ($intersection as $key)
        {
            $responseKey = $nameToTitle[$key];

            $value = $key;

            if($key === 'pri__ref__id')
                $value = 'primary_reference_id';

            if((isset($paymentPageRecord[strtolower($key)]) === true) and
                ($paymentPageRecord[strtolower($key)] !== null))
                $data[$value] = $paymentPageRecord[strtolower($key)];
        }

        $otherDetails = json_decode($paymentPageRecord['other_details'],true);

        $otherDetails = array_diff_key($otherDetails,$response);

        $response['data'] = $data;

        $response['data'][PaymentPageRecord\Entity::PRIMARY_REF_ID] = $paymentPageRecord[PaymentPageRecord\Entity::PRIMARY_REFERENCE_ID];
        $response['data'][PaymentPageRecord\Entity::SECONDARY_1] = $input[PaymentPageRecord\Entity::SECONDARY_1];
        $response['data'][PaymentPageRecord\Entity::PHONE] = $paymentPageRecord[PaymentPageRecord\Entity::CONTACT];

        $response['other_details'] = $otherDetails;

        $response['other_details']['status'] = $paymentPageRecord[PaymentPageRecord\Entity::STATUS];
        $response['other_details'][PaymentPageRecord\Entity::AMOUNT] = $paymentPageRecord[PaymentPageRecord\Entity::AMOUNT];

        return $response;
    }

    public function createOrder(string $id, array $input)
    {
        $paymentLink = Tracer::inSpan(['name' => 'payment_page.order.create.get_payment_link'], function() use($id)
        {
            return $this->getPaymentLinkAndSetModeAndMerchant($id);
        });

        $data = Tracer::inSpan(['name' =>  'payment_page.order.create.core'], function() use($paymentLink, $input)
        {
            return (new Core)->createOrder($paymentLink, $input);
        });

        for($i = 0; $i < count($data[Entity::LINE_ITEMS]); $i++)
        {
            $data[Entity::LINE_ITEMS][$i] = $data[Entity::LINE_ITEMS][$i]->toArrayPublic();
        }

        $data[Entity::ORDER] = $data[Entity::ORDER]->toArrayPublic();

        $this->trace->count(Metric::PAYMENT_PAGE_CREATE_ORDER, $paymentLink->getMetricDimensions());

        return $data;
    }

    public function updatePaymentPageItem(string $paymentPageItemId, array $input)
    {
        $paymentPageItem = Tracer::inSpan(['name' => 'payment_page.ppi.update.find_entity'], function() use($paymentPageItemId)
        {
            return $this->repo->payment_page_item->findByPublicIdAndMerchant($paymentPageItemId, $this->merchant);
        });

        $paymentPageItem = Tracer::inSpan(['name' => 'payment_page.ppi.update.updating'], function() use($paymentPageItem, $input)
        {
            return $this->core->updatePaymentPageItem($paymentPageItem, $input);
        });

        return $paymentPageItem->toArrayPublic();
    }

    public function createPaymentPageFileUploadRecord(string $paymentPageId, string $batchId, array $input)
    {
        try {

            Tracer::inSpan(['name' => 'payment_page.ppr.create.record'], function () use ($paymentPageId, $batchId, $input) {
                return $this->core->createPaymentPageFileUploadRecord($paymentPageId, $batchId, $input);
            });

            $input['error_code'] = '';
            $input['error_description'] = '';

            return $input;
        } catch (\Throwable $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::PAYMENT_PAGE_CREATE_RECORD_EXCEPTION,
                [
                    'error' => $e->getMessage(),
                ]
            );

            $input['error_code'] = $e->getCode();
            $input['error_description'] = $e->getMessage();
            return $input;
        }
    }

    public function getPendingPaymentsAndRevenue(string $paymentPageId)
    {
        return Tracer::inSpan(['name' => 'payment_page.ppi.update.updating'], function() use($paymentPageId)
        {

            $unpaidAmount = $this->repo->payment_page_record->findByPaymentPageIdAndStatus($paymentPageId);

            $response[PaymentPageRecord\Entity::TOTAL_PENDING_PAYMENTS] = count($unpaidAmount);

            $revenue = 0;
            foreach ($unpaidAmount as $amount) {
            $revenue = $revenue + $amount['amount'];
            }

            $response[PaymentPageRecord\Entity::TOTAL_PENDING_REVENUE] = $revenue;

            return $response;
        });

    }

    public function getPaymentPageBatches(string $paymentPageId,$input)
    {
        return Tracer::inSpan(['name' => 'payment_page.ppr.get.batches'], function() use($paymentPageId, $input)
        {
            $validator = (new Validator);

            $validator->validateInput('getPaymentPageBatches',$input);

            $id = Entity::stripDefaultSign($paymentPageId);

            if (isset($input[PaymentPageRecord\Entity::ALL_BATCHES]) === true)
            {
                $batches = $this->repo->payment_page_record->getAllBatchesByPaymentPageId($id);

                return array_column($batches, PaymentPageRecord\Entity::BATCH_ID);
            }

            $count = $input['count'] ?? PaymentPageRecord\Constants::MAX_LIMIT_FOR_GET_BATCH;

            $skip = $input['skip'] ?? 0;

            $hasMore = false;

            //Since batch service only supports max 25 batches at a time for this route
            if ($count > PaymentPageRecord\Constants::MAX_LIMIT_FOR_GET_BATCH)
            {
                $count = PaymentPageRecord\Constants::MAX_LIMIT_FOR_GET_BATCH;
            }

            $result = $this->repo->payment_page_record->getBatchesByPaymentPageId($id, $skip, $count);

            $batches = $result['records'];
            $totalCount = $result['totalCount'];


            $count = 0;
            $batchArr = [];
            foreach ($batches as $batchId) {
              $batchArr[$count]  = $batchId[PaymentPageRecord\Entity::BATCH_ID];
              $count++;
            }

            $this->merchant = $this->auth->getMerchant();

            $batchResponse = [];
            if (count($batchArr) > 0) {
                $batchResponse = $this->app->batchService->getMultipleBatchesFromBatchService($this->merchant, $batchArr) ?? [];
            }

            // change the status of the batch as per the mapping defined in statusClusterMapping
            foreach ($batchResponse as &$batch)
            {
                $mappedStatus =  (new Services\BatchMicroService())->statusClusterMapping($batch[Batch\Entity::STATUS]);

                $batch[Batch\Entity::STATUS] = $mappedStatus;

                $batch[Batch\Entity::ID] = 'batch_'.$batch[Batch\Entity::ID];

                $batch['entity'] = 'batch';

                $batch['config'] = $batch['settings'];

                unset($batch['settings']);
            }

            // build response

            $response = [];

            $response['entity'] = 'collection';

            $response['count'] = count($batchArr);

            $response['items'] = $batchResponse;

            if ($count + $skip < $totalCount)
            {
                $hasMore = true;
            }

            $response['has_more'] = $hasMore;

            $this->trace->info(
                TraceCode::GET_MULTIPLE_BATCHES_BATCH_SERVICE,
                [
                    'Batch service Response' => $batchResponse,
                ]);

            return $response;

        });

    }

    public function setMerchantDetails(array $input)
    {
        return $this->core->setMerchantDetails($input);
    }

    public function fetchMerchantDetails()
    {
        return $this->core->fetchMerchantDetails();
    }

    public function setReceiptDetails(string $id, array $input)
    {
        $paymentLink = Tracer::inSpan(['name' => 'payment_page.receipts.entity.find'], function() use ($id) {
            return $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);
        });

        return Tracer::inSpan(['name' => 'payment_page.receipts.create'], function() use ($paymentLink, $input) {
            return $this->core->setReceiptDetails($paymentLink, $input);
        });
    }

    public function getInvoiceDetails(string $paymentId)
    {
        return $this->core->getInvoiceDetails($paymentId);
    }

    public function sendReceipt(string $paymentId, array $input)
    {
        return $this->core->sendReceipt($paymentId, $input);
    }

    public function saveReceiptForPayment(string $paymentId, array $input)
    {
        return $this->core->saveReceiptForPaymentAndGeneratePdf($paymentId, $input);
    }

    public function getPayments(string $id, array $input)
    {
        $merchant = $this->merchant;

        $paymentPage = Tracer::inSpan(['name' => 'payment_page.payments.get.find_payment_page'], function() use($id)
        {
            return $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);
        });

        $payload = $paymentPage->toArrayPublic();

        $payload['payments'] = [];

        $input[Payment\Entity::PAYMENT_LINK_ID] = $paymentPage->getPublicId();

        $payments = Tracer::inSpan(['name' => 'payment_page.payments.get.fetch_payments'], function() use($input, $merchant)
        {
            $variant = $this->app['razorx']->getTreatment(
                'paymentPageGetPayments',
                'pp_payment_fetch_query_migration',
                $this->mode ?? Mode::LIVE);

            $this->trace->info(TraceCode::ARCHIVAL_EXPERIMENTS_REPOSITORY_VARIANT, [
                'variant'    => $variant,
                'feature'    => 'pp_payment_fetch_query_migration',
                'context_id' => 'paymentPageGetPayments',
            ]);

            if ($variant === "tidb")
            {
                return $this->repo->payment->fetch($input, $merchant->getId(), ConnectionType::DATA_WAREHOUSE_MERCHANT);
            }

            if ($variant === "replica")
            {
                return $this->repo->payment->fetch($input, $merchant->getId(), ConnectionType::PAYMENT_FETCH_REPLICA);
            }

            return $this->repo->payment->fetch($input, $merchant->getId());
        });

        foreach ($payments as $payment)
        {
            $order = $payment->order;

            $lineItems = $order->lineItems;

            $payment = $payment->toArrayPublic();

            $payment[E::ORDER] = $order->toArrayPublic();

            if ($lineItems !== null)
            {
                $payment[E::ORDER]['items'] = $lineItems->toArrayPublic()['items'];
            }

            array_push($payload['payments'], $payment);
        }

        return $payload;
    }

    /**
     * @param string $merchantId
     *
     * @return array
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function createPaymentHandle(string $merchantId)
    {
        $this->trace->count(Metric::PAYMENT_HANDLE_CREATION_REQUEST);

        $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATE_L1_ACTIVATION_START);

        $startTime = millitime();

        $prevMode = null;

        $modifiedResponse = [];

        try
        {
            $prevMode = $this->mode;

            $prevBasicAuth = $this->getPrevAuthAndSetVariables($merchantId);

            $ph = $this->createPaymentHandleV2();

            $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATE_L1_ACTIVATION_COMPLETED, [
                Entity::HANDLE       => $ph[Entity::SLUG],
            ]);

            $this->trace->count(Metric::PAYMENT_HANDLE_CREATION_SUCCESSFUL_COUNT);
        }
        catch(\Throwable $e)
        {
            $this->trace->count(Metric::PAYMENT_HANDLE_CREATION_FAILED_COUNT, [
                'error'   => $e
                ]);

            $this->trace->traceException($e, Trace::ERROR,
                TraceCode::PAYMENT_HANDLE_CREATION_FAILED,
                [
                    'merchant_id' => $merchantId,
                ]);
        }
        finally
        {
            $this->app['basicauth'] = $prevBasicAuth;

            $this->app['basicauth']->setModeAndDbConnection($prevMode);
        }

        $this->trace->histogram(Metric::PAYMENT_HANDLE_CREATION_TIME_TAKEN, millitime() - $startTime);

        return $modifiedResponse;
    }

    public function updatePaymentHandle(array $input): array
    {
        if (($this->merchant->isActivated() ===  true) &&
            ($this->mode !== Mode::LIVE))
        {
            throw new BadRequestValidationFailureException(
                'Payment handle can only be updated in live mode.',
                null,
                null);
        }

        $this->trace->info(TraceCode::PAYMENT_HANDLE_UPDATE_INITIATED,[
            Entity::MERCHANT_ID    => $this->merchant->getId(),
        ]);

        $validator = (new Validator);

        $validator->validateInput('updatePaymentHandle',$input);

        $validator->validatePaymentHandleUpdation($input, $this->merchant);

        // TODO Add validation to see if id and default payment handle id is same
        $response = Tracer::inSpan(['name' => Constants::HT_PH_UPDATE], function() use($input)
        {
            return $this->core->updatePaymentHandle($input);
        });

        $this->trace->info(TraceCode::PAYMENT_HANDLE_UPDATE_SUCCESSFUL,[
           Entity::MERCHANT_ID     => $this->merchant->getId(),
           Entity::SLUG            => $response[Entity::SLUG]
        ]);

        return $response;
    }

    public function getPaymentHandleByMerchant(): array
    {
        if (($this->merchant->isActivated() ===  true) &&
            ($this->mode !== Mode::LIVE))
        {
            throw new BadRequestValidationFailureException(
                'Payment handle can only be fetched in live mode.',
                null,
                null);
        }

        $this->trace->info(TraceCode::PAYMENT_HANDLE_GET_REQUEST_INITIATED, [
            Entity::MERCHANT_ID     => $this->merchant->getId(),
        ]);

        $response = Tracer::inSpan(['name' => Constants::HT_PH_GET], function()
        {
            return $this->core->getPaymentHandleByMerchant($this->merchant);
        });

        return $response;
    }

    public function suggestionPaymentHandle($input)
    {
        $this->trace->info(TraceCode::PAYMENT_HANDLE_SUGGESTION_INITIATED, [
            Entity::MERCHANT_ID  => $this->merchant->getId()
        ]);

        $count = Entity::DEFAULT_PAYMENT_HANDLE_SUGGESTION_COUNT;

        if(array_key_exists(Entity::COUNT, $input) === true)
        {
            (new Validator)->validatePaymentHandleSuggestionCount($input[Entity::COUNT]);

            $count = $input[Entity::COUNT];
        }

        $suggestions[Entity::SUGGESTIONS] = $this->core->suggestionPaymentHandle($count);

        return $suggestions;
    }

    public function paymentHandleExists(string $slug) : bool
    {
        (new Validator)->isValidPaymentHandle($slug);

        return $this->core->slugExists($slug);
    }

    public function createPaymentHandleV2(): array
    {
        // If live mode exists it means that merchant is activated
        if($this->mode !== Mode::LIVE)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle can be created in live mode only.'
            );
        }

        $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATE_START);

        $validator = new Validator();

        $validator->validatePaymentHandleCreatedForMerchant($this->merchant);

        $input = $this->core->getDefaultValuesPaymentHandle();

        //fetching payment handle if pre-create api was called before this api
        $precreatedHandle = $this->core->getHandleFromTestMode();

        // ie precreate was not called
        if (empty($precreatedHandle) === false)
        {
            $input[Entity::SLUG] = $precreatedHandle;
        }

        $validator->isValidPaymentHandle($input[Entity::SLUG]);

        $paymentHandle = $this->core->createPaymentHandle($input, $this->merchant);

        $paymentHandle = $this->core->modifyResponseForPaymentHandle($paymentHandle);

        $this->trace->info(TraceCode::PAYMENT_HANDLE_CREATE_COMPLETED);

        return $paymentHandle;
    }

    public function getPaymentHandlePreviewPage(string $slug, string $merchantId)
    {
        $this->trace->info(TraceCode::PAYMENT_HANDLE_PREVIEW_PAGE,[
            Entity::MERCHANT_ID     => $merchantId,
            Entity::SLUG            => $slug
        ] );

        $input[Entity::SLUG]  = $slug;

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $input[Entity::TITLE] = $merchant->getBillingLabel();

        $this->core->modifyInputForPaymentHandle($input);

        $viewPayload = $this->core->getAttributesForPaymentHandlePreview($input, $merchant, $slug);

        $this->trace->count(Metric::PAYMENT_HANDLE_PREVIEW_PAGE_VIEW_TOTAL);

        return $viewPayload;
    }

    public function encryptAmountForPaymentHandle(array $input): array
    {
        (new Validator)->validateInput('encryptAmountForPaymentHandle', $input);

        $this->trace->count(Metric::PAYMENT_HANDLE_AMOUNT_ENCRYPTION_TOTAL_REQUEST);

        $this->trace->info(TraceCode::PAYMENT_HANDLE_AMOUNT_ENCRYPTION,[
            Entity::AMOUNT      => $input[Entity::AMOUNT]
        ]);

        return $this->core->encryptAmountForPaymentHandle($input);
    }

    protected function getPaymentLinkAndSetModeAndMerchant(string $id)
    {
        $paymentPage = null;

        try
        {
            $this->app['basicauth']->setModeAndDbConnection('live');

            $paymentPage = $this->repo->payment_link->findByPublicId($id);
        }
        catch (\Exception $e)
        {
            $this->app['basicauth']->setModeAndDbConnection('test');

            $paymentPage = $this->repo->payment_link->findByPublicId($id);
        }

        $this->app['basicauth']->setMerchant($paymentPage->merchant);

        return $paymentPage;
    }

    protected function fetchSettingForPPI(array & $paymentLink)
    {
        $PPICore = new PaymentPageItem\Core;

        for ($i = 0; $i < count(array_get($paymentLink, Entity::PAYMENT_PAGE_ITEMS, [])); $i++)
        {
            $paymentPageItem = $paymentLink[Entity::PAYMENT_PAGE_ITEMS][$i];

            $paymentPageItem = Tracer::inSpan(['name' => 'payment_page.fetch_payment_page_item'], function() use($PPICore, $paymentPageItem)
            {
                return $PPICore->fetch($paymentPageItem[PaymentPageItem\Entity::ID], $this->merchant);
            });

            $paymentPageItem->settings = $paymentPageItem->getSettings();

            $paymentLink[Entity::PAYMENT_PAGE_ITEMS][$i] = $paymentPageItem->toArrayPublic();
        }
    }

    protected function getPrevAuthAndSetVariables(string $merchantId)
    {
        $prevBasicAuth = $this->app['basicauth'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $this->app['basicauth']->setModeAndDbConnection('live');

        $this->app = App::getFacadeRoot();

        $this->app['basicauth']->setMerchant($merchant);

        $this->mode = Mode::LIVE;

        $this->merchant = $merchant;

        $this->core = new Core;

        return $prevBasicAuth;
    }

    /**
     * @return array
     * @throws BadRequestValidationFailureException
     */
    public function precreatePaymentHandle(): array
    {
        $this->trace->info(
            TraceCode::PAYMENT_HANDLE_PRECREATE_STARTED,
            [
                Entity::MERCHANT_ID => $this->merchant->getId()
            ]);

        (new Validator)->validatePaymentHandlePrecreateAndMode($this->merchant);

        $paymentHandle = $this->core->precreatePaymentHandle($this->merchant);

        return $paymentHandle;
    }

    /**
     * Retrieves slug's metadata from Gimli which contains entity, id & mode
     *
     * @param string      $slug
     * @param string|null $host
     *
     * @return array|null
     */
    public function getSlugMetaData(string $slug, string $host = null): ?array
    {
        return Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_SLUG_DATA], function() use ($slug, $host) {
            $domain = null;

            if (empty($host) !== true) {
                $domain = NocodeCustomUrl\Entity::determineDomainFromUrl($host);
            }

            return (new ElfinWrapper(ElfinService::GIMLI))->expandAndGetMetadata($slug, $domain);
        });
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function cdsCallback(array $input): array
    {
        $this->trace->info(TraceCode::CDS_CALLBACK_RECIEVED, $input);

        $processor = CustomDomain\Factory::getWebHookHandler();

        // TODO: Make it async
        $processor->process($input)->handle();

        return [];
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     * @throws \Throwable
     */
    public function cdsDomainCreate(array $input): array
    {
        $this->trace->info(TraceCode::CDS_DOMAIN_CREATE_RECIEVED, $input);

        (new CDS_PLANS\Validator())->validatePlanIdPresent($input);

        $input['merchant_id'] = $this->merchant->getId();

        $domainClient = CustomDomain\Factory::getDomainClient();

        $res = $domainClient->createDomain($input);

        // add domain in whitelisted domains for the merchant
        $merchantCore = new Merchant\Core;

        $domain = NocodeCustomUrl\Entity::determineDomainFromUrl($input['domain_name']);

        $merchantCore->addDomainInWhitelistedDomain($this->merchant, $domain);

        $this->merchant->saveOrFail();

        (new CustomDomain\Plans\Core)->createOrUpdatePlanForMerchant($input);

        return $res;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function cdsDomainList(array $input): array
    {
        $this->trace->info(TraceCode::CDS_DOMAIN_LIST_RECIEVED, $input);

        $input['merchant_id'] = $this->merchant->getId();

        $domainClient = CustomDomain\Factory::getDomainClient();

        return $domainClient->listDomain($input);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     * @throws \Throwable
     */
    public function cdsDomainDelete(array $input): array
    {
        $this->trace->info(TraceCode::CDS_DOMAIN_DELETE_RECIEVED, $input);

        $input['merchant_id'] = $this->merchant->getId();

        $domainClient = CustomDomain\Factory::getDomainClient();

        $res = $domainClient->deleteDomain($input);

        // remove domain from whitelisted domains for the merchant
        $merchantCore = new Merchant\Core;

        $domain = NocodeCustomUrl\Entity::determineDomainFromUrl($input['domain_name']);

        $merchantCore->removeDomainFromWhitelistedDomain($this->merchant, $domain);

        $this->merchant->saveOrFail();

        (new CustomDomain\Plans\Service())->deletePlanForMerchant();

        return $res;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function cdsPropagation(array $input): array
    {
        $this->trace->info(TraceCode::CDS_PROPAGATION_RECIEVED, $input);

        $propagationClient = CustomDomain\Factory::getPropagationClient();

        return $propagationClient->checkPropagation($input);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \RZP\Exception\IntegrationException
     */
    public function cdsIsSubDomain(array $input): array
    {
        $this->trace->info(TraceCode::CDS_PROPAGATION_RECIEVED, $input);

        $domainClient = CustomDomain\Factory::getDomainClient();

        $input['merchant_id'] = $this->merchant->getId();

        return $domainClient->isSubDomain($input);
    }

    /**
     * @param $origin
     *
     * @return bool
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\IntegrationException
     */
    public function cdsHas($origin): bool
    {
        if (empty($origin) === true)
        {
            return false;
        }

        $domain = NocodeCustomUrl\Entity::determineDomainFromUrl($origin);

        $fromCache = CustomDomain\Helper::getDomainFromCache($domain);

        if (empty($fromCache) !== true)
        {
            return true;
        }

        $input['domain_name'] = $domain;

        $domainClient = CustomDomain\Factory::getDomainClient();

        $domains = $domainClient->listDomain($input);

        if ($domains['count'] == 0)
        {
            return false;
        }

        CustomDomain\Helper::cacheDomain($domains['items']['0']);

        return true;
    }

    /**
     * @param array $input
     *
     * @return bool[]
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\IntegrationException
     */
    public function cdsDomainExists(array $input): array
    {
        return [
            "exists"    => $this->cdsHas(array_get($input, 'domain_name'))
        ];
    }
}
