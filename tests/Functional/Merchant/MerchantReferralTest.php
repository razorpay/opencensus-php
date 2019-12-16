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

    public function setUp()
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
                                            'merchant_id' => $merchantId
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

}
