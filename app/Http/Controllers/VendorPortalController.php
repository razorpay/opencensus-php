<?php

namespace RZP\Http\Controllers;

class VendorPortalController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['vendor-portal'];
    }

    public function listVendorInvoices(string $vendorInviteId)
    {
        return $this->service->listVendorInvoices($this->ba->getUser(), $this->input, $vendorInviteId);
    }

    public function getVendorInvoice(string $vendorInviteId, string $vendorPaymentId)
    {
        return $this->service->getVendorInvoiceById($this->ba->getUser(), $vendorPaymentId, $vendorInviteId);
    }

    public function createVendorInvoice(string $vendorInviteId)
    {
        return $this->service->create($this->ba->getUser(), $this->input, $vendorInviteId);
    }

    public function listTdsCategories()
    {
        return $this->service->listTdsCategories();
    }

    public function getInvoiceSignedUrl(string $vendorInviteId, string $vendorPaymentId)
    {
        return $this->service->getInvoiceSignedUrl($this->ba->getUser(), $vendorInviteId, $vendorPaymentId);
    }

    public function listVendorPortalInvites()
    {
        return $this->service->listVendorPortalInvites($this->ba->getUser());
    }

    public function uploadInvoice(string $vendorInviteId)
    {
        return $this->service->uploadInvoice($vendorInviteId, $this->input, $this->ba->getUser());
    }

    public function getOcrData(string $vendorInviteId, string $ocrId)
    {
        return $this->service->getOcrData($vendorInviteId, $ocrId, $this->ba->getUser());
    }

    public function getVendorPreferences(string $vendorInviteId)
    {
        return $this->service->getVendorPreferences($this->ba->getUser(), $vendorInviteId);
    }

    public function updateVendorPreferences(string $vendorInviteId)
    {
        return $this->service->updateVendorPreferences($this->ba->getUser(), $this->input, $vendorInviteId);
    }
}
