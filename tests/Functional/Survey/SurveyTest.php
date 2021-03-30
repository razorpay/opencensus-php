<?php

namespace RZP\Tests\Functional\AppFramework;

use DB;
use Mail;
use Hash;
use Queue;
use Config;
use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
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

    private $merchant;

    private $user1;

    private $user2;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/SurveyTestData.php';

        parent::setUp();

        $this->liveSetUp();

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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts'
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
            'type' => 'nps_payouts',
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
            'type' => 'nps_payouts',
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
            'type' => 'nps_payouts',
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
            'type' => 'nps_payouts',
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
            'type' => 'nps_payouts',
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
            'type' => 'nps_payouts',
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
            'type' => 'nps_payouts',
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
            'balance_id'            => $balance['id'],
        ]);

        $survey = $this->fixtures->on('live')->create('survey', [
            'id' => 'GLuIMZYR32kZiB',
            'description' => 'RazorpayX survey',
            'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            'survey_ttl' => 30,
            'type'  => 'nps_csat',
        ]);

        $this->ba->cronAuth('live');

        $this->startTest();

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

}
