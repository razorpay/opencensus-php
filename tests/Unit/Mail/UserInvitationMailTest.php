<?php


namespace Unit\Mail;

use DB;
use Carbon\Carbon;
use RZP\Models\Invitation\Core;
use RZP\Tests\Functional\TestCase;


class CoreTest extends Core {

    public function isExistingUserOnX($allMerchantsForInvitedUser): bool
    {
        return parent::isExistingUserOnX($allMerchantsForInvitedUser);
    }
}

class UserInvitationMailTest extends TestCase
{

    public function createUser(array $attributes = [])
    {
        return $this->fixtures->user->createEntityInTestAndLive('user', $attributes);
    }

    public function testisExistingUserOnX()
    {
        $invitedUser1 = $this->createUser(['email' => 'testteamxinvite1@razorpay.com']);
        $invitedUser2 = $this->createUser(['email' => 'testteamxinvite2@razorpay.com']);

        DB::table('merchant_users')
            ->insert([
                'merchant_id'    => "10000000000000",
                'user_id'        => $invitedUser1->getId(),
                'product'        => 'banking',
                'role'           => 'owner',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ], [
                'merchant_id'    => "10000000000001",
                'user_id'        => $invitedUser2->getId(),
                'product'        => 'payment',
                'role'           => 'owner',
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);

        $allMerchantsForInvitedUser1 = optional($invitedUser1)->merchants;
        $allMerchantsForInvitedUser2 = optional($invitedUser2)->merchants;

        $output1 = (new CoreTest)->isExistingUserOnX($allMerchantsForInvitedUser1);
        $output2 = (new CoreTest)->isExistingUserOnX($allMerchantsForInvitedUser2);

        self::assertEquals(true,$output1);
        self::assertEquals(false,$output2);


    }
}
