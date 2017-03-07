<?php

namespace RZP\Models\Invoice;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\LineItem;

class Service extends Base\Service
{
    protected $core;

    protected  $userId = null;

    public function __construct()
    {
        parent::__construct();

        $this->setUserId();

        $this->core = new Core();
    }

    public function create($input)
    {
        $this->appendUserIdToInput($input);

        $invoice = $this->core->create($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetch($id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);


        return $invoice->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $this->appendUserIdToInput($input);

        $invoices = $this->repo->invoice
                               ->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $invoice = $this->core->update($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function issue(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $invoice = $this->core->issue($invoice, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function delete(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $invoice = $this->core->delete($invoice);

        if ($invoice === null)
        {
            return [];
        }

        return $invoice->toArrayPublic();
    }

    public function addLineItems(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $invoice = $this->core->addLineItems($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function updateLineItem(string $id, string $lineItemId, array $input)
    {
        $invoice  = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

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

    public function removeLineItem(string $id, string $lineItemId)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMorphEntity(
                                    $lineItemId,
                                    $invoice
                                );

        $invoice = $this->core->removeLineItem($invoice, $lineItem);

        return $invoice->toArrayPublic();
    }

    public function removeManyLineItems(string $id, array $input)
    {
        $invoice  = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        (new LineItem\Validator)->validateInput('remove_many', $input);

        $lineItems = $this->repo->line_item
                               ->findManyByPublicIdsAndMorphEntity(
                                    $input[LineItem\Entity::IDS],
                                    $invoice
                                );

        $invoice = $this->core->removeManyLineItems($invoice, $lineItems);

        return $invoice->toArrayPublic();
    }

    public function sendNotification($id, $medium)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $data = $this->core->sendNotification($invoice, $medium);

        return $data;
    }

    public function expireInvoice($id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $invoice = $this->core->expireInvoice($invoice);

        return $invoice->toArrayPublic();
    }

    public function expireInvoices()
    {
        return $this->core->expireInvoices();
    }

    public function sendNotificationsInBulk()
    {
        return (new Notifier())->sendNotificationsInBulk();
    }

    public function fetchStatus($id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $data = $this->core->fetchStatus($invoice);

        return $data;
    }

    public function getInvoiceViewData($invoiceId)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if (($routeName === 'invoice_view_test') or
            ($routeName === 'invoice_view_test_post'))
        {
            $mode = Mode::TEST;
        }
        else
        {
            $mode = Mode::LIVE;
        }

        \Database\DefaultConnection::set($mode);

        $this->app['rzp.mode'] = $mode;

        $invoice = $this->repo->invoice->findByPublicId($invoiceId);

        return (new ViewDataSerializer($invoice))->get();
    }

    public function getInvoicePdf(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUserId(
                                            $id,
                                            $this->merchant,
                                            $this->userId);

        $displayName = $invoice->getPdfDisplayName();

        $path = $this->core->getFreshInvoicePdf($invoice);

        return [$displayName, $path];
    }

    /**
     * Ref: https://github.com/razorpay/api/issues/2397
     *
     * @return null
     */
    protected function setUserId()
    {
        $dashboardHeaders = $this->app['basicauth']->getDashboardHeaders();

        $userRole = $dashboardHeaders['user_role'] ?? null;
        $userId   = $dashboardHeaders['user_id'] ?? null;

        if ($userRole === 'sellerapp')
        {
            $this->userId = $userId;
        }
    }

    /**
     * If user id is sent in headers from dashboard then we do this for two cases:
     * - In create: To have user_id in db too.
     * - In list (fetch multiple): To filter invoices based on user_id, restricts
     *   visibility.
     *
     * @param array $input
     *
     * @return null
     */
    protected function appendUserIdToInput(array & $input)
    {
        if ($this->userId !== null)
        {
            $input[Entity::USER_ID] = $this->userId;
        }
    }
}
