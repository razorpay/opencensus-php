<?php

namespace RZP\Tests\Functional\AppFramework;

use DB;
use Mail;
use Hash;
use Queue;
use Config;
use Mockery;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Survey\Response\Service;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SurveyTest extends TestCase
{
    use PaymentTrait;
    use PayoutTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;

    //Merchant_id = '10000000000000';

    //USER_ID = 'MerchantUser01';
    //USER_EMAIL = 'merchantuser01@razorpay.com';

    //USER_ID = '20000000000000';
    //USER_EMAIL = 'test1@razorpay.com';

    //USER_ID = '20000000000001';
    //USER_EMAIL = 'test2@razorpay.com';

    private $merchant;

    private $user1;

    private $user2;

    protected $surveyResponseCoreMock;

    protected $surveyResponseService;

    private $unitTestCase;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

        $this->testDataFilePath = __DIR__ . '/SurveyTestData.php';

        parent::setUp();

        $this->liveSetUp();

        $this->setupTypeformResponsesMock();

//        $this->fixtures->org->createRazorpayOrgLive();

        $this->user1 = $this->fixtures->on('live')->create('user', [
            'id' => '20000000000000',
            'name' => 'Test User Account1',
            'email' => 'test1@razorpay.com',
            'password' => '$2y$10$c05NhdCd5JE6WcTeQw00eeGZLzPx8aZh9B2eWmMawtC71FxZfgP42',
            'contact_mobile' => '9999999998',
        ]);

        $mappingData = [
            'user_id'     => $this->user1['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->on('live')->create('user:user_merchant_mapping', $mappingData);


        $this->user2 = $this->fixtures->on('live')->create('user', [
            'id' => '20000000000001',
            'name' => 'Test User Account2',
            'email' => 'test2@razorpay.com',
            'password' => '$2y$10$c05NhdCd5JE6WcTeQw00eeGZLzPx8aZh9B2eWmMawtC71FxZfgP42',
            'contact_mobile' => '9999999999',
        ]);

        $mappingData = [
            'user_id'     => $this->user2['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->on('live')->create('user:user_merchant_mapping', $mappingData);

        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->on('live')->create('permission', ['name' => 'nps_survey']);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth('live');
    }

    public function testCreateSurvey()
    {
        $this->startTest();
    }

    public function testCreateSurveyWithDuplicateType()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 1
        ]);

        $this->startTest();
    }

    public function testUpdateSurveyTTL()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 2,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/' . $survey['id'];

        $this->startTest();
    }

    public function testUpdateSurveyURL()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/' . $survey['id'];

        $this->startTest();
    }

    public function testUpdateSurveyName()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/' . $survey['id'];

        $this->startTest();
    }

    public function testUpdateSurveyChannel()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/' . $survey['id'];

        $this->startTest();
    }

    public function testUpdateSurveyDescription()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/' . $survey['id'];

        $this->startTest();
    }

    public function testUpdateSurveyWithInvalidId()
    {
        $survey = $this->fixtures->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'Test Survey',
            'description' => 'This is test survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/abcdef' ;

        $this->startTest();
    }

    public function testInvalidSurveyType()
    {
        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyWithUserId()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $payout1 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901231',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user1['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $payout2 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901232',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user2['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(3)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(3)->getTimestamp(),
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 1
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyWithNoUserId()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyWithSameMerchantAndUserId()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user1['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901235',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user1['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyWithSameMerchantAndDifferentUserId()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user1['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901235',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user2['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyWithDifferentMerchantAndSameUserId()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $merchant = $this->fixtures->on('live')->create('merchant',
            ['id'                    => '10000000000001',
                'product_international' => '2000',
                'pricing_plan_id'       => 'BTo98voDY05ueB']);

        $this->fixtures->on('live')->create('balance', ['id' => '10000000000001', 'type' => 'banking', 'balance' => '0', 'merchant_id' => '10000000000001']);

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => $this->user1['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901235',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000001',
            'user_id' => $this->user1['id'],
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyWithEmailAlreadySent()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $payout = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 3,
        ]);

        $surveySentAt = Carbon::now(Timezone::IST)->subHours(4)->getTimestamp();

        $this->fixtures->on('live')->create('survey_tracker', [
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity = $this->getDbLastEntity('survey_tracker', 'live');

        $this->assertGreaterThan($surveySentAt, $surveyTrackerEntity['survey_sent_at']);
    }

    public function testSurveyAfterSurveyTTL()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 2
        ]);

        $previousSurveySentAt = Carbon::now(Timezone::IST)->subHours(31)->getTimestamp();

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => $previousSurveySentAt,
            'attempts' => 1,
        ]);

        $payout = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntities = $this->getDbEntities('survey_tracker', [], 'live');

        $previousSurveyTracker = $surveyTrackerEntities->pop();

        $surveyTrackerEntity = $surveyTrackerEntities->pop();

        $this->assertNotEquals($previousSurveyTracker['id'], $surveyTrackerEntity['id']);
    }

    public function testSurveywithExternalUserId()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 1,
        ]);

        $this->ba->cronAuth('live');

        $cohort = [
            'merchant_id'   => '10000000000000',
            'user_id'       => $this->user1['id']
        ];

        $this->testData[__FUNCTION__]['request']['content']['cohort_list'] = [$cohort];

        $this->startTest();
    }

    public function testPendingSurvey()
    {
        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 2,
        ]);

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 0,
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testPendingSurveyWithSurveyAlreadyFilled()
    {
        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 3,
        ]);

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 0,
        ]);

        $this->fixtures->on('live')->create('survey_response', [
            'id' => 'JLrIMZYR32kZiB',
            'tracker_id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testPendingSurveyWithSurveyAlreadySkipped()
    {
        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 2
        ]);

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 1,
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->startTest();
    }

    public function testSkipInAppSurvey()
    {
        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_api',
            'channel' => 3,
        ]);

        $tracker = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 0,
        ]);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->testData[__FUNCTION__]['request']['url'] = '/survey/tracker/' . $tracker['id'];

        $this->startTest();
    }

    public function testFailureSurveyTypeformWebhookConsumptionSecurity()
    {
        $this->startTest();
    }

    public function testSuccessSurveyTypeformWebhookConsumptionWithoutTrackerId()
    {
        $this->ba->directAuth();

        $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 0,
        ]);

        $this->startTest();
    }

    public function testSuccessSurveyTypeformWebhookConsumptionWithTrackerId()
    {
        $this->ba->directAuth();

        $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 0,
        ]);

        $this->startTest();
    }

    public function testSuccessSurveyTypeformWebhookWithSurveyAlreadyFilledBefore()
    {
        $this->markTestSkipped();

        $this->ba->directAuth();

        $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'name' => 'RazorpayX survey',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type' => 'nps_payouts_dashboard',
            'channel' => 2,
        ]);

        $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => Carbon::now(Timezone::IST)->subHours(31)->getTimestamp(),
            'attempts' => 1,
            'skip_in_app' => 0,
        ]);

        $this->fixtures->on('live')->create('survey_response', [
            'id' => 'JLrIMZYR32kZiB',
            'tracker_id' => 'PLtIMZYR32kZiB',
            'survey_id' => 'GLuIMZYR32kZiB',
        ]);

        $this->startTest();
    }

    public function testSurveyOnCAOnboarding()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance['id']
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();
    }

    public function testSurveyOnCAOnboardedBeneficiaryEmail()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $bankingAccount = $this->fixtures->on('live')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance['id'],
            'beneficiary_email'     => 'beneficiary@test.com'
        ]);

        $bankingAccountActivationDetails = $this->fixtures->on('live')->create('banking_account_activation_detail',[
            'banking_account_id' => $bankingAccount['id'],
            'merchant_poc_email'       => '']);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity = $this->getDbEntity('survey_tracker',['survey_id' => 'GLuIMZYR32kZiB'] , 'live' );

        $this->assertEquals('beneficiary@test.com', $surveyTrackerEntity['survey_email']);
    }

    public function testSurveyOnCAOnboardingMerchantPocAndBeneficiary()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $bankingAccount = $this->fixtures->on('live')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance['id'],
            'beneficiary_email'     => 'test1@razorpay.com'
        ]);

        $bankingAccountActivationDetails = $this->fixtures->on('live')->create('banking_account_activation_detail',[
            'banking_account_id' => $bankingAccount['id'],
            'merchant_poc_email'       => 'merchant.poc@gmail.com, test1@razorpay.com & test2@razorpay.com']);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity = $this->getDbEntity('survey_tracker',['survey_id' => $survey['id']] , 'live' );

        $this->assertEquals('merchant.poc@gmail.com', $surveyTrackerEntity['survey_email']);
    }

    public function testSurveyOnCAWithAcrossSurveyCheckFailing()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance['id'],
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $surveySentAt = Carbon::now(Timezone::IST)->subHours(4)->getTimestamp();

        $this->fixtures->on('live')->create('survey_tracker', [
                                                                                'id' => 'GAX5zcOdI0Y663',
                                                                                'survey_id' => 'GLuIMZYR32kZiB',
                                                                                'survey_email' => 'merchantuser01@razorpay.com',
                                                                                'survey_sent_at' => $surveySentAt,
                                                                                'attempts' => 1,
                                                                            ]);

        $this->fixtures->on('live')->create('survey_tracker', [
                                                                                'id' => 'GAX5zcOdI0Y664',
                                                                                'survey_id' => 'GLuIMZYR32kZiB',
                                                                                'survey_email' => 'test1@razorpay.com',
                                                                                'survey_sent_at' => $surveySentAt,
                                                                                'attempts' => 1,
                                                                            ]);

        $this->fixtures->on('live')->create('survey_tracker', [
                                                                                'id' => 'GAX5zcOdI0Y665',
                                                                                'survey_id' => 'GLuIMZYR32kZiB',
                                                                                'survey_email' => 'test2@razorpay.com',
                                                                                'survey_sent_at' => $surveySentAt,
                                                                                'attempts' => 1,
                                                                            ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity = $this->getDbLastEntity('survey_tracker', 'live');

        $this->assertEquals($surveySentAt, $surveyTrackerEntity['survey_sent_at']);
    }

    //Scenario: Testing the scenario where three users of same merchant were sent all three different type of surveys
    // and then we triggered the nps_csat survey for all three users
    //Expected behaviour: The nps_csat survey should not be sent the user who has already received nps_csat remaining two will get the survey.
    //Asserted on survey_tracker table will have entry towards the end of handler code. Hence, handler logic is also covered.
    public function testPrecedenceForCSAT()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $this->fixtures->on('live')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance['id'],
        ]);

        $payoutsSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 720,
            'type'  => 'nps_payouts_api',
            'channel' => 2,
        ]);

        $csatSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiY',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 720,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $caSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiZ',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 1080,
            'type'  => 'nps_active_ca',
            'channel' => 3,
        ]);

        $surveySentAt = Carbon::now(Timezone::IST)->subHours(4)->getTimestamp();

        $tracker1 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y663',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $tracker2 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y664',
            'survey_id' => 'GLuIMZYR32kZiY',
            'survey_email' => 'test1@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $tracker3 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y665',
            'survey_id' => 'GLuIMZYR32kZiZ',
            'survey_email' => 'test2@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity1 = $this->getDbEntity('survey_tracker',['survey_email' => 'merchantuser01@razorpay.com'] , 'live' );

        $surveyTrackerEntity2 = $this->getDbEntity('survey_tracker',['survey_email' => 'test1@razorpay.com'] , 'live' );

        $surveyTrackerEntity3 = $this->getDbEntity('survey_tracker',['survey_email' => 'test2@razorpay.com'] , 'live' );

        $this->assertEquals($tracker2['id'], $surveyTrackerEntity2['id']);
        $this->assertEquals($tracker3['id'], $surveyTrackerEntity3['id']);

        $this->assertNotEquals($tracker1['id'], $surveyTrackerEntity1['id']);

        $this->assertGreaterThan($tracker1['survey_sent_at'], $surveyTrackerEntity1['survey_sent_at']);
    }

    //Scenario: Testing the scenario where three users of same merchant were sent all three different type of surveys
    // and then we triggered the nps_payouts survey for all three users
    //Expected behaviour: The nps_payouts survey should not be sent to any of the users.
    //Asserted on survey_tracker table will have entry towards the end of handeler code. Hence, handler logic is also covered.
    public function testPrecedenceForNPSPayouts()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $payout1 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901231',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => 'MerchantUser01',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $payout2 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => '20000000000000',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $payout3 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901235',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => '20000000000001',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ]);

        $payoutsSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 720,
            'type'  => 'nps_payouts_dashboard',
            'channel' => 1,
        ]);

        $caSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiY',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 1080,
            'type'  => 'nps_active_ca',
            'channel' => 3,
        ]);

        $csatSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiZ',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 720,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $surveySentAt = Carbon::now(Timezone::IST)->subHours(4)->getTimestamp();

        $tracker1 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y663',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $tracker2 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y664',
            'survey_id' => 'GLuIMZYR32kZiY',
            'survey_email' => 'test1@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $tracker3 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y665',
            'survey_id' => 'GLuIMZYR32kZiZ',
            'survey_email' => 'test2@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity1 = $this->getDbEntity('survey_tracker',['survey_email' => 'merchantuser01@razorpay.com'] , 'live' );

        $surveyTrackerEntity2 = $this->getDbEntity('survey_tracker',['survey_email' => 'test1@razorpay.com'] , 'live' );

        $surveyTrackerEntity3 = $this->getDbEntity('survey_tracker',['survey_email' => 'test2@razorpay.com'] , 'live' );

        $this->assertEquals($tracker1['id'], $surveyTrackerEntity1['id']);
        $this->assertEquals($tracker2['id'], $surveyTrackerEntity2['id']);
        $this->assertEquals($tracker3['id'], $surveyTrackerEntity3['id']);
    }

    //Scenario: We have created the scenario where three users of same merchant were sent all three different type of surveys
    // and then we triggered the nps_active_ca survey for all three users
    //Expected behaviour: The nps_active_ca will be sent only to the user who received nps_payouts survey.
    //Asserted on survey_tracker table will have entry towards the end of handler code. Hence, handler logic is also covered.
    public function testPrecedenceForActiveCAPayouts()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $payout1 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901231',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => 'MerchantUser01',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subDay(42)->subHours(2)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subDay(42)->subHours(2)->getTimestamp(),
        ]);

        $payout2 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901234',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => '20000000000000',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subDay(20)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subDay(20)->getTimestamp(),
        ]);

        $payout3 = $this->fixtures->on('live')->create('payout' , [
            'id' =>  '12345678901235',
            'status' => 'created',
            'balance_id' =>  $balance->getId(),
            'merchant_id' =>  '10000000000000',
            'user_id' => '20000000000001',
            'amount' =>  1,
            'created_at'=> Carbon::now(Timezone::IST)->subDay(47)->getTimestamp(),
            'updated_at'=> Carbon::now(Timezone::IST)->subDay(47)->getTimestamp(),
        ]);

        $balanceCreatedAt = Carbon::now(Timezone::IST)->subDay(90)->getTimestamp();

        $this->fixtures->on('live')->edit('balance', $balance['id'], ['account_type' => 'direct', 'created_at' => $balanceCreatedAt]);

        $payoutsSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 720,
            'type'  => 'nps_payouts_api',
            'channel' => 1,
        ]);

        $caSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiZ',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 1080,
            'type'  => 'nps_active_ca',
            'channel' => 3,
        ]);

        $csatSurvey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiY',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 720,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $surveySentAt = Carbon::now(Timezone::IST)->subDays(10)->getTimestamp();

        $tracker1 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y663',
            'survey_id' => 'GLuIMZYR32kZiB',
            'survey_email' => 'merchantuser01@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $tracker2 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y664',
            'survey_id' => 'GLuIMZYR32kZiZ',
            'survey_email' => 'test1@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $tracker3 = $this->fixtures->on('live')->create('survey_tracker', [
            'id' => 'GAX5zcOdI0Y665',
            'survey_id' => 'GLuIMZYR32kZiY',
            'survey_email' => 'test2@razorpay.com',
            'survey_sent_at' => $surveySentAt,
            'attempts' => 1,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity1 = $this->getDbEntity('survey_tracker',['survey_email' => 'merchantuser01@razorpay.com'] , 'live' );

        $surveyTrackerEntity2 = $this->getDbEntity('survey_tracker',['survey_email' => 'test1@razorpay.com'] , 'live' );

        $surveyTrackerEntity3 = $this->getDbEntity('survey_tracker',['survey_email' => 'test2@razorpay.com'] , 'live' );

        $this->assertNotEquals($tracker1['id'], $surveyTrackerEntity1['id']);
        $this->assertGreaterThan($tracker1['survey_sent_at'], $surveyTrackerEntity1['survey_sent_at']);

        $this->assertEquals($tracker2['id'], $surveyTrackerEntity2['id']);

        $this->assertEquals($tracker3['id'], $surveyTrackerEntity3['id']);
    }

    public function testSurveyOnAccountArchived()
    {
        $balance = $this->getDbLastEntity('balance', 'live');

        $bankingAccount = $this->fixtures->on('live')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'archived',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance['id'],
            'beneficiary_email'     => 'beneficiary@test.com'
        ]);

        $bankingAccountActivationDetails = $this->fixtures->on('live')->create('banking_account_activation_detail',[
            'banking_account_id' => $bankingAccount['id'],
            'merchant_poc_email'       => '']);

        $statusCreatedAt = Carbon::now(Timezone::IST)->subHours(1)->getTimestamp();

        $this->fixtures->on('live')->create('banking_account_state', [
            'merchant_id'           => '10000000000000',
            'banking_account_id'    => $bankingAccount['id'],
            'status'                => 'archived',
            'created_at'            => $statusCreatedAt
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type'  => 'nps_csat',
            'channel' => 3,
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

        $surveyTrackerEntity = $this->getDbEntity('survey_tracker',['survey_id' => 'GLuIMZYR32kZiB'] , 'live' );

        $this->assertEquals('beneficiary@test.com', $surveyTrackerEntity['survey_email']);
    }

    private function setupTypeformResponsesMock()
    {
        $this->app['rzp.mode']= 'test';

        $this->surveyResponseService = new Service();

        $this->surveyResponseCoreMock = Mockery::mock('RZP\Models\Survey\Response\Core', [$this->app])->makePartial();

        $this->surveyResponseCoreMock->shouldAllowMockingProtectedMethods();

        $this->unitTestCase->setPrivateProperty($this->surveyResponseService, 'core', $this->surveyResponseCoreMock);
    }

    public function testPushTypeformResponsesToDatalake()
    {
        $this->ba->cronAuth('live');

        $this->setupGetTypeformResponse();

        $ufhServiceClientMock = Mockery::mock('RZP\Services\UfhService');

        $this->app->instance('ufh_service', $ufhServiceClientMock);

        $ufhServiceClientMock->shouldReceive('uploadFileAndGetUrl')
            ->with(Mockery::type('Symfony\Component\HttpFoundation\File\UploadedFile'), 'nps_response_IWuWQPm5', 'file', Mockery::type(null));

        $request = $this->testData[__FUNCTION__]['request']['content'];

        $response = $this->surveyResponseService->pushTypeFormResponsesToDataLake($request);

        $this->assertEquals($this->testData[__FUNCTION__]['response']['content'], $response);
    }

    private function setupGetTypeformResponse()
    {
        $this->surveyResponseCoreMock
            ->shouldReceive('getTypeformResponses')
            ->times(1)
            ->andReturnUsing(function ()
            {
                return $this->testData['typeformResponsesTemplate'];
            });
    }

    public function testPushTypeformResponsesToDatalakeInvalidTypeformResponse()
    {
        $this->ba->cronAuth('live');

        $this->expectException(Exception\BadRequestException::class);

        $this->setupGetTypeformResponseInvalidResponse();

        $request = $this->testData[__FUNCTION__]['request']['content'];

        $response = $this->surveyResponseService->pushTypeFormResponsesToDataLake($request);
    }

    private function setupGetTypeformResponseInvalidResponse()
    {
        $this->surveyResponseCoreMock
            ->shouldReceive('getTypeformResponses')
            ->times(1)
            ->andReturnUsing(function ()
            {
                return $this->testData['typeformResponsesTemplateInvalidResponse'];
            });
    }
}
