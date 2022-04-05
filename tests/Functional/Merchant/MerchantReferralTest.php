<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Event;
use Mail;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MerchantReferralTest extends OAuthTestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/MerchantReferralTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    /**
     * Asserts that the function returns the expected Referral
     *
     */
    public function testCreateMerchantReferral()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp();

        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $testData['request']['url'] = "/merchant/referral";

        $this->startTest();

        $referrals = $this->getDbEntity('referrals',
                                        [
                                            'merchant_id' => $merchantId, 'product' => 'primary'
                                        ], 'live');

        $this->assertNotEmpty($referrals->getReferralLink());
    }

    /**
     * Asserts that the function returns the expected Referral Entity for a particular merchant
     * stored in database
     *
     */
    public function testFetchMerchantReferral()
    {
        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $this->fixtures->create(
            'referrals'
        );

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/referral";

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->startTest();
    }

    public function testFetchMerchantReferralBatch()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $this->fixtures->create(
            'referrals'
        );

        $this->ba->batchAppAuth();

        $response = $this->startTest();

        $pgReferral = $this->getDbEntity('referrals',
            [
                'merchant_id' => $merchantId
            ], 'test');


        $this->assertEquals($pgReferral->getReferralCode(), $response['ref_code']);
        $this->assertEquals($pgReferral->getReferralLink(), $response['url']);
    }

    public function testFetchMerchantReferralBatchFailureReferralNotFound()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testFetchMerchantReferralBatchFailureMerchantNotFound()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testFetchMerchantReferralBatchFailureInvalidId()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testFetchMerchantReferralBatchFailureMerchantNotPartner()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID);

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    /**
     * creating Referral for not reseller partner
     */
    public function testCreateReferralNonResellerPartner()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID);

        $this->fixtures->merchant->createDummyPartnerApp();

        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $testData['request']['url'] = "/merchant/referral";

        $this->startTest();
    }

    /**
     * Asserts that the create referral returns the Referral Entity for a particular merchant
     * by creating or fetching an existing entry from db.
     *
     */
    public function testCreateOrFetchReferral()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp();

        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $testData = &$this->testData[__FUNCTION__];

        $this->ba->proxyAuth();

        $testData['request']['url'] = "/merchant/referral";

        $response  = $this->startTest();

        $bankingReferral = $this->getDbEntity('referrals',
            [
                'merchant_id' => $merchantId, 'product' => 'banking'
            ], 'live');

        $this->assertEquals($bankingReferral->getReferralCode(), $response['referrals']['banking']['ref_code']);
        $this->assertEquals($bankingReferral->getReferralLink(), $response['referrals']['banking']['url']);

        $pgReferral = $this->getDbEntity('referrals',
            [
                'merchant_id' => $merchantId, 'product' => 'primary'
            ], 'live');

        $this->assertEquals($pgReferral->getReferralCode(), $response['referrals']['primary']['ref_code']);
        $this->assertEquals($pgReferral->getReferralLink(), $response['referrals']['primary']['url']);

        $this->assertEquals($pgReferral->getReferralCode(), $response['ref_code']);
        $this->assertEquals($pgReferral->getReferralLink(), $response['url']);
    }

}
