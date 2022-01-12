<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use Requests_Response;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Card\Constants;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\TestsWebhookEvents;

class TokenTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['network_tokenization', 'allow_network_tokens']);
    }

    public function testCreateTokenWithoutEncryptedAndPlainTextCardNumber()
    {
        $this->ba->privateAuth();

        try
        {
            $this->startTest();
        }
        catch (\Exception $e)
        {
            $this->assertEquals("The number field is required.",$e->getMessage());
        }
    }

    public function testCreateTokenEncryptedWithInvalidData()
    {
        $this->ba->privateAuth();

        try
        {
            $this->startTest();
        }
        catch (\Exception $e)
        {
            $this->assertEquals("The number must be a number.", $e->getMessage());
        }
    }

    public function testCreateTokenEncrypted()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals(null, $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals(null, $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertEquals(null, $response['expired_at']);

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

        $this->assertEquals('12', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2024', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $MCPayload = $this->testData['testCreateToken'];

        $MCPayload['request']['content']['card']['number'] = '5122600005005789';

        $MCResponse = $this->startTest($MCPayload);

        $this->assertEquals('card', $MCResponse['method']);

        $this->assertEquals('MasterCard', $MCResponse['service_provider_tokens'][0]['provider_name']);

        $this->assertEquals(null, $MCResponse['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals(null, $MCResponse['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertEquals(null, $MCResponse['service_provider_tokens'][0]['provider_data']['token_iin']);

        $this->assertEquals('created', $MCResponse['status']);

        $this->assertEquals(null, $MCResponse['expired_at']);
    }

    public function testCreateToken()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals('12', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertEquals('1704047399', $response['expired_at']);

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

        $this->assertEquals('12', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2024', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $MCPayload = $this->testData['testCreateToken'];

        $MCPayload['request']['content']['card']['number'] = '5122600005005789';

        $MCResponse = $this->startTest($MCPayload);

        $this->assertEquals('card', $MCResponse['method']);

        $this->assertEquals('MasterCard', $MCResponse['service_provider_tokens'][0]['provider_name']);

        $this->assertEquals(null, $MCResponse['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals(null, $MCResponse['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertEquals(null, $MCResponse['service_provider_tokens'][0]['provider_data']['token_iin']);

        $this->assertEquals('created', $MCResponse['status']);

        $this->assertEquals(null, $MCResponse['expired_at']);
    }

    public function testCreateTokenWithCustmerId()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $createPayload['request']['content']['customer_id'] = 'cust_100000customer';

        $response = $this->startTest($createPayload);

        $this->assertNotNull($response['customer_id']);

        $this->assertEquals('card', $response['method']);

        $this->assertEquals(true, $response['compliant_with_tokenisation_guidelines']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals('12', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);
    }

    public function testGetAllCustomerTokensWithNetworkTokenizedFlag()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            if ($route === Constants::TOKENS_UPDATE)
            {
                return ['success' => true];
            }

            $response['success'] = true;

            $token = base64_encode($input['card']['number']);
            $response['id'] = 'token_Ankit';
            $response['token']  = $token;
            $response['length'] = '16';

            $response['fingerprint'] = strrev($token);
            $token_iin = substr($input['card']['number'] ?? null, 0, 6);

            $expiry_year = $input['card']['expiry_year'];
            if (strlen($expiry_year) == 2)
            {
                $expiry_year = '20' . $expiry_year;
            }

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => 'visa',
                    'status'                 => 'activated',
                    'interoperable'          => true,
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_expiry_month'     => $input['card']['expiry_month'],
                        'token_expiry_year'      => $expiry_year,
                        'token_iin'              => $token_iin,
                        'token_number'           => $input['card']['number'],
                        'cryptogram_value'       => '',
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
        $fetchTokenResponse = $this->doFetchTokenFromCustomerID($response['id']);
        $this->assertTrue($fetchTokenResponse['compliant_with_tokenisation_guidelines']);
    }

    public function testMigrateTokenToTokenizedCard()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $payment = $this->getDefaultPaymentArray();

        $payment['save'] = 1;

        $payment['customer_id']='cust_100000customer';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['token_id']);

        $token = $this->getLastEntity('token', true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('card_' . $token['card_id'], $card['id']);

        $this->assertEquals($card['vault'], 'visa');
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

        $this->assertNotNull($fetchResponse['service_provider_tokens']);

        $this->assertEquals('12', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_year']);
        $this->assertEquals(true, $fetchResponse['compliant_with_tokenisation_guidelines']);
    }

    public function testFetchCryptogram()
    {
        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchCryptogram'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $response = $this->startTest($fetchPayload);

        $this->assertNotNull($response['service_provider_tokens'][0]['provider_data']['token_number']);

        $this->assertNotNull($response['service_provider_tokens'][0]['provider_data']['cryptogram_value']);

        $this->assertEquals('12', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);
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
            if ($route === Constants::TOKENS_UPDATE)
            {
                return ['success' => true];
            }

            $response['success'] = true;

            $token = base64_encode($input['card']['number']);
            $response['token']  = $token;
            $response['length'] = '16';

            $response['fingerprint'] = strrev($token);
            $token_iin = substr($input['card']['number'] ?? null, 0, 6);

            $expiry_year = $input['card']['expiry_year'];
            if (strlen($expiry_year) == 2)
            {
                $expiry_year = '20' . $expiry_year;
            }

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => 'visa',
                    'status'                 => 'activated',
                    'interoperable'          => true,
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_expiry_month'     => $input['card']['expiry_month'],
                        'token_expiry_year'      => $expiry_year,
                        'token_iin'              => $token_iin,
                        'token_number'           => $input['card']['number'],
                        'cryptogram_value'       => '',
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

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals('12', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertArrayNotHasKey('customer_id', $response);

        $this->assertArrayHasKey('notes', $response);
    }

    public function testCreateTokenAndTokenizeCardNotAllowed()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new Requests_Response();

            $response->body = '{
                "success": false,
                "error": {
                  "internal_error_code": "BAD_REQUEST_CARD_NOT_ALLOWED_BY_BANK",
                  "gateway_error_code": "BAD_REQUEST_ERROR",
                  "gateway_error_description": "The card is not allowed for tokenization due to some reasons at issuer bank.",
                  "description": "The card is not allowed for tokenization due to some reasons at issuer bank."
                }
              }';
            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
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

        $this->app['rzp.mode']= 'test';
        try
        {
            $response = $this->startTest();
        }
        catch (Exception\BaseException $e)
        {
            $this->assertEquals($e->getGatewayErrorDesc(), "The card is not allowed for tokenization due to some reasons at issuer bank.");

            $this->assertEquals($e->getError()->getInternalErrorCode(), "BAD_REQUEST_CARD_NOT_ALLOWED_BY_BANK");

            $this->assertEquals($e->getError()->getPublicErrorCode(), 'BAD_REQUEST_ERROR');

            $this->assertEquals($e->getError()->getReason(), "card_not_allowed");

            $this->assertEquals($e->getError()->getSource(), "Visa");

            $this->assertEquals($e->getError()->getStep(), "token_creation");
        }
    }

    public function testCreateTokenAndTokenizeCardGatewayError()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new Requests_Response();

            $response->body = '{
                "success": false,
                "error": {
                  "internal_error_code": "BAD_REQUEST_INVALID_CARD_NUMBER",
                  "gateway_error_code": "BAD_REQUEST_ERROR",
                  "gateway_error_description": "The card number is invalid. Please check the card details.",
                  "description": "The card number is invalid. Please check the card details."
                }
              }';

            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
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
        try
        {
            $response = $this->startTest();
        }
        catch (Exception\BaseException $e)
        {
            $this->assertEquals($e->getGatewayErrorDesc(), "The card number is invalid. Please check the card details.");

            $this->assertEquals($e->getError()->getPublicErrorCode(), 'BAD_REQUEST_ERROR');

            $this->assertEquals($e->getError()->getInternalErrorCode(), "BAD_REQUEST_INVALID_CARD_NUMBER");

            $this->assertEquals($e->getError()->getReason(), "NA");

            $this->assertEquals($e->getError()->getSource(), "Visa");

            $this->assertEquals($e->getError()->getStep(), "NA");
        }
    }


    public function testCreateTokenAndTokenizeCardMC()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            if ($route === Constants::TOKENS_UPDATE)
            {
                return ['success' => true];
            }

            $response['success'] = true;
            $token = base64_encode($input['card']['number']);
            $response['token']  = $token;
            $response['fingerprint'] = strrev($token);

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => 'mastercard',
                    'status'         => 'created',
                    'interoperable'  => true,
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_expiry_month' => 0,
                        'token_expiry_year' => 0,
                        'token_iin' => "",
                        'token_number' => "",
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

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertArrayNotHasKey('customer_id', $response);
    }

    public function testCreateTokenAndTokenizeCardRuPay()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            if ($route === 'tokens/update')
            {
                return ['success' => true];
            }

            $response['success'] = true;

            $token = base64_encode($input['card']['number']);
            $response['token']  = $token;
            $response['length'] = '16';

            $response['fingerprint'] = strrev($token);
            $token_iin = substr($input['card']['number'] ?? null, 0, 6);

            $expiry_year = $input['card']['expiry_year'];
            if (strlen($expiry_year) == 2)
            {
                $expiry_year = '20' . $expiry_year;
            }

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => 'rupay',
                    'status'                 => 'activated',
                    'interoperable'          => true,
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_expiry_month'     => $input['card']['expiry_month'],
                        'token_expiry_year'      => $expiry_year,
                        'token_iin'              => $token_iin,
                        'token_number'           => $input['card']['number'],
                        'cryptogram_value'       => '',
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
            'iin'     => '607148',
            'country' => 'IN',
            'issuer'  => 'UTIB',
            'network' => 'RuPay',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
                'otp'  => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertArrayNotHasKey('customer_id', $response);
    }

    public function testCreateTokenAndTokenizeCardValidationFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input)
        {
            if ($route === Constants::TOKENS_UPDATE)
            {
                return ['success' => true];
            }

            $response['success'] = true;
            $response['provider'] = $input['provider']['network'];

            $token = base64_encode($input['card']['number']);
            $response['token']  = $token;

            $response['fingerprint'] = strrev($token);
            $response['last4'] = substr($input['card']['number'] ?? null, 0, 4);

            $token_iin = substr($input['card']['number'] ?? null, 0, 6);

            $expiry_year = $input['card']['expiry_year'];
            if (strlen($expiry_year) == 2)
            {
                $expiry_year = '20' . $expiry_year;
            }

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => $input['provider']['network'],
                    'status'         => 'created',
                    'interoperable'  => true,
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_expiry_month'     => $input['card']['expiry_month'],
                        'token_expiry_year'      => $expiry_year,
                        'token_iin'              => $token_iin,
                        'token_number'           => $input['card']['number'],
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
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new Requests_Response();

            $response->body = '{
                "success": false,
                "error": {
                  "internal_error_code": "SERVER_ERROR_ASSERTION_ERROR",
                  "gateway_error_code": "SERVER_ERROR",
                  "gateway_error_description": "We are facing some trouble completing your request at the moment. Please try again shortly.",
                  "description": "We are facing some trouble completing your request at the moment. Please try again shortly."
                }
              }';

            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
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

        try {
            $response = $this->startTest();
        }
        catch (Exception\LogicException $e)
        {
            $this->assertEquals($e->getError()->getInternalErrorCode(), "SERVER_ERROR_ASSERTION_ERROR");

            $this->assertEquals($e->getError()->getPublicErrorCode(), 'SERVER_ERROR');

            $this->assertEquals($e->getError()->getReason(), "server_error");

            $this->assertEquals($e->getError()->getSource(), "Visa");

            $this->assertEquals($e->getError()->getStep(), "payment_initiation");
        }
    }

    public function testFetchCryptogramLive()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ()
        {
            $dummyCardNumber = '4100000000000099';

            $responsebody['success'] = true;
            $responsebody['service_provider_tokens'] = [
                [
                    'id' => 'spt_IW48g8IeV3uUHA',
                    'entity' => '',
                    'interoperable' => '',
                    'provider_type'  => 'network',
                    'provider_name'  => 'Visa',
                    'provider_data'  => [
                        'token_reference_number' => '',
                        'card_reference_number' => '',
                        'token_iin' => '',
                        'token_number' => $dummyCardNumber,
                        'cryptogram_value' => 12,
                        'token_expiry_month' => 12,
                        'token_expiry_year' => 2021,
                    ],
                    'status' => '',
                ],
            ];
            $response = new Requests_Response();

            $response->body = json_encode($responsebody, JSON_FORCE_OBJECT);

            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLive'];

        $fetchPayload['request']['content'] = ['id' => 'spt_IW48g8IeV3uUHA'];

        $response = $this->startTest($fetchPayload);

        $this->assertNotNull($response['token_number']);

        $this->assertNotNull($response['cryptogram_value']);

        $this->assertEquals('12', $response['token_expiry_month']);

        $this->assertEquals('2021', $response['token_expiry_year']);

        $this->assertEquals('4100000000000099', $response['token_number']);
    }

    public function testFetchCryptogramLiveBadRequest()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ()
        {
            $responsebody['success'] = false;

            $responsebody['error'] = [
                "internal_error_code"       => "BAD_REQUEST_CARD_EXPIRED",
                "gateway_error_code"        => "BAD_REQUEST_ERROR",
                "gateway_error_description" => "The card is expired",
                "description"               => "The card is expired"
            ];

            $response = new Requests_Response();

            $response->body = json_encode($responsebody, JSON_FORCE_OBJECT);

            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->startTest($createPayload);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        try
        {
            $response = $this->startTest($fetchPayload);
        }
        catch (Exception\BaseException $e)
        {
            $this->assertEquals($e->getGatewayErrorDesc(), "The card is expired");

            $this->assertEquals($e->getError()->getPublicErrorCode(), 'BAD_REQUEST_ERROR');

            $this->assertEquals($e->getError()->getInternalErrorCode(), "BAD_REQUEST_CARD_EXPIRED");

            $this->assertEquals($e->getError()->getReason(), "card_expired");

            $this->assertEquals($e->getError()->getSource(), "customer");

            $this->assertEquals($e->getError()->getStep(), "token_creation");
        }
    }

    public function testFetchCryptogramLiveInvalidToken()
    {
        $this->markTestSkipped();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ()
        {
            $dummyCardNumber = '4100000000000099';

            $responsebody['success'] = true;
            $responsebody['service_provider_tokens'] = [
                'provider_type'  => 'network',
                'provider_name'  => 'Visa',
                'provider_data'  => [
                    'token_number' => $dummyCardNumber,
                    'cryptogram_value' => 12,
                    'token_expiry_month' => 12,
                    'token_expiry_year' => 2021,
                ],
            ];

            $response = new Requests_Response();

            $response->body = json_encode($responsebody, JSON_FORCE_OBJECT);

            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLiveInvalidTokenId'];

        $fetchPayload['request']['content'] = ['id' => '123'];

        $this->startTest($fetchPayload);
    }

    public function testFetchCryptogramLiveVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new Requests_Response();

            $response->body = '{
                "success": false,
                "error": {
                  "internal_error_code": "SERVER_ERROR_ASSERTION_ERROR",
                  "gateway_error_code": "SERVER_ERROR",
                  "gateway_error_description": "We are facing some trouble completing your request at the moment. Please try again shortly.",
                  "description": "We are facing some trouble completing your request at the moment. Please try again shortly."
                }
              }';

            return $response;
        };

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->makeRequestAndGetContent($createPayload['request']);

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchCryptogramLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];
        try {
            $this->startTest($fetchPayload);
        }
        catch (Exception\BaseException $e)
        {
            $this->assertEquals($e->getError()->getInternalErrorCode(), "SERVER_ERROR_ASSERTION_ERROR");

            $this->assertEquals($e->getError()->getPublicErrorCode(), "SERVER_ERROR");

            $this->assertEquals($e->getError()->getReason(), "server_error");

            $this->assertEquals($e->getError()->getSource(), "internal");

            $this->assertEquals($e->getError()->getStep(), "payment_initiation");
        }
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

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => 'visa',
                    'interoperable'  => true,
                    'status'         => 'activated',
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_iin'              => '400000',
                        'token_expiry_month'     => '12',
                        'token_expiry_year'      => '2023',
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

        $this->assertEquals('12', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $fetchResponse['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertNotNull($fetchResponse['service_provider_tokens']);
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

            $response['service_provider_tokens'] = [
                [
                    'id'            => 'spt_1234abcd',
                    'entity'        => 'service_provider_token',
                    'provider_type' => 'network',
                    'provider_name' => 'visa',
                    'interoperable' => true,
                    'status'        => 'activated',
                    'provider_data' => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_iin'              => '400000',
                        'token_expiry_month'     => '12',
                        'token_expiry_year'      => '2023',
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

        $fetchPayload['request']['content'] = ['id' => '123'];

        $this->startTest($fetchPayload);
    }

    public function testFetchTokenLiveVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new Requests_Response();

            $response->body = '{
                "success": false,
                "error": {
                  "internal_error_code": "SERVER_ERROR_ASSERTION_ERROR",
                  "gateway_error_code": "SERVER_ERROR",
                  "gateway_error_description": "We are facing some trouble completing your request at the moment. Please try again shortly.",
                  "description": "We are facing some trouble completing your request at the moment. Please try again shortly."
                }
              }';

            return $response;
        };

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->makeRequestAndGetContent($createPayload['request']);

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $fetchPayload = $this->testData['testFetchTokenLive'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        try {
            $response = $this->startTest($fetchPayload);
        }
        catch (Exception\LogicException $e)
        {
            $this->assertEquals($e->getError()->getInternalErrorCode(), "SERVER_ERROR_ASSERTION_ERROR");

            $this->assertEquals($e->getError()->getPublicErrorCode(), "SERVER_ERROR");

            $this->assertEquals($e->getError()->getReason(), "server_error");

            $this->assertEquals($e->getError()->getSource(), "internal");

            $this->assertEquals($e->getError()->getStep(), "payment_initiation");
        }
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

    public function testTokenDeleteLiveExpiredCard()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input) {
            $responsebody = [
                "success" => false,
                "error" => [
                    "internal_error_code" => "BAD_REQUEST_CARD_EXPIRED",
                    "gateway_error_code" => "BAD_REQUEST_ERROR",
                    "gateway_error_description" => "The card is expired",
                    "description" => "The card is expired"
                ]
            ];
            $response = new Requests_Response();

            $response->body = json_encode($responsebody, JSON_FORCE_OBJECT);

            return $response;
        };

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
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

        try
        {
            $this->startTest($deletePayload);
        }
        catch(Exception\BaseException $e)
        {
            $c = 0;

            $this->assertEquals($e->getGatewayErrorDesc(), "The card is expired");

            $this->assertEquals($e->getError()->getInternalErrorCode(), "BAD_REQUEST_CARD_EXPIRED");

            $this->assertEquals($e->getError()->getPublicErrorCode(), "BAD_REQUEST_ERROR");

            $this->assertEquals($e->getError()->getReason(), "card_expired");

            $this->assertEquals($e->getError()->getSource(), "customer");

            $this->assertEquals($e->getError()->getStep(), "token_creation");
        }
    }

    public function testTokenDeleteLiveVaultFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new Requests_Response();

            $response->body = '{
                "success": false,
                "error": {
                  "internal_error_code": "SERVER_ERROR_ASSERTION_ERROR",
                  "gateway_error_code": "SERVER_ERROR",
                  "gateway_error_description": "We are facing some trouble completing your request at the moment. Please try again shortly.",
                  "description": "We are facing some trouble completing your request at the moment. Please try again shortly."
                }
              }';

            return $response;
        };

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        $response = $this->makeRequestAndGetContent($createPayload['request']);

        $cardVault->shouldReceive('sendCardVaultRequest')
            ->with(Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $fetchPayload = $this->testData['testFetchToken'];

        $fetchPayload['request']['content'] = ['id' => $response['id']];

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $deletePayload = $this->testData['testTokenDelete'];

        $deletePayload['request']['content'] = ['id' => $response['id']];

        try {
            $response = $this->startTest($deletePayload);
        }
        catch (Exception\LogicException $e)
        {
            $this->assertEquals($e->getError()->getInternalErrorCode(), "SERVER_ERROR_ASSERTION_ERROR");

            $this->assertEquals($e->getError()->getPublicErrorCode(), "SERVER_ERROR");

            $this->assertEquals($e->getError()->getReason(), "server_error");

            $this->assertEquals($e->getError()->getSource(), "internal");

            $this->assertEquals($e->getError()->getStep(), "payment_initiation");
        }
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

        $deletePayload['request']['content'] = ['id' => '123'];

        $this->startTest($deletePayload);
    }

    public function testTokenStatusLive()
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

            $response['service_provider_tokens'] = [
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'network',
                    'provider_name'  => 'visa',
                    'interoperable'  => true,
                    'status'         => 'suspended',
                    'provider_data'  => [
                        'token_reference_number' => $token,
                        'card_reference_number'  => strrev($token),
                        'token_iin'              => '400000',
                        'token_expiry_month'     => '12',
                        'token_expiry_year'      => '2023',
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

        $statusPayload = $this->testData['testTokenStatusLive'];

        $statusPayload['request']['content'] = [
            'token_id'     => Token\Entity::verifyIdAndStripSign($response['id']),
            'iin'          => '123456',
            'expiry_month' => '12',
            'expiry_year'  => '21',
            'status'       => 'suspended',
        ];

        $this->ba->appAuth('rzp_test','');

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $statusResponse = $this->startTest($statusPayload);

        $this->assertEquals($response['id'], $statusResponse['token_id']);

        $this->assertEquals($statusPayload['request']['content']['status'], $statusResponse['status']);

        $this->assertNotNull($statusResponse['vault_token']);
    }

    public function testTokenStatusLiveFailure()
    {
        $this->ba->privateAuth();

        $statusPayload = $this->testData['testTokenStatusLiveFailure'];

        $statusPayload['request']['content'] = [
            'token_id'     => 'IH1DUoeHzMRMHO',
            'iin'          => '123456',
            'expiry_month' => '12',
            'expiry_year'  => '21',
            'status'       => 'suspended',
        ];

        $this->ba->appAuth('rzp_test','');

        $statusResponse = $this->startTest($statusPayload);
    }

    public function mockCardVaultWithMigrateToken()
    {
         $app = App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = function ($route, $method, $input)
        {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'tokens/migrate':
                    $response['success'] = true;

                    $response['provider'] = strtolower($input['iin']['network']);

                    $token = base64_encode($input['card']['vault_token']);
                    $response['token']  = $token;

                    $response['fingerprint'] = strrev($token);
                    $response['last4'] = 1234;

                    $token_iin = 411111;

                    $expiry_year = $input['card']['expiry_year'];
                    if (strlen($expiry_year) == 2)
                    {
                        $expiry_year = '20' . $expiry_year;
                    }

                    $response['service_provider_tokens'] = [
                        [
                            'id'             => 'spt_1234abcd',
                            'entity'         => 'service_provider_token',
                            'provider_type'  => 'network',
                            'provider_name'  => $input['iin']['network'],
                            'status'         => 'created',
                            'interoperable'  => true,
                            'provider_data'  => [
                                'token_reference_number' => $token,
                                'card_reference_number'  => strrev($token),
                                'token_expiry_month'     => $input['card']['expiry_month'],
                                'token_expiry_year'      => $expiry_year,
                                'token_iin'              => $token_iin,
                                'token_number'           => 411111,
                            ],
                        ]
                    ];
                    break;

                case 'delete':
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', null)
                  ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }
}
