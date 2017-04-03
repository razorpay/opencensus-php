<?php

namespace RZP\Models\Invoice;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\LineItem;

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

    public function create($input)
    {
        // Appends USER_ID in create input:
        // - if not already set, and
        // - if available in headers via dashboard
        if ((isset($input[Entity::USER_ID]) === false) and
            ($this->userId !== null))
        {
            $input[Entity::USER_ID] = $this->userId;
        }

        $invoice = $this->core->create($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetch($id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        return $invoice->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        // Appends USER_ID in query input if userId available in headers via
        // dashboard given userRole is sellerapp so only invoices created by
        // that user is visible in fetched list.
        if (($this->userId !== null) and
            ($this->userRole === Constants::SELLERAPP_ROLE))
        {
            $input[Entity::USER_ID] = $this->userId;
        }

        $invoices = $this->repo->invoice
                               ->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->update($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function issue(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->issue($invoice, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function delete(string $id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->delete($invoice);

        if ($invoice === null)
        {
            return [];
        }

        return $invoice->toArrayPublic();
    }

    public function addLineItems(string $id, array $input)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->addLineItems($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function updateLineItem(string $id, string $lineItemId, array $input)
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

    public function removeLineItem(string $id, string $lineItemId)
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

    public function removeManyLineItems(string $id, array $input)
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

    public function sendNotification($id, $medium)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $data = $this->core->sendNotification($invoice, $medium);

        return $data;
    }

    public function cancelInvoice($id)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $invoice = $this->core->cancelInvoice($invoice);

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
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

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
        $invoice = $this->repo->invoice->findByPublicIdAndMerchantAndUser(
                                            $id,
                                            $this->merchant,
                                            $this->userId,
                                            $this->userRole);

        $displayName = $invoice->getPdfDisplayName();

        $path = $this->core->getFreshInvoicePdf($invoice);

        return [$displayName, $path];
    }

    /**
     * Sets userId and userRole members of this class by reading values from
     * request headers sent from dashboard.
     *
     * @return null
     */
    protected function setUser()
    {
        $dashboardHeaders = $this->app['basicauth']->getDashboardHeaders();

        $this->userId   = $dashboardHeaders['user_id'] ?? null;
        $this->userRole = $dashboardHeaders['user_role'] ?? null;
    }
}
