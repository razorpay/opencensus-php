<?php

namespace RZP\Tests\Unit\Services;

use Mockery;
use \WpOrg\Requests\Response;
use RZP\Services\RazorXClient;
use RZP\Exception\TwirpException;
use RZP\Tests\Functional\TestCase;

class MerchantRiskAlertClientTest extends TestCase
{
    const REQUEST_CLOSURE_THROWS_EXCEPTION = 0;

    const REQUEST_CLOSURE_RETURNS_VALID_RESPONSE = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('services.merchant_risks_alerts.mock', true);
    }

    public function testNonRiskyMerchantWithRasSuccess()
    {
        $this->mockRazorx('notify_risky_merchant');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(1)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_RETURNS_VALID_RESPONSE)
            );

        $rasClient->notifyNonRiskyMerchant('1234567');
    }

    public function testNonRiskyMerchantWithRasFailureAndFallback()
    {
        $this->markTestSkipped('As we are switching off fallback on failure');

        $this->mockRazorx('notify_risky_merchant');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(2)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_THROWS_EXCEPTION),
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_RETURNS_VALID_RESPONSE)
            );

        $rasClient->notifyNonRiskyMerchant('1234567');
    }

    public function testNonRiskyMerchantFailureWithoutRas()
    {
        $this->mockRazorx('notify_risky_merchant_1');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(1)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_THROWS_EXCEPTION)
            );

        $rasClient->notifyNonRiskyMerchant('1234567');
    }

    public function testBlacklistCountryAlertsWithRasSuccess()
    {
        $this->mockRazorx('blacklist_country_cron');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(1)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_RETURNS_VALID_RESPONSE)
            );

        $rasClient->identifyBlacklistCountryAlerts([]);
    }

    public function testBlacklistCountryAlertsWithRasFailureAndFallback()
    {
        $this->markTestSkipped('As we are switching off fallback on failure');

        $this->mockRazorx('blacklist_country_cron');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(2)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_THROWS_EXCEPTION),
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_RETURNS_VALID_RESPONSE)
            );

        $rasClient->identifyBlacklistCountryAlerts([]);
    }

    public function testBlacklistCountryAlertsFailureWithoutRas()
    {
        $this->mockRazorx('blacklist_country_cron_1');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(1)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_THROWS_EXCEPTION)
            );

        $rasClient->identifyBlacklistCountryAlerts([]);
    }

    public function testCreateMerchantAlertWithRasSuccess()
    {
        $payload = [
            'merchant_id' => '1232134',
            'entity_type' => 'payment',
            'entity_id'   => '1233456',
            'category'    => 'customer_flag',
            'source'      => 'txn_confirm_mail',
            'event_type'  => 'report_fraud',
            'event_timestamp' => 1628610706,
            'data' => [
                'name' => 'Test Name',
                'comments' => 'fake website/company',
                'email_id' => 'abc@gmail.com',
                'contact_no' => '123133',
                'apps_exempt_risk_check' => '0',
            ]
        ];

        list($merchanId, $entityType, $entityId, $category, $source, $eventType, $eventTimestamp, $data) = [
            $payload['merchant_id'],
            $payload['entity_type'],
            $payload['entity_id'],
            $payload['category'],
            $payload['source'],
            $payload['event_type'],
            $payload['event_timestamp'],
            $payload['data'],
        ];

        $this->mockRazorx($category);

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(1)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_RETURNS_VALID_RESPONSE)
            );

        $rasClient->createMerchantAlert(
            $merchanId, $entityType, $entityId, $category, $source, $eventType, $eventTimestamp, $data);
    }

    public function testCreateMerchantAlertWithRasFailureAndFallback()
    {
        $this->markTestSkipped('As we are switching off fallback on failure');

        $payload = [
            'merchant_id' => '1232134',
            'entity_type' => 'payment',
            'entity_id'   => '1233456',
            'category'    => 'customer_flag',
            'source'      => 'txn_confirm_mail',
            'event_type'  => 'report_fraud',
            'event_timestamp' => 1628610706,
            'data' => [
                'name' => 'Test Name',
                'comments' => 'fake website/company',
                'email_id' => 'abc@gmail.com',
                'contact_no' => '123133',
                'apps_exempt_risk_check' => '0',
            ]
        ];

        list($merchanId, $entityType, $entityId, $category, $source, $eventType, $eventTimestamp, $data) = [
            $payload['merchant_id'],
            $payload['entity_type'],
            $payload['entity_id'],
            $payload['category'],
            $payload['source'],
            $payload['event_type'],
            $payload['event_timestamp'],
            $payload['data'],
        ];

        $this->mockRazorx($category);

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(2)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_THROWS_EXCEPTION),
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_RETURNS_VALID_RESPONSE)
            );

        $rasClient->createMerchantAlert(
            $merchanId, $entityType, $entityId, $category, $source, $eventType, $eventTimestamp, $data);
    }

    public function testCreateMerchantAlertFailureWithoutRas()
    {
        $payload = [
            'merchant_id' => '1232134',
            'entity_type' => 'payment',
            'entity_id'   => '1233456',
            'category'    => 'customer_flag',
            'source'      => 'txn_confirm_mail',
            'event_type'  => 'report_fraud',
            'event_timestamp' => 1628610706,
            'data' => [
                'name' => 'Test Name',
                'comments' => 'fake website/company',
                'email_id' => 'abc@gmail.com',
                'contact_no' => '123133',
                'apps_exempt_risk_check' => '0',
            ]
        ];

        list($merchanId, $entityType, $entityId, $category, $source, $eventType, $eventTimestamp, $data) = [
            $payload['merchant_id'],
            $payload['entity_type'],
            $payload['entity_id'],
            $payload['category'],
            $payload['source'],
            $payload['event_type'],
            $payload['event_timestamp'],
            $payload['data'],
        ];

        $this->mockRazorx($category . '_1');

        $this->app['rzp.mode'] = 'test';

        $rasClient = Mockery::mock('RZP\Services\Mock\MerchantRiskAlertClient', [])->makePartial();

        $rasClient->shouldReceive('request')
            ->times(1)
            ->andReturnUsing(
                $this->getReturnValueClosure(self::REQUEST_CLOSURE_THROWS_EXCEPTION)
            );

        $rasClient->createMerchantAlert(
            $merchanId, $entityType, $entityId, $category, $source, $eventType, $eventTimestamp, $data);
    }

    protected function mockRazorx(string $categoryToTest)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->will($this->returnCallback(function ($mid, $feature, $mode) use ($categoryToTest) {
                $expectedFeature = sprintf('ras_route_caller_api_category_%s', $categoryToTest);

                if ($feature === $expectedFeature)
                {
                    return 'on';
                }

                return 'control';
            }));
    }

    protected function getReturnValueClosure(int $closureOption)
    {
        $closure = NULL;

        switch ($closureOption)
        {
            case self::REQUEST_CLOSURE_THROWS_EXCEPTION:
                $closure = function ($path, $payload, $timeoutMs = null)
                {
                    throw new TwirpException(json_decode('{}', true));
                };

                break;

            default:
                $closure = function ($path, $payload, $timeoutMs = null)
                {
                    $res = new \WpOrg\Requests\Response;

                    $res->success = true;

                    $res->body = '{}';

                    return $res;
                };
        }

        return $closure;
    }
}
