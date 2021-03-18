<?php

namespace Functional\Partner\Activation;

use DB;
use Mail;
use RZP\Tests\Functional\Batch\BatchTestTrait;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;

class PartnerActivationTest extends OAuthTestCase
{
    use PartnerTrait;
    use BatchTestTrait;

    const MERCHANT_ID = '1cXSLlUU8V9sXl';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerActivationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['marketplace']);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);

        $this->ba->privateAuth();
    }

    public function testFetchPartnerActivationForNonRegisteredBusiness()
    {
        $this->createMerchant(self::MERCHANT_ID, false, 'activated');

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSavePartnerActivationForNonRegisteredBusiness()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSavePartnerActivationForRegisteredBusiness()
    {
        $this->createMerchant(self::MERCHANT_ID, true, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSubmitPartnerActivationForNonRegisteredBusinessActivated()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fillRequirementStatus(self::MERCHANT_ID, false, 'verified');

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();

        $state = $this->getDbEntity('action_state');

        $this->assertEquals(self::MERCHANT_ID, $state['merchant_id']);

        $this->assertEquals('activated', $state['name']);

        $this->assertEquals('partner_activation', $state['entity_type']);
    }

    public function testSubmitPartnerActivationForNonRegisteredBusinessUnderReview()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->fillAllRequirements(self::MERCHANT_ID, false);

        $this->fillRequirementStatus(self::MERCHANT_ID, false, 'pending');

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();

        $state = $this->getDbEntity('action_state');

        $this->assertEquals(self::MERCHANT_ID, $state['merchant_id']);

        $this->assertEquals('under_review', $state['name']);

        $this->assertEquals('partner_activation', $state['entity_type']);
    }

    public function testFetchPartnerActivationForNonPartner()
    {
        $this->createMerchant(self::MERCHANT_ID, false , null, false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testSavePartnerActivationForNonPartner()
    {
        $this->createMerchant(self::MERCHANT_ID, false , null, false);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testActivatePartnerFromUnderReview()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testActivatePartnerFromUnderReview'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID . '/status';
        $this->runRequestResponseFlow($testData);

        $partnerActivation = $this->getDbEntity('partner_activation');
        $this->assertNotNull($partnerActivation['submitted_at']);
        $this->assertNotNull($partnerActivation['activated_at']);

        $actionStates = $this->getDbEntities('action_state');
        $this->assertEquals(2, count($actionStates));
        $this->assertEquals('under_review', $actionStates->get(0)['name']);
        $this->assertEquals('activated', $actionStates->get(1)['name']);

    }

    public function testPartnerNeedsClarification()
    {
        Mail::fake();

        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testPartnerNeedsClarification'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID;
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testUpdatePartnerActivationToNeedsClarification'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID. '/status';
        $this->runRequestResponseFlow($testData);


        $partnerActivation = $this->getDbEntity('partner_activation');
        $this->assertNotNull($partnerActivation['submitted_at']);
        $this->assertNull($partnerActivation['activated_at']);

        $actionStates = $this->getDbEntities('action_state');
        $this->assertEquals(2, count($actionStates));
        $this->assertEquals('under_review', $actionStates->get(0)['name']);
        $this->assertEquals('needs_clarification', $actionStates->get(1)['name']);

    }

    public function testPartnerRejected()
    {
        Mail::fake();

        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testUpdatePartnerActivationToRejected'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID. '/status';
        $this->runRequestResponseFlow($testData);


        $partnerActivation = $this->getDbEntity('partner_activation');
        $this->assertNotNull($partnerActivation['submitted_at']);
        $this->assertNull($partnerActivation['activated_at']);
        $this->assertTrue($partnerActivation['hold_funds']);

        $actionStates = $this->getDbEntities('action_state');
        $this->assertEquals(2, count($actionStates));
        $this->assertEquals('under_review', $actionStates->get(0)['name']);
        $this->assertEquals('rejected', $actionStates->get(1)['name']);

        $rejectedActionState = $actionStates->get(1);
        $reasons = $this->getDbEntities('state_reason');
        $this->assertEquals(2, count($reasons));
        $this->assertEquals($rejectedActionState->getId(), $reasons->get(0)['state_id']);
        $this->assertEquals($rejectedActionState->getId(), $reasons->get(1)['state_id']);
    }

    public function testInvalidExtraFieldsPartnerDetailsFormSave()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);

        $this->startTest();
    }

    public function testInvalidStatusChange()
    {
        $this->createMerchant(self::MERCHANT_ID, false, null);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['saveAllPartnerActivationDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->proxyAuth('rzp_test_' . self::MERCHANT_ID);
        $testData = $this->testData['submitActivationDataForUnVerifiedDetails'];
        $this->runRequestResponseFlow($testData);

        $this->ba->adminAuth();
        $testData                   = $this->testData['testInvalidStatusChange'];
        $testData['request']['url'] = '/partner/activation/' . self::MERCHANT_ID. '/status';
        $this->runRequestResponseFlow($testData);

    }

    private function createMerchant(string $merchantId, bool $registeredBusinessType, $activationStatus, bool $isPartner = true)
    {
        $businessType = $registeredBusinessType === true ? 4 : 1;

        $this->fixtures->create('merchant_detail', [
            'merchant_id'       => $merchantId,
            'business_type'     => $businessType,
            'contact_name'      => 'contact name',
            'contact_mobile'    => '8888888888',
            'activation_status' => $activationStatus
        ]);

        $this->fixtures->create('stakeholder',
                                [
                                    'merchant_id' => $merchantId,
                                    'name'        => 'stakeholder1',
                                ]);

        if ($isPartner === true)
        {
            $this->fixtures->edit('merchant', $merchantId, ['partner_type' => 'reseller']);
        }
    }

    private function fillAllRequirements(string $merchantId, bool $registeredBusinessType)
    {
        $payload = [
            'bank_account_name'   => 'User 1',
            'bank_account_number' => '051610000039259',
            'bank_branch_ifsc'    => 'UBIN0805165'
        ];

        if ($registeredBusinessType === true)
        {
            $promoterDetails = ['company_pan' => 'EBPCK8222K'];
            $payload         = array_merge($payload, $promoterDetails);
        }
        else
        {
            $promoterDetails = ['promoter_pan' => 'EBPPK8222K', 'promoter_pan_name' => 'User 1'];
            $payload         = array_merge($payload, $promoterDetails);
        }

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }

    private function fillPartialRequirements(string $merchantId)
    {
        $payload = [
            'bank_account_name'   => 'User 1',
            'bank_account_number' => '051610000039259',
            'bank_branch_ifsc'    => 'UBIN0805165'
        ];

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }


    private function fillRequirementStatus(string $merchantId, bool $registeredBusinessType, string $status)
    {
        if ($registeredBusinessType === true)
        {
            $payload = [
                'company_pan_verification_status'  => $status,
                'bank_details_verification_status' => $status,
            ];
        }
        else
        {
            $payload = [
                'poi_verification_status'          => $status,
                'bank_details_verification_status' => $status,
            ];
        }

        $this->fixtures->merchant_detail->onLive()->edit($merchantId, $payload);
        $this->fixtures->merchant_detail->onTest()->edit($merchantId, $payload);
    }
}

