<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Queue;
use Config;
use RZP\Models\Base\EsDao;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

/**
 * todo, need to add test cases for VA Emails (https://razorpay.atlassian.net/browse/RX-1025)
 */
class HUFTest extends OAuthTestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '10000000000000';
    const RZP_ORG                   = '100000razorpay';
    const MERCHANT_ACTIVATED_WORKFLOW_DATA = 'MERCHANT_ACTIVATED_WORKFLOW_DATA';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/HUFTestData.php';

        parent::setUp();
    }

    protected function createAndFetchMocks($experimentEnabled)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app['razorx']->method('getTreatment')
                            ->willReturn($experimentEnabled ? 'on' : 'off');
    }

    public function testGetBusinessTypeExperimentOn()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->createAndFetchMocks(true);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant->getId()
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();
    }

    public function testGetBusinessTypeExperimentOff()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->createAndFetchMocks(false);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchant->getId()
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->id);

        $this->ba->proxyAuth('rzp_test_' . $merchant->id, $merchantUser['id']);

        $this->startTest();
    }

    public function testGetBusinessTypeSubMerchant()
    {
        $subMerchant = $this->fixtures->create('merchant');

        $subMerchantId = $subMerchant->getId();

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator']);

        // Assign submerchant to partner
        $accessMapData = [
            'entity_type'     => 'application',
            'merchant_id'     => $subMerchantId,
            'entity_owner_id' => '10000000000000',
        ];

        $this->fixtures->create('merchant_access_map', $accessMapData);

        $this->createAndFetchMocks(true);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $subMerchant->getId()
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($subMerchant->id);

        $this->ba->proxyAuth('rzp_test_' . $subMerchant->id, $merchantUser['id']);

        $this->startTest();
    }
    public function testGetBusinessTypeAdmin()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }
}
