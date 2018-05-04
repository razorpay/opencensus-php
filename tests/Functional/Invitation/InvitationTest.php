<?php

namespace RZP\Tests\Functional\Invitation;

use DB;
use Mail;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Invitation\Invite as InvitationMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvitationTest extends TestCase
{
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '1000InviteMerc';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InvitationTestData.php';

        parent::setUp();

        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_MERCHANT_ID ]);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);
    }

    public function testPostSendInvitationToNewUser()
    {
        Mail::fake();

        $this->startTest();

        Mail::assertQueued(InvitationMail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('sender_name', $viewData);
            $this->assertArrayHasKey('merchant_name', $viewData);
            $this->assertArrayHasKey('token', $viewData);

            $this->assertEquals('emails.invitation.new', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToExistingUser()
    {
        Mail::fake();

        $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'existingInvite@razorpay.com'
                                ]);

        $this->startTest();

        Mail::assertQueued(InvitationMail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertArrayHasKey('sender_name', $viewData);
            $this->assertArrayHasKey('merchant_name', $viewData);
            $this->assertArrayHasKey('token', $viewData);

            $this->assertEquals('emails.invitation.existing', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToInvitedUser()
    {
        $this->fixtures->create('invitation');

        $this->startTest();
    }

    public function testPostSendInvitationWithInvalidRole()
    {
        $this->startTest();
    }

    public function testPostResendInvitation()
    {
        $invitation = $this->fixtures->create('invitation');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/resend';

        $this->startTest();
    }

    public function testAcceptInvitation()
    {
        $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'testTeamInvite@razorpay.com'
                                ]);

        $invitation = $this->fixtures->create('invitation');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->appAuth();

        $this->startTest();

        $invite = \DB::table('invitations')
                     ->where('id', '=', $invitation['id'])
                     ->whereNull('deleted_at')
                     ->first();

        $this->assertNull($invite);

        $merchants = DB::table('merchant_users')
                       ->where('user_id', '=', '1000InviteUser')
                       ->where('merchant_id', self::DEFAULT_MERCHANT_ID)
                       ->first();

        $this->assertEquals('manager', $merchants->role);

    }

    public function testRejectInvitation()
    {
        $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'reject@razorpay.com'
                                ]);

        $invitation = $this->fixtures->create('invitation',
                                [
                                    'user_id'     => '1000InviteUser',
                                    'email'       => 'reject@razorpay.com'
                                ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/reject';

        $this->ba->appAuth();

        $this->startTest();

        $invite = \DB::table('invitations')
                     ->where('id', '=', $invitation['id'])
                     ->whereNotNull('deleted_at')
                     ->first();

        $this->assertNotNull($invite);

        $merchants = DB::table('merchant_users')
                       ->where('user_id', '=', '1000InviteUser')
                       ->where('merchant_id', self::DEFAULT_MERCHANT_ID)
                       ->first();

        $this->assertNull($merchants);
    }

    public function testInvalidResponseToInvitation()
    {
        $invitation = $this->fixtures->create('invitation', [ 'email' => 'asd']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/hello';

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testUpdateInvitation()
    {
        $invitation = $this->fixtures->create('invitation', ['email' => 'update@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'];

        $this->startTest();
    }

    public function testUpdateInvitationWithInvalidRole()
    {
        $invitation = $this->fixtures->create('invitation');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'];

        $this->startTest();
    }

    public function testUpdateDeletedInvitation()
    {
        $invitation = $this->fixtures->create('invitation',
                                [
                                    'email'       => 'update@razorpay.com',
                                    'deleted_at'  => '144339434',
                                ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'];

        $this->startTest();
    }

    public function testDeleteMerchantInvitation()
    {
        $invitation = $this->fixtures->create('invitation', ['email' => 'delete@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'];

        $this->startTest();

        $invite = \DB::table('invitations')
                     ->where('id', '=', $invitation['id'])
                     ->whereNull('deleted_at')
                     ->first();

        $this->assertNull($invite);
    }

    public function testGetPendingInvitations()
    {
        $this->fixtures->create('invitation', ['email' => 'pending1@razorpay.com']);

        $this->fixtures->create('invitation',
                                [
                                    'email'       => 'pending2@razorpay.com',
                                    'role'        => 'finance',
                                ]);

        $testData = & $this->testData[__FUNCTION__];

        $response = $this->makeRequestAndGetContent($testData['request']);

        $this->assertEquals(count($response), 2);
    }

    public function testGetInvitationByToken()
    {
        $invitation = $this->fixtures->create('invitation');

        $invite = \DB::table('invitations')
                     ->where('id', '=', $invitation['id'])
                     ->first();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/token/' . $invite->token;

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetInvitationByInvalidToken()
    {
        $this->fixtures->create('invitation');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/token/' . '2000000000000020000000000000200000000008';

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetInvitationsReceivedBeforeSignup()
    {
        $this->fixtures->create('invitation', ['email' => 'old@razorpay.com']);

        $this->fixtures->create('invitation',
            [
                'email'       => 'old@razorpay.com',
                'role'        => 'finance',
            ]);

        $this->fixtures->create('invitation', ['email' => 'someelse@razorpay.com']);

        $user = $this->fixtures->create('user', ['email' => 'old@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $this->ba->appAuth();

        $testData['request']['url'] = '/users/' . $user['id'];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(count($response['invitations']), 2);
    }

    public function testGetInvitationsReceivedPostSignup()
    {
        $user = $this->fixtures->create('user', ['email' => 'old@razorpay.com']);

        $this->fixtures->create('invitation', ['email' => 'someelse@razorpay.com']);

        $this->fixtures->create('invitation', ['email' => 'old@razorpay.com']);

        $this->fixtures->create('invitation',
            [
                'email'       => 'old@razorpay.com',
                'role'        => 'finance',
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $this->ba->appAuth();

        $testData['request']['url'] = '/users/' . $user['id'];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(count($response['invitations']), 2);
    }
}
