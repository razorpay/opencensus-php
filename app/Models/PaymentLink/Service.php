<?php

namespace RZP\Models\PaymentLink;

use Request;
use Illuminate\Http\Request  as CurrentRequest;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);

        return $entity->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $this->modifyInputForFetch($input);

        $entities = $this->entityRepo
            ->fetch($input, $this->merchant->getId());

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
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant, $input);

        $data = $entity->toArrayPublic();

        $this->fetchSettingForPPI($data);

        $extra[Entity::SLUG] = $entity->getSlugFromShortUrl();
        $extra[Entity::CAPTURED_PAYMENTS_COUNT] = $entity->getCapturedPaymentsCount();

        $extra[Entity::SETTINGS] = (new ViewSerializer($entity))->serializeSettingsWithDefaults();

        return $data + $extra;
    }

    public function create(array $input): array
    {
        $entity = $this->core->create($input, $this->merchant, $this->user);

        return $entity->toArrayPublic();
    }

    public function sendNotification(string $id, array $input)
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $this->core->sendNotification($paymentLink, $input);
    }

    public function expirePaymentLinks(): array
    {
        return $this->core->expirePaymentLinks();
    }

    public function deactivate(string $id): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

        $paymentLink = $this->core->deactivate($paymentLink);

        return $paymentLink->toArrayPublic();
    }

    public function activate(string $id, array $input): array
    {
        $paymentLink = $this->repo->payment_link->findByPublicIdAndMerchant($id, $this->merchant);

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

        return [$view, $payload];
    }

    public function getViewNameAndPayload(string $id)
    {
        $this->trace->count(Metric::PAYMENT_PAGE_VIEW_TOTAL);

        /** @var Entity $paymentLink */
        $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

        (new Validator)->validatePageViewable($paymentLink);

        $viewPayload = $this->core->getHostedViewPayload($paymentLink);

        $view = $this->core->getHostedViewTemplate($paymentLink);

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
        (new Validator)->validateInput('uploadImages', $input);

        return $this->core->upload($input, $this->merchant);
    }

    public function appendAmountIfPossible(string $id, array $input, array & $payload)
    {
        if (isset($input['razorpay_payment_id']) === true)
        {
            $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

            $paymentId = Payment\Entity::verifyIdAndStripSign($input['razorpay_payment_id']);

            $payment = $this->repo->payment->findOrFailPublic($paymentId);

            if ($paymentLink->getId() === $payment->getPaymentLinkId())
            {
                $payload[Entity::REQUEST_PARAMS][Entity::AMOUNT] = $payment->getAmount();
            }
        }
    }

    public function migratePaymentPageItems(array $input)
    {
        return $this->core->migratePaymentPageItems($input);
    }

    public function migratePaymentPageItemForMinPurchase($input)
    {
        return $this->core->migratePaymentPageItemsForMinPurchase($input);
    }

    public function createOrder(string $id, array $input)
    {
        $paymentLink = $this->getPaymentLinkAndSetModeAndMerchant($id);

        $data = (new Core)->createOrder($paymentLink, $input);

        for($i = 0; $i < count($data[Entity::LINE_ITEMS]); $i++)
        {
            $data[Entity::LINE_ITEMS][$i] = $data[Entity::LINE_ITEMS][$i]->toArrayPublic();
        }

        $data[Entity::ORDER] = $data[Entity::ORDER]->toArrayPublic();

        return $data;
    }

    public function updatePaymentPageItem(string $paymentPageItemId, array $input)
    {
        $paymentPageItem = $this->repo->payment_page_item->findByPublicIdAndMerchant($paymentPageItemId, $this->merchant);

        $paymentPageItem = $this->core->updatePaymentPageItem($paymentPageItem, $input);

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
        $paymentLink = $this->repo->payment_link->findActiveByPublicId($id);

        return $this->core->setReceiptDetails($paymentLink, $input);

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
        if (isset($paymentLink[Entity::PAYMENT_PAGE_ITEMS]) === false)
        {
            return;
        }

        $PPICore = new PaymentPageItem\Core;

        for ($i = 0; $i < count($paymentLink[Entity::PAYMENT_PAGE_ITEMS]); $i++)
        {
            $paymentPageItem = $paymentLink[Entity::PAYMENT_PAGE_ITEMS][$i];

            $paymentPageItem = $PPICore->fetch($paymentPageItem[PaymentPageItem\Entity::ID], $this->merchant);

            $paymentPageItem->settings = $paymentPageItem->getSettings();

            $paymentLink[Entity::PAYMENT_PAGE_ITEMS][$i] = $paymentPageItem->toArrayPublic();
        }
    }
}
