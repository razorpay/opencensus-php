<?php

namespace Tests\Functional\Merchant;

use Carbon\Carbon;
use Mockery;
use Tests\Functional\TestCase;
use Tests\Functional\RequestResponseFlowTrait;
use Tests\Functional\Settlement\SettlementTrait;

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

        $content = $this->sendHolidayNotification();
    }

    protected function sendHolidayNotification()
    {
        $request = [
            'url' => '/merchants/notify/holiday',
            'method' => 'POST',
            'content' => ['action' => 'email', 'lists' => 'live'],
        ];

        $this->ba->appAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
