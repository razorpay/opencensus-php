<?php

namespace RZP\Models\PaymentLink;

use App;
use phpseclib\Crypt\AES;
use Razorpay\Trace\Logger as Trace;
use Request;
use RZP\Encryption\AESEncryption;
use RZP\Error\Error;
use RZP\Exception\BadRequestValidationFailureException;
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
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Models\PaymentLink\PaymentPageItem as PPI;

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

    public function sendNotification(string $id, array $input)
    {
        $paymentLink = Tracer::inSpan(['name' => 'payment_page.send_notification.find_payment_link'], function() use($id)
        {
            return $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);
        });

        Tracer::inSpan(['name' => 'payment_page.send_notification.core'], function() use($paymentLink, $input)
        {
            $this->core->sendNotification($paymentLink, $input);
        });
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

        $startTime = millitime();

        $prevBasicAuth = $this->getPrevAuthAndSetVariables($merchantId);

        $prevMode = $this->mode;

        $modifiedResponse = [];

        try
        {
            $input = $this->getDefaultValuesPaymentHandle();

            $validator = (new Validator);

            $validator->validateInput('createPaymentHandle',$input);

            $validator->validatePaymentHandleCreation($input, $this->merchant);

            $this->modifyInputForPaymentHandle($input);

            $this->core->precreatePaymentHandle($this->merchant);

            $response = $this->core->createPaymentHandle($input, $this->merchant, $this->user);

            $modifiedResponse = $this->modifyResponseForPaymentHandle($response);

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

        $this->trace->count(Metric::PAYMENT_HANDLE_UPDATE_TOTAL_REQUEST);

        $validator = (new Validator);

        $validator->validateInput('updatePaymentHandle',$input);

        $validator->validatePaymentHandleUpdation($input, $this->merchant);

        // TODO Add validation to see if id and default payment handle id is same
        $response = $this->core->updatePaymentHandle($input);

        $this->trace->count(Metric::PAYMENT_HANDLE_UPDATE_TOTAL_SUCCESSFUL_REQUEST);

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

        $response = $this->core->getPaymentHandleByMerchant($this->merchant);

        return $response;
    }

    public function suggestionPaymentHandle($input)
    {
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

        $gimli  = $this->app['elfin']->driver('gimli');

        return $gimli->expand($slug) !== null;
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

        try
        {
            $this->trace->count(Metric::PAYMENT_HANDLE_CREATION_REQUEST);

            $startTime = millitime();

            $precreatedHandle = $this->core->getHandleFromTestMode();

            $input = $this->getDefaultValuesPaymentHandle();

            // ie precreate was not called on payment handle
            if (empty($precreatedHandle) === true)
            {
                $ph = $this->core->precreatePaymentHandle($this->merchant);

                // edit here
                $input[Entity::SLUG] = $ph[Entity::SLUG];
            }
            else
            {
                $input[Entity::SLUG] = $precreatedHandle;
            }

            $this->modifyInputForPaymentHandle($input);

            // TODO: change this with validatepaymenthandle later

            $validator = new Validator();

            $validator->isValidPaymentHandle($input[Entity::SLUG]);

            $validator->validatePaymentHandleCreatedForMerchant($this->merchant);

            $paymentHandle = $this->core->createPaymentHandle($input, $this->merchant);

            $paymentHandle = $this->modifyResponseForPaymentHandle($paymentHandle);

            $this->trace->count(Metric::PAYMENT_HANDLE_CREATION_SUCCESSFUL_COUNT);

            $this->trace->histogram(Metric::PAYMENT_HANDLE_CREATION_TIME_TAKEN, millitime() - $startTime);

            return $paymentHandle;
        }
        catch(\Exception $e)
        {
            $this->trace->count(Metric::PAYMENT_HANDLE_CREATION_FAILED_COUNT);

            $this->trace->traceException($e, Trace::ERROR, TraceCode::PAYMENT_HANDLE_CREATION_FAILED);

            throw $e;
        }
    }

    public function getPaymentHandlePreviewPage(string $slug, string $merchantId)
    {
        $input[Entity::SLUG]  = $slug;

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $input[Entity::TITLE] = $merchant->getBillingLabel();

        $this->modifyInputForPaymentHandle($input);

        $viewPayload = $this->core->getAttributesForPaymentHandlePreview($input, $merchant, $slug);

        $this->trace->count(Metric::PAYMENT_HANDLE_PREVIEW_PAGE_VIEW_TOTAL);

        return $viewPayload;
    }

    public function encryptAmountForPaymentHandle(array $input): array
    {
        (new Validator)->validateInput('encryptAmountForPaymentHandle', $input);

        $this->trace->count(Metric::PAYMENT_HANDLE_AMOUNT_ENCRYPTION_TOTAL_REQUEST);

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

    protected function modifyInputForPaymentHandle(array & $input)
    {
        // adding currency parameter
        $input[Entity::CURRENCY] =  empty($input[Entity::CURRENCY]) ? 'INR' : $input[Entity::CURRENCY];

        // adding empty payment_page_items
        $input[Entity::PAYMENT_PAGE_ITEMS] =  [
                [
                    PPI\Entity::ITEM     => [
                        LineItem\Entity::NAME     => ENTITY::AMOUNT,
                        'currency' => $input[Entity::CURRENCY],
                ],
                    PPI\Entity::SETTINGS   => [
                        PPI\Entity::POSITION     => 0
                ],
                    PPI\Entity::MANDATORY  => true,
                    PPI\Entity::MIN_AMOUNT => 100
            ]
        ];

        $input[ENTITY::SETTINGS]  = [
            ENTITY::UDF_SCHEMA  => "[{\"name\":\"comment\",\"title\":\"Comment\",\"required\":true,\"type\":\"string\",\"options\":{},\"settings\":{\"position\":1}}]"
        ];

        $input[ENTITY::VIEW_TYPE]  = ViewType::PAYMENT_HANDLE;
    }

    protected function modifyResponseForPaymentHandle(Entity $response) : array
    {
        $modifiedResponse = [];

        $modifiedResponse[ENTITY::TITLE] = $response[ENTITY::TITLE];

        $modifiedResponse[ENTITY::ID]    = $response->getPublicId();

        $modifiedResponse[ENTITY::SLUG]  = $response->getSlugFromShortUrl();

        $modifiedResponse[ENTITY::URL]   = $response->getHandleUrl();

        return $modifiedResponse;
    }

    private function getDefaultValuesPaymentHandle(): array
    {
        $input = [];

        $suggestedPaymentHandle = $this->core->suggestionPaymentHandle(1);

        $input[Entity::SLUG] = $suggestedPaymentHandle[0];

        $title = $this->merchant->getBillingLabel();

        if(strlen($title) > 80)
        {
            $title = substr($title, 0, 80);
        }

        $input[Entity::TITLE] = $title;

        return $input;
    }

    protected function getPrevAuthAndSetVariables(string $merchantId)
    {
        $prevBasicAuth = $this->app['basicauth'];

        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $this->app['basicauth']->setModeAndDbConnection('live');

        $this->app = App::getFacadeRoot();

        $this->app['basicauth']->setMerchant($merchant);

        $this->merchant = $merchant;

        $this->core = new Core;

        return $prevBasicAuth;
    }

    public function precreatePaymentHandle(): array
    {
        if($this->mode === Mode::LIVE)
        {
            throw new BadRequestValidationFailureException(
                'Payment Handle can be pre-created in test mode only.'
            );
        }

        (new Validator)->validatePaymentHandleExistsForMerchant($this->merchant);

        $paymentHandle = $this->core->precreatePaymentHandle($this->merchant);

        return $paymentHandle;
    }

    /**
     * Retrieves slug's metadata from Gimli which contains entity, id & mode
     *
     * @param string $slug
     *
     * @return array|null
     */
    public function getSlugMetaData(string $slug): ?array
    {
        return Tracer::inSpan(['name' => Constants::HT_PP_HOSTED_SLUG_DATA], function() use ($slug) {
            return (new ElfinWrapper(ElfinService::GIMLI))->expandAndGetMetadata($slug);
        });
    }
}
