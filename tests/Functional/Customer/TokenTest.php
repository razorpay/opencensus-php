<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use \WpOrg\Requests\Response;
use RZP\Error\Error;
use RZP\Exception;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Constants;
use RZP\Models\Card\Network;
use RZP\Models\Customer\Token;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class TokenTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use TerminalTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TokenTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['network_tokenization', 'allow_network_tokens']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
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

    public function testCreateTokenWithCardNumberSpaceData()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals('12', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

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

        $this->assertEquals('initiated', $MCResponse['status']);

        $this->assertEquals(null, $MCResponse['expired_at']);
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

        $this->assertEquals('initiated', $MCResponse['status']);

        $this->assertEquals(null, $MCResponse['expired_at']);
    }

    public function testParApiWithTokenId()
    {
        $this->markTestSkipped();

        $this->setUpMockPar();

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateTokenAndTokenizeCard'];

        $response = $this->startTest($createPayload);

        $parApiPayload = $this->testData['testParApiWithTokenIdTestData'];

        $parApiPayload['request']['content'] = ['token' => $response['id']];

        $parApiResponse = $this->startTest($parApiPayload);

        $this->assertEquals($parApiResponse['payment_account_reference'], '=ETdmZ3MvlmMtF2QsJTS');
    }

    private function setUpMockPar()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($route, $method, $input) {
            $response = [];

            if($route === "tokens") {
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
                            'token_reference_number'     => $token,
                            'payment_account_reference'  => strrev($token),
                            'token_expiry_month'         => '11',
                            'token_expiry_year'          => '2026',
                            'token_iin'                  => $token_iin,
                            'token_number'               => $input['card']['number'],
                            'cryptogram_value'           => '',
                        ],
                    ]
                ];
            }
            if($route === "cards/fingerprints")
            {
                $response = ["service_provider_tokens" => [
                    [
                        'provider_data' => [
                            'payment_account_reference' => '50014EES0F4P295H2FQG7Q37823B9'
                        ]
                    ]
                ]];
            }
           if($route === "tokens/fetch")
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
                           'token_reference_number'     => $token,
                           'payment_account_reference'  => strrev($token),
                           'token_iin'              => '400000',
                           'token_expiry_month'     => '12',
                           'token_expiry_year'      => '2023',
                       ],
                   ]
               ];
            }
           if($route === "tokens/cryptogram") {
               $dummyCardNumber = '4610151724696781';

               $response['success'] = true;
               $response['service_provider_tokens'] = [
                   [
                       'id' => 'spt_IW48g8IeV3uUHA',
                       'entity' => '',
                       'interoperable' => '',
                       'provider_type'  => 'network',
                       'provider_name'  => 'Visa',
                       'provider_data'  => [
                           'token_reference_number'    => '',
                           'payment_account_reference' => '',
                           'token_iin' => '',
                           'token_number' => $dummyCardNumber,
                           'cryptogram_value' => 12,
                           'token_expiry_month' => 12,
                           'token_expiry_year' => 2021,
                       ],
                       'status' => '',
                   ],
               ];
           }
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

        $this->fixtures->merchant->addFeatures(['card_fingerprints','network_tokenization_live']);
    }


    public function testParApiWithEncryptedCardNumber()
    {
        $this->setUpMockPar();

        $this->ba->privateAuth();

        $parApiResponse = $this->startTest();

        $this->assertEquals($parApiResponse["payment_account_reference"], "50014EES0F4P295H2FQG7Q37823B9");
    }

    public function testParApiWithCardNumber()
    {
        $this->setUpMockPar();

        $this->ba->privateAuth();

        $parApiResponse = $this->startTest();

        $this->assertEquals($parApiResponse["payment_account_reference"], "50014EES0F4P295H2FQG7Q37823B9");
    }

    public function  testParApiWithTokenPanWithTokenisedTrue()
    {
        $this->setUpMockPar();

        $this->ba->privateAuth();

        $createPayload = $this->testData['testCreateToken'];

        unset($createPayload["request"]["content"]["card"]["name"]);

        $response = $this->startTest($createPayload);

        $fetchPayload = $this->testData['testFetchCryptogramLive'];

        $fetchPayload['request']['content'] = ['id' => 'spt_IW48g8IeV3uUHA'];

        $response = $this->startTest($fetchPayload);

        $fetchParPayload = $this->testData["testParApiWithTokenPanWithTokenisedTrueTestData"];

        $fetchParResponse = $this->startTest($fetchParPayload);

        $this->assertEquals($fetchParResponse["payment_account_reference"], "50014EES0F4P295H2FQG7Q37823B9");
    }

    public function testParApiWithCardNumberWithTokenisedFalse()
    {
        //TODO : Testcase has to be fixed
        $this->markTestSkipped("Skipping Testcase, Need to be fixed");

        $this->setUpMockPar();

        $this->ba->privateAuth();

        $fetchParPayload = $this->testData["testParApiWithCardNumberWithTokenisedFalseTestData"];

        $fetchParResponse = $this->startTest($fetchParPayload);

        $this->assertEquals($fetchParResponse["payment_account_reference"], "50014EES0F4P295H2FQG7Q37823B9");
    }

    public function testCreateToken()
    {
        $this->ba->privateAuth();

        $response = $this->startTest();

        $this->assertEquals('card', $response['method']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals('12', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2023', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

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

        $this->assertEquals('initiated', $MCResponse['status']);

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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month'         => $input['card']['expiry_month'],
                        'token_expiry_year'          => $expiry_year,
                        'token_iin'                  => $token_iin,
                        'token_number'               => $input['card']['number'],
                        'cryptogram_value'           => '',
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

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY]);

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';

        $payment['save'] = 1;

        $payment['customer_id']='cust_100000customer';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['token_id']);

        $token = $this->getLastEntity('token', true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('card_' . $token['card_id'], $card['id']);

        $this->assertEquals($card['vault'], 'visa');

        $this->assertEquals('2099', $card['expiry_year']);
        $this->assertEquals('01', $card['expiry_month']);
        $this->assertEquals('3335', $card['last4']);
        $this->assertEquals('2024', $card['token_expiry_year']);
        $this->assertEquals('12', $card['token_expiry_month']);
        $this->assertNull($card['token_last4']);
    }

    public function testMigrateTokenToTokenizedCardAmex()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::VISA, Network::MC, Network::RUPAY, Network::AMEX]);

        $payment = $this->getDefaultPaymentArray();

        $payment['card']['cvv'] = '1234';

        $this->fixtures->edit('iin',
            401200,
            [
                'network'       => 'American Express'
            ]);

        $payment['_']['library'] = 'razorpayjs';

        $payment['save'] = 1;

        $payment['customer_id']='cust_100000customer';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['token_id']);

        $token = $this->getLastEntity('token', true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('card_' . $token['card_id'], $card['id']);

        $this->assertEquals($card['vault'], 'american express');

        $this->assertEquals('2099', $card['expiry_year']);
        $this->assertEquals('01', $card['expiry_month']);
        $this->assertEquals('3335', $card['last4']);
        $this->assertEquals('2024', $card['token_expiry_year']);
        $this->assertEquals('12', $card['token_expiry_month']);
        $this->assertNull($card['token_last4']);
    }

    public function testMigrateTokenWithoutConsent()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';

        $payment['customer_id']='cust_100000customer';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertArrayNotHasKey('token', $payment);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals($payment['card_id'], $card['id']);

        $this->assertEquals($card['vault'], 'rzpvault');
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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month'         => '11',
                        'token_expiry_year'          => '2026',
                        'token_iin'                  => $token_iin,
                        'token_number'               => $input['card']['number'],
                        'cryptogram_value'           => '',
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

        $response2 = $this->startTest();

        $this->assertEquals($response['id'], $response2['id']);

        $this->assertEquals('card', $response['method']);

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertEquals('11', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2026', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertArrayNotHasKey('customer_id', $response);

        $this->assertArrayHasKey('notes', $response);

        //assert card
        $card = $this->getLastEntity('card', true);

        $this->assertEquals('01', $card['expiry_month']);
        $this->assertEquals('2099', $card['expiry_year']);
        $this->assertEquals('11', $card['token_expiry_month']);
        $this->assertEquals('2026', $card['token_expiry_year']);
    }

    public function testCreateDualTokenAndTokenizeCardVisa()
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
            $token_iin = substr($input['card']['number'] ?? null, 0, 9);
            $providerReferenceId = 'KYewh236572184';
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
                    'status'                 => 'active',
                    'interoperable'          => true,
                    'provider_data'  => [
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month'         => '11',
                        'token_expiry_year'          => '2026',
                        'token_iin'                  => $token_iin,
                        'token_number'               => $input['card']['number'],
                        'providerReferenceId'        => $providerReferenceId
                    ],
                ],
                [
                    'id'             => 'spt_1234abcd',
                    'entity'         => 'service_provider_token',
                    'provider_type'  => 'issuer',
                    'provider_name'  => 'axis',
                    'status'                 => 'active',
                    'interoperable'          => true,
                    'provider_data'  => [
                        'token_reference_number'     => $token,
                        'token_expiry_month'         => '01',
                        'token_expiry_year'          => '2024',
                        'token_number'               => $input['card']['number']
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
            'issuer'  => 'Axis',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'headless_otp'  => '1',
            ]
        ]);

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);
        $this->fixtures->merchant->addFeatures(['issuer_tokenization_live']);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $tokenId = $response['id'];

        $token = $this->getDbEntityById('token', $tokenId);

        $card = $this->getDbEntityById('card', 'card_' . $token->getCardId());

        $this->assertEquals('card', $response['method']);

        $this->assertEquals('active', $token->getStatus());

        $this->assertEquals('providers', $card->getVault());

        $this->assertNotNull($response['service_provider_tokens']);

        $this->assertNotNull($response['service_provider_tokens'][0]['provider_data']['providerReferenceId']);

        $this->assertEquals('11', $response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

        $this->assertEquals('2026', $response['service_provider_tokens'][0]['provider_data']['token_expiry_year']);

        $this->assertArrayNotHasKey('customer_id', $response);

        //assert card
        $card = $this->getLastEntity('card', true);

        $this->assertEquals('01', $card['expiry_month']);
        $this->assertEquals('2099', $card['expiry_year']);
        $this->assertEquals('11', $card['token_expiry_month']);
        $this->assertEquals('2026', $card['token_expiry_year']);
    }

    public function testCreateDualTokenAndTokenizeCardVisaFailure()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new \WpOrg\Requests\Response();

            $response->body = '{
            "success": false,
            "error":{
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

        try
        {
            $response = $this->startTest();
        }
        catch (Exception\LogicException $e)
        {
            $this->assertEquals($e->getError()->getInternalErrorCode(), "SERVER_ERROR_ASSERTION_ERROR");

            $this->assertEquals($e->getError()->getPublicErrorCode(), "SERVER_ERROR");

            $this->assertEquals($e->getError()->getReason(), "server_error");

            $this->assertEquals($e->getError()->getSource(), "Visa");

            $this->assertEquals($e->getError()->getStep(), "payment_initiation");
        }
    }

    public function testCreateTokenAndTokenizeCardNotAllowed()
    {
        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new \WpOrg\Requests\Response();

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
            $response = new \WpOrg\Requests\Response();

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
                    'status'         => 'initiated',
                    'interoperable'  => true,
                    'provider_data'  => [
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month' => 10,
                        'token_expiry_year' => 30,
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

    public function testCreateTokenAndTokenizeCardAmex()
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
                    'provider_name'  => 'Amex',
                    'status'         => 'initiated',
                    'interoperable'  => true,
                    'provider_data'  => [
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month' => 10,
                        'token_expiry_year' => 30,
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
            'network' => 'American Express',
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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month'         => $input['card']['expiry_month'],
                        'token_expiry_year'          => $expiry_year,
                        'token_iin'                  => $token_iin,
                        'token_number'               => $input['card']['number'],
                        'cryptogram_value'           => '',
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
                    'status'         => 'initiated',
                    'interoperable'  => true,
                    'provider_data'  => [
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_expiry_month'         => $input['card']['expiry_month'],
                        'token_expiry_year'          => $expiry_year,
                        'token_iin'                  => $token_iin,
                        'token_number'               => $input['card']['number'],
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
            $response = new \WpOrg\Requests\Response();

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
                        'token_reference_number'    => '',
                        'payment_account_reference' => '',
                        'token_iin' => '',
                        'token_number' => $dummyCardNumber,
                        'cryptogram_value' => 12,
                        'token_expiry_month' => 12,
                        'token_expiry_year' => 2021,
                    ],
                    'status' => '',
                ],
            ];
            $response = new \WpOrg\Requests\Response();

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

    public function testFetchCryptogramAmexLive()
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
                    'provider_name'  => 'Amex',
                    'provider_data'  => [
                        'token_reference_number'    => '',
                        'payment_account_reference' => '',
                        'token_iin' => '',
                        'token_number' => $dummyCardNumber,
                        'cvv' => 1234,
                        'token_expiry_month' => 12,
                        'token_expiry_year' => 2021,
                    ],
                    'status' => '',
                ],
            ];
            $response = new \WpOrg\Requests\Response();

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

        $this->assertNotNull($response['cvv']);

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

            $response = new \WpOrg\Requests\Response();

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

            $response = new \WpOrg\Requests\Response();

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
            $response = new \WpOrg\Requests\Response();

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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_iin'                  => '400000',
                        'token_expiry_month'         => '12',
                        'token_expiry_year'          => '2023',
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
        $this->markTestSkipped();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('mpan.cardVault', $cardVault);

        $callable = function ($input)
        {
            $response = new \WpOrg\Requests\Response();

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
            $response = new \WpOrg\Requests\Response();

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
            $response = new \WpOrg\Requests\Response();

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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_iin'                  => '400000',
                        'token_expiry_month'         => '12',
                        'token_expiry_year'          => '2023',
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

        //assert card
        $card = $this->getLastEntity('card', true);

        $this->assertEquals('12', $card['expiry_month']);
        $this->assertEquals('2023', $card['expiry_year']);
        $this->assertEquals('12', $card['token_expiry_month']);
        $this->assertEquals('2021', $card['token_expiry_year']);
    }

    public function testTokenStatusDualWrite()
    {
        $this->markTestSkipped("Dual write trait removed on card");

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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_iin'                  => '400000',
                        'token_expiry_month'         => '12',
                        'token_expiry_year'          => '2023',
                    ],
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $fixedTimestamp = 1664193747;

        $card = $this->fixtures->create('card', [
                'country'       => 'IN',
                "last4"         => "1234",
                "network"       => "Visa",
                "type"          => "credit",
                "issuer"        => "sbi",
                "expiry_month"  => 12,
                "expiry_year"   => 2024,
                "created_at"    => $fixedTimestamp,
                "updated_at"    => $fixedTimestamp,
        ]);

        $token = $this->fixtures->create('token', ['method' => 'card', 'recurring' => false, 'card_id' => $card['id'], ]);

        $cardsNew = \DB::table('cards_new')->select(\DB::raw("*"))->where('id', '=', $card['id'])->get()->first();

        $this->assertNull($cardsNew);

        // Test insert to new entity when update is happening on older original entity
        $statusPayload = $this->testData['testTokenStatusLive'];

        $statusPayload['request']['content'] = [
            'token_id'     => $token['id'],
            'iin'          => '123456',
            'expiry_month' => '12',
            'expiry_year'  => '21',
            'status'       => 'suspended',
        ];

        $this->ba->appAuth('rzp_test','');

        $this->fixtures->merchant->addFeatures(['network_tokenization_live']);

        $statusResponse = $this->startTest($statusPayload);

        $cards    = \DB::table('cards')->select(\DB::raw("*"))->where('id', '=', $card['id'])->get()->first();
        $cardsNew = \DB::table('cards_new')->select(\DB::raw("*"))->where('id', '=', $card['id'])->get()->first();

        $this->assertNotNull($cardsNew);
        $this->assertNotNull($cards);

        $cardsArray    = (array) $cards;
        $cardsNewArray = (array) $cardsNew;

        $this->assertEquals($cardsArray['id'], $cardsNewArray['id']);
        $this->assertNotEquals($fixedTimestamp, $cardsNewArray['updated_at']);
        $this->assertEquals($cardsArray['updated_at'], $cardsNewArray['updated_at']);
        $this->assertEquals($cardsArray['created_at'], $cardsNewArray['created_at']);
        $this->assertEquals('123456', $cardsArray['token_iin']);
        $this->assertEquals('123456', $cardsNewArray['token_iin']);

        // test update when both entities exist
        $statusPayload['request']['content'] = [
            'token_id'     => $token['id'],
            'iin'          => '666666',
            'expiry_month' => '12',
            'expiry_year'  => '21',
            'status'       => 'activated',
        ];

        $this->ba->appAuth('rzp_test','');

        $this->startTest($statusPayload);

        $cards    = \DB::table('cards')->select(\DB::raw("*"))->where('id', '=', $card['id'])->get()->first();
        $cardsNew = \DB::table('cards_new')->select(\DB::raw("*"))->where('id', '=', $card['id'])->get()->first();

        $this->assertNotNull($cardsNew);
        $this->assertNotNull($cards);

        $cardsArray    = (array) $cards;
        $cardsNewArray = (array) $cardsNew;

        $this->assertEquals($cardsArray['id'], $cardsNewArray['id']);
        $this->assertEquals($cardsArray['updated_at'], $cardsNewArray['updated_at']);
        $this->assertEquals($cardsArray['created_at'], $cardsNewArray['created_at']);
        $this->assertEquals('666666', $cardsArray['token_iin']);
        $this->assertEquals('666666', $cardsNewArray['token_iin']);
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

    public function testMigrateTokenWithoutNetworkOnboardingShouldFail()
    {
        $this->mockCardVaultWithMigrateToken();

        $this->fixtures->merchant->addFeatures(['network_tokenization_live', 'network_tokenization_paid']);

        $this->mockFetchMerchantTokenisationOnboardedNetworks([Network::RUPAY]);

        $payment = $this->getDefaultPaymentArray();

        $payment['_']['library'] = 'razorpayjs';

        $payment['save'] = 1;

        $payment['customer_id']='cust_100000customer';

        $response = $this->doAuthPayment($payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertNotNull($payment['token_id']);

        $token = $this->getLastEntity('token', true);

        $card = $this->getLastEntity('card', true);

        $this->assertEquals('card_' . $token['card_id'], $card['id']);

        $this->assertEquals($card['vault'], 'rzpvault');
    }

    public function testCreateTokenForRearch()
    {
        $this->enablePgRouterConfig();

        $transaction = $this->fixtures->create('transaction', [
            'entity_id' => 'GfnS1Fj048VHo2',
            'type' => 'payment',
            'merchant_id' => '10000000000000',
            'amount' => 50000,
            'fee' => 1000,
            'mdr' => 1000,
            'tax' => 0,
            'pricing_rule_id' => NULL,
            'debit' => 0,
            'credit' => 49000,
            'currency' => 'INR',
            'balance' => 2025400,
            'gateway_amount' => NULL,
            'gateway_fee' => 0,
            'gateway_service_tax' => 0,
            'api_fee' => 0,
            'gratis' => FALSE,
            'fee_credits' => 0,
            'escrow_balance' => 0,
            'channel' => 'axis',
            'fee_bearer' => 'platform',
            'fee_model' => 'prepaid',
            'credit_type' => 'default',
            'on_hold' => FALSE,
            'settled' => FALSE,
            'settled_at' => 1614641400,
            'gateway_settled_at' => NULL,
            'settlement_id' => NULL,
            'reconciled_at' => NULL,
            'reconciled_type' => NULL,
            'balance_id' => '10000000000000',
            'reference3' => NULL,
            'reference4' => NULL,
            'balance_updated' => TRUE,
            'reference6' => NULL,
            'reference7' => NULL,
            'reference8' => NULL,
            'reference9' => NULL,
            'posted_at' => NULL,
            'created_at' => 1614262078,
            'updated_at' => 1614262078,

        ]);

        $hdfc = $this->fixtures->create('hdfc', [
            'payment_id' => 'GfnS1Fj048VHo2',
            'refund_id' => NULL,
            'gateway_transaction_id' => 749003768256564,
            'gateway_payment_id' => NULL,
            'action' => 5,
            'received' => TRUE,
            'amount' => '500',
            'currency' => NULL,
            'enroll_result' => NULL,
            'status' => 'captured',
            'result' => 'CAPTURED',
            'eci' => NULL,
            'auth' => '999999',
            'ref' => '627785794826',
            'avr' => 'N',
            'postdate' => '0225',
            'error_code2' => NULL,
            'error_text' => NULL,
            'arn_no' => NULL,
            'created_at' => 1614275082,
            'updated_at' => 1614275082,
        ]);

        $card = $this->fixtures->create('card', [
            'merchant_id' => '10000000000000',
            'name' => 'Harshil',
            'expiry_month' => 12,
            'expiry_year' => 2024,
            'iin' => '401200',
            'last4' => '3335',
            'length' => '16',
            'network' => 'Visa',
            'type' => 'credit',
            'sub_type' => 'consumer',
            'category' => 'STANDARD',
            'issuer' => 'HDFC',
            'international' => FALSE,
            'emi' => TRUE,
            'vault' => 'rzpvault',
            'vault_token' => 'NDAxMjAwMTAzODQ0MzMzNQ==',
            'global_fingerprint' => '==QNzMzM0QDOzATMwAjMxADN',
            'trivia' => NULL,
            'country' => 'IN',
            'global_card_id' => NULL,
            'created_at' => 1614256967,
            'updated_at' => 1614256967,
        ]);

        // sd($card->getId());

        $pgService = \Mockery::mock('RZP\Services\PGRouter')->makePartial();

        $this->app->instance('pg_router', $pgService);

        $this->disputed = false;

        $paymentData = [
            'body' => [
                "data" => [
                    "payment" => [
                        'id' => 'GfnS1Fj048VHo2',
                        'merchant_id' => '10000000000000',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'base_amount' => 50000,
                        'method' => 'card',
                        'status' => 'captured',
                        'two_factor_auth' => 'not_applicable',
                        'order_id' => NULL,
                        'invoice_id' => NULL,
                        'transfer_id' => NULL,
                        'payment_link_id' => NULL,
                        'receiver_id' => NULL,
                        'receiver_type' => NULL,
                        'international' => FALSE,
                        'amount_authorized' => 50000,
                        'amount_refunded' => 0,
                        'base_amount_refunded' => 0,
                        'amount_transferred' => 0,
                        'amount_paidout' => 0,
                        'refund_status' => NULL,
                        'description' => 'description',
                        'card_id' => $card->getId(),
                        'bank' => NULL,
                        'wallet' => NULL,
                        'vpa' => NULL,
                        'on_hold' => FALSE,
                        'on_hold_until' => NULL,
                        'emi_plan_id' => NULL,
                        'emi_subvention' => NULL,
                        'error_code' => NULL,
                        'internal_error_code' => NULL,
                        'error_description' => NULL,
                        'customer_id' => '100000customer',
                        'app_token' => NULL,
                        'global_token_id' => NULL,
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => [
                            'merchant_order_id' => 'id',
                        ],
                        'transaction_id' => $transaction->getId(),
                        'authorized_at' => 1614253879,
                        'auto_captured' => FALSE,
                        'captured_at' => 1614253880,
                        'gateway' => 'hdfc',
                        'terminal_id' => '1n25f6uN5S1Z5a',
                        'authentication_gateway' => NULL,
                        'batch_id' => NULL,
                        'reference1' => NULL,
                        'reference2' => NULL,
                        'cps_route' => 0,
                        'signed' => FALSE,
                        'verified' => NULL,
                        'gateway_captured' => TRUE,
                        'verify_bucket' => 0,
                        'verify_at' => 1614253880,
                        'callback_url' => NULL,
                        'fee' => 1000,
                        'mdr' => 1000,
                        'tax' => 0,
                        'otp_attempts' => NULL,
                        'otp_count' => NULL,
                        'recurring' => FALSE,
                        'save' => FALSE,
                        'late_authorized' => FALSE,
                        'convert_currency' => NULL,
                        'disputed' => FALSE,
                        'recurring_type' => NULL,
                        'auth_type' => NULL,
                        'acknowledged_at' => NULL,
                        'refund_at' => NULL,
                        'reference13' => NULL,
                        'settled_by' => 'Razorpay',
                        'reference16' => NULL,
                        'reference17' => NULL,
                        'created_at' => 1614253879,
                        'updated_at' => 1614253880,
                        'captured' => TRUE,
                        'reference2' => '12343123',
                        'entity' => 'payment',
                        'fee_bearer' => 'platform',
                        'error_source' => NULL,
                        'error_step' => NULL,
                        'error_reason' => NULL,
                        'dcc' => FALSE,
                        'gateway_amount' => 50000,
                        'gateway_currency' => 'INR',
                        'forex_rate' => NULL,
                        'dcc_offered' => NULL,
                        'dcc_mark_up_percent' => NULL,
                        'dcc_markup_amount' => NULL,
                        'mcc' => FALSE,
                        'forex_rate_received' => NULL,
                        'forex_rate_applied' => NULL,
                    ]
                ]
            ]
        ];

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'), Mockery::type('int'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure, int $timeout, bool $retry) use ($card, $transaction, $paymentData) {

                if ($method === 'GET')
                {
                    return $paymentData;
                }

            });

        $pgService->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), Mockery::type('string'), Mockery::type('array'), Mockery::type('bool'))
            ->andReturnUsing(function (string $endpoint, string $method, array $data, bool $throwExceptionOnFailure) use ($card, $transaction, $paymentData) {
                if ($method === 'GET')
                {
                    return $paymentData;
                }

                if ($method === 'POST')
                {
                    $this->assertEquals($data['disputed'], true);
                    return [];
                }

            });

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
                        'token_reference_number'     => $token,
                        'payment_account_reference'  => strrev($token),
                        'token_iin'                  => '400000',
                        'token_expiry_month'         => '12',
                        'token_expiry_year'          => '2023',
                    ],
                ]
            ];

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
            ->with(Mockery::type('string'), 'post', Mockery::type('array'))
            ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);

        $this->ba->pgRouterAuth();

        $testData['request']['content']['payment_id'] = 'GfnS1Fj048VHo2';

        $response = $this->startTest($testData);
        $card = $this->getLastEntity('card', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals($token['id'], $response['id']);
        $this->assertEquals($card['id'], 'card_'.$token['card_id']);

    }

    public function testFetchMerchantsWithTokenPresent()
    {

        $this->setUpMockPar();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['push_provisioning_live']);

        $createPayload = $this->testData['testFetchMerchantsWithToken'];

        $response = $this->startTest($createPayload);

        $this->assertEquals('acc_J312gerdk2aaaa', $response['account_ids'][0]);
        $this->assertEquals('acc_10000000000000', $response['account_ids'][1]);

    }
}
