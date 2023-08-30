<?php

namespace RZP\Tests\Functional\International;

use Functional\Helpers\BvsTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Unit\Models\Invoice\Traits\CreatesInvoice;


class CBMerchantTest extends TestCase
{
    use PaymentTrait;
    use ScheduleTrait;
    use SettlementTrait;
    use InteractsWithSession;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use CreatesInvoice;
    use PartnerTrait;
    use WorkflowTrait;
    use TestsWebhookEvents;
    use EventsTrait;
    use TestsBusinessBanking;
    use CustomBrandingTrait;
    use MocksRedisTrait;
    use FreshdeskTrait;
    use BvsTrait;
    use MocksDiagTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CBMerchantTestData.php';

        parent::setUp();
    }

    public function testAddBankAccountLRSSettlement()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->startTest();
    }

    public function testEditBankAccountLRSSettlement()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->startTest();
    }

    public function testBankAccountLRSSettlementWithoutAdmin()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $merchantUser = $this->fixtures->user->createUserForMerchant(10000000000000);

        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $this->startTest();
    }
}
