<?php

namespace RZP\Tests\Functional\Settlement;

use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Holidays;

trait SettlementTrait
{
    protected function getSettlementsMerchantDashboardServiceMock()
    {
        $settlementsMerchantDashboardMock = Mockery::mock('RZP\Services\Settlements\MerchantDashboard', [$this->app])->makePartial();

        $settlementsMerchantDashboardMock->shouldAllowMockingProtectedMethods();

        $this->app['settlements_merchant_dashboard'] = $settlementsMerchantDashboardMock;

        return $settlementsMerchantDashboardMock;
    }

    protected function getSettlementsDashboardServiceMock()
    {
        $settlementsDashboardMock = Mockery::mock('RZP\Services\Settlements\Dashboard', [$this->app])->makePartial();

        $settlementsDashboardMock->shouldAllowMockingProtectedMethods();

        $this->app['settlements_dashboard'] = $settlementsDashboardMock;

        return $settlementsDashboardMock;
    }

    protected function createPaymentAndRefundEntities(int $count = 5, $dt = null)
    {
        $prEntities = [];

        $r = range(1, $count);

        if ($dt === null)
        {
            $dt = Carbon::today(Timezone::IST)->subDays(20);
        }

        $createdAt = $dt->timestamp + 5;
        $capturedAt = $dt->timestamp + 10;

        foreach ($r as $i)
        {
            $payment = $this->fixtures->create('payment:captured',
                ['captured_at' => $capturedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt + 10]);

            $attrs = [
                'payment' => $payment,
                'amount' => '100000',
                'created_at' => $createdAt + 20,
                'updated_at' => $createdAt + 20];

            $refund = $this->fixtures->create('refund:from_payment', $attrs);

            array_push($prEntities, $payment);
            array_push($prEntities, $refund);
        }

        return $prEntities;
    }

    /**
     * Days used for testing creating a payment on settlement holiday
     **/
    protected function getDaysForSettlementHolidayTests()
    {
        $date = Carbon::today(Timezone::IST)->subDays(30);

        $holidayDate = Holidays::getNextSettlementHoliday($date)->addHours(7);

        $paymentCreatedAt = $holidayDate->copy();

        return [
            'payment_created_at'         => $paymentCreatedAt->subDays(4)->format('j M Y'),
            'payment_settlement_holiday' => $holidayDate->format('j M Y h:i:s'),
            'payment_settlement_on'      => Holidays::getNextWorkingDay($holidayDate->addDay())->format('j M Y h:i:s'),
        ];
    }

    /**
     * Days used for testing creating a payment on settlement non holiday
     **/
    protected function getDaysForSettlementNonHolidayTests()
    {
        $prevWorkingDay = Holidays::getPreviousWorkingDay((Carbon::today(Timezone::IST))->subDays(25));

        $paymentCreatedOn = $prevWorkingDay->copy();

        return [
            'payment_settlement_on' => $prevWorkingDay->addHours(7)->format('j M Y'),
            'payment_created_at'    => $paymentCreatedOn->subDays(8)->format('j M Y h:i:s'),
        ];
    }

    protected function runPaymentOnHoldUpdateCron()
    {
        $request = [
            'url' => '/payments/on_hold/update',
            'method' => 'POST',
            'content' => []
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function initiateSettlements($channel, $testTimeStamp = null, $useQueue = false, $merchantIds = [])
    {
        $content = ['all' => 1];

        if ($testTimeStamp !== null)
        {
            $content['testSettleTimeStamp'] = $testTimeStamp;
        }

        if ($useQueue === true)
        {
            $content['use_queue'] = '1';
        }

        if (empty($merchantIds) === false)
        {
            $content['merchant_ids'] = $merchantIds;

            $content['settled_at'] = 1534648600;

            $content['initiated_at'] = 1534658600;
        }

        $request = [
            'url' => '/settlements/initiate/'.$channel,
            'method' => 'POST',
            'content' => $content,
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function initiateDailySettlements()
    {
        $request = [
            'url'       => '/settlements/initiate_daily',
            'method'    => 'POST'
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function retryIntiateSettlements(array $setlIds)
    {
        $request = [
            'url'     => '/settlements/retry',
            'method'  => 'POST',
            'content' => [
                'settlement_ids' => $setlIds
            ]
        ];

        $this->ba->adminAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function createPaymentEntities(int $count = 5, $merchantId = null, $dt = null, $amount = 1000000)
    {
        if ($dt === null)
        {
            $dt = Carbon::today(Timezone::IST)->subDays(50);
        }

        $createdAt = $dt->timestamp + 5;
        $capturedAt = $dt->timestamp + 10;

        $attrs = [
            'captured_at' => $capturedAt,
            'method'      => 'card',
            'amount'      => $amount,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt + 10
        ];

        if ($merchantId !== null)
        {
            $attrs['merchant_id'] = $merchantId;
        }

        $payments = $this->fixtures->times($count)->create(
            'payment:captured',
            $attrs
        );

        return $payments;
    }

    protected function generateDailyReport()
    {
        $request = [
            'url' => '/merchants/report',
            'method' => 'post',
            'content' => [],
        ];

        $this->ba->cronAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function unlinkFile($file)
    {
         $this->assertTrue(
            unlink($file),
            'Could not delete file generated during testing. Filename: ' . $file);
    }

    protected function getTransfer(string $id)
    {

        $request = [
            'method'        => 'get',
            'url'           => '/transfers/' . $id,
        ];

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    protected function createSettlementEntry(array $content)
    {
        $request = [
            'url'     => '/settlements/create',
            'method'  => 'POST',
            'content' => $content
        ];

        $this->ba->settlementsAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }

    protected function getGlobalConfig(string $merchantId)
    {
        $request = [
            'url' => '/merchant/'. $merchantId . '/configs',
            'method' => 'GET'
        ];

        return $this->makeRequestAndGetContent($request);
    }

    protected function getSettlementDetails(string $id)
    {
        $request = [
            'url'    => '/settlements/'. $id . '/details',
            'method' => 'GET',
        ];

        $this->ba->proxyAuth();

        $content = $this->makeRequestAndGetContent($request);

        return $content;
    }
}
