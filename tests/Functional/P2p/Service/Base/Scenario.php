<?php

namespace RZP\Tests\P2p\Service\Base;

use RZP\Gateway\P2p\Upi\Mock;
use PHPUnit\Framework\Assert;
use Illuminate\Foundation\Testing\TestResponse;

class Scenario extends Mock\Scenario
{
    public function getScenarioCallback(): callable
    {
        $expected = $this->getScenarionCallbackMap();

        $wrapper = function(TestResponse $response) use ($expected)
        {
            Assert::assertArraySubset($expected, $response->json(), true, $this->getId());
        };

        return $wrapper;
    }

    public function getScenarionCallbackMap()
    {
        $map = [
            self::DE101 => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'SMS sending failed.',
                ],
            ],
            self::DE102 => [
            ],
            self::DE201 => [
                'error' => [
                    'code'          => 'BAD_REQUEST_ERROR',
                    'description'   => 'Device is not registered for the given handle',
                ],
            ],
            self::BA101 => [

            ],
            self::BA202 => [

            ],
            self::BA203 => [

            ],
            self::BA204 => [

            ],
            self::BA205 => [

            ],
            self::BA301 => [

            ],
            self::BA302 => [

            ],
            self::BA303 => [

            ],
            self::BA304 => [

            ],
            self::BA305 => [

            ],
            self::BA401 => [

            ],
            self::BA402 => [

            ],
            self::BA403 => [

            ],
            self::BA404 => [

            ],
            self::VA101 => [

            ],
            self::VA201 => [

            ],
            self::VA202 => [

            ],
            self::VA203 => [

            ],
            self::VA301 => [

            ],
            self::VA302 => [

            ],
            self::VA303 => [

            ],
            self::VA304 => [

            ],
            self::VA305 => [

            ],
            self::VA401 => [

            ],
            self::VA501 => [

            ],
            self::VA601 => [

            ],
            self::VA701 => [

            ],
            self::VA702 => [

            ],
            self::VA703 => [

            ],
            self::VA704 => [

            ],
            self::VA801 => [

            ],
            self::VA901 => [

            ],
            self::VA902 => [

            ],
            self::TR101 => [

            ],
            self::TR201 => [

            ],
            self::TR301 => [

            ],
            self::TR302 => [

            ],
            self::TR401 => [

            ],
            self::TR501 => [

            ],
            self::TR601 => [

            ],
            self::TR602 => [

            ],
        ];

        return $map[$this->id];
    }
}
