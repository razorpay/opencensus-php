<?php

namespace RZP\Models\Invoice;

use Mail;

use Response;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\Error;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\LineItem;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use BaconQrCode\Writer;
use BaconQrCode\Renderer;
use RZP\Models\User\Role;
use RZP\Http\RequestHeader;
use RZP\Constants\Entity as E;
use RZP\Models\QrCode\Constants;
use Illuminate\Http\UploadedFile;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\FileStore\Type as FileType;
use RZP\Mail\Invoice\PaymentLinkServiceBase;
use RZP\Models\QrCode\Generator as QrCodeGenerator;

use RZP\Jobs\Invoice\BatchNotify as InvoiceBatchNotifyJob;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeWriter;

class Service extends Base\Service
{
    protected $core;

    // This is dashboard's userId and userRole. Used to support access control
    // for one specific use case of sellerapp.
    // Ref: https://github.com/razorpay/api/issues/2397
    protected $userId   = null;
    protected $userRole = null;

    public function __construct()
    {
        parent::__construct();

        $this->setUser();

        $this->core = new Core();
    }

    public function create(array $input): array
    {
        $batchId = null;

        if ($this->app['basicauth']->isBatchApp() === true)
        {
            $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id) ?? null;

            $invoice = $this->core->create($input, $this->merchant, null, null,null, $batchId);
        }
        else
        {
            $invoice = $this->core->create($input, $this->merchant);
        }

        return $invoice->toArrayPublic();
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\BadRequestException
     */
    public function createBulkInvoice(array $input)
    {
        $invoiceBatch = new Base\PublicCollection();

        (new Validator)->validateBulkInvoiceCount($input);

        foreach ($input as $item)
        {
            try
            {
                $idempotency_key = isset($item[Entity::IDEMPOTENCY_KEY]) ? $item[Entity::IDEMPOTENCY_KEY] : null;

                $response = $this->create($item);

                $invoiceBatch->push($response);

            }
            catch (Exception\BaseException $exception)
            {
                $this->trace->traceException($exception,
                                             Trace::INFO,
                                             TraceCode::BATCH_SERVICE_BULK_BAD_REQUEST);
                $exceptionData = [
                    Entity::IDEMPOTENCY_KEY => $idempotency_key,
                    'error'                 => [
                        Error::DESCRIPTION       => $exception->getError()->getDescription(),
                        Error::PUBLIC_ERROR_CODE => $exception->getError()->getPublicErrorCode(),
                    ],
                    Error::HTTP_STATUS_CODE => $exception->getError()->getHttpStatusCode(),
                ];

                $invoiceBatch->push($exceptionData);
            }
            catch (\Throwable $throwable)
            {
                $this->trace->traceException($throwable,
                                             Trace::CRITICAL,
                                             TraceCode::BATCH_SERVICE_BULK_EXCEPTION);

                $exceptionData = [
                    Entity::IDEMPOTENCY_KEY => $idempotency_key,
                    'error'                 => [
                        Error::DESCRIPTION       => $throwable->getMessage(),
                        Error::PUBLIC_ERROR_CODE => $throwable->getCode(),
                    ],
                    Error::HTTP_STATUS_CODE => 500,
                ];

                $invoiceBatch->push($exceptionData);
            }
        }

        return $invoiceBatch->toArrayWithItems();
    }

    public function fetch(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole,
                                            $input);

        return $invoice->toArrayPublic();
    }

    public function getInvoiceDetailsForCheckout(string $invoiceId): array
    {
        $data = $this->core->getFormattedInvoiceData($invoiceId, $this->merchant);

        if (isset($data['customer']['notes'])) {
            $data['customer']['notes'] = (object) ($data['customer']['notes'] ?? []);
        }

        return $data;
    }

    public function checkForInvoiceTypeForPlServiceForwarding(string $id, array $input): bool
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
            $id,
            $this->merchant,
            $this->userId,
            $this->userRole,
            $input);

        if (($invoice->isTypeInvoice() === true) ||
            ($invoice->isTypeOfSubscriptionRegistration() === true))
        {
            // we dont want to forward invoice and auth links requests to pl service
            return false;
        }

        return true;
    }

    public function fetchMultiple(array $input): array
    {

        $this->trace->info(TraceCode::INVOICE_FETCH_MULTIPLE_REQUEST,
            [
                'input' => $input,
            ]);

        // Appends USER_ID in query input if userId available in headers via
        // dashboard given userRole is sellerapp so only invoices created by
        // that user is visible in fetched list.
        if (($this->userId !== null) and
            (($this->userRole === Role::SELLERAPP) or ($this->userRole === Role::SELLERAPP_PLUS)))
        {
            $input[Entity::USER_ID] = $this->userId;
        }

        // fetching only those where entity_type is null
        // (invoices having entity_type not null will be fetched by respective entity apis)
        $invoices = $this->repo->invoice
                               ->fetchForEntityType($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function getInvoicesCount(array $input)
    {
        return $this->repo->invoice->getInvoicesCount($input);
    }

    public function update(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        //
        // `findByPublicIdAndMerchantAndUser` handles ACL for the `sellerapp` role.
        // Here, we also validate access for the `agent` role
        //
        $this->validateAgentRoleAcl($invoice);

        $invoice = $this->core->update($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function updateBillingPeriod(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($id,$this->merchant);

        $invoice = $this->core->updateBillingPeriod($invoice, $input);

        return $invoice->toArrayPublic();
    }

    public function issue(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->issue($invoice, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function notifyInvoicesOfBatch(string $batchId, array $input)
    {
        try
        {
            $this->app->batchService->forwardNotify($batchId, $input, $this->merchant);

            // forward call PL service
            if ($this->core->shouldForwardToPaymentLinkService() === true)
            {
                try
                {
                    $paymentLinkService = $this->app['paymentlinkservice'];

                    $response = $paymentLinkService->sendRequest($this->app->request);

                    $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_RESPONSE,
                        [
                            'batch'    => $batchId,
                            'response' => $response['response']
                        ]);
                }
                catch(\Throwable $e)
                {
                    // in case of error, just log and continue for normal API flow
                    $this->trace->warning(TraceCode::PAYMENT_LINK_SERVICE_NO_DATA_FOUND, ['batch' => $batchId]);
                }
            }

            $batchId = Batch\Entity::verifyIdAndStripSign($batchId);

            InvoiceBatchNotifyJob::dispatch($this->mode, $batchId, $input);
        }
        catch (Exception\ServerNotFoundException $exception)
        {
            $batch = $this->repo->batch->findByPublicIdAndMerchant(
                $batchId,
                $this->merchant);

            $this->core->notifyInvoicesOfBatch($batch, $input);
        }
    }

    public function cancelInvoicesOfBatch(string $batchId)
    {
        $batch = [];

        $batch = $this->fetchBatchById($batchId);

        if ($batch === [])
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                null,
                [
                    'batch_id'      => $batchId,
                ]
            );
        }

        return $this->core->cancelInvoicesOfBatch($batch);
    }

    protected function fetchBatchById(string $batchId): array
    {
        $batch = [];

        if ($this->auth->isAdminAuth() === true)
        {
            $batch = (new Batch\Service())->fetchBatchById($batchId);
        }
        else
        {
            $batch = (new Batch\Service())->getBatchById($batchId, $this->merchant);

            if (($batch !== []) and
                (
                    (array_key_exists(Batch\ResponseEntity::BATCH_TYPE_ID, $batch) === false) or
                    (array_key_exists(Batch\ResponseEntity::BATCH_TYPE_ID, $batch) === true) and
                    ($batch[Batch\ResponseEntity::BATCH_TYPE_ID] !== Batch\Type::PAYMENT_LINK)
                ))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_TYPE, // To be changed
                    null,
                    [
                        'batch_id'      => $batchId,
                    ]
                );
            }
        }

        return $batch;
    }

    public function delete(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        //
        // `findByPublicIdAndMerchantAndUser` handles ACL for the `sellerapp` role.
        // Here, we also validate access for the `agent` role
        //
        $this->validateAgentRoleAcl($invoice);

        $this->core->delete($invoice);

        return [];
    }

    public function addLineItems(string $id, array $input): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->addLineItems($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function updateLineItem(
        string $id,
        string $lineItemId,
        array $input): array
    {
        $invoice  = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMorphEntity(
                                    $lineItemId,
                                    $invoice
                                );

        $invoice = $this->core->updateLineItem(
            $invoice,
            $lineItem,
            $input,
            $this->merchant
        );

        return $invoice->toArrayPublic();
    }

    public function removeLineItem(string $id, string $lineItemId): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMorphEntity(
                                    $lineItemId,
                                    $invoice
                                );

        $invoice = $this->core->removeLineItem($invoice, $lineItem);

        return $invoice->toArrayPublic();
    }

    public function removeManyLineItems(string $id, array $input): array
    {
        $invoice  = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        (new LineItem\Validator)->validateInput('remove_many', $input);

        $lineItems = $this->repo->line_item
                               ->findManyByPublicIdsAndMorphEntity(
                                    $input[LineItem\Entity::IDS],
                                    $invoice
                                );

        $invoice = $this->core->removeManyLineItems($invoice, $lineItems);

        return $invoice->toArrayPublic();
    }

    public function sendNotification(string $id, string $medium): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice->setRelation('entity', $invoice->entity);

        $data = $this->core->sendNotification($invoice, $medium);

        return $data;
    }

    public function cancelInvoice(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        //
        // `findByPublicIdAndMerchantAndUser` handles ACL for the `sellerapp` role.
        // Here, we also validate access for the `agent` role
        //
        $this->validateAgentRoleAcl($invoice);

        $invoice = $this->core->cancelInvoice($invoice);

        return $invoice->toArrayPublic();
    }

    public function expireInvoices(): array
    {
        return $this->core->expireInvoices();
    }

    public function deleteInvoices($input): array
    {
        $limit = $input['limit'] ?? 5000;

        $merchantIds = $input['merchant_ids'] ?? [];

        if (empty($merchantIds) === true)
        {
            return [];
        }

        $hours = $input['hours'] ?? 24;

        $pastTime = Carbon::now()->subHours($hours)->getTimestamp();

        return $this->core->deleteInvoices($pastTime, $merchantIds, $limit);
    }

    public function sendNotificationsInBulk(): array
    {
        return (new Notifier())->sendNotificationsInBulk();
    }

    public function fetchStatus(string $id): array
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $data = $this->core->fetchStatus($invoice);

        return $data;
    }

 public function getInvoiceViewData(string $invoiceId): array
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Gets mode per route and sets application & db mode.
        $mode = str_contains($routeName, '_test') ? Mode::TEST : Mode::LIVE;
        $this->app['basicauth']->setModeAndDbConnection($mode);

        $invoice = $this->repo->invoice->findByPublicId($invoiceId);

        $this->trace->count(Metric::INVOICE_VIEW_TOTAL, $invoice->getMetricDimensions(['merchant_country_code' => (string) $invoice->merchant->getCountry()]));

        $invoice->getValidator()->validateInvoiceViewable();

        return (new ViewDataSerializerHosted($invoice))->serializeForHostedV2();
    }

	/*
	 * Below function is added to test Rendering Preferences on a different route.
	 * Will delete after testing.
	 */
    public function getInvoiceViewDataForTest(string $invoiceId): array
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        // Gets mode per route and sets application & db mode.
        $mode = str_contains($routeName, '_test') ? Mode::TEST : Mode::LIVE;
        $this->app['basicauth']->setModeAndDbConnection($mode);

        $invoice = $this->repo->invoice->findByPublicId($invoiceId);

        $this->trace->count(Metric::INVOICE_VIEW_TOTAL, $invoice->getMetricDimensions([], $invoice->merchant));

        $invoice->getValidator()->validateInvoiceViewable();

        // Get razorx treatment
        $variant = $this->app->razorx->getTreatment(
            $invoice->merchant->getId(),
            Merchant\RazorxTreatment::RENDERING_PREFERENCES_PAYMENT_LINKS,
            $mode
        );

        if (strtolower($variant) === 'on')
        {
            return (new ViewDataSerializerHosted($invoice))->serializeForHostedV2();
        }
        else
        {
            return (new ViewDataSerializerHosted($invoice))->serializeForHostedV2();
        }
    }

    /**
     * @param string       $id
     * @param bool|boolean $download
     *
     * @return string|null
     * @throws \RZP\Exception\BadRequestException
     */

    public function getInvoicePdfSignedUrl(string $id, bool $download = false)
    {
        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

    public function issueInvoicesOfBatch(string $batchId, array $input): array
    {
        (new Validator)->validateInput(Validator::ISSUE_BATCH, $input);

        $batch = $this->repo
                      ->batch
                      ->findByPublicIdAndMerchant($batchId, $this->merchant);

        $response = $this->core->issueInvoicesOfBatch($batch, $input);

        return $response;
    }

    /**
     * Returns filtered list of batch ids which should be allowed
     * 'Issue all links' action.
     *
     * @param array $input
     *
     * @return array
     */
    public function getIssuableByBatchIds(array $input): array
    {
        (new Validator)->validateInput('invoiceStatsByBatches', $input);

        $batchIds = $input[Entity::BATCH_IDS];

        Batch\Entity::verifyIdAndSilentlyStripSignMultiple($batchIds);

        $results = $this->repo->invoice->getNonDraftInvoiceCountByBatchIds($batchIds);

        // Following batch ids have non draft invoices and we assume this
        // whole batch was already issued.

        $results = array_filter($results, function ($result)
        {
            return ($result['count'] > 0);
        });

        $results = array_column($results, Entity::BATCH_ID);

        // Return the list which can be shown 'Issue all links' action

        $results = array_values(array_diff($batchIds, $results));

        Batch\Entity::getSignedIdMultiple($results);

        return $results;
    }

    public function getSignedUrlForQrCodeImage($input)
    {
        $localSaveDir = (new QrCodeGenerator())->getLocalSaveDir();

        $localFilePath = $localSaveDir . '/' . $input['invoice']['id'] . '.' . Constants::QR_CODE_EXTENSION;

        $localFilePathQrBasicFilePath = $localSaveDir . '/' . $input['invoice']['id'] . '_basic.png';

        $qrCodeString = $input['intent_url'];

        $qrCodeWriter = QrCodeWriter::format('png');

        $qrCodeWriter->size(Constants::QR_EMAIL_HEIGHT)
                     ->generate($qrCodeString, $localFilePathQrBasicFilePath);

        $qrCodeImage = imagecreatefrompng($localFilePathQrBasicFilePath);

        $logoImage = imagecreatefrompng(public_path() . '/img/template_qr_on_email.png');

        imagecopymerge($logoImage, $qrCodeImage,
                       Constants::QR_EMAIL_X, Constants::QR_EMAIL_Y,
                       Constants::SORCE_X, Constants::SORCE_Y,
                       Constants::QR_EMAIL_WIDTH, Constants::QR_EMAIL_HEIGHT,
                       Constants::OPACITY);

        imagejpeg($logoImage, $localFilePath);

        imagedestroy($logoImage);

        imagedestroy($qrCodeImage);

        $uploadedFile = new UploadedFile(
            $localFilePath,
            $input['invoice']['id']. '.jpeg',
            'image/jpeg',
            null,
            true
        );

        $ufhService  = (new FileUploadUfh())->getUfhService();

        if($ufhService !== null)
        {
            $ufhResponse = $ufhService->uploadFileAndGetUrl(
                $uploadedFile,
                $input['invoice']['id'],
                FileType::QR_CODE_IMAGE,
                []
            );

            $this->trace->info(
                TraceCode::INVOICE_IMAGE_UFH_FILE_UPLOAD_RESPONSE,
                $ufhResponse
            );
        }

        $fileId = $ufhResponse['file_id'];

        $response = $ufhService->getSignedUrl($fileId);

        $this->trace->info(
            TraceCode::INVOICE_PDF_SIGNED_URL_FETCH_RESPONSE,
            $response
        );

        return $response['signed_url'];
    }

    public function checkIfQronEmailExperimentEnabled()
    {
        $isExperimentEnabled = (new Merchant\Core())->isRazorxExperimentEnable($this->merchant->getId(),
                                                                               RazorxTreatment::QR_ON_EMAIL);

        $this->trace->info(TraceCode::QR_ON_EMAIL_RAZORX_EXPERIMENT,
                           [
                               'merchant'            => $this->merchant->getId(),
                               'isExperimentEnabled' => $isExperimentEnabled,
                           ]);

        return $isExperimentEnabled;
    }

    public function sendEmailForPaymentLinkService(array $input): array
    {
        (new Validator)->validateInput('payment_link_service_send_email', $input);

        $input[E::MERCHANT] = $this->serializeMerchantForHostedForPaymentLinkService($this->merchant);

        $input[E::ORG] = $this->serializeOrgPropertiesForHostedForPaymentLinkService();

        $input['view_preferences'] = $this->getViewPreferencesForPaymentLinkService($this->merchant);

        $input['is_test_mode'] = ($this->mode === Mode::TEST);

        $input['view_extend_address'] = 'emails.invoice.notification';

        if (empty($input['intent_url']) === false)
        {
            $input['qr_code_image_address'] = $this->getSignedUrlForQrCodeImage($input);

            $this->trace->info(TraceCode::QR_ON_EMAIL_IMAGE_ADDRESS,
                               [
                                   'qr_code_image_address' => $input['qr_code_image_address'],
                               ]);

            $input['view_extend_address'] = 'emails.invoice.customer.notification_qr_pl_v2';
        }

        $mailable = new PaymentLinkServiceBase($input);

        try
        {
            Mail::queue($mailable);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::PAYMENT_LINK_NOTIFY_BY_EMAIL_FAILURE,
                [
                    'input' => $input,
                ]
            );

            throw $ex;
        }

        return [];
    }

    public function switchPlVersions(array $input): array
    {
        (new Validator)->validateInput('payment_link_switch_version', $input);

        return $this->core->switchPlVersions($input, $this->merchant);
    }

    public function dccPaymentInvoiceCron(array $input)
    {
        $this->trace->info(TraceCode::DCC_PAYMENT_E_INVOICE_CRON_INIT,[
            'input'  => $input,
        ]);
        try
        {
            $dccCore = new DccEInvoiceCore();
            // if payload has IDs then process payload request (manual run of cron)
            if (isset($input[\RZP\Models\Invoice\Constants::REFERENCE_ID]) and isset($input[\RZP\Models\Invoice\Constants::REFERENCE_TYPE]))
            {
                $dccCore->processPayload($input);
            }
            // process yesterday's failed invoices (default cron job)
            else
            {
                $dccCore->processFailedInvoices();
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::DCC_PAYMENT_E_INVOICE_CRON_FAILED,[
                    'payload' => $input,
                ]
            );
        }

        $this->trace->info(TraceCode::DCC_PAYMENT_E_INVOICE_CRON_COMPLETED, [
            'input'  => $input,
        ]);
        return ['success' => true];
    }

    public function createPaymentSupportingDocuments(array $input, $payment): Entity
    {
        return (new OPGSPImportInvoiceCore())->create($input, $payment->merchant, null, null, $payment);
    }

    public function findByPaymentIdDocumentType($paymentId, $documentType)
    {
        return (new OPGSPImportInvoiceCore())->findByPaymentIdDocumentType($paymentId, $documentType);
    }

    public function findByPaymentIds($paymentIds, $merchantId)
    {
        return (new OPGSPImportInvoiceCore())->findByPaymentIds($paymentIds,$merchantId);
    }

    public function findByMerchantIdDocumentTypeDocumentNumber($merchantId, $documentType, $documentNumber)
    {
        return (new OPGSPImportInvoiceCore())->findByMerchantIdDocumentTypeDocumentNumber($merchantId, $documentType, $documentNumber);
    }

    public function findByPaymentId($paymentId)
    {
        return (new OPGSPImportInvoiceCore())->findByPaymentId($paymentId);
    }

    protected function serializeOrgPropertiesForHostedForPaymentLinkService()
    {
        $org = $this->merchant->org;

        $branding = [
            'show_rzp_logo' => true,
            'branding_logo' => '',
        ];

        if($this->merchant->shouldShowCustomOrgBranding() === true)
        {
            $branding['show_rzp_logo'] = false;

            $branding['branding_logo'] = $org->getInvoiceLogo() ?: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.png';
        }

        return [
            'branding'  => $branding,
            'custom_code'=> $org->getCustomCode()
        ];
    }

    protected function serializeMerchantForHostedForPaymentLinkService(Merchant\Entity $merchant): array
    {
        $exemptCustomerFlagging = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXEMPT_CUSTOMER_FLAGGING);

        return [
            'id'                               => $merchant->getId(),
            'name'                             => $merchant->getLabelForInvoice(),
            'brand_color'                      => get_rgb_value($merchant->getBrandColorOrDefault()),
            'brand_text_color'                 => get_brand_text_color($merchant->getBrandColorOrDefault()),
            'business_registered_address_text' => $merchant->getBusinessRegisteredAddressAsText(', '),
            'image'                            => $merchant->getFullLogoUrlWithSize(Merchant\Logo::LARGE_SIZE),
            'business_registered_address'      => optional($merchant->merchantDetail)->getBusinessRegisteredAddress(),
            'exempt_customer_flagging'         => $exemptCustomerFlagging,
        ];
    }

    protected function getViewPreferencesForPaymentLinkService(Merchant\Entity $merchant)
    {
        $exemptCustomerFlagging = $merchant->isFeatureEnabled(Feature\Constants::APPS_EXEMPT_CUSTOMER_FLAGGING);

        return ['exempt_customer_flagging' => $exemptCustomerFlagging];
    }

    /**
     * Sets userId and userRole members of this class by reading values from
     * request headers sent from dashboard.
     *
     * @return null
     */
    protected function setUser()
    {
        $user = $this->auth->getUser();

        $this->userId   = (empty($user) === false) ? $user->getId() : null;

        $this->userRole = $this->auth->getUserRole();
    }

    /**
     * If the current user has role: `agent`, validate ACL for certain operations
     *
     * @param Entity $invoice
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateAgentRoleAcl(Entity $invoice)
    {
        if (($this->userRole === Role::AGENT) and
            ($invoice->getUserId() !== $this->userId))
        {
            throw new Exception\BadRequestValidationFailureException(
                'This operation can only be performed by the creator');
        }
    }
}
