<?php

namespace RZP\Tests\Unit\Mandate;

use Carbon\Carbon;
use RZP\Models\UpiMandate;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Models\Payment\Processor\UpiTrait;
use RZP\Models\Payment\UpiMetadata\Entity;

class UpiTraitTest extends TestCase
{
    use UpiTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trace = $this->app['trace'];
    }

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

    public function functionSetUpiMetadataIfApplicable()
    {
        // ['test_name' => [ INPUT_WITH_UPI_BLOCK , UPI_METADATA_EXPECTED, THROWABLE_PARAMS ] ]
        $cases = [];

        $cases['extra_upi_param'] = [
            [[
                'upi' => [
                    '0' => 5,
                    'flow' => 'collect',
                    'type' => 'default',
                    'vpa'  => 'aa@asd',
                    'app'=> 'some.app.com',
                    'mode'=> 'upi_qr'
                ],
            ]],
            [
                'flow' => 'collect',
                'type' => 'default',
                'vpa'  => 'aa@asd',
                'app'=> 'some.app.com',
                'mode'=> 'upi_qr'
            ],
            null,
        ];

        $cases['extra_upi_param_mixed'] = [
            [[
                'upi' => [
                    '0'    => 5,
                    'flow' => 'collect',
                    'type' => 'default',
                    'vpa'  => 'aa@asd',
                    '1'    => 3,
                    'app'=> 'some.app.com',
                    'mode'=> 'upi_qr'
                ],
            ]],
            [
                'flow' => 'collect',
                'type' => 'default',
                'vpa'  => 'aa@asd',
                'app'=> 'some.app.com',
                'mode'=> 'upi_qr'
            ],
            null,
        ];

        $cases['invalid_values'] = [
            [[
                'upi' => [
                    'flow' => 4,
                    'type' => 'otm'
                ],
            ]],
            null,
            null,
        ];

        return $cases;
    }

    /**
     * @dataProvider functionSetUpiMetadataIfApplicable
     */
    public function testFunctionSetUpiMetadataIfApplicable($input, $expected, $throwable)
    {
        $payment = $this->fixtures->create('payment', ['method' => 'upi']);

        $this->goWithTheFlow($input, $throwable ,function ($input) use ($payment, $expected)
        {
            $this->setUpiMetadataIfApplicable($payment, $input);

            $upiMetadata = $payment->getMetadata(Entity::UPI_METADATA);

            if ($expected === null)
            {
                $this->assertNull($upiMetadata);
            }
            else
            {
                $this->assertArraySubset($expected, $upiMetadata->toArray());
            }
        });
    }

    public function functionPreProcessForUpiIfApplicable()
    {
        $cases['only_upi_param'] = [
            [
                // Expected future flow for creating payment with mode and app
                [
                    'method' => 'upi',
                    'upi' => [
                        'vpa'   => 'abc@xyz',
                        'flow'  => 'intent',
                        'mode'  => 'upi_qr',
                        'app'   => 'some.app.com',
                    ],
                ],
            ],
            [
                'method' => 'upi',
                'upi' => [
                    'vpa'       => 'abc@xyz',
                    'flow'      => 'intent',
                    'mode'      => 'upi_qr',
                    'app'       => 'some.app.com',
                    'type'      => 'default',
                ],
                'vpa' => "abc@xyz",
                '_' => [
                    'flow' => 'intent',
                    'upiqr' => true,
                ],
            ],
        ];

        $cases['upi_param_with_different_mode'] = [
            [
                [
                    'method' => 'upi',
                    'upi' => [
                        'vpa'   => 'abc@xyz',
                        'flow'  => 'intent',
                        'mode'  => 'initial',
                    ],
                ],
            ],
            [
                'method' => 'upi',
                'upi' => [
                    'vpa'       => 'abc@xyz',
                    'flow'      => 'intent',
                    'mode'      => 'initial',
                    'type'      => 'default'
                ],
                'vpa' => "abc@xyz",
                '_' => [
                    'flow' => 'intent'
                ],
            ],
        ];

        $cases['only_underscore_param'] = [
            [
                [
                    'method' => 'upi',
                    '_' => [
                        'flow' => 'intent',
                        'app' => 'some.app.com',
                        'upiqr' => true,
                    ],
                    'vpa' => "abc@xyz",
                ],
            ],
            [
                'method' => 'upi',
                '_' => [
                    'flow' => 'intent',
                    'app' => 'some.app.com',
                    'upiqr' => true,
                ],
                'vpa' => "abc@xyz",
                'upi' => [
                    'vpa'   => "abc@xyz",
                    'flow'  => 'intent',
                    'type'  => 'default',
                    'app'   => 'some.app.com',
                    'mode'  => 'upi_qr',
                ],
            ],
        ];

        $cases['mixed_param'] = [
            [
                [
                    'method' => 'upi',
                    '_' => [
                        'app'   => 'some.app.com',
                        'upiqr' => true
                    ],
                    'upi_provider' => 'some_provider',
                ],
            ],
            [
                'method' => 'upi',
                '_' => [
                    'app'   => 'some.app.com',
                    'upiqr' => true
                ],
                'upi_provider' => 'some_provider',
                'upi' => [
                    'flow'      => 'collect',
                    'type'      => 'default',
                    'provider'  => 'some_provider',
                    'app'       => 'some.app.com',
                    'mode'      => 'upi_qr',
                ],
            ],
        ];

        $cases['vpa_with_space'] = [
            [
                [
                    'method' => 'upi',
                    'upi' => [
                        'vpa'   => ' abc@xyz  ',
                        'flow'  => 'intent',
                    ],
                    'vpa' => ' abc@xyz  ',
                ],
            ],
            [
                'method' => 'upi',
                'upi' => [
                    'vpa'       => 'abc@xyz',
                    'flow'      => 'intent',
                    'type'      => 'default',
                ],
                'vpa' => "abc@xyz",
                '_' => [
                    'flow' => 'intent',
                ],
            ],
        ];

        $cases['upi_intent_mode'] = [
            [
                // Expected future flow for creating payment with mode and app
                [
                    'method' => 'upi',
                    'upi' => [
                        'vpa'   => 'abc@xyz',
                        'flow'  => 'intent',
                        'mode'  => 'in_app',
                        'app'   => 'some.app.com',
                    ],
                ],
            ],
            [
                'method' => 'upi',
                'upi' => [
                    'vpa'       => 'abc@xyz',
                    'flow'      => 'intent',
                    'mode'      => 'in_app',
                    'app'       => 'some.app.com',
                    'type'      => 'default',
                ],
                'vpa' => "abc@xyz",
                '_' => [
                    'flow' => 'intent',
                    'in_app' => true,
                ],
            ],
        ];

        return $cases;
    }

    /**
     * @dataProvider functionPreProcessForUpiIfApplicable
     */
    public function testFunctionPreProcessForUpiIfApplicable($params, $expected, $throwable = null)
    {
        $this->goWithTheFlow(
            $params,
            $throwable,
            function ($input) use ($expected)
            {
                $this->preProcessForUpiIfApplicable($input);

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
