<?php

namespace RZP\Tests\Functional\Merchant\Account;

use Mail;
use Illuminate\Database\Eloquent\Factory;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\User\Role;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as FName;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Mail\Merchant\CreateSubMerchantAffiliate;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PartnerAccountTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use PartnerTrait;

    const RZP_ORG = '100000razorpay';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerAccountTestData.php';

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);
    }

    public function testCreateAccountForCompletelyFilledRequest()
    {
        Mail::fake();

        $this->setUpPartnerWithKycHandled();

        $response = $this->startTest();

        // assert that legal entity is created for the submerchant
        $legalEntity = $this->getDbLastEntity('legal_entity');

        $this->assertEquals($legalEntity->getId(), $response['legal_entity_id']);
        $this->assertEquals(6, $legalEntity->getBusinessTypeValue());
        $this->assertEquals($legalEntity->getMcc(), 7011);
        $this->assertEquals('tours_and_travel', $legalEntity->getBusinessCategory());
        $this->assertEquals('accommodation', $legalEntity->getBusinessSubcategory());

        // check that user creation email is not sent to submerchant from rzp
        Mail::assertNotQueued(CreateSubMerchantAffiliate::class);

        $subMerchant = $this->getDbLastEntity('merchant');

        $this->assertEquals('greylist', $subMerchant->merchantDetail->getActivationFlow());
    }

    public function testCreateAccountUnderLegalEntity()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $response1 = $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $testData['request']['content']['legal_entity_id'] = $response1['legal_entity_id'];

        $response2 = $this->runRequestResponseFlow($testData);

        $this->assertEquals($response1['legal_entity_id'], $response2['legal_entity_id']);
    }

    public function testCreateAccountForThinRequest()
    {
        $this->setUpPartnerWithKycHandled();

        $this->startTest();
    }

    public function testCreateAccountWithDuplicateEmail()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['email'] = 'email.Ojha@test.com';

        $this->startTest($testData);
    }

    public function testCreateAccountWithInvalidMCCCode()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['profile']['mcc'] = '1234';

        $this->startTest($testData);
    }

    public function testCreateAccountWithoutRegisteredAddress()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData[__FUNCTION__];

        $testData['request'] = $this->testData['testCreateAccountForThinRequest']['request'];
        $testData['request']['content']['profile']['addresses'][0]['type'] = 'operation';

        $this->startTest($testData);
    }

    public function testCreateAccountForInvalidPartner()
    {
        $this->fixtures->merchant->addFeatures([FName::SUBMERCHANT_ONBOARDING]);

        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::RESELLER);

        $this->ba->privateAuth();

        $this->startTest();
    }

    /**
     * Test that changing all the attributes works
     */
    public function testEditAccount()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    /**
     * Test that un-setting all the non required attributes works
     */
    public function testEditThinAccount()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testEditPhoneNumber()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testEditProfileData()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAccount()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $result = $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'];

        $this->startTest($testData);
    }

    public function testFetchAllAccounts()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testCreateAccountForThinRequest'];

        $this->runRequestResponseFlow($testData);

        $result = $this->startTest();

        $this->assertCount(2, $result);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = ['skip' => 1];

        $result = $this->startTest($testData);

        $this->assertCount(1, $result);
    }

    public function testEnableAccountAction()
    {
        $this->setUpPartnerWithKycHandled();

        // creating account
        $testData = $this->testData['testCreateAccountForThinRequest'];

        $result = $this->runRequestResponseFlow($testData);

        // disable account
        $testData = $this->testData['testDisableAccountAction'];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/disable';

        $this->runRequestResponseFlow($testData);

        // enable account
        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/enable';

        $this->startTest($testData);
    }

    public function testCreateAccountCompletelyFilledRequestWithKycNotHandled()
    {
        Mail::fake();

        $this->setUpPartnerWithKycNotHandled();

        $response = $this->startTest();

        // assert that legal entity is created for the submerchant
        $legalEntity = $this->getDbLastEntity('legal_entity');

        $this->assertEquals($legalEntity->getId(), $response['legal_entity_id']);
        $this->assertEquals(6, $legalEntity->getBusinessTypeValue());
        $this->assertEquals($legalEntity->getMcc(), 7011);
        $this->assertEquals('tours_and_travel', $legalEntity->getBusinessCategory());
        $this->assertEquals('accommodation', $legalEntity->getBusinessSubcategory());

        // check that user creation email is not sent to submerchant from rzp
        Mail::assertNotQueued(CreateSubMerchantAffiliate::class);

        $subMerchant = $this->getDbLastEntity('merchant');

        $this->assertEquals('greylist', $subMerchant->merchantDetail->getActivationFlow());
    }

    public function testCreateAccountForThinRequestWithKycNotHandled()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        $this->startTest();
    }

    public function testFetchAccountForKycNotHandledAndNeedsClarification()
    {
        $this->setUpPartnerWithKycNotHandled();

        // testUpdateKYCClarificationReason
        $subMerchant = $this->createUnderReviewAccount();

        $testData = $this->testData['updateClarificationReason'];

        $testData['request']['url'] = '/merchant/activation/'.$subMerchant->getId().'/update';

        $this->ba->adminAuth();

        $this->runRequestResponseFlow($testData);

        $this->changeActivationStatus($subMerchant->getId(), 'needs_clarification');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/acc_'. $subMerchant->getId();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->startTest($testData);
    }

    public function testAddAccountUnderLegalEntityWithKycNotHandled()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        // testUpdateKYCClarificationReason
        $subMerchant = $this->createUnderReviewAccount();

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['legal_entity_id'] = $subMerchant->getLegalEntityId();

        $response2 = $this->runRequestResponseFlow($testData);

        $this->assertEquals($subMerchant->getLegalEntityId(), $response2['legal_entity_id']);
    }

    public function testFetchAccountWitKycNotHandledAfterActivation()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        // testUpdateKYCClarificationReason
        $subMerchant = $this->createUnderReviewAccount();

        $this->ba->adminAuth();

        $this->changeActivationStatus($subMerchant->getId(), 'activated');

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/acc_'. $subMerchant->getId();

        $this->runRequestResponseFlow($testData);
    }

    // Partner should be able to create account using partner Auth as well, if it does not send X-Razorpay-Account
    public function testCreateAccountWithPartnerAuth()
    {
        $this->setUpPartnerAuthWithoutSubMerchantAccountId();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $acc = $this->runRequestResponseFlow($testData);

        $merchant = (new Merchant\Repository)->getPartnerMerchantFromSubMerchantId(substr($acc['id'],4));

        $this->assertEquals($merchant->getId(), '10000000000000');
    }

    protected function changeActivationStatus($merchantId, $status)
    {
        $testData = $this->testData['changeActivationStatus'];

        $testData['request']['url'] = '/merchant/activation/'. $merchantId. '/activation_status';
        $testData['request']['content']['activation_status'] = $status;
        $testData['response']['content']['activation_status'] = $status;

        $this->runRequestResponseFlow($testData);
    }

    protected function createUnderReviewAccount()
    {
        $testData = $this->testData['testCreateAccountCompletelyFilledRequestWithKycNotHandled'];

        $result = $this->runRequestResponseFlow($testData);

        $detailsData = [];

        foreach ($result['review_status']['requirements']['businesses']['documents'] as $document)
        {
            $detailsData[$document['type']] = '1234';

            $documentArray = [
                'document_type' => $document['type'],
                'merchant_id'   => Account\Entity::stripDefaultSign($result['id']),
            ];

            $this->fixtures->merchant_document->create($documentArray);
        }

        $merchant = $this->getDbLastEntity('merchant');

        $this->fixtures->on('test')->edit('merchant_detail', $merchant->getId(), $detailsData);
        $this->fixtures->on('live')->edit('merchant_detail', $merchant->getId(), $detailsData);

        $testData = $this->testData['submitKyc'];

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId());

        $this->runRequestResponseFlow($testData);

        return $merchant;
    }

    protected function setUpPartnerWithKycHandled()
    {
        $this->setUpNonPurePlatformPartner();

        $features = [
            FName::NO_COMM_WITH_SUBMERCHANTS,
            FName::KYC_HANDLED_BY_PARTNER,
            FName::SUBMERCHANT_ONBOARDING,
        ];

        $this->fixtures->merchant->addFeatures($features);
    }

    protected function setUpPartnerWithKycNotHandled()
    {
        $this->setUpNonPurePlatformPartner();

        $features = [
            FName::NO_COMM_WITH_SUBMERCHANTS,
            FName::SUBMERCHANT_ONBOARDING,
        ];

        $this->fixtures->merchant->addFeatures($features);
    }

    protected function setUpNonPurePlatformPartner()
    {
        $this->markMerchantAsNonPurePlatformPartner('10000000000000', Constants::AGGREGATOR);

        $this->fixtures->user->createUserForMerchant('10000000000000', [], Role::OWNER, Mode::LIVE);

        $this->fixtures->merchant->editPricingPlanId('1hDYlICobzOCYt');

        $orgHostName = $this->fixtures->org->build('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.in'
        ]);

        $orgHostName->setConnection('live')->saveOrFail();

        // Merchant needs to be activated to make live requests
        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');
    }

    protected function setUpPartnerAuthWithoutSubMerchantAccountId()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->merchant->edit('10000000000000', ['activated' => 1]);

        $features = [
            FName::KYC_HANDLED_BY_PARTNER,
            FName::SUBMERCHANT_ONBOARDING,
        ];

        $this->fixtures->merchant->addFeatures($features);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'aggregator', 'pricing_plan_id' => '1hDYlICobzOCYt']);

        $partner = $this->getDbEntityById('merchant', '10000000000000');

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());
    }
}
