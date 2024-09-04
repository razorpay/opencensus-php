<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Illuminate\Support\Facades\App;
use Mail;
use Queue;
use Config;
use RZP\Models\Batch\Header;
use RZP\Models\Feature\Constants as FeatureConstants;
use Functional\Helpers\BvsTrait;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Mail\Merchant\MerchantBusinessWebsiteAdd;
use RZP\Mail\Merchant\RejectionReasonNotification;
use RZP\Mail\Merchant\MerchantBusinessWebsiteUpdate;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;


class MerchantDetailsTestBanking extends OAuthTestCase
{
    use PartnerTrait;
    use BvsTrait;
    use RazorxTrait;
    use PaymentTrait;
    use TerminalTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use WorkflowTrait;
    use MocksSplitz;
    use MocksDiagTrait;
    use CreateLegalDocumentsTrait;

    protected $config;

    protected $validator = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantDetailTestData.php';

        parent::setUp();

        $this->config = App::getFacadeRoot()['config'];

    }

    public function testUpdateBusinessTypeNotYetRegistered()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '4'
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $response = $this->startTest();

        $merchantDetails = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals($merchantDetails->getBusinessTypeValue(), $response[Header::BUSINESS_TYPE]);
    }

    public function testUpdateBusinessTypeNull()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateBusinessWebsiteRZPUrl()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '11'
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateBusinessWebsiteDummyUrl()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '11'
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateCompanyCinForPublicLimitedNegative()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '5',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateCompanyCinForPublicLimitedPositive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '5',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateCompanyCinForProprietorshipPositive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '1',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateCompanyCinForPrivateLimitedNegative()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '4',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateCompanyCinForPrivateLimitedPositive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '4',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateCompanyCinForLLPPositive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '6',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateGSTINForPrivateLimitedPositive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '4',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateGSTINForNotYetRegisteredPositive()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '11',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }

    public function testUpdateGSTINForProprietorshipNegative()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'business_type' => '1',
            'business_name' => 'ABC LTD',
        ]);

        $this->fixtures->org->addFeatures([FeatureConstants::VAS_ORG_IDENTIFIER],'100000razorpay');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        $this->startTest();
    }
}
