<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Mail;

class AccountingPayoutsController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = $this->app['accounting-payouts'];
    }

    public function updateBAMapping()
    {
        return $this->service->updateBAMapping($this->ba->getMerchant(), $this->input);
    }

    public function listCashFlowBA()
    {
        return $this->service->listCashFlowBA($this->ba->getMerchant(), $this->input);
    }

    public function integrationAppGetURL(string $app)
    {
        return $this->service->getIntegrationURL($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function integrationAppInitiate(string $app)
    {
        return $this->service->integrationAppInitiate($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function integrationStatus()
    {
        return $this->service->integrationStatus($this->ba->getMerchant(), $this->input, $this->ba->getUser());
    }

    public function integrationStatusApp(string $app)
    {
        return $this->service->integrationStatusApp($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function callback()
    {
        return $this->service->callback($this->input);
    }

    public function appCredentials(string $app)
    {
        return $this->service->appCredentials($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function deleteIntegration(string $app)
    {
        return $this->service->deleteIntegration($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function syncStatus(string $app)
    {
        return $this->service->syncStatus($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function sync(string $app)
    {
        return $this->service->sync($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function syncInternal(string $app)
    {
        return $this->service->syncInternal($this->ba->getMerchant(), $this->input, $app);
    }

    public function waitlist(string $app)
    {
        return $this->service->waitlist($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function createTallyInvoice()
    {
        return $this->service->createTallyInvoice($this->ba->getMerchant(), $this->input);
    }

    public function fetchTallyInvoice()
    {
        return $this->service->fetchTallyInvoice($this->ba->getMerchant(), $this->input);
    }

    public function cancelTallyInvoice()
    {
        return $this->service->cancelTallyInvoice($this->ba->getMerchant(), $this->input);
    }

    public function fetchTallyPayments()
    {
        return $this->service->fetchTallyPayments($this->ba->getMerchant(), $this->input);
    }

    public function acknowledgeTallyPayment(string $id)
    {
        return $this->service->acknowledgeTallyPayment($this->ba->getMerchant(), $id, $this->input);
    }

    public function integrateTally()
    {
        return $this->service->integrateTally($this->ba->getMerchant(), $this->input);
    }

    public function deleteIntegrationTally()
    {
        return $this->service->deleteIntegrationTally($this->ba->getMerchant(), $this->input);
    }

    public function getOrganisationsInfo(string $app)
    {
        return $this->service->getOrganisationsInfo($this->ba->getMerchant(), $app);
    }

    public function setOrganisationInfo(string $app)
    {
        return $this->service->setOrganisationInfo($this->ba->getMerchant(), $app, $this->input);
    }
    public function getChartOfAccounts(string $app)
    {
        return $this->service->getChartOfAccounts($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function putChartOfAccounts(string $app)
    {
        return $this->service->putChartOfAccounts($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }

    public function syncChartOfAccounts(string $app)
    {
        return $this->service->syncChartOfAccounts($this->ba->getMerchant(), $this->input, $app, $this->ba->getUser());
    }
}
