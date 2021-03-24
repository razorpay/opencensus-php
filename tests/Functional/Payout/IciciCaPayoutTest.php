<?php

namespace Functional\Payout;

use Queue;
use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Constants\Timezone;
use RZP\Models\Pricing\Fee;
use RZP\Services\Mock\Mozart;
use RZP\Models\BankingAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\GatewayErrorException;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Jobs\IciciBankingAccountGatewayBalanceUpdate;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class IciciCaPayoutTest extends TestCase
{
    use PayoutTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/IciciCaPayoutTestData.php';

        parent::setUp();

        $this->fixtures->create('org:razorpay_org_live');

        $this->fixtures->on('test')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('test')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000, 'direct', 'icici');

        $bankingAccountParams = [
            'id'             => 'xba00000000002',
            'merchant_id'    => '10000000000000',
            'account_ifsc'   => 'ICIC0000047',
            'account_number' => '2224440041626907',
            'status'         => 'activated',
            'channel'        => 'icici',
            'balance_id'     => $this->bankingBalance->getId(),
        ];

        $this->createBankingAccount($bankingAccountParams);
    }

    protected function liveSetUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutTestData.php';

        $this->fixtures->on('live')->create('contact', ['id' => '1000001contact', 'active' => 1]);

        $this->fixtures->on('live')->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBankingLive(true, 10000000, 'direct', 'icici');

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['pricing_plan_id' => Fee::DEFAULT_PRICING_PLAN_ID]);

        // Merchant needs to be activated to make live requests
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        // Create merchant user mapping
        $this->fixtures->on('live')->user->createUserMerchantMapping([
                                                                         'merchant_id' => '10000000000000',
                                                                         'user_id'     => User::MERCHANT_USER_ID,
                                                                         'product'     => 'primary',
                                                                         'role'        => 'owner',
                                                                     ], 'live');
    }

    protected function mockMozartResponseForFetchingBalanceFromIciciGateway($amount, $exception = null): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
                                  ->setConstructorArgs([$this->app])
                                  ->setMethods(['sendMozartRequest'])
                                  ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
                          ->willReturn([
                                           Icici\Fields::DATA => [

                                               Icici\Fields::BALANCE => $amount
                                           ]
                                       ]);

        if ($exception !== null)
        {
            $mozartServiceMock->method('sendMozartRequest')
                              ->willThrowException($exception);
        }

        $this->app->instance('mozart', $mozartServiceMock);
    }

    protected function setupIciciDispatchGatewayBalanceUpdateForMerchants()
    {
        (new Admin\Service)->setConfigKeys([
            Admin\ConfigKey::ICICI_BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_RATE_LIMIT => 1]);

        $request = [
            'method'  => 'put',
            'url'     => '/banking_accounts/gateway/icici/balance',
        ];

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testDispatchGatewayBalanceUpdateJob()
    {
        Queue::fake();

        $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        Queue::assertPushed(IciciBankingAccountGatewayBalanceUpdate::class, 1);
    }

    public function testProcessGatewayBalanceUpdate()
    {
        /** @var BankingAccount\Entity $baBeforeTest */
        $baBeforeTest = $this->getDbEntityById('banking_account', 'xba00000000002');

        $this->assertNull($baBeforeTest->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(500);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var BankingAccount\Entity $baAfterCronRuns */
        $baAfterCronRuns = $this->getDbEntityById('banking_account', 'xba00000000002');

        /** @var Details\Entity $baAfterCronRuns */
        $basDetailsAfterCronRuns = $this->getDbEntity('banking_account_statement_details',
                                                      ['account_number' => 2224440041626907]);

        $this->assertEquals(50000, $basDetailsAfterCronRuns->getGatewayBalance());

        $this->assertNotNull($baAfterCronRuns->getBalanceLastFetchedAt());
    }

    public function testGatewayBalanceFetchWithGatewayFailure()
    {
        $exception = new GatewayErrorException("GATEWAY_ERROR_UNKNOWN_ERROR",
                                               "Failure",
                                               "(No error description was mapped for this error code)");
        $mozartError = [
            "description"               => "",
            "gateway_error_code"        => "Failure",
            "gateway_error_description" => "(No error description was mapped for this error code)",
            "gateway_status_code"       => 200,
            "internal_error_code"       => "GATEWAY_ERROR_UNKNOWN_ERROR",
        ];

        /** @var BankingAccount\Entity $baBeforeTest */
        $baBeforeTest = $this->getDbEntityById('banking_account', 'xba00000000002');

        $this->assertNull($baBeforeTest->getBalanceLastFetchedAt());

        $this->mockMozartResponseForFetchingBalanceFromIciciGateway(null, $exception);

        $response = $this->setupIciciDispatchGatewayBalanceUpdateForMerchants();

        /** @var BankingAccount\Entity $baAfterCronRuns */

        $baAfterCronRuns = $this->getDbEntityById('banking_account', 'xba00000000002');

        $this->assertNull($baAfterCronRuns->getBalanceLastFetchedAt());
    }
}
