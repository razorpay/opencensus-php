<?php

namespace RZP\Tests\Functional\AccountingPayouts;

use Mockery;

use App;

use Nyholm\Psr7\Factory\HttplugFactory;
use RZP\Models\User\BankingRole;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class AccountingPayoutsTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    protected $config;

    private $ownerRoleUser;

    private $finL1RoleUser;

    private $viewOnlyRoleUser;

    private $opsRoleUser;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/AccountingPayoutsData.php';

        parent::setUp();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->setupUserRoles();

        $this->config = App::getFacadeRoot()['config'];

    }

    public function setupUserRoles()
    {
        $this->ownerRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->finL1RoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'finance_l1', 'live');

        $this->viewOnlyRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'view_only', 'live');

        $this->opsRoleUser = $this->fixtures->user->createBankingUserForMerchant('10000000000000', [], 'operations', 'live');
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn($returnValue);
    }

    public function testGetIntegrationUrlServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getIntegrationURL')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getIntegrationURL');
    }

    public function testOperationsCannotAccessUpdateBankMapping()
    {
        $this->mockRazorx();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->opsRoleUser->getId());

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();
    }

    public function testOperationsCannotCreateIntegrationURLMapping()
    {
        $this->mockRazorx();

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->opsRoleUser->getId());

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();
    }

    public function testInitiateIntegrationServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationAppInitiate')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrationAppInitiate');
    }

    public function testIntegrationStatusServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrationStatus');
    }

    public function testIntegrationStatusAppServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationStatusApp')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrationStatusApp');
    }

    public function testCallbackServiceMethod()
    {
        $this->ba->directAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('callback')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('callback');
    }

    public function testAppCredentialsServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('appCredentials')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('appCredentials');
    }

    public function testDeleteServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('deleteIntegration')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('deleteIntegration');
    }

    public function testSyncStatusServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('syncStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('syncStatus');
    }


    public function testSyncServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('sync')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('sync');
    }

    public function testWaitlistServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('waitlist')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('waitlist');
    }

    public function testCallbackRequestCalledDirectly()
    {
        $apMock = Mockery::mock('RZP\Services\AccountingPayouts\Service')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $apMock->shouldAllowMockingMethod('request')
            ->shouldReceive('request')
            ->andReturn([]);

        $apMock->shouldAllowMockingMethod('makeRequest')
            ->shouldReceive('makeRequest')
            ->never();

        $apMock->callback([]);
    }

    public function testIntegrationStatusForViewOnlyUsers()
    {
        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->mockRazorxTreatment('on');

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->viewOnlyRoleUser->getId());

        $this->startTest();

        $apMock->shouldHaveReceived('integrationStatus');
    }

    public function testSyncStatusForViewOnlyUsers()
    {
        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('syncStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->mockRazorxTreatment('on');

        $this->ba->proxyAuth('rzp_live_10000000000000', $this->viewOnlyRoleUser->getId());

        $this->startTest();

        $apMock->shouldHaveReceived('syncStatus');
    }


    public function testCreateTallyInvoiceServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('createTallyInvoice')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('createTallyInvoice');
    }

    public function testGetTaxSlabServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getTaxSlabs')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getTaxSlabs');
    }

    public function testGetAllSettingsMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getAllSettings')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getAllSettings');
    }

    public function testAddOrUpdateSettingsMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('addOrUpdateSettings')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('addOrUpdateSettings');
    }

    public function testGetBankStatementReportMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getBankStatementReport')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getBankStatementReport');
    }

    public function testCreateTallyVendorsServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('createTallyVendors')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('createTallyVendors');
    }

    public function testSyncVendorStatusServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('fetchSyncStatus')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('fetchSyncStatus');
    }

    public function testGetCashFlowEntriesServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getCashFlowEntries')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getCashFlowEntries');
    }

    public function testGetTallyBankTransactionsServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getTallyBankTransactions')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getTallyBankTransactions');
    }

    public function testAckTallyBankTransactionsServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('ackTallyBankTransactions')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('ackTallyBankTransactions');
    }

    public function testFetchTallyInvoiceServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('fetchTallyInvoice')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('fetchTallyInvoice');
    }

    public function testCancelTallyInvoiceServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('cancelTallyInvoice')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('cancelTallyInvoice');
    }

    public function testFetchTallyPaymentsServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('fetchTallyPayments')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('fetchTallyPayments');
    }

    public function testAcknowledgeTallyPaymentServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('acknowledgeTallyPayment')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('acknowledgeTallyPayment');
    }

    public function testIntegrateTallyServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrateTally')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('integrateTally');
    }

    public function testDeleteIntegrationTallyServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('deleteIntegrationTally')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('deleteIntegrationTally');
    }

    public function testGetOrganisationsInfoServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getOrganisationsInfo')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getOrganisationsInfo');
    }

    public function testSetOrganisationsInfoServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('setOrganisationInfo')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('setOrganisationInfo');
    }

    public function testUpdateBankAccountMappingCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('updateBAMapping')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('updateBAMapping');
    }

    public function testListCashFlowBankAccountCallsServiceMethods()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('listCashFlowBA')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('listCashFlowBA');
    }

    public function testGetChartOfAccountsServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getChartOfAccounts')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getChartOfAccounts');
    }

    public function testPutChartOfAccountsServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('putChartOfAccounts')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('putChartOfAccounts');
    }

    public function testSyncChartOfAccountsServiceMethod()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('syncChartOfAccounts')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('syncChartOfAccounts');
    }

    public function testCreateIntegrationFromL1Role()
    {
        $user = $this->fixtures->create('user', ['id' => '20000000000006']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => BankingRole::FINANCE_L1,
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user['id']);

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('integrationAppInitiate')->andReturn([]);
        $this->app->instance('accounting-payouts', $apMock);

        $this->mockRazorxTreatment();

        $this->startTest();

        $apMock->shouldHaveReceived('integrationAppInitiate');

    }

    public function testCashFlowEntriesAckServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('acknowledgeCashFlowEntries')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('acknowledgeCashFlowEntries');
    }

    public function testCashFlowUpdateMappingServiceMethod()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('updateMappingCashFlowEntries')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('updateMappingCashFlowEntries');
    }

    public function testBankStatementFetchTriggerMerchant()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('bankStatementFetchTriggerMerchant')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('bankStatementFetchTriggerMerchant');
    }

    public function testBankStatementFetchTriggerCron()
    {
        $this->ba->cronAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('bankStatementFetchTriggerCron')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('bankStatementFetchTriggerCron');
    }

    public function testZohoStatementSyncCron()
    {
        $this->ba->cronAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('zohoStatementSyncCron')->andReturn([]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('zohoStatementSyncCron');
    }

    public function testGetMerchantBankingAccountsForTally()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getMerchantBankingAccountsForTally')->andReturn([
            'account_details' => [
                [
                    'account_number' => '12345566',
                    'account_type' => 'Virtual Account'
                ],
                [
                    'account_number' => '12345577',
                    'account_type' => 'Current Account'
                ]
            ]
        ]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getMerchantBankingAccountsForTally');
    }

    public function testUpdateRxTallyLedgerMapping()
    {
        $this->ba->privateAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('updateRxTallyLedgerMapping')->andReturn([
            'success' => 'true',
        ]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('updateRxTallyLedgerMapping');
    }

    public function testGetBankTransactionsSyncStatus()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('getBankTransactionsSyncStatus')->andReturn([
            'count' => 2,
            'entity' => 'collection',
            'items' => [
                [
                    'entity_id' => 'txn_id-1',
                    'entity_type' => 'transaction',
                    'reason' => '',
                    'status' => 'ready-to-sync',
                    'sync_completed_at' => 0,
                    'sync_failed_at' => 0,
                    'sync_started_at' => 12345688,
                ],
                [
                    'entity_id' => 'txn_id-2',
                    'entity_type' => 'transaction',
                    'reason' => '',
                    'status' => 'syncing',
                    'sync_completed_at' => 0,
                    'sync_failed_at' => 0,
                    'sync_started_at' => 12345688,
                ]
            ]
        ]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('getBankTransactionsSyncStatus');
    }

    public function testCheckIfBankMappingRequired()
    {
        $this->ba->proxyAuth();

        $apMock = Mockery::mock('RZP\Services\AccountingPayouts');

        $apMock->shouldReceive('checkIfBankMappingRequired')->andReturn([
            "is_required" => true
        ]);

        $this->app->instance('accounting-payouts', $apMock);

        $this->startTest();

        $apMock->shouldHaveReceived('checkIfBankMappingRequired');
    }

    public function testEdgeProxyForAccountingIntegrationsSuccessProxyAuth()
    {
        $this->ba->proxyAuth();

        $data = [
            'id' => 'rule_testid',
            'type' => 'rx_contact'
        ];

        $httpMock = $this->mockEdgeProxyHttpClient($data, 200);

        $this->app->instance('edge_proxy_http_client', $httpMock);

        $this->startTest();

        $httpMock->shouldHaveReceived('sendRequest');
    }

    public function testEdgeProxyForAccountingIntegrations5xxProxyAuth()
    {
        $this->ba->proxyAuth();

        $data = [
            'message' => 'request could not be completed due to internal error',
            'details' => [
                'code' => 'INTERNAL_SERVER_ERROR'
            ]
        ];

        $httpMock = $this->mockEdgeProxyHttpClient($data, 500);

        $this->app->instance('edge_proxy_http_client', $httpMock);

        $this->startTest();

        $httpMock->shouldHaveReceived('sendRequest');
    }

    public function testEdgeProxyForAccountingIntegrationsDirectAuth()
    {
        $this->ba->directAuth();

        $data = [
            'message' => 'integration completed successfully'
        ];

        $httpMock = $this->mockEdgeProxyHttpClient($data, 200);

        $this->app->instance('edge_proxy_http_client', $httpMock);

        $this->startTest();

        $httpMock->shouldHaveReceived('sendRequest');
    }

    protected function mockEdgeProxyHttpClient(array $data, int $statusCode)
    {
        $expectedResp = (new HttplugFactory)->createResponse($statusCode, null, [], json_encode($data));

        $httpMock = Mockery::mock('RZP\Base\Http');

        $httpMock->shouldReceive('sendRequest')->andReturn($expectedResp);

        return $httpMock;
    }

    /**
     *  added for RAZORPAY_X_ACL_DENY_UNAUTHORISED which was added to
     *  identify impact on other clients if unauthorised requests are blocked.
     *  check UserAccess.php -> validateBankingUserAccess for better understanding
     */
    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode)
                {
                    if ($feature === 'razorpay_x_acl_deny_unauthorised')
                    {
                        return 'on';
                    }
                    return 'on';
                }));
    }
}

