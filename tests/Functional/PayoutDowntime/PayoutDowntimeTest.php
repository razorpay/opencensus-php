<?php

namespace RZP\Tests\Functional\PayoutDowntime;

use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutDowntime\Entity;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\PayoutDowntime\Constants;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use DB;
use Mail;
use RZP\Mail\PayoutDowntime\PayoutDowntimeMail;
use Carbon\Carbon;

class PayoutDowntimeTest extends TestCase
{

    use HeimdallTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;
    use DbEntityFetchTrait;

    /* @var \RZP\Models\Merchant\Balance\Entity */
    private $balance;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PayoutDowntimeTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $this->fixtures->create('org_hostname', [
            'org_id'   => $this->org->getId(),
            'hostname' => 'dashboard.sampleorg.dev',
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());
    }

    public function testCreateEntity()
    {
        $this->startTest();
    }

    public function testCreateEntityEndTimeException()
    {
        $this->startTest();
    }

    public function testCreateEntityStatusException()
    {
        $this->startTest();
    }

    public function testCreateEntityChannelException()
    {
        $this->startTest();
    }

    public function testCreateEntityDownTimeException()
    {
        $this->startTest();
    }

    public function testEditEntity()
    {
        $attributes = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "pdown_" . $downtime['id'];

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditEntityStatusOnly()
    {
        $attributes = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'end_time'         => '1622065955',
            'downtime_message' => 'HDFC bank NEFT payments are down',
            'uptime_message'   => 'RBL is up'
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "pdown_" . $downtime['id'];

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditEntityDisabledStateRequiredFieldsException()
    {
        $attributes = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'end_time'         => '1622065955',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "pdown_" . $downtime['id'];

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditEntityInvalidStatusException()
    {
        $attributes = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "pdown_" . $downtime['id'];

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchById()
    {
        $attributes = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes);

        $data = &$this->testData[__FUNCTION__];

        $url = $data['request']['url'] . "pdown_" . $downtime['id'];

        $data['request']['url'] = $url;

        $this->startTest();
    }

    public function testFetchAll()
    {
        $attributes1 = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes1);

        $attributes2 = [
            'status'           => 'Scheduled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468988',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes2);

        $this->startTest();
    }

    public function testEnabledDowntimeXDashboard()
    {
        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $attributes1 = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'start_time'       => Carbon::now()->getTimestamp(),
            'downtime_message' => 'RBL bank payments are down',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes1);

        $attributes2 = [
            'status'           => 'Enabled',
            'channel'          => 'Pool Network',
            'created_by'       => 'OPS_A',
            'start_time'       => Carbon::now()->getTimestamp(),
            'downtime_message' => 'Pool Network payments are down',
        ];

        $downtimePoolNetwork = $this->fixtures->create('payout_downtimes', $attributes2);

        $attributes3 = [
            'status'           => 'Enabled',
            'channel'          => 'All',
            'created_by'       => 'OPS_A',
            'start_time'       => Carbon::now()->subDays(2)->getTimestamp(),
            'downtime_message' => 'Razorpay Systems are down',
        ];

        $downtimeAll = $this->fixtures->create('payout_downtimes', $attributes3);

        $attributes3 = [
            'status'           => 'Disabled',
            'channel'          => 'All',
            'created_by'       => 'OPS_A',
            'start_time'       => Carbon::now()->subDays(2)->getTimestamp(),
            'downtime_message' => 'Razorpay Systems are down',
        ];

        $downtimeDisabled = $this->fixtures->create('payout_downtimes', $attributes3);

        $this->ba->proxyAuth("rzp_test_".'10000000000000');

        $response = $this->startTest();

        $this->assertEquals('pdown_'.$downtime->id, $response[0]['id']);

        $this->assertEquals('pdown_'.$downtimePoolNetwork->id, $response[1]['id']);

        $this->assertEquals('pdown_'.$downtimeAll->id, $response[2]['id']);

        foreach ($response as $key => $val)
        {
            $this->assertNotEquals('pdown_'.$downtimeDisabled->id, $val['id']);
        }

    }

    public function testSendEmailForCurrentAccount()
    {
        Mail::fake();

        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->startTest();

        Mail::assertQueued(PayoutDowntimeMail::class, function($mail) {
            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);
            $this->assertEquals('x.support@razorpay.com', $mail->cc[0]['address']);
            $this->assertEquals('helloworld@razorpay.com', $mail->bcc[0]['address']);
            $this->assertEquals('Important Update for your RazorpayX account.', $mail->subject);
            $this->assertEquals('emails.payout_downtime.enabled', $mail->view);
            $this->assertEquals('RBL bank payments are down', $mail->viewData['email_message']);
            return true;
        });

    }

    public function testSendEmailForPoolAccount()
    {
        Mail::fake();

        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::SHARED,
            Channel::YESBANK);

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->startTest();

        Mail::assertQueued(PayoutDowntimeMail::class, function($mail) {
            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);
            $this->assertEquals('x.support@razorpay.com', $mail->cc[0]['address']);
            $this->assertEquals('helloworld@razorpay.com', $mail->bcc[0]['address']);
            $this->assertEquals('Important Update for your RazorpayX account.', $mail->subject);
            $this->assertEquals('emails.payout_downtime.enabled', $mail->view);
            $this->assertEquals('Pool Network payments are down', $mail->viewData['email_message']);
            return true;
        });
    }

    //Channel All is used when razorpay systems are down.
    public function testSendEmailForAll()
    {
        Mail::fake();

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000011',
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->startTest();

        Mail::assertQueued(PayoutDowntimeMail::class, function($mail) {
            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);
            $this->assertEquals('x.support@razorpay.com', $mail->cc[0]['address']);
            $this->assertEquals('helloworld@razorpay.com', $mail->bcc[0]['address']);
            $this->assertEquals('Important Update for your RazorpayX account.', $mail->subject);
            $this->assertEquals('emails.payout_downtime.enabled', $mail->view);
            $this->assertEquals('All payments are down', $mail->viewData['email_message']);
            return true;
        });
    }

    public function testSendEmailForPrimaryMerchant()
    {
        Mail::fake();

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000011',
            'role'        => 'owner',
            'product'     => 'primary',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->startTest();

        Mail::assertNotQueued(PayoutDowntimeMail::class);
    }


    public function testSendEmailDisabledState()
    {
        Mail::fake();

        $this->setUpMerchantForBusinessBanking(
            false,
            10000,
            AccountType::DIRECT,
            Channel::RBL);

        $this->balance = $this->getDbEntity('balance', ['merchant_id' => '10000000000000', 'type' => 'banking']);

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => '10000000000000',
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $attributes1 = [
            'status'               => 'Enabled',
            'channel'              => 'RBL',
            'created_by'           => 'OPS_A',
            'start_time'           => Carbon::now()->getTimestamp(),
            'end_time'             => Carbon::now()->addDays(1)->getTimestamp(),
            'downtime_message'     => 'RBL payments are down',
            'uptime_message'       => 'RBL payments are up',
            'enabled_email_option' => 'Yes',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes1);

        $input = [
            'payout_downtime' => [
                'status'                => 'Disabled',
                'disabled_email_option' => 'Yes',
            ],
        ];

        $data = &$this->testData[__FUNCTION__];

        $data['request']['content'] = $input;

        $url = "/payouts/downtime/" . "pdown_" . $downtime['id'];

        $data['request']['url'] = $url;

        $this->startTest();

        $entity = DB::table('payout_downtimes')->where(Entity::ID, '=', $downtime['id'])
                    ->where(Entity::DISABLED_EMAIL_STATUS, '=', Constants::SENT)
                    ->where(Entity::DISABLED_EMAIL_OPTION, '=', Constants::YES)
                    ->get();

        $this->assertNotNull($entity);

        Mail::assertQueued(PayoutDowntimeMail::class, function($mail) {
            $this->assertEquals('x.support@razorpay.com', $mail->from[0]['address']);
            $this->assertEquals('x.support@razorpay.com', $mail->cc[0]['address']);
            $this->assertEquals('helloworld@razorpay.com', $mail->bcc[0]['address']);
            $this->assertEquals('Important Update for your RazorpayX account.', $mail->subject);
            $this->assertEquals('emails.payout_downtime.disabled', $mail->view);
            $this->assertEquals('RBL payments are up', $mail->viewData['email_message']);
            return true;
        });

    }
}
