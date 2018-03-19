<?php

namespace RZP\Tests\Functional\Gateway\Enach\Rbl;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payment\Entity as Payment;
use Illuminate\Http\Testing\File as TestingFile;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Fixtures\Entity\TransactionTrait;

class EnachRblGatewayTest extends TestCase
{
    use PaymentTrait;
    use TransactionTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures->create('terminal:shared_enach_rbl_terminal');
        $this->fixtures->create(Entity::CUSTOMER);

        $this->fixtures->merchant->enableEmandate();
        $this->fixtures->merchant->addFeatures([Constants::CHARGE_AT_WILL]);

        $this->gateway = 'enach_rbl';
    }

    public function testSuccessfulEsignGeneration()
    {
        $payment = $this->getEmandatePaymentArray('UTIB', 'aadhaar', 0);
        $payment['bank_account'] = [
            'account_number'    => '914010009305862',
            'ifsc'              => 'UTIB0000123',
            'name'              => 'Test account',
        ];

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $payment['amount']]);
        $payment['order_id'] = $order->getPublicId();

        $this->doAuthPayment($payment);

        $enach = $this->getLastEntity('enach', true);

        $this->assertEquals('authorize', $enach['action']);
        $this->assertEquals('UTIB', $enach['bank']);
        $this->assertEquals('ratn', $enach['acquirer']);
        $this->assertEquals(0, $enach['amount']);
        $this->assertNotNull($enach['signed_xml']);
    }

    public function testAcknowledgementReconciliation()
    {
        list($payment, $token, $order) = $this->createEmandatePayment();

        $replacePair = [
            '{$date}' => Carbon::now()->toIso8601String(),
            '{$paymentId}' => $payment->getId(),
            '{$status}' => 'true',
            '{$mandateId}' => 'UTIB6000000005844847',
            '{$firstCol}' => Carbon::now()->addDay()->format('Y-m-d'),
            '{$finalCol}' => Carbon::now()->addDay()->addYears(5)->format('Y-m-d'),
            '{$currency}' => 'INR',
            '{$maxAmount}' => '0',
            '{$accountNumber}' => $token->getAccountNumber(),
            '{$ifsc}' => $token->getIfsc(),
        ];

        $reconFileStub = file_get_contents(__DIR__ . '/acknowledge.stub');

        $reconFileContent = strtr($reconFileStub, $replacePair);

        $handle = tmpfile();
        fwrite($handle, $reconFileContent);
        fseek($handle, 0);
        $file = (new TestingFile('MMS-CREATE-RATN-RATNA0001-06032018-ESIGN6000001-INP-ACK.xml', $handle));

        $request = [
            'url' => '/batches',
            'method' => 'POST',
            'content' => [
                'type' => 'emandate',
                'sub_type' => 'acknowledge',
                'gateway' => 'enach_rbl',
            ],
            'files' => [
                'file' => $file,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_100000Razorpay');

        $batch = $this->makeRequestAndGetContent($request);

        $this->assertEquals('emandate', $batch['type']);
        $this->assertEquals('created', $batch['status']);

        sd($this->getDbLastEntityToArray('enach'));
        s($token->reload()->toArray());
    }

    protected function createEmandatePayment($amount = 0, $recurringType = 'initial')
    {
        $order = $this->fixtures->create('order:emandate_order', [
            'status' => 'attempted',
            'amount' => 0]);

        $token = $this->fixtures->create('customer:emandate_token', [
            'aadhaar_number' => '390051307206',
            'auth_type' => 'aadhaar']);

        $payment = [
            'auth_type'         => 'aadhaar',
            'terminal_id'       => '1000EnachRblTl',
            'order_id'          => $order->getId(),
            'amount'            => $order->getAmount(),
            'amount_authorized' => $order->getAmount(),
            'gateway'           => 'enach_rbl',
            'bank'              => 'UTIB',
            'recurring'         => '1',
            'customer_id'       => $token->getCustomerId(),
            'token_id'          => $token->getId(),
            'recurring_type'    => $recurringType,
        ];

        $payment = $this->fixtures->create('payment:emandate_authorized', $payment);

        return [$payment, $token, $order];
    }

    protected function runPaymentCallbackFlowEnachRbl($response, &$callback = null)
    {
        $mock = $this->isGatewayMocked();

        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        if ($mock)
        {
            $request = $this->makeFirstGatewayPaymentMockRequest(
                                                    $url, $method, $content);
        }
        else
        {
            ;
        }

        return $this->submitPaymentCallbackRequest($request);
    }
}
