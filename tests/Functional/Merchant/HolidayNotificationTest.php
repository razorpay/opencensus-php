<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Constants\Mode;
use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Holidays;
use Http\Adapter\Guzzle6\Client as GuzzleClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

class HolidayNotificationTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        parent::setUp();
    }

    public function testHolidayNotification()
    {
        \Mail::shouldReceive('send')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $testData = array(
                                'subject' => 'Notification of Bank Holiday');

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any()
                );

        $content = $this->sendHolidayNotification(Mode::TEST);
    }

    public function testHolidayNotificationOnLiveHoliday()
    {
        $this->markTestSkipped();

        $date = Carbon::today('Asia/Kolkata');

        $date = $this->getRandomWorkingDay($date);

        Carbon::setTestNow($date);

        $content = $this->sendHolidayNotification(Mode::LIVE);

        assert($content['message'] === "Next working day is not a bank holiday. Nothing to send.");

        Carbon::setTestNow();
    }


    public function testHolidayNotificationOnLiveHolidaySend()
    {
        \Mail::shouldReceive('send')
              ->once()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $testData = array(
                                'subject' => 'Notification of Bank Holiday');

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any()
                );

        $date = Carbon::parse('3 September 2016', 'Asia/Kolkata');

        $date = $this->getRandomWorkingDayThatIsASettlementHoliday($date);

        Carbon::setTestNow($date);

        $content = $this->sendHolidayNotification(Mode::LIVE);

        assert($content['email'] === 'live@razorpay.com');

        Carbon::setTestNow();
    }

    protected function getRandomWorkingDayThatIsASettlementHoliday($date)
    {
        $nextSettlementHoliday = Holidays::getNextSettlementHoliday($date);

        $previousWorkingDay = Holidays::getPreviousWorkingDay($nextSettlementHoliday);

        return $previousWorkingDay;
    }

    protected function getRandomWorkingDay($date, $ignoreBankHolidays = false)
    {
        return Holidays::getNextWorkingDay($date, $ignoreBankHolidays);
    }

    protected function sendHolidayNotification($mode)
    {
        $request = [
            'url' => '/merchants/notify/holiday',
            'method' => 'POST',
            'content' => ['action' => 'email', 'lists' => 'live'],
        ];

        if ($mode === Mode::LIVE)
        {
            $this->ba->appAuthLive();
        }
        else if ($mode === Mode::TEST)
        {
            $this->ba->appAuthTest();
        }

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
