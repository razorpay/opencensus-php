<?php

namespace RZP\Tests\Functional\Gateway\Upi\Rbl;

use RZP\Gateway\Upi\Rbl\Action;
use RZP\Gateway\Upi\Rbl\Fields;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiRblGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    /**
     * @var Terminal
     */
    protected $collectTerminal;
    protected $intentTerminal;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/UpiRblGatewayTestData.php';

        parent::setUp();

        $this->collectTerminal = $this->fixtures->create('terminal:shared_upi_rbl_collect_terminal');

        $this->intentTerminal = $this->fixtures->create('terminal:shared_upi_rbl_intent_terminal');

        $this->gateway = 'upi_rbl';

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->payment = $this->getDefaultUpiPaymentArray();
    }

    public function testUpiCollectSuccess()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastEntity('payment');

        $this->assertEquals('created', $payment['status']);

        $this->assertNotNull($payment['vpa']);

        $this->assertNotNull($upi['expiry_time']);

        $this->assertNotNull($upi['gateway_payment_id']);

        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $this->assertEquals('authorized', $payment['status']);

        $upi->reload();

        $this->assertNotNull($upi['npci_txn_id']);

        $this->assertEquals(
            '<UPI_PUSH_Response><statuscode>0</statuscode>'
            . '<description>ACK Success</description></UPI_PUSH_Response>',
            $response);

        $this->assertEquals('ICIC', $upi['bank']);
    }

    public function testCollectWithAuthTokenFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action)
        {
            if($action === Action::GENERATE_AUTH_TOKEN)
            {
                $content = [
                    Fields::GENERATE_AUTH_TOKEN_RESPONSE => [
                        Fields::STATUS      => '0',
                        Fields::DESCRIPTION => 'E004:No channel found or channel is not active',
                    ]
                ];
            }
        });

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);

        });
    }

    public function testCollectGenerateTransactionIdFailure()
    {
        $this->mockServerContentFunction(function(&$content, $action)
        {
            if($action === Action::GET_TRANSACTION_ID)
            {
                $content = [
                    Fields::GET_TRANSACTION_ID_RESPONSE => [
                        Fields::STATUS      => '0',
                        Fields::DESCRIPTION => 'Your Session has been Expired.Please Relogin the Application',
                    ]
                ];
            }
        });

        $this->runRequestResponseFlow($this->testData[__FUNCTION__], function()
        {
            $this->doAuthPaymentViaAjaxRoute($this->payment);

        });
    }

    public function testCollectFailedPaymentWithNpciErrorCode()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastEntity('payment');

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if($action === Action::CALLBACK)
            {
                $content[Fields::UPI_PUSH_REQUEST][Fields::TRANSACTION_STATUS] = 'FAILED';
                $content[Fields::UPI_PUSH_REQUEST][Fields::NPCI_ERROR_CODE]    = 'U17';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(),
            $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            '<UPI_PUSH_Response><statuscode>1</statuscode>'
            . '<description>ACK Success</description></UPI_PUSH_Response>',
            $response);

        $upi->reload();

        $this->assertEquals('U17', $upi['status_code']);

    }

    public function testCollectFailedPaymentWithRblErrorCode()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastEntity('payment');

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if($action === Action::CALLBACK)
            {
                $content[Fields::UPI_PUSH_REQUEST][Fields::TRANSACTION_STATUS] = 'FAILED';
                $content[Fields::UPI_PUSH_REQUEST][Fields::NPCI_ERROR_CODE]    = 'MRER006';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(),
            $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            '<UPI_PUSH_Response><statuscode>1</statuscode>'
            . '<description>ACK Success</description></UPI_PUSH_Response>',
            $response);

        $upi->reload();

        $this->assertEquals('MRER006', $upi['status_code']);

        $this->assertNotNull($upi['npci_reference_id']);
    }

    public function testCollectCallbackWithAmountTampering()
    {
        $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastEntity('payment');

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if($action === Action::CALLBACK)
            {
                $content[Fields::UPI_PUSH_REQUEST][Fields::AMOUNT] = '800.00';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(),
            $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            '<UPI_PUSH_Response><statuscode>1</statuscode>'
            . '<description>ACK Success</description></UPI_PUSH_Response>',
            $response);

        $payment->reload();

        $this->assertSame('SERVER_ERROR', $payment->getErrorCode());

        $this->assertSame('SERVER_ERROR_AMOUNT_TAMPERED', $payment->getInternalErrorCode());
    }

    public function testCollectVerifyPayment()
    {
        $this->testUpiCollectSuccess();

        $payment = $this->getLastEntity('payment', true);

        $upi = $this->getLastEntity('upi', true);

        $this->mockServerContentFunction(function(&$content, $action) use ($payment, $upi)
        {
            if ($action === Action::VERIFY)
            {
                $content[Fields::SEARCH_REQUEST][Fields::AMOUNT] = $payment['amount'] / 100;

                $content[Fields::SEARCH_REQUEST][Fields::CUSTOMER_REF] = $upi['npci_reference_id'];
            }
        });

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testCollectVerifyPaymentApiFailed()
    {
        $this->testUpiCollectSuccess();

        $payment = $this->getLastEntity('payment', true);

        $upi = $this->getLastEntity('upi', true);

        $this->mockServerContentFunction(function(&$content, $action) use ($payment, $upi)
        {
            if ($action === Action::VERIFY)
            {
                $content[Fields::SEARCH_REQUEST][Fields::STATUS] = 0;

                $content[Fields::SEARCH_REQUEST][Fields::DESCRIPTION] = 'Your session has expired';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment){

            $this->verifyPayment($payment['id']);
        });
    }

    public function testCollectVerifyPaymentFailed()
    {
        $this->testUpiCollectSuccess();

        $payment = $this->getLastEntity('payment', true);

        $upi = $this->getLastEntity('upi', true);

        $this->mockServerContentFunction(function(&$content, $action) use ($payment, $upi)
        {
            if($action === Action::VERIFY)
            {
                $content[Fields::SEARCH_REQUEST][Fields::STATUS] = 1;

                $content[Fields::SEARCH_REQUEST][Fields::AMOUNT] = '9000.00';
            }
        });

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($payment){

            $this->verifyPayment($payment['id']);
        });
    }

    public function testIntentPayment()
    {
        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastEntity('payment');

        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(), $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $payment->reload();

        $upi->reload();

        $this->assertEquals('SUCCESS', $upi['status_code']);

        $this->assertEquals('ICIC', $upi['bank']);

        $this->assertEquals('authorized', $payment['status']);

        $this->assertEquals('random@icici', $payment['vpa']);

        $this->assertEquals($payment['reference16'], $upi['npci_reference_id']);

        $this->assertNotNull($payment['acquirer_data']['rrn']);
    }

    public function testIntentPaymentVerify()
    {
        $this->testIntentPayment();

        $payment = $this->getLastEntity('payment', true);

        $upi = $this->getLastEntity('upi', true);

        $this->mockServerContentFunction(function(&$content, $action) use ($payment, $upi)
        {
            if ($action === Action::VERIFY)
            {
                $content[Fields::SEARCH_REQUEST][Fields::AMOUNT] = $payment['amount'] / 100;

                $content[Fields::SEARCH_REQUEST][Fields::CUSTOMER_REF] = $upi['npci_reference_id'];
            }
        });

        $this->verifyPayment($payment['id']);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(1, $payment['verified']);
    }

    public function testIntentPaymentFailed()
    {
        unset($this->payment['description']);
        unset($this->payment['vpa']);

        $this->payment['_']['flow'] = 'intent';

        $response = $this->doAuthPaymentViaAjaxRoute($this->payment);

        $upi = $this->getDbLastEntity('upi');

        $payment = $this->getDbLastEntity('payment');

        $this->mockServerContentFunction(function(&$content, $action)
        {
            if ($action === Action::CALLBACK)
            {
                $content[Fields::UPI_PUSH_REQUEST][Fields::TRANSACTION_STATUS] = 'FAILED';
                $content[Fields::UPI_PUSH_REQUEST][Fields::NPCI_ERROR_CODE]    = 'U17';
            }
        });

        $content = $this->getMockServer()->getAsyncCallbackContent($upi->toArray(),
            $payment->toArray());

        $response = $this->makeS2SCallbackAndGetContent($content);

        $this->assertEquals(
            '<UPI_PUSH_Response><statuscode>1</statuscode>'
            . '<description>ACK Success</description></UPI_PUSH_Response>',
            $response);

        $upi->reload();

        $this->assertEquals('U17', $upi['status_code']);
    }
}
