<?php

namespace RZP\Tests\Functional\Invitation;

use DB;
use Mail;
use Carbon\Carbon;

use RZP\Constants\Table;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Invitation\Invite as InvitationMail;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Mail\Invitation\RazorpayX\Invite as xInvitationMail;
use RZP\Models\Merchant\MerchantUser\Entity as MerchantUserEntity;

class InvitationTest extends TestCase
{
    use CustomBrandingTrait;
    use RequestResponseFlowTrait;

    const DEFAULT_MERCHANT_ID = '1000InviteMerc';

    const DEFAULT_X_MERCHANT_ID = '100XInviteMerc';

    const EXISTING_MERCHANT_FOR_INVITED_USER_ID = '10000000000001';

    protected $merchantUser;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InvitationTestData.php';

        parent::setUp();

        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_MERCHANT_ID ]);

        $this->merchantUser = $this->fixtures->user->createUserForMerchant(self::DEFAULT_MERCHANT_ID);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_MERCHANT_ID, $this->merchantUser->getId());
    }

    public function createXMerchantUser()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant',[ 'id' => self::EXISTING_MERCHANT_FOR_INVITED_USER_ID ]);

        $xMerchantUser = $this->createUserForMerchantWithoutCreatingMerchantUser(['email' => 'testteamxinvite@razorpay.com']);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        return $xMerchantUser;
    }

    public function createUserForMerchantWithoutCreatingMerchantUser(array $attributes = [])
    {
        $user = $this->fixtures->user->createEntityInTestAndLive('user', $attributes);

        return $user;
    }

    public function testPostSendInvitationToNonExistingUserInX()
    {
        Mail::fake();

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $this->startTest();

        Mail::assertQueued(xInvitationMail::class, function ($mail)
        {
            $this->assertEquals('emails.invitation.razorpayx.invite_new_user', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToNonExistingUserInXForCARole()
    {
        Mail::fake();

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $this->startTest();

        Mail::assertQueued(xInvitationMail::class, function ($mail) {
            $this->assertEquals('emails.invitation.razorpayx.ca-invitation', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToNewUserInX()
    {
        Mail::fake();

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $this->startTest();

        Mail::assertQueued(xInvitationMail::class, function ($mail)
        {
            $this->assertEquals('emails.invitation.razorpayx.invite_existing_user', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToPgAdminInX()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testPostSendInvitationToNewUserInX'];

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $userId = $xMerchantUser['id'];

        DB::table('merchant_users')
            ->insert([
                'merchant_id'    => self::EXISTING_MERCHANT_FOR_INVITED_USER_ID,
                'user_id'        => $userId,
                'product'        => 'primary',
                'role'           => 'admin',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);

        $this->startTest();

        Mail::assertQueued(xInvitationMail::class, function ($mail)
        {
            $this->assertEquals('emails.invitation.razorpayx.invite_existing_user', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToPgOwnerInX()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testPostSendInvitationToNewUserInX'];

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $userId = $xMerchantUser['id'];

        DB::table('merchant_users')
            ->insert([
                'merchant_id'    => self::EXISTING_MERCHANT_FOR_INVITED_USER_ID,
                'user_id'        => $userId,
                'product'        => 'primary',
                'role'           => 'owner',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);

        $this->startTest();

        Mail::assertQueued(xInvitationMail::class, function ($mail)
        {
            $this->assertEquals('emails.invitation.razorpayx.invite_existing_user', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToExistingUserInX()
    {
        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testPostSendInvitationToNewUserInX'];

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $userId = $xMerchantUser['id'];

        DB::table('merchant_users')
            ->insert([
                'merchant_id'    => self::EXISTING_MERCHANT_FOR_INVITED_USER_ID,
                'user_id'        => $userId,
                'product'        => 'banking',
                'role'           => 'owner',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);

        $this->startTest();

        Mail::assertQueued(xInvitationMail::class, function ($mail)
        {
            $this->assertEquals('emails.invitation.razorpayx.invite_existing_x_user', $mail->view);

            return true;
        });
    }

    public function testPostSendInvitationToNewUserCustomBrandingOrg()
    {
        Mail::fake();

        $org = $this->createCustomBrandingOrgAndAssignMerchant(self::DEFAULT_MERCHANT_ID);

        $this->testData[__FUNCTION__] = $this->testData['testPostSendInvitationToNewUser'];

        $this->startTest();

        Mail::assertQueued(InvitationMail::class, function ($mail) use ($org)
        {
            $this->assertCustomBrandingMailViewData($org, $mail->viewData);

            return true;
        });
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
                                    'email' => 'existinginvite@razorpay.com'
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

    public function testPostSendInvitationToExistingTeamUser()
    {
        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['content']['email'] = $this->merchantUser['email'];

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

    public function testPostSendInvitationWithOwnerRole()
    {
        $this->startTest();
    }

    public function testPostSendInvitationByRBLSupervisorToValidRole()
    {
        $nonOwnerUser = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $nonOwnerUser->id,
                                                             'role'        => 'rbl_supervisor',
                                                         ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $nonOwnerUser->id);

        $this->startTest();
    }

    public function testPostSendInvitationByRBLSupervisorToInvalidRole()
    {
        $nonOwnerUser = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $nonOwnerUser->id,
                                                             'role'        => 'rbl_supervisor',
                                                         ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $nonOwnerUser->id);

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
                                    'email' => 'testteaminvite@razorpay.com'
                                ]);

        $invitation = $this->fixtures->create('invitation', ['email' => 'testteaminvite@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->dashboardGuestAppAuth();

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

    public function testAcceptInvitationByAlreadyExistingUserOnX()
    {
        $this->mockRazorxTreatment('on');

        $this->fixtures->create('user',
            [
                'id'    => '1000InviteUser',
                'email' => 'testteaminvite@razorpay.com'
            ]);

        DB::table('merchant_users')
            ->insert([
                'merchant_id'    => self::DEFAULT_MERCHANT_ID,
                'user_id'        => '1000InviteUser',
                'product'        => 'banking',
                'role'           => 'finance_l1',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);

        $invitation = $this->fixtures->create('invitation', [
            'email'     => 'testteaminvite@razorpay.com',
            'product'   => 'banking',
            'role'      => 'admin'
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testAcceptInvitationByAlreadyExistingUserOnXWithExperimentOff()
    {
        $this->mockRazorxTreatment('off');

        $this->fixtures->create('user',
            [
                'id'    => '1000InviteUser',
                'email' => 'testteaminvite@razorpay.com'
            ]);

        DB::table(Table::MERCHANT_USERS)
            ->insert([
                'merchant_id'    => self::DEFAULT_MERCHANT_ID,
                'user_id'        => '1000InviteUser',
                'product'        => 'banking',
                'role'           => 'finance_l1',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);

        $invitation = $this->fixtures->create('invitation', [
            'email'     => 'testteaminvite@razorpay.com',
            'product'   => 'banking',
            'role'      => 'admin'
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        $data = DB::table(Table::MERCHANT_USERS)->where(MerchantUserEntity::MERCHANT_ID, '=', self::DEFAULT_MERCHANT_ID)
                                                ->where(MerchantUserEntity::USER_ID, '=', '1000InviteUser')
                                                ->where(MerchantUserEntity::PRODUCT, '=', 'banking')
                                                ->get();

        $this->assertEquals(count($data), 2);
    }

    public function testAcceptInvitationByRestrictedMerchant()
    {
        $this->mockRazorxTreatment();

        $this->fixtures->create(
            'user',
            [
                'id'    => '1000InviteUser',
                'email' => 'testteaminvite@razorpay.com'
            ]);

        $merchant = $this->fixtures->create('merchant',
                                            ['restricted' => true]);

        $invitation = $this->fixtures->create('invitation',
                                              ['merchant_id' => $merchant['id'],
                                               'email'       => 'testteaminvite@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testAcceptInvitationForRestrictedUser()
    {
        $this->mockRazorxTreatment();

        $user = $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'testteaminvite@razorpay.com'
                                ]);

        $merchantIdsCaller = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->edit($merchantIdsCaller[0], ['restricted' => true]);

        $invitation = $this->fixtures->create('invitation',
                                               ['email'       => 'testteaminvite@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
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

        $this->ba->dashboardGuestAppAuth();

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
        $this->fixtures->create('user',
                                [
                                    'id'    => '1000InviteUser',
                                    'email' => 'reject@razorpay.com'
                                ]);

        $invitation = $this->fixtures->create('invitation',
                                                [ 'user_id'     => '1000InviteUser',
                                                  'email'       => 'reject@razorpay.com'
                                                ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/hello';

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testAcceptRandomValidInvitation()
    {
        $this->fixtures->create('user',
            [
                'id'    => '1000InviteUser',
                'email' => 'testteaminvite@razorpay.com'
            ]);

        $invitation = $this->fixtures->create('invitation', [ 'email' => 'other@razorpay.com']);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/' . $invitation['id'] .'/accept';

        $this->ba->dashboardGuestAppAuth();

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

    public function testGetPendingInvitationsWhichAreNotDraft()
    {
        $this->fixtures->create('invitation',
            [
                'email'       => 'pending1@razorpay.com',
                'product'     => 'banking',
                'merchant_id' => '10000000000000',
            ]);

        $this->fixtures->create('invitation',
            [
                'email'       => 'pending1@razorpay.com',
                'role'        => 'manager',
                'is_draft'    =>  0,
                'merchant_id' => '10000000000000',
            ]);

        $nonOwnerUser = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $nonOwnerUser->id,
            'role'        => 'owner',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $nonOwnerUser->toArrayPublic(), 'owner');

        $this->startTest();
    }

    public function testGetPendingInvitationsByNonOwnerMember()
    {
        $this->fixtures->create('invitation', ['email' => 'pending1@razorpay.com']);

        $this->fixtures->create('invitation',
            [
                'email'       => 'pending2@razorpay.com',
                'role'        => 'finance',
            ]);

        $nonOwnerUser = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $nonOwnerUser->id,
            'role'        => 'operations',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $nonOwnerUser->toArrayPublic());

        $this->startTest();
    }

    public function testGetInvitationByToken()
    {
        $invitation = $this->fixtures->create('invitation');

        $invite = \DB::table('invitations')
                     ->where('id', '=', $invitation['id'])
                     ->first();

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/token/' . $invite->token;

        $this->ba->dashboardGuestAppAuth();

        $this->startTest();
    }

    public function testGetInvitationByInvalidToken()
    {
        $this->fixtures->create('invitation');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/invitations/token/' . '2000000000000020000000000000200000000008';

        $this->ba->dashboardGuestAppAuth();

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

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

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

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $this->ba->dashboardGuestAppAuth();

        $testData['request']['url'] = '/users/' . $user['id'];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(count($response['invitations']), 2);
    }

    public function testGetInvitationsSentToUpperCaseEmail()
    {
        $user = $this->fixtures->create('user', ['email' => 'upper_case@razorpay.com']);

        $invite = [
            'role' => 'finance',
            'email' => 'UPPER_Case@razorpay.com',
            'sender_name' => 'sender'
        ];

        $this->sendInvitation($invite);

        $this->fixtures->create('invitation',
            [
                'email'       => 'UPPER_Case@razorpay.com',
                'role'        => 'finance',
            ]);

        $testData = & $this->testData[__FUNCTION__];

        $this->ba->dashboardGuestAppAuth();

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-Id'] = $user['id'];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertEquals(count($response['invitations']), 1);
    }

    public function testPostSendInvitationForUserRestricted()
    {
        $this->mockRazorxTreatment();

        $userCaller = $this->fixtures->create('user');

        $merchantIdsCaller = $userCaller->merchants()->get()->pluck('id')->toArray();

        $user = $this->fixtures->create('user', ['email' => 'testteaminvite@razorpay.com']);

        $merchantIds = $user->merchants()->get()->pluck('id')->toArray();

        $this->fixtures->merchant->edit($merchantIds[0], ['restricted' => true]);

        $this->ba->proxyAuth('rzp_test_' . $merchantIdsCaller[0], $userCaller['id'], 'owner');

        $this->startTest();
    }

    protected function mockRazorxTreatment(string $returnValue = 'On')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn($returnValue);
    }

    public function testPostSendInvitationByMerchantRestricted()
    {
        $this->mockRazorxTreatment();

        $userCaller = $this->fixtures->create('user');

        $merchantIdsCaller = $userCaller->merchants()->get()->pluck('id')->toArray();

        $user = $this->fixtures->create('user', ['email' => 'testteaminvite@razorpay.com']);

        $this->fixtures->merchant->edit($merchantIdsCaller[0], ['restricted' => true]);

        $this->ba->proxyAuth('rzp_test_' . $merchantIdsCaller[0], $userCaller['id'], 'owner');

        $this->startTest();
    }

    protected function sendInvitation($attributes)
    {
        $this->ba->proxyAuth();

        $request['content'] = $attributes;

        $request['url'] = '/invitations';

        $request['method'] = 'POST';

        $this->makeRequestAndGetContent($request);
    }

    public function testDraftInvitationsCreate()
    {
        $invitation = $this->fixtures->create('invitation', ['email' => 'testteaminvite@razorpay.com']);

        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $this->startTest();

        $invite = DB::table('invitations')
            ->where('id', '=', $invitation['id'])
            ->first();
    }

    public function testEmailDraftInvitations()
    {
        $xMerchantUser = $this->createXMerchantUser();

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $xMerchantUser->getId());

        $invitation = $this->fixtures->create('invitation',['merchant_id'=> self::DEFAULT_X_MERCHANT_ID,
                                                                    'is_draft'    =>  1,
        ]);

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/draft_invitations/accept';

        $testData['request']['content']['invitation_ids'][]= $invitation['id'];

        $this->startTest();
    }


    public function testGetPendingInvitationsWithDraftStateAsTrue()
    {
        $this->fixtures->create('invitation', ['email' =>  'pending1@razorpay.com']);

        $this->fixtures->create('invitation',
            [
                'email'       => 'pending2@razorpay.com',
                'role'        => 'finance',
                'is_draft'    =>  1,
                'merchant_id' => '10000000000000',
            ]);

        $nonOwnerUser = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
            'merchant_id' => '10000000000000',
            'user_id'     => $nonOwnerUser->id,
            'role'        => 'operations',
        ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $nonOwnerUser->toArrayPublic(), 'operations');

        $this->startTest();
    }

}
