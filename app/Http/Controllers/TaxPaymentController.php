<?php

namespace RZP\Http\Controllers;

use Mail;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Mail\VendorPayments\Unpaid;
use RZP\Models\User\Core as UserCore;

class TaxPaymentController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['tax-payments'];
    }

    public function payTaxPayment(string $taxPaymentId)
    {
        return $this->service->payTaxPayment($this->ba->getMerchant(),
                                             $taxPaymentId,
                                             $this->input,
                                             $this->ba->getUser());
    }

    public function listTaxPayments()
    {
        return $this->service->listTaxPayments($this->ba->getMerchant(), $this->input);
    }

    /*
     * returns all the tax-payment related settings
     */
    public function getAllSettings()
    {
        return $this->service->getAllSettings($this->ba->getMerchant());
    }

    public function addOrUpdateSettings()
    {
        return $this->service->addOrUpdateSettings($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function getTaxPayment(string $taxPaymentId)
    {
        return $this->service->getTaxPayment($this->ba->getMerchant(), $taxPaymentId, $this->input);
    }

}
