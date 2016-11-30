<?php

namespace RZP\Models\Invoice;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant\Checkout;
use RZP\Exception;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create($input)
    {
        $invoice = $this->core->create($input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetch($id)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($id, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $invoices = $this->repo->invoice
                               ->fetch($input, $this->merchant->getId());

        return $invoices->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($id, $this->merchant);

        $invoice = $this->core->update($invoice, $input, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function issue(string $id)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($id, $this->merchant);

        $invoice = $this->core->issue($invoice, $this->merchant);

        return $invoice->toArrayPublic();
    }

    public function delete(string $id)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($id, $this->merchant);

        $invoice = $this->core->delete($invoice);

        if ($invoice === null)
        {
            return [];
        }

        return $invoice->toArrayPublic();
    }

    public function addLineItem(string $id, array $input)
    {
        $invoice = $this->repo->invoice
                              ->findByPublicIdAndMerchant($id, $this->merchant);

        $invoice = $this->core->addLineItem(
            $invoice,
            $input,
            $this->merchant
        );

        return $invoice->toArrayPublic();
    }

    public function updateLineItem(string $id, string $lineItemId, array $input)
    {
        $invoice  = $this->repo->invoice
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMerchant(
                                    $lineItemId,
                                    $this->merchant
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
        $invoice  = $this->repo->invoice
                               ->findByPublicIdAndMerchant($id, $this->merchant);

        $lineItem = $this->repo->line_item
                               ->findByPublicIdAndMerchant(
                                    $lineItemId,
                                    $this->merchant
                                );

        return $this->core->removeLineItem($invoice, $lineItem);
    }

    public function sendNotification($id, $medium)
    {
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($id, $this->merchant);

        $data = $this->core->sendNotification($invoice, $medium);

        return $data;
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
        $invoice = $this->repo->invoice->findByPublicIdAndMerchant($id, $this->merchant);

        $data = $this->core->fetchStatus($invoice);

        return $data;
    }

    public function getInvoiceViewDetails($invoiceId)
    {
        $routeName = $this->app['api.route']->getCurrentRouteName();

        if ($routeName === 'invoice_view_test')
        {
            $mode = Mode::TEST;
        }
        else
        {
            $mode = Mode::LIVE;
        }

        \Database\DefaultConnection::set($mode);

        Entity::verifyIdAndStripSign($invoiceId);
        $invoice = $this->repo->invoice->findOrFailPublic($invoiceId);

        if ($invoice->isDraft())
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invoice with id ' . $invoice->getPublicId() . 'is not issued yet'
            );
        }

        $merchant = $invoice->merchant;

        $keys = $this->repo->key->getKeysForMerchant($merchant->getId());
        $publicKey = $keys->first()->getPublicKey($mode);

        $merchantDetails = [
            'color' => $merchant->getBrandColor(),
            'image' => $merchant->getFullLogoUrlWithSize(Checkout::CHECKOUT_LOGO_SIZE),
            'name'  => $merchant->getBillingLabelElseName(),
            'id'    => $merchant->getId(),
        ];

        // This is required so that the mode and the db connection are set.
        // Since this is via direct auth, this will not set on its own.
        // $this->app['basicauth']->checkAndSetKeyId($publicKey);

        $viewDetails = [
            'customer_email'    => $invoice->getCustomerEmail(),
            'customer_contact'  => $invoice->getCustomerContact(),
            'invoice_id'        => Entity::getSignedId($invoiceId),
            'status'            => $invoice->getStatus(),
            'key_id'            => $publicKey,
            'amount'            => $invoice->order->getAmount(),
            'environment'       => $this->app->environment(),
            'view_less'         => $invoice->getViewLess(),
            'merchant_details'  => $merchantDetails,
            'payment_id'        => $invoice->getPaymentId(),
        ];

        return $viewDetails;
    }
}
