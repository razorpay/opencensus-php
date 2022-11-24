<?php

namespace RZP\Tests\Traits;

use Mockery;
use \WpOrg\Requests\Response;
use RZP\Models\Base\UniqueIdEntity;
use PHPUnit\Framework\ExpectationFailedException;
use RZP\Models\Order\OrderMeta\Order1cc\Fields as Fields;

trait TestsWebhookEvents
{
    use TestsStorkServiceRequests;

    /**
     * Sets expectation for new webhook event.
     *
     * Usage:
     * - Expecting webhook event(s):
     *     $this->expectWebhookEvent('payment.authorized');
     *     $this->expectWebhookEvent('payment.captured');
     * - Expecting webhook event and matching payload:
     *     $this->expectWebhookEvent(
     *         'payment.authorized',
     *         function(array $event)
     *         {
     *             $this->assertSame("pay_10000000000000", $event['payload']['payment']['id']);
     *         }
     *     );
     * - Also see dontExpectWebhookEvent, dontExpectAnyWebhookEvent and expectWebhookEventWithContents.
     *
     * @param  string   $name
     * @param  callable $matcher
     * @return void
     */
    protected function expectWebhookEvent(string $name, callable $matcher = null)
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $argMatcher = function(array $arg) use ($name, $matcher)
        {
            // $arg['event'] is stork's event struct.
            if ($name !== $arg['event']['name'])
            {
                return false;
            }

            if ((isset($arg['event']['id']) === false) ||
                (UniqueIdEntity::verifyUniqueId($arg['event']['id'], false) === false))
            {
                return false;
            }

            // $arg['event']['payload'] is serialized api's event entity OR some unstructured content from mozart(transaction etc).
            $event = json_decode($arg['event']['payload'], true);
            if (json_last_error() !== JSON_ERROR_NONE)
            {
                $event = $arg['event']['payload'];
            }

            // The matcher should return boolean and not throw exceptions.
            // But it has been easier to call assert functions in tests and hence below stuff.
            try
            {
                return (($matcher === null) or ($matcher($event) ?? true));
            }
            catch (ExpectationFailedException $e)
            {
                return false;
            }
        };

        $this->storkMock
            ->shouldReceive('request')
            ->zeroOrMoreTimes()
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent', Mockery::on($argMatcher), 350)
            ->andReturn(new \WpOrg\Requests\Response);
    }

    /**
     * @param  string       $name
     * @param  string|array $expectedEvent
     * @return void
     */
    protected function expectWebhookEventWithContents(string $name, $expectedEvent)
    {
        // If passed string value assumes is a key of test data.
        $expectedEvent = is_string($expectedEvent) ? $this->testData[$expectedEvent] : $expectedEvent;
        $matcher = function (array $event) use ($expectedEvent)
        {
            $this->assertArraySelectiveEquals($expectedEvent, $event);
        };

        return $this->expectWebhookEvent($name, $matcher);
    }

    protected function getCustomerDetails(): array
    {
        return [
            "email"  => "a@b.com",
            "phone"  => "+919918899029",
            "shipping_address" => [
                "name" => "demo name",
                "type" => "shipping_address",
                "line1"=> "xyz 1",
                "line2"=> "xyz 2",
                "zipcode"=> "560001",
                "city"=> "Bengaluru",
                "state"=> "Karnataka",
                "country"=> "in",
                "contact"=> "+919918899029"
            ],
            "billing_address" => [
                "name" => "demo name",
                "line1"=> "xyz 1",
                "line2"=> "xyz 2",
                "zipcode"=> "560001",
                "city"=> "Bengaluru",
                "state"=> "Karnataka",
                "country"=> "in",
                "contact"=> "+919918899029"
            ],
        ];
    }

    protected function getNotes(): array
    {
        return [
            "email"  => "a@b.com",
            "phone"  => "+919918899029",
            "name"    =>  "demo name",
            "address" => "xyz 1xyz 2",
            "city" => "Bengaluru",
            "state" => "Karnataka",
            "pincode"=> "560001"
        ];
    }

    /**
     * Do not use this function. It makes tests brittle.
     * @return void
     */
    protected function dontExpectAnyWebhookEvent()
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $this->storkMock
            ->shouldNotReceive('request')
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent', Mockery::any(), 350);
    }

    /**
     * @param  string $name
     * @return void
     */
    protected function dontExpectWebhookEvent(string $name)
    {
        $this->storkMock = $this->storkMock ?: $this->createStorkMock();

        $argMatcher = function(array $arg) use ($name)
        {
            return $name === $arg['event']['name'];
        };

        $this->storkMock
            ->shouldNotReceive('request')
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/ProcessEvent', Mockery::on($argMatcher), 350);
    }
}
