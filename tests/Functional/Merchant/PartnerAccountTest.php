<?php

namespace RZP\Tests\Functional\Merchant\Account;

use DB;
use Mail;
use Mockery;
use Illuminate\Database\Eloquent\Factory;

use Psr\Http\Message\ResponseInterface;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\User\Core;
use RZP\Models\User\Role;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Constants;
use RZP\Tests\Functional\Fixtures\Entity\Org as Org;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsMetrics;
use RZP\Models\Merchant\Account\Metric;
use RZP\Tests\Traits\TestsWebhookEvents;
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
    use TestsWebhookEvents;
    use TestsMetrics;

    const RZP_ORG = '100000razorpay';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerAccountTestData.php';

        parent::setUp();

        $storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $storkMock);

        $this->app['stork_service']->shouldReceive('sendWhatsappMessage')->andReturn([]);
    }

    public function testCreateAccountForCompletelyFilledRequest()
    {
        Mail::fake();

        [$client] = $this->setUpPartnerWithKycHandled();

        // expectWebhookEvent overrides the stork mock.
        $this->expectWebhookEvent('account.under_review');

        $this->app['stork_service']->shouldReceive('sendWhatsappMessage')->andReturn([]);

        $metricsMock = $this->createMetricsMock();

        $metricCaptured = false;

        $expectedMetricData = $this->getDimensionsForAccountMetrics();

        $this->mockAndCaptureCountMetric(Metric::ACCOUNT_V1_CREATE_SUCCESS_TOTAL, $metricsMock, $metricCaptured, $expectedMetricData);

        $response = $this->startTest();

        $this->assertTrue($metricCaptured);

        // assert that legal entity is created for the submerchant
        $legalEntity = $this->getDbLastEntity('legal_entity');

        $this->assertEquals($legalEntity->getId(), $response['legal_entity_id']);
        $this->assertEquals(6, $legalEntity->getBusinessTypeValue());
        $this->assertEquals($legalEntity->getMcc(), '7011');
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
        $testData['request']['content']['email'] = 'email.ojha@test.com';

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

        // create terminal
        $terminal = $this->fixtures->on('live')->create('terminal', [
            'enabled'     => true,
            'status'      => 'activated',
            'merchant_id' => Account\Entity::stripDefaultSign($result['id']),
            'gateway'     => 'worldline',
            'mc_mpan'     => '1234567890123456',
            'visa_mpan'   => '9876543210123456',
            'rupay_mpan'  => '1234123412341234',
            'notes'       => 'some notes',
            'type'        => [
                Terminal\Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Terminal\Type::NON_RECURRING                    => '1',
            ],
        ]);

        $this->app['config']->set('gateway.mock_mozart', true);

        // disable account
        $testData = $this->testData['testDisableAccountAction'];

        $testData['request']['url'] = '/accounts/'. $result['id'] . '/disable';

        $this->runRequestResponseFlow($testData);

        $terminal->reload();

        $this->assertEquals($terminal['enabled'], false);

        $this->assertEquals($terminal['status'], 'deactivated');

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
        $this->assertEquals($legalEntity->getMcc(), '7011');
        $this->assertEquals('FBLegalExternalId', $legalEntity->getExternalId());
        $this->assertEquals('tours_and_travel', $legalEntity->getBusinessCategory());
        $this->assertEquals('accommodation', $legalEntity->getBusinessSubcategory());

        // check that user creation email is not sent to submerchant from rzp
        Mail::assertNotQueued(CreateSubMerchantAffiliate::class);

        $subMerchant = $this->getDbLastEntity('merchant');

        $this->assertEquals('greylist', $subMerchant->merchantDetail->getActivationFlow());
        $this->assertEquals('greylist', $subMerchant->merchantDetail->getInternationalActivationFlow());

        $this->assertEquals('FBUniqueExternalId', $subMerchant->getExternalId());

        $legalEntities = $this->getDbEntities('legal_entity');

        $this->assertEquals(1, $legalEntities->count());
    }

    public function testFetchAllAccountWithKycNotHandled()
    {
        $this->setUpPartnerWithKycNotHandled();

        $testData = $this->testData['testCreateAccountCompletelyFilledRequestWithKycNotHandled'];

        $this->runRequestResponseFlow($testData);

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        $testData = $this->testData['testCreateAccountForThinRequestWithKycNotHandled'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);
    }

    public function testFetchAccountByExternalId()
    {
        $this->setUpPartnerWithKycNotHandled();

        $testData = $this->testData['testCreateAccountCompletelyFilledRequestWithKycNotHandled'];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData['testFetchAccountByInvalidExternalId'];

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAccountWithKycNotHandledAndDuplicateExternalId()
    {
        $this->setUpPartnerWithKycNotHandled();

        $testData = $this->testData['testCreateAccountCompletelyFilledRequestWithKycNotHandled'];

        $this->runRequestResponseFlow($testData);

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData);
    }

    public function testCreateAccountForThinRequestWithKycNotHandled()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        $this->startTest();
    }

    public function testFetchAccountForKycNotHandledAndNeedsClarification()
    {
        [$client] = $this->setUpPartnerWithKycNotHandled();

        $this->expectWebhookEvent('account.under_review');

        $this->app['stork_service']->shouldReceive('sendWhatsappMessage')->andReturn([]);

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

        $legalEntities = $this->getDbEntities('legal_entity');

        $this->assertEquals(1, $legalEntities->count());
    }

    public function testFetchAccountWitKycNotHandledAfterActivation()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::ALLOW_SUBMERCHANT_WITHOUT_EMAIL]);

        // testUpdateKYCClarificationReason
        $subMerchant = $this->createUnderReviewAccount();

        $this->fixtures->create('merchant_website', [
            'merchant_id' => $subMerchant->getId()
        ]);

        $this->ba->adminAuth();

        $this->changeActivationStatus($subMerchant->getId(), 'activated');

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/accounts/acc_'. $subMerchant->getId();

        $this->runRequestResponseFlow($testData);

        $subMerchant = $this->getDbLastEntity('merchant');

        // check that international is enabled
        $this->assertTrue($subMerchant->isInternational());
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

    public function testSimulateActivationForPartner()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::PARTNER_ACTIVATE_MERCHANT]);

        $subMerchant = $this->createUnderReviewAccount();

        $this->ba->privateAuth();

        $testData = $this->testData['testSimulateUpdate'];

        $testData['request']['url'] = '/partner/merchant/acc_'. $subMerchant->getId().'/activation/update';

        $this->runRequestResponseFlow($testData);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/partner/merchant/acc_'. $subMerchant->getId().'/activation/status';

        $this->runRequestResponseFlow($testData);
    }

    public function testSimulateInternationalActivationForPartner()
    {
        $this->setUpPartnerWithKycNotHandled();

        $this->fixtures->merchant->addFeatures([FName::PARTNER_ACTIVATE_MERCHANT, FName::SKIP_WEBSITE_INTERNAT]);

        $subMerchant = $this->createUnderReviewAccount();

        $merchantAttributes = [
            'website'        => null,
            'has_key_access' => false,
        ];

        $this->fixtures->on('test')->edit('merchant', $subMerchant->getId(), $merchantAttributes);
        $this->fixtures->on('live')->edit('merchant', $subMerchant->getId(), $merchantAttributes);

        $detail = [
            'business_website' => null
        ];

        $this->fixtures->on('test')->edit('merchant_detail', $subMerchant->getId(), $detail);
        $this->fixtures->on('live')->edit('merchant_detail', $subMerchant->getId(), $detail);

        $this->ba->privateAuth();

        $testData = $this->testData['testSimulateActivationForPartner'];

        $testData['request']['url'] = '/partner/merchant/acc_' . $subMerchant->getId() . '/activation/status';

        $this->runRequestResponseFlow($testData);

        $sm = $this->getDbEntityById('merchant', $subMerchant->getId());

        $this->assertEquals($sm['international'], true);
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId(), [], Role::OWNER, Mode::LIVE);

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId(), $merchantUser['id']);

        $this->runRequestResponseFlow($testData);

        return $merchant;
    }

    public function testUpdateContactNumberForSubmerchatnUser()
    {
        $this->setUpPartnerWithKycHandled();

        $testData = $this->testData['testCreateAccountForCompletelyFilledRequest'];

        $response = $this->runRequestResponseFlow($testData);

        $accountId = $response['id'];

        Account\Entity::verifyIdAndSilentlyStripSign($accountId);

        (new Core())->updateContactNumberForSubMerchantUser($accountId, '7302202220');

        $user = (new Core())->fetchSubmerchantUser($accountId);

        $this->assertEquals('7302202220', $user->getContactMobile());
    }

    private function getDimensionsForAccountMetrics(): array
    {
        return [
            'submerchant_business_type' => 'llp',
        ];
    }
}
