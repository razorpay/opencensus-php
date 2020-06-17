<?php

namespace RZP\Tests\Functional\PayoutDowntime;

use RZP\Constants\Table;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutDowntime\Core;
use RZP\Models\PayoutDowntime\Entity;
use RZP\Models\PayoutDowntime\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use DB;
use Mail;
use RZP\Mail\PayoutDowntime\PayoutDowntimeMail;

class PayoutDowntimeTest extends TestCase
{

    use HeimdallTrait;
    use RequestResponseFlowTrait;

    public function setUp()
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

    public function testCreateEntityModeException()
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
            'mode'             => 'NEFT',
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
            'mode'             => 'NEFT',
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
            'mode'             => 'NEFT',
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
            'mode'             => 'NEFT',
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
            'mode'             => 'NEFT',
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
            'mode'             => 'NEFT',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes1);

        $attributes2 = [
            'status'           => 'Scheduled',
            'channel'          => 'RBL',
            'mode'             => 'NEFT',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468988',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes2);

        $this->startTest();
    }

    public function testEnabledDowntime()
    {
        $this->ba->proxyAuth();

        $attributes1 = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'mode'             => 'IMPS',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468916',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes1);

        $attributes2 = [
            'status'           => 'Scheduled',
            'channel'          => 'RBL',
            'mode'             => 'NEFT',
            'created_by'       => 'OPS_A',
            'start_time'       => '1590468988',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes2);

        $attributes3 = [
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'mode'             => 'NEFT',
            'created_by'       => 'OPS_A',
            'start_time'       => '1906062755',
            'downtime_message' => 'HDFC bank NEFT payments are down',
        ];

        $this->fixtures->create('payout_downtimes', $attributes3);

        $this->startTest();
    }

    public function testSendEmailEnabledState()
    {
        Mail::fake();

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant', ['id' => '100abc000abc00', 'email' => 'dharmana.sunil@razorpay.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
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
            $this->assertEquals('HDFC bank NEFT payments are down', $mail->viewData['email_message']);
            return true;
        });

    }

    public function testSendEmailEnabledStateException()
    {
        Mail::fake();

        $this->startTest();

    }

    public function testSendEmailInvalidMIDException()
    {
        Mail::fake();

        $this->startTest();

        Mail::assertNotSent(PayoutDowntimeMail::class);
    }

    public function testSendEmailInvalidMIDException2()
    {
        Mail::fake();

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'dharmana.sunil@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant', ['id' => '100abc000abc00', 'email' => 'dharmana.sunil@razorpay.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->startTest();

        Mail::assertNotSent(PayoutDowntimeMail::class);
    }

    public function testSendEmailDisabledState()
    {
        Mail::fake();

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'helloworld@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant', ['id' => '100abc000abc00', 'email' => 'helloworld@razorpay.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
            'product'     => 'banking',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $attributes1 = [
            'status'               => 'Enabled',
            'channel'              => 'RBL',
            'mode'                 => 'NEFT',
            'created_by'           => 'OPS_A',
            'start_time'           => '1590468916',
            'end_time'             => '1599468916',
            'downtime_message'     => 'HDFC bank NEFT payments are down',
            'uptime_message'       => 'HDFC bank NEFT payments are up',
            'enabled_email_option' => 'Yes',
        ];

        $downtime = $this->fixtures->create('payout_downtimes', $attributes1);

        $input = [
            'payout_downtime' => [
                'status'                => 'Disabled',
                'disabled_email_option' => 'Yes',
                Constants::MID_LIST     => [
                    '100abc000abc00'
                ],
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
            $this->assertEquals('HDFC bank NEFT payments are up', $mail->viewData['email_message']);
            return true;
        });

    }

    public function testSendEmailForPrimaryMerchant()
    {
        Mail::fake();

        $userAttributes = [
            'id'       => '20000000000000',
            'email'    => 'dharmana.sunil@razorpay.com',
            'password' => '1234567890'
        ];

        $user = $this->fixtures->create('user', $userAttributes);

        $merchant = $this->fixtures->create('merchant', ['id' => '100abc000abc00', 'email' => 'dharmana.sunil@razorpay.com']);

        $mappingData = [
            'user_id'     => $user['id'],
            'merchant_id' => $merchant['id'],
            'role'        => 'owner',
            'product'     => 'product',
        ];

        $this->fixtures->create('user:user_merchant_mapping', $mappingData);

        $this->startTest();

        Mail::assertNotSent(PayoutDowntimeMail::class);

    }

}
