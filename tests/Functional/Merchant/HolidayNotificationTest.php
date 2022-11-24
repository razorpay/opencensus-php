<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Mode;
use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Settlement\Holidays;
use Http\Adapter\Guzzle7\Client as GuzzleClient;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Settlement\SettlementTrait;

class HolidayNotificationTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testHolidayNotification()
    {
        \Mail::shouldReceive('send')
              ->twice()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $testData = array(
                                'subject' => 'Bank Holiday - Settlement Update');

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any()
                );

        $content = $this->sendHolidayNotification(Mode::TEST);
    }

    public function testHolidayNotificationOnLiveHoliday1()
    {
        $date = Carbon::today(Timezone::IST);

        $newDate = $this->getNextRandomDoubleWorkingDay($date);

        Carbon::setTestNow($newDate);

        $content = $this->sendHolidayNotification(Mode::LIVE);

        $this->assertEquals($content[0]['settlement_default']['message'], "Next working day is not a bank holiday. Nothing to send.");

        $this->assertEquals($content[1]['settlement_on_demand']['message'], "Next working day is not a bank holiday. Nothing to send.");

        Carbon::setTestNow();
    }


    public function testHolidayNotificationOnLiveHolidaySend()
    {
        \Mail::shouldReceive('send')
              ->twice()
              ->with(
                    Mockery::any(),
                    Mockery::on(function ($data)
                        {
                            $testData = array(
                                'subject' => 'Bank Holiday - Settlement Update');

                            $this->assertArraySelectiveEquals($testData, $data);

                            return true;
                        }),
                    Mockery::any()
                );

        $date = Carbon::parse('3 September 2016', Timezone::IST);

        $date = $this->getRandomWorkingDayThatIsASettlementHoliday($date);

        Carbon::setTestNow($date);

        $content = $this->sendHolidayNotification(Mode::LIVE);

        $this->assertEquals($content[0]['settlement_default']['email'], 'live_settlement_default@razorpay.com');

        $this->assertEquals($content[1]['settlement_on_demand']['email'], 'live_settlement_on_demand@razorpay.com');

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

    protected function getNextRandomDoubleWorkingDay($date, $ignoreBankHolidays = false)
    {
        // get two days where on
        $tempDate = $date;

        do
        {
            $tempDate = $this->getRandomWorkingDay($tempDate, $ignoreBankHolidays);

            $nextDate = $tempDate->copy();

            $nextDate = $nextDate->addDay();
        }
        while((Holidays::isWorkingDay($tempDate) === false) or
              (Holidays::isWorkingDay($nextDate) === false));

        return $tempDate;
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
            $this->ba->cronAuth($mode);
        }
        else if ($mode === Mode::TEST)
        {
            $this->ba->cronAuth($mode);
        }

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
