<?php

namespace Unit\Models\Merchant\Asv;

use RZP\Exception\LogicException;
use \RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Acs\Service;
use RZP\Models\Merchant\Acs\AsvClient\BaseClient;
use RZP\Models\Merchant\Acs\EventProcessor\LoggingEventProcessor;

class ServiceTest extends TestCase
{

    public function testHandleAccountUpdateEventSuccessfully()
    {
        $accountService = new Service();
        $accountService->eventProcessorFactory->SetEventProcessorClassList([
            LoggingEventProcessor::class
        ]);

        $input = [
            "deliveryAttempt" => 1,
            "message" => [
                "data" => "eyJhY2NvdW50X2lkIjoiS0ppVE1XQ3hRYmRqM0QiLCJtZXRhZGF0YSI6eyJtb2RpZmllZF9lbnRpdHkiOiJhY2NvdW50X2RvY3VtZW50IiwibW9kaWZpZWRfY2hpbGRfZW50aXR5IjoiYWNjb3VudF9kb2N1bWVudCIsIm1vZGlmaWVkX3RpbWVzdGFtcCI6MTY2MzU4MTY1N319",
                "messageId" => "cck3rno41gnglj2u4kig",
                "publishTime" => "2022-09-19T10:01:03Z"

            ],
            "subscription" => "projects/prod-api/subscriptions/prod-api-asv-side-effect-events-consumer"
        ];

        $accountService->handleAccountUpdateEvent($input);
    }

    public function testHandleAccountUpdateEventWithException()
    {
        $accountService = new Service();
        $accountService->eventProcessorFactory->SetEventProcessorClassList([
            BaseClient::class
        ]);

        $input = [
            "deliveryAttempt" => 1,
            "message" => [
                "data" => "eyJhY2NvdW50X2lkIjoiS0ppVE1XQ3hRYmRqM0QiLCJtZXRhZGF0YSI6eyJtb2RpZmllZF9lbnRpdHkiOiJhY2NvdW50X2RvY3VtZW50IiwibW9kaWZpZWRfY2hpbGRfZW50aXR5IjoiYWNjb3VudF9kb2N1bWVudCIsIm1vZGlmaWVkX3RpbWVzdGFtcCI6MTY2MzU4MTY1N319",
                "messageId" => "cck3rno41gnglj2u4kig",
                "publishTime" => "2022-09-19T10:01:03Z"

            ],
            "subscription" => "projects/prod-api/subscriptions/prod-api-asv-side-effect-events-consumer"
        ];

        $this->expectException(LogicException::class);

        $accountService->handleAccountUpdateEvent($input);
    }
}
