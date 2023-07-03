<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use App;
use Mail;
use Event;
use Throwable;
use RZP\Services\Elfin;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Models\Merchant\Referral;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Partner\Constants;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Services\Elfin\Mock\Service as ElfinMock;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class MerchantReferralTest extends OAuthTestCase
{
    use OAuthTrait;
    use MocksSplitz;
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

    /**
     * This test case first creates referral links for PG and X products via /merchant/referral API and
     * regenerate function stub is invoked to test the regeneration.
     * 1. Create referrals for the partner
     * 2. Invoke regenerate referral links function
     * 3. Validate new referral links for PG and banking are updated
     */
    public function testRegenerateReferralLinks()
    {
        $this->fixtures->merchant->edit(Constants::DEFAULT_MERCHANT_ID, ['partner_type' => 'reseller']);

        $this->fixtures->merchant->createDummyPartnerApp();

        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $testData = &$this->testData['testCreateOrFetchReferral'];

        $app = App::getFacadeRoot();

        $app['elfin'] = (new Elfin\Mock\Service($app['config'], $app['trace']));

        $this->ba->proxyAuth();

        $testData['request']['url'] = "/merchant/referral";

        $response  = $this->runRequestResponseFlow($testData);

        $bankingReferral = $this->getDbEntity('referrals',
                                              [
                                                  'merchant_id' => $merchantId, 'product' => 'banking'
                                              ], 'live');

        $oldBankingReferralUrl = $bankingReferral['url'];
        $pgReferral = $this->getDbEntity('referrals',
                                         [
                                             'merchant_id' => $merchantId, 'product' => 'primary'
                                         ], 'live');
        $oldPgReferralUrl = $pgReferral['url'];

        $partners = $this->getDbEntities('merchant', ['id' => $merchantId] );

        (new Referral\Core())->regenerate($partners);

        $newPgReferral = $this->getDbEntity('referrals',
                                           [
                                               'merchant_id' => $merchantId, 'product' => 'primary'
                                           ], 'live');
        $newBankingReferral = $this->getDbEntity('referrals',
                                              [
                                                  'merchant_id' => $merchantId, 'product' => 'banking'
                                              ], 'live');

        $this->assertNotEquals($oldBankingReferralUrl, $newBankingReferral['url']);
        $this->assertNotEquals($oldPgReferralUrl, $newPgReferral['url']);
    }

    /**
     * This test case intend to test when a not whitelisted merchant try to invoke the regenerate API
     * Asserts Bad request error
     */
    public function testRegenerateReferralLinksWithInvalidMerchant()
    {
        $this->mockAllExperiments("disable");
        $this->ba->privateAuth();
        $this->startTest();
    }

    private function mockAllExperiments(string $variant = 'enable')
    {
        $output = [
            "response" => [
                "variant" => [
                    "name" => $variant,
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);
    }

    public function testCreateOrFetchNewMerchantReferralPartnerEligibleForCapital(): void
    {
        $input = [
            'experiment_id' => 'M6dmstlJXnnU8F',
            'id'            => Constants::DEFAULT_MERCHANT_ID,
        ];

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            'experiment_id' => 'L0rynez0HhIXHb',
            'id'            => Constants::DEFAULT_MERCHANT_ID,
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER
            ]
        );

        $this->fixtures->merchant->createDummyPartnerApp();

        $this->ba->proxyAuth();

        $testdata = &$this->testData['testCreateOrFetchMerchantReferralPartnerEligibleForCapital'];

        $response = $this->startTest($testdata);

        $bankingReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::BANKING,
            ],
            Mode::LIVE
        );

        $capitalReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::CAPITAL,
            ],
            Mode::LIVE
        );

        $pgReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::PRIMARY,
            ],
            Mode::LIVE
        );

        $pgReferralUrl = $this->config['applications.dashboard.url']
                         . 'signup?referral_code='
                         . $pgReferral->getReferralCode()
                         . '&eo=1';
        $bankingReferralUrl = $this->config['applications.banking_service_url']
                              . '/auth/signup?referral_code='
                              . $bankingReferral->getReferralCode();
        $capitalReferralUrl = Merchant\Constants::RAZORPAY_LINE_OF_CREDIT_SIGN_UP
                              . '?referral_code='
                              . $capitalReferral->getReferralCode()
                              . '&intent=capital_loc_emi';

        $this->assertEquals(ElfinMock::$shortURLToURL[$response['referrals']['primary']['url']], $pgReferralUrl);
        $this->assertEquals(ElfinMock::$shortURLToURL[$response['referrals']['banking']['url']], $bankingReferralUrl);
        $this->assertEquals(ElfinMock::$shortURLToURL[$response['referrals']['capital']['url']], $capitalReferralUrl);

        $this->assertEquals($response['referrals']['primary']['ref_code'], $pgReferral->getReferralCode());
        $this->assertEquals($response['referrals']['banking']['ref_code'], $bankingReferral->getReferralCode());
        $this->assertEquals($response['referrals']['capital']['ref_code'], $capitalReferral->getReferralCode());

        $this->assertEquals($response['referrals']['primary']['url'], $pgReferral->getReferralLink());
        $this->assertEquals($response['referrals']['banking']['url'], $bankingReferral->getReferralLink());
        $this->assertEquals($response['referrals']['capital']['url'], $capitalReferral->getReferralLink());

    }

    /**
     * Asserts that the function returns the expected capital Referral Entity as well
     * for a particular merchant whitelisted for the capital partnership experiment
     * @return void
     */
    public function testCreateOrFetchMerchantReferralPartnerEligibleForCapital(): void
    {
        $input = [
            'experiment_id' => 'L0rynez0HhIXHb',
            'id'            => Constants::DEFAULT_MERCHANT_ID,
        ];

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER
            ]
        );

        $this->fixtures->merchant->createDummyPartnerApp();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $bankingReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::BANKING,
            ],
            Mode::LIVE
        );

        $capitalReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::CAPITAL,
            ],
            Mode::LIVE
        );

        $pgReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::PRIMARY,
            ],
            Mode::LIVE
        );

        $pgReferralUrl = $this->config['applications.dashboard.url']
                         . 'signup?referral_code='
                         . $pgReferral->getReferralCode()
                         . '&eo=1';
        $bankingReferralUrl = $this->config['applications.banking_service_url']
                              . '/auth/signup?referral_code='
                              . $bankingReferral->getReferralCode();
        $capitalReferralUrl = $this->config['applications.banking_service_url']
                              . '/auth/signup?referral_code='
                              . $capitalReferral->getReferralCode()
                              . '&intent=capital_loc_emi';

        $this->assertEquals(ElfinMock::$shortURLToURL[$response['referrals']['primary']['url']], $pgReferralUrl);
        $this->assertEquals(ElfinMock::$shortURLToURL[$response['referrals']['banking']['url']], $bankingReferralUrl);
        $this->assertEquals(ElfinMock::$shortURLToURL[$response['referrals']['capital']['url']], $capitalReferralUrl);

        $this->assertEquals($response['referrals']['primary']['ref_code'], $pgReferral->getReferralCode());
        $this->assertEquals($response['referrals']['banking']['ref_code'], $bankingReferral->getReferralCode());
        $this->assertEquals($response['referrals']['capital']['ref_code'], $capitalReferral->getReferralCode());

        $this->assertEquals($response['referrals']['primary']['url'], $pgReferral->getReferralLink());
        $this->assertEquals($response['referrals']['banking']['url'], $bankingReferral->getReferralLink());
        $this->assertEquals($response['referrals']['capital']['url'], $capitalReferral->getReferralLink());
    }

    /**
     * Asserts that the function does not return capital Referral Entity
     * for a particular merchant NOT whitelisted for the capital partnership experiment
     *
     * @return void
     */
    public function testCreateOrFetchMerchantReferralPartnerNotEligibleForCapital(): void
    {
        $this->fixtures->merchant->edit(
            Constants::DEFAULT_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER
            ]
        );

        $this->fixtures->merchant->createDummyPartnerApp();

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $bankingReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::BANKING,
            ],
            Mode::LIVE
        );

        $pgReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => Constants::DEFAULT_MERCHANT_ID,
                'product'     => Product::PRIMARY,
            ],
            Mode::LIVE
        );

        $this->assertEquals($response['referrals']['primary']['ref_code'], $pgReferral->getReferralCode());
        $this->assertEquals($response['referrals']['banking']['ref_code'], $bankingReferral->getReferralCode());

        $this->assertEquals($response['referrals']['primary']['url'], $pgReferral->getReferralLink());
        $this->assertEquals($response['referrals']['banking']['url'], $bankingReferral->getReferralLink());

        $this->assertArrayNotHasKey('capital', $response['referrals']);
    }

    /**
     * Asserts that the function regenerates capital Referral as well
     * for a particular merchant whitelisted for the capital partnership experiment
     *
     * @return void
     * @throws Throwable
     */
    public function testRegenerateReferralLinksForCapital(): void
    {
        $input = [
            'experiment_id' => 'L0rynez0HhIXHb',
            'id'            => Constants::DEFAULT_MERCHANT_ID,
        ];

        $output = [
            'response' => [
                'variant' => [
                    'name' => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->fixtures->merchant->edit(
            Constants::DEFAULT_MERCHANT_ID,
            [
                'partner_type' => Merchant\Constants::RESELLER
            ]
        );

        $this->fixtures->merchant->createDummyPartnerApp();

        $merchantId = Constants::DEFAULT_MERCHANT_ID;

        $testData = &$this->testData['testCreateOrFetchReferral'];

        $app = App::getFacadeRoot();

        $app['elfin'] = (new Elfin\Mock\Service($app['config'], $app['trace']));

        $this->ba->proxyAuth();

        $testData['request']['url'] = "/merchant/referral";

        $response = $this->runRequestResponseFlow($testData);

        $bankingReferral       = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId,
                'product'     => 'banking'
            ],
            'live'
        );
        $oldBankingReferralUrl = $bankingReferral['url'];

        $pgReferral       = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId,
                'product'     => 'primary'
            ],
            'live'
        );
        $oldPgReferralUrl = $pgReferral['url'];

        $capitalReferral       = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId,
                'product'     => 'capital'
            ],
            'live'
        );
        $oldCapitalReferralUrl = $capitalReferral['url'];

        $partners = $this->getDbEntities('merchant', ['id' => $merchantId]);

        (new Referral\Core())->regenerate($partners);

        $newPgReferral      = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId,
                'product'     => Product::PRIMARY,
            ],
            Mode::LIVE
        );

        $newBankingReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId,
                'product'     => Product::BANKING,
            ],
            Mode::LIVE
        );

        $newCapitalReferral = $this->getDbEntity(
            'referrals',
            [
                'merchant_id' => $merchantId,
                'product'     => Product::CAPITAL,
            ],
            Mode::LIVE
        );

        $this->assertNotEquals($oldBankingReferralUrl, $newBankingReferral['url']);
        $this->assertNotEquals($oldPgReferralUrl, $newPgReferral['url']);
        $this->assertNotEquals($oldCapitalReferralUrl, $newCapitalReferral['url']);
    }
}
