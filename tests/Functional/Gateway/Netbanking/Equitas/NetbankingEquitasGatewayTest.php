<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Equitas;

use Mail;

use RZP\Models\Payment\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Gateway\Netbanking\Equitas\Status;
use RZP\Gateway\Netbanking\Equitas\Constants;
use RZP\Gateway\Netbanking\Equitas\Mock\Server;
use RZP\Gateway\Netbanking\Equitas\RequestFields;
use RZP\Gateway\Netbanking\Equitas\ResponseFields;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class NetbankingEquitasGatewayTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/NetbankingEquitasGatewayTestData.php';

        parent::setUp();

        $this->gateway = 'netbanking_equitas';

        $this->bank = 'ESFB';

        $this->payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $this->setMockGatewayTrue();

        $this->fixtures->create('terminal:shared_netbanking_equitas_terminal');
    }

    public function testPayment()
    {
        $this->doNetbankingEquitasAuthAndCapturePayment();

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity);

        $this->assertEquals('AB1234', $paymentEntity[Entity::ACQUIRER_DATA]['bank_transaction_id']);

        $netbankingEntity = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertArraySelectiveEquals(
            $this->testData['testPaymentNetbankingEntity'], $netbankingEntity);
    }

    public function testTpvPayment()
    {
        $terminal = $this->fixtures->create('terminal:shared_netbanking_equitas_tpv_terminal');

        $this->ba->privateAuth();

        $data = $this->testData[__FUNCTION__]['request']['content'];

        $this->fixtures->merchant->enableTPV();

        $order = $this->startTest();

        $order = $this->getLastEntity('order');

        $this->payment['order_id'] = $order['id'];

        $payment = $this->doAuthAndCapturePayment($this->payment);

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals($payment['terminal_id'], $terminal->getId());

        $this->assertEquals($payment['reference1'], Server::TRANSACTION_ID);

        $this->assertEquals($payment['status'], 'captured');

        $this->assertEquals($payment['amount'], $data['amount']);

        $gatewayPayment = $this->getLastEntity('netbanking', true);

        $this->assertNotNull($gatewayPayment['account_number']);

        $this->assertEquals($gatewayPayment['account_number'], $data['account_number']);

        $this->assertEquals($gatewayPayment['bank_payment_id'], Server::TRANSACTION_ID);

        $this->assertEquals($gatewayPayment['bank'], $data['bank']);

        $this->fixtures->merchant->disableTPV();
    }

    public function testTamperedAmount()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[RequestFields::AMOUNT] = '50';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testPaymentIdMismatch()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[RequestFields::PAYMENT_ID] = 'ABCD1234567890'; //some random payment_id
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function()
        {
            $this->doAuthPayment($this->payment);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testChecksumValidationFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'authorize')
            {
                $content[ResponseFields::CHECKSUM] = '1234';
            }
        });

        $this->runRequestResponseFlow($data, function()
        {
            $this->doNetbankingEquitasAuthAndCapturePayment();
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testAuthFailed()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[ResponseFields::AUTH_STATUS] = Status::NO;
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doNetbankingEquitasAuthAndCapturePayment();
        });
    }

    public function testAuthInvalidStatus()
    {
        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[ResponseFields::AUTH_STATUS] = 'invalid';
            }
        });

        $testData = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($testData, function ()
        {
            $this->doNetbankingEquitasAuthAndCapturePayment();
        });
    }

    public function testCallbackResponseError()
    {
        $data = $this->testData[__FUNCTION__];

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'callback')
            {
                $content[ResponseFields::AUTH_STATUS] = 'N';
            }

            if ($action === 'authorize')
            {
                $content[ResponseFields::ERROR_CODE]    = '01';
                $content[ResponseFields::ERROR_MESSAGE] = 'Invalid Branch Directory';
            }
        });

        $this->runRequestResponseFlow($data, function()
        {
            $this->doNetbankingEquitasAuthAndCapturePayment();
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentFailedPaymentEntity');
    }

    public function testAuthFailedVerifySuccess()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testAuthFailed();

        $payment = $this->getLastEntity('payment');

        $this->mockServerContentFunction(function (&$content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::VERIFY_STATUS] = Status::YES;
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });
    }

    public function testAuthSuccessVerifyFailed()
    {
        $data = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment');

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::VERIFICATION] = ResponseFields::VERIFY_STATUS . '=' . Status::NO;
            }
        });

        $this->runRequestResponseFlow($data, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $gatewayPayment = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertTestResponse($gatewayPayment, 'testAuthSuccessVerifyFailedNetbankingEntity');
    }

    public function testPaymentVerify()
    {
        $payment = $this->doAuthAndCapturePayment($this->payment);

        $verify = $this->verifyPayment($payment['id']);

        assert($verify['payment']['verified'] === 1);

        $gatewayPayment = $this->getDbLastEntityToArray('netbanking', 'test');

        $this->assertTestResponse($gatewayPayment, 'testPaymentVerifySuccessEntity');
    }

    public function testVerifyInvalidResponse()
    {
        $testData = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verifyXml')
            {
                $content = 'Invalid Status';
            }
        });

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentInvalidPaymentEntity');
    }

    public function testVerifyResponseError()
    {
        $this->markTestSkipped();
        $testData = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if ($action === 'verify')
            {
                $content[ResponseFields::VERIFY_ERROR_CODE]     = '01';
                $content[ResponseFields::VERIFY_ERROR_MESSAGE]  = 'Invalid Branch Directory';
            }
        });

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentErrorPaymentEntity');
    }

    public function testVerifyChecksumStatusFalse()
    {
        $this->markTestSkipped();
        $testData = $this->testData[__FUNCTION__];

        $this->testPayment();

        $payment = $this->getLastEntity('payment', true);

        $this->mockServerContentFunction(function(& $content, $action = null)
        {
            if($action === 'verify')
            {
                $content[ResponseFields::VERIFY_CHECKSUM_STATUS] = Constants::FALSE;
            }
        });

        $this->runRequestResponseFlow($testData, function() use ($payment)
        {
            $this->verifyPayment($payment['id']);
        });

        $paymentEntity = $this->getDbLastEntityToArray('payment', 'test');

        $this->assertTestResponse($paymentEntity, 'testPaymentErrorPaymentEntity');
    }

    protected function doNetbankingEquitasAuthAndCapturePayment()
    {
        $payment = $this->getDefaultNetbankingPaymentArray($this->bank);

        $payment = $this->doAuthAndCapturePayment($payment);
    }
}
