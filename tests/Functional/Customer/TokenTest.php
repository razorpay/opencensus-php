<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;

use RZP\Exception;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;

class TokenTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['network_tokenization', 'allow_network_tokens']);
    }

    public function testCreateToken()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertEquals('12', $response['expiry_month']);

        $this->assertEquals('2023', $response['expiry_year']);

        $this->assertNotNull($response['service_providers']);

        $this->assertArrayNotHasKey('customer_id', $response);

        $response2 = $this->startTest();

        $this->assertEquals($response['id'], $response2['id']);

        $payment = $this->getDefaultPaymentArray();

        $payment['save'] = 1;

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->ba->privateAuth();

        $this->doS2SPrivateAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['token_id']);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $payment['token_id']];

        $this->ba->privateAuth();

        $fetchResponse = $this->startTest($fetchPayload);

        $this->assertEquals('card', $fetchResponse['method']);

        $this->assertEquals('12', $fetchResponse['expiry_month']);

        $this->assertEquals('2024', $fetchResponse['expiry_year']);
    }

     public function testCreateTokenWithCustmerId()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $createPayload['request']['content']['customer_id'] = 'cust_100000customer';

        $response = $this->startTest($createPayload);

        $this->assertNotNull($response['customer_id']);

        $this->assertEquals('card', $response['method']);

        $this->assertEquals('12', $response['expiry_month']);

        $this->assertEquals('2023', $response['expiry_year']);

        $this->assertNotNull($response['service_providers']);
    }


    public function testFetchToken()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $fetchResponse = $this->startTest($fetchPayload);

        $this->assertEquals('card', $fetchResponse['method']);

        $this->assertEquals('12', $fetchResponse['expiry_month']);

        $this->assertEquals('2023', $fetchResponse['expiry_year']);

        $this->assertNotNull($fetchResponse['service_providers']);
    }

    public function testFetchCryptogram()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchCryptogram'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $response = $this->startTest($fetchPayload);

        $this->assertNotNull($response['provider']['data']['token_number']);

        $this->assertNotNull($response['provider']['data']['cryptogram_value']);

        $this->assertEquals('12', $response['provider']['data']['expiry_month']);

        $this->assertEquals('2023', $response['provider']['data']['expiry_year']);
    }

    public function testTokenDelete()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $deletePayload = $this->testData['testTokenDelete'];

        $deletePayload['request']['content'] = ['id' => $response['id']];

        $this->startTest($deletePayload);

         $content = $this->makeRequestAndCatchException(function() use ($fetchPayload) {
            $this->makeRequestAndGetContent($fetchPayload['request']);
        },
            BadRequestValidationFailureException::class);
    }

    public function testCreateTokenAndTokenizeCard()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = true;
            $response['provider'] = $input['provider']['network'];
            $token = base64_encode($input['card']['number']);

            $response['success'] = true;
            $response['token']  = $token;
            $response['fingerprint'] = strrev($token);
            $response['token_iin'] = substr($input['card']['number'] ?? null, 0, 6);
            $response['last4'] = substr($input['card']['number'] ?? null, 0, 4);
            $response['expiry_month'] = $input['card']['expiry_month'];
            $response['expiry_year'] = $input['card']['expiry_year'];
            $response['length'] = strlen($input['card']['number']);

            if (strlen($response['expiry_year']) == 2)
            {
                $response['expiry_year'] = '20' . $response['expiry_year'];
            }

            $response['service_providers'] = [
                [
                    'type'  => 'network',
                    'name'  => $input['provider']['network'],
                    'data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'interoperable'          => true,
                    ],
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertEquals('12', $response['expiry_month']);

        $this->assertEquals('2023', $response['expiry_year']);

        $this->assertNotNull($response['service_providers']);

        $this->assertArrayNotHasKey('customer_id', $response);
    }

    public function testCreateTokenAndTokenizeCardValidationFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = true;
            $response['provider'] = $input['provider']['network'];
            $token = base64_encode($input['card']['number']);

            $response['success'] = true;
            $response['token']  = $token;
            $response['fingerprint'] = strrev($token);
            $response['token_iin'] = substr($input['card']['number'] ?? null, 0, 6);
            $response['last4'] = substr($input['card']['number'] ?? null, 0, 4);
            $response['expiry_month'] = $input['card']['expiry_month'];
            $response['expiry_year'] = $input['card']['expiry_year'];
            $response['length'] = strlen($input['card']['number']);

            if (strlen($response['expiry_year']) == 2)
            {
                $response['expiry_year'] = '20' . $response['expiry_year'];
            }

            $response['service_providers'] = [
                [
                    'type'  => 'network',
                    'name'  => $input['provider']['network'],
                    'data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'interoperable'          => true,
                    ],
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('BAD_REQUEST_ERROR', $response['error']['code']);
    }

    public function testCreateTokenAndTokenizeCardVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = false;

            $response['error'] = [
                [
                    'code'        => 'SERVER_ERROR',
                    'description' => 'The server encountered an error. The incident has been reported to admins.'
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('SERVER_ERROR', $response['error']['code']);
    }

    public function testFetchCryptogramLive()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ()
        {
            $dummyCardNumber = '4100000000000099';

            $response['success'] = true;
            $response['service_providers'] = [
                'type'  => 'network',
                'name'  => 'Visa',
                'data'  => [
                    'token_number' => $dummyCardNumber,
                    'cryptogram_value' => 12,
                    'expiry_month' => 12,
                    'expiry_year' => 2021,
                ],
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $response = $this->startTest($fetchPayload);

        $this->assertNotNull($response['service_providers']['data']['token_number']);

        $this->assertNotNull($response['service_providers']['data']['cryptogram_value']);

        $this->assertEquals('12', $response['service_providers']['data']['expiry_month']);

        $this->assertEquals('2021', $response['service_providers']['data']['expiry_year']);

        $this->assertEquals('4100000000000099', $response['service_providers']['data']['token_number']);
    }

    public function testFetchCryptogramLiveInvalidToken()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ()
        {
            $dummyCardNumber = '4100000000000099';

            $response['success'] = true;
            $response['service_providers'] = [
                'type'  => 'network',
                'name'  => 'Visa',
                'data'  => [
                    'token_number' => $dummyCardNumber,
                    'cryptogram_value' => 12,
                    'expiry_month' => 12,
                    'expiry_year' => 2021,
                ],
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLiveInvalidTokenId'];

        $this->startTest($fetchPayload);
    }

    public function testFetchCryptogramLiveVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = false;

            $response['error'] = [
                [
                    'code'        => 'SERVER_ERROR',
                    'description' => 'The server encountered an error. The incident has been reported to admins.'
                ]
            ];

            return $response;
        };

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->makeRequestAndGetContent($createPayload['request']);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $this->startTest($fetchPayload);
    }

    public function testFetchTokenLive()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = true;
            $token = base64_encode('I2lCam2io3vfu1');

            $response['token'] = 'I2lCam2io3vfu1';
            $response['fingerprint'] = strrev($token);
            $response['status'] = 'activated';

            $response['service_providers'] = [
                [
                    'type'  => 'network',
                    'name'  => 'visa',
                    'data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'interoperable'          => true,
                    ],
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchTokenLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $fetchResponse = $this->startTest($fetchPayload);

        $this->assertEquals('card', $fetchResponse['method']);

        $this->assertEquals('12', $fetchResponse['expiry_month']);

        $this->assertEquals('2023', $fetchResponse['expiry_year']);

        $this->assertNotNull($fetchResponse['service_providers']);
    }

    public function testFetchTokenLiveInvalidToken()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = true;
            $token = base64_encode('I2lCam2io3vfu1');

            $response['token'] = 'I2lCam2io3vfu1';
            $response['fingerprint'] = strrev($token);
            $response['status'] = 'activated';

            $response['service_providers'] = [
                [
                    'type'  => 'network',
                    'name'  => 'visa',
                    'data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'interoperable'          => true,
                    ],
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchTokenLive'];

        $this->startTest($fetchPayload);
    }

    public function testFetchTokenLiveVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = false;

            $response['error'] = [
                [
                    'code'        => 'SERVER_ERROR',
                    'description' => 'The server encountered an error. The incident has been reported to admins.'
                ]
            ];

            return $response;
        };

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->makeRequestAndGetContent($createPayload['request']);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchTokenLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $this->startTest($fetchPayload);
    }

    public function testTokenDeleteLive()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = true;

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $deletePayload = $this->testData['testTokenDelete'];

        $deletePayload['request']['content'] = ['id' => $response['id']];

        $this->startTest($deletePayload);
    }

    public function testTokenDeleteLiveVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = false;

            $response['error'] = [
                'code' => 'SERVER_ERROR',
                'description' => 'The server encountered an error. The incident has been reported to admins.'
            ];

            return $response;
        };

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->makeRequestAndGetContent($createPayload['request']);

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $deletePayload = $this->testData['testTokenDelete'];

        $deletePayload['request']['content'] = ['id' => $response['id']];

        $this->startTest($deletePayload);
    }

    public function testTokenDeleteLiveInvalidToken()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            $response['success'] = true;

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $deletePayload = $this->testData['testTokenDelete'];

        $this->startTest($deletePayload);
    }
}
