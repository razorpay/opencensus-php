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

    public function getBankStatementReport()
    {
        return $this->service->getBankStatementReport($this->ba->getMerchant(), $this->input);
    }

    public function addOrUpdateSettings()
    {
        return $this->service->addOrUpdateSettings($this->ba->getMerchant(), $this->input);
    }

    public function getAllSettings()
    {
        return $this->service->getAllSettings($this->ba->getMerchant(), $this->input);
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

    public function acknowledgeCashFlowEntries()
    {
        return $this->service->acknowledgeCashFlowEntries($this->ba->getMerchant(), $this->input);
    }

    public function updateMappingCashFlowEntries()
    {
        return $this->service->updateMappingCashFlowEntries($this->ba->getMerchant(), $this->input);
    }

    public function createTallyVendors()
    {
        return $this->service->createTallyVendors($this->ba->getMerchant(), $this->input);
    }

    public function fetchSyncStatus()
    {
        return $this->service->fetchSyncStatus($this->ba->getMerchant(), $this->input);
    }

    public function getTaxSlabs()
    {
        return $this->service->getTaxSlabs($this->ba->getMerchant(), $this->input);
    }

    public function getCashFlowEntries()
    {
        return $this->service->getCashFlowEntries($this->ba->getMerchant(), $this->input);
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

    public function bankStatementFetchTriggerMerchant()
    {
        return $this->service->bankStatementFetchTriggerMerchant($this->ba->getMerchant(), $this->input);
    }

    public function bankStatementFetchTriggerCron()
    {
        return $this->service->bankStatementFetchTriggerCron();
    }

    public function zohoStatementSyncCron()
    {
        return $this->service->zohoStatementSyncCron();
    }

    public function getMerchantBankingAccountsForTally()
    {
        return $this->service->getMerchantBankingAccountsForTally($this->ba->getMerchant());
    }

    public function updateRxTallyLedgerMapping()
    {
        return $this->service->updateRxTallyLedgerMapping($this->ba->getMerchant(), $this->input);
    }

    public function getTallyBankTransactions()
    {
        return $this->service->getTallyBankTransactions($this->ba->getMerchant(), $this->input);
    }

    public function ackTallyBankTransactions()
    {
        return $this->service->ackTallyBankTransactions($this->ba->getMerchant(), $this->input);
    }

    public function getBankTransactionsSyncStatus()
    {
        return $this->service->getBankTransactionsSyncStatus($this->ba->getMerchant(), $this->input);
    }

    public function checkIfBankMappingRequired()
    {
        return $this->service->checkIfBankMappingRequired($this->ba->getMerchant());
    }

}
