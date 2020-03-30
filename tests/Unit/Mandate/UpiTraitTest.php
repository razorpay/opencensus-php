<?php

namespace RZP\Tests\Unit\Mandate;

use Carbon\Carbon;
use RZP\Models\UpiMandate;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Models\Payment\Processor\UpiTrait;

class UpiTraitTest extends TestCase
{
    use UpiTrait;

    public function functionIsOtmPayment()
    {
        $cases = [
            'not_an_otm_payment_1' => [
                'params'     => [
                    // first parameter
                    []
                ],
                'expected'  => false,
            ],

            'not_an_otm_payment_2' => [
                'params'     => [
                    // first parameter
                    ['upi' => []]
                ],
                'expected'  => false,
            ],

            'not_an_otm_payment_3' => [
                'params'     => [
                    // first parameter
                    ['upi' => [
                        'type' => 'collect',
                    ]],
                ],
                'expected'  => false,
            ],

            'the_otm_payment_1' => [
                'params'     => [
                    // first parameter
                    ['upi' => [
                        'type' => 'otm'
                    ]]
                ],
                'expected'  => true,
            ],
        ];

        return $cases;
    }

    /**
     * @dataProvider functionIsOtmPayment
     */
    public function testFunctionIsOtmPayment($params, $expected, $throwable = null)
    {
        $this->goWithTheFlow($params, $throwable,
            function($input) use ($expected)
            {
                $this->assertSame($expected, $this->isOtmPayment($input));
            });

    }

    public function functionPreProcessForUpiOtmIfApplicable()
    {
        $param  = [];
        $delta   = [];
        $throwable = null;

        $cases = [
            'not_an_otm_flow_method' => [
                [$param],
                array_merge($param, $delta),
                $throwable
            ],
        ];

        return $cases;
    }

    /**
     * @dataProvider functionPreProcessForUpiOtmIfApplicable
     */
    public function testFunctionPreProcessForUpiOtmIfApplicable($params, $expected, $throwable = null)
    {
        $this->goWithTheFlow($params, $throwable,
            function($input) use ($expected)
            {
                $this->preProcessForUpiOtmIfApplicable($input);

                $this->assertSame($expected, $input);
            });
    }

    /**
     * @param array $params
     * @param array $expected
     * @param null|array $throwable
     * @param $closure
     */
    private function goWithTheFlow(array $params, $throwable, $closure)
    {
        // throwable is not expected if there is no throwable
        $throwableExpected = (is_null($throwable) === false);
        $throwableThrown = false;
        $response = null;

        try
        {
            $response = $closure(...$params);
        }
        catch (\Throwable $t)
        {
            $throwableThrown = true;

            $this->assertExceptionClass($t, $throwable['class']);

            $message = $throwable['message'] ?? null;
            if ($message)
            {
                $this->assertSame($message, $t->getMessage());
            }
        }

        $this->assertSame($throwableExpected, $throwableThrown, 'Exception not thrown');

        return $response;
    }
}
