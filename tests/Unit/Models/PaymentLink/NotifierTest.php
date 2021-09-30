<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Illuminate\Support\Facades\Mail;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink\Notifier;
use RZP\Models\PaymentLink\Entity;
use RZP\Tests\Traits\PaymentLinkTestTrait;

class NotifierTest extends TestCase
{
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';

    /**
     * @group nocode_pp_notifier
     */
    public function testNotifyByEmailShouldGracefullyHandleExceptionOnEmailFailure()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID,  [
            'view_type' => 'page',
            Entity::EXPIRE_BY   => Carbon::now(Timezone::IST)->addDays(100)->getTimestamp()
        ]);
        Mail::shouldReceive('send')->andThrow(new \Exception());

        $notifier = new Notifier();
        $this->assertNull($notifier->notifyByEmailAndSms($pl, [Entity::EMAILS => ["random@email.com"]]));
    }

    /**
     * @group nocode_pp_notifier
     */
    public function testNotifyByEmailShouldGracefullyHandleExceptionOnRavenFailure()
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID,  [
            'view_type' => 'page',
            Entity::EXPIRE_BY   => Carbon::now(Timezone::IST)->addDays(100)->getTimestamp()
        ]);

        $raven = \Mockery::mock('RZP\Services\Raven')->makePartial();

        $this->app->instance('raven', $raven);

        $raven->shouldReceive('sendRequest')->andThrow(new \Exception());
        $this->app->instance('raven', $raven);
        $notifier = new Notifier();
        $this->assertNull($notifier->notifyByEmailAndSms($pl, [Entity::CONTACTS => ["8685748574"]]));
    }
}
