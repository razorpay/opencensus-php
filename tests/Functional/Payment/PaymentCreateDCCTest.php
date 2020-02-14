<?php

namespace RZP\Tests\Functional\Payment;

use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\TestCase;

class PaymentCreateDCCTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateTestData.php';

        parent::setUp();

        $this->payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['dcc']);

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testPaymentCreateWithDCC()
    {
        $iin = $this->fixtures->iin->create([
            'iin'     => '414366',
            'country' => 'US',
            'issuer'  => 'UTIB',
            'network' => 'Visa',
            'flows'   => [
                '3ds'  => '1',
                'pin'  => '1',
                'otp'  => '1',
            ]
        ]);

        $flowsData = [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'iin'      => $iin->getIin()
            ],
            'method'  => 'POST',
            'url'     => '/payment/flows',
        ];

        $this->ba->privateAuth();

        $response = $this->sendRequest($flowsData);

        $responseContent = json_decode($response->getContent(), true);

        $cardCurrency = $responseContent['card_currency'];

        $this->assertEquals(true, $responseContent['is_international']);
        $this->assertEquals("USD", $cardCurrency);
        $this->assertNotNull($responseContent['all_currencies']);
        $this->assertNotNull($responseContent['currency_request_id']);

        $currencyRequestId = $responseContent['currency_request_id'];

        $usdAmount = $responseContent['all_currencies'][$cardCurrency]['amount'];

        $payment = $this->payment;

        $payment['dcc_currency'] = $cardCurrency;

        $payment['dcc_amount'] = $usdAmount;

        $payment['currency_request_id'] = $currencyRequestId;

        $this->doAuthAndCapturePayment($payment, $usdAmount, $cardCurrency);

    }
}
