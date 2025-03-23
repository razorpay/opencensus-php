<?php

namespace RZP\Tests\Functional\International;

use Accounts\Account\V1\Account;
use Accounts\Account\V1\GetAccountByIdResponse;
use Functional\Helpers\BvsTrait;
use Illuminate\Foundation\Testing\Concerns\InteractsWithSession;
use RZP\Jobs\CrossBorder\CrossBorderCommonUseCases;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Account as AsvSdkAccount;
use RZP\Services\PaymentsCrossBorderClient;
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

    public function testAddBankAccountLrsTravelFlow()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures(['lrs_travel_flow']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->startTest();
    }

    public function testEditBankAccountLrsTravelFlow()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures(['lrs_travel_flow']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->startTest();
    }

    public function testBankAccountLrsTravelFlowWithoutAdmin()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $merchantUser = $this->fixtures->user->createUserForMerchant(10000000000000);

        $this->fixtures->merchant->addFeatures(['lrs_travel_flow']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $this->startTest();
    }

    public function testAddBankAccountLrsEducationFlow()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->startTest();
    }

    public function testEditBankAccountLrsEducationFlow()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin['id'], ['allow_all_merchants' => true]);

        $this->ba->adminProxyAuth('10000000000000', 'rzp_test_' . '10000000000000');

        $this->startTest();
    }

    public function testBankAccountLrsEducationFlowWithoutAdmin()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $merchantUser = $this->fixtures->user->createUserForMerchant(10000000000000);

        $this->fixtures->merchant->addFeatures(['lrs_education_flow']);

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser['id']);

        $this->startTest();
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

    public function testFetchPXBDocuments()
    {
        $this->ba->proxyAuth();

        $mockResponse = [
            "items" => [
                [
                    "id" => "CCOhinUeUsT8HN",
                    "document_type" => "lrs_swift_copy",
                    "file_id" => "MQlRFCy5mrJDQT",
                    "entity_id" => "CCOhinUeUsT8HN",
                    "document_date" => "1692194209",
                    "created_at" => "1692194209",
                    "signed_url" => "https://s3file/test_doc.pdf"
                ]
            ],
            "success" => true
        ];

        $pxbServiceMock = $this->getMockBuilder(PaymentsCrossBorderClient::class)
            ->onlyMethods(['getDocuments'])->getMock();
        $this->app->instance('payments-cross-border', $pxbServiceMock);
        $pxbServiceMock->method("getDocuments")
            ->willReturn($mockResponse);

        $response = $this->startTest();

        $this->assertEquals($mockResponse,$response);
    }

    public function testSaveCaseApprovalInAccountAdditionalDetailsForCrossBorderOnboarding()
    {
        $merchantId= '10000000000002';

        $merchant = $this->fixtures->create('merchant', ['id' => '10000000000002',
            'email' => 'razorpay@razorpay.com']);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $merchantDetails = $this->fixtures->merchant_detail->create([
            'merchant_id'    => $merchant->getId(),
        ]);

        $this->fixtures->edit('merchant',
            '10000000000002',
            ['activated' => false]);

        $asvSdkAccount = new AsvSdkAccount();
        $mockAccountClient = $this->getMockAsvClient();

        $this->mockAsvResponsesForCrossBorderOnboarding('get_additional_details', $asvSdkAccount, $mockAccountClient);

        $this->mockAsvResponsesForCrossBorderOnboarding('edd_non_verified', $asvSdkAccount, $mockAccountClient);

        $this->mockAsvResponsesForCrossBorderOnboarding('save_additional_details', $asvSdkAccount, $mockAccountClient);

        $data = [
            'merchant_id' => $merchantId,
            'action' => CrossBorderCommonUseCases::ACTIVATE_CROSS_BORDER_MODULAR_ONBOARDING_MERCHANT,
            'mode' => 'live',
        ];
        (new CrossBorderCommonUseCases($data))->handle();
    }

    protected function mockAsvResponsesForCrossBorderOnboarding($case, $asvSdkAccount,  $mockAccountClient)
    {
        $asvSdkAccount->getAsvSdkClient()->setAccount($mockAccountClient);

        // Choose JSON data based on the case
        switch ($case) {
            case 'get_additional_details':
                $jsonData = '{"id":"10000000000002","additional_detail":{"details":{"cross_border_onboarding":{"cross_border_intent":true}}}}';
                break;

            case 'edd_non_verified':
                $jsonData = '{"id":"10000000000002","account_detail":{"edd_verification_status":"pending"}}';
                break;
            case 'save_additional_details':
                $jsonData = '{"id":"10000000000002","additional_detail":{"details":{"cross_border_onboarding":{"cross_border_intent":true, "case_approved":true}}}}';
                break;

            default:
                $jsonData = '{"id":"10000000000002","additional_detail":{"details":{"cross_border_onboarding":{"cross_border_intent":true}}}}';

        }

        $account = new Account();
        $account->mergeFromJsonString($jsonData);


        $response = new GetAccountByIdResponse();
        $response->setAccount($account);
        if ($case === 'save_additional_details') {
            $fieldList = ["account.additional_detail.details"];
            $mockAccountClient->expects($this->once())->method("Save")->willReturn([$response, null]);
        } else {
            $mockAccountClient->expects($this->any())
                ->method('GetAccountById')
                ->withAnyParameters()
                ->willReturn([$response, null]);
        }
    }

    protected function getMockAsvClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\AccountInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }
}
