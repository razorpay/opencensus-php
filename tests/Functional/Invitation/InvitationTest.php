<?php

namespace RZP\Tests\Functional\Invitation;

use DB;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class InvitationTest extends TestCase
{
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '1000InviteMerc';

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InvitationTestData.php';

        parent::setUp();

        $this->fixtures->create('merchant', ['id' => self::DEFAULT_MERCHANT_ID]);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID);
    }

    public function testPostSendInvitationToNewUser()
    {
        $this->startTest();
    }

    public function testPostSendInvitationToExistingUser()
    {
        $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'existingInvite@razorpay.com'
                                ]);

        $this->startTest();
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
        $this->fixtures->create('invitation');

        $this->startTest();
    }

    public function testAcceptInvitation()
    {
        $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'testTeamInvite@razorpay.com'
                                ]);

        $this->fixtures->create('invitation');

        $this->startTest();

        $invite = \DB::table('invitations')
                     ->where('id', '=', '8hd48md930kel3')
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

        $this->fixtures->create('invitation',
                                [
                                    'user_id'     => '1000InviteUser',
                                    'email'       => 'reject@razorpay.com'
                                ]);

        $this->startTest();

        $invite = \DB::table('invitations')
                     ->where('id', '=', '8hd48md930kel3')
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
        $this->fixtures->create('invitation', [ 'email' => 'asd']);

        $this->startTest();
    }

    public function testUpdateInvitation()
    {
        $this->fixtures->create('invitation', ['email' => 'update@razorpay.com']);

        $this->startTest();
    }

    public function testUpdateInvitationWithInvalidRole()
    {
        $this->fixtures->create('invitation');

        $this->startTest();
    }

    public function testUpdateDeletedInvitation()
    {
        $this->fixtures->create('invitation',
                                [
                                    'email'       => 'update@razorpay.com',
                                    'deleted_at'  => '144339434',
                                ]);

        $this->startTest();
    }

    public function testDeleteMerchantInvitation()
    {
        $this->fixtures->create('invitation', ['email' => 'delete@razorpay.com']);

        $this->startTest();

        $invite = \DB::table('invitations')
                     ->where('id', '=', '8hd48md930kel3')
                     ->whereNull('deleted_at')
                     ->first();

        $this->assertNull($invite);
    }

    public function testGetPendingInvitations()
    {
        $this->fixtures->create('invitation', ['email' => 'pending1@razorpay.com']);

        $this->fixtures->create('invitation',
                                [
                                    'id'          => '8hd48md930kel4',
                                    'email'       => 'pending2@razorpay.com',
                                    'role'        => 'finance',
                                ]);

        $this->startTest();
    }
}
