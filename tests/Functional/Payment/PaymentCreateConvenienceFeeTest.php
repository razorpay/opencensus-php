<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class PaymentCreateConvenienceFeeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentCreateConvenienceFeeTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->fixtures->merchant->enableConvenienceFeeModel();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    public function testFees($payment = null)
    {
        if ($payment === null)
        {
            $payment = $this->getDefaultPaymentArray();
        }

        $feesArray = $this->createAndGetFeesForPayment($payment);

        if ($payment['amount'] === 50000)
        {
            assert($feesArray['input']['fee'] === 1173);

            assert($feesArray['display']['service_tax'] === 1.49);
        }

        return $feesArray;
    }

    public function testPaymentWithConvenienceFees()
    {
        $payment   = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $amount = $payment['amount'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'];

        // This is for simulating capture with the
        // original amount

        $this->doAuthAndCapturePayment($payment, $amount);

        $payment = $this->getLastPayment();

        assert($payment['fee'] === 1150);

        assert($payment['service_tax'] === 154);
    }

    public function testAmountMismatch()
    {
        $payment           = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $feesArray['input']['fee'] = 0;

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'];

        try
        {
            $this->doAuthAndCapturePayment($payment);
        }
        catch (\Exception $e)
        {
            // assert($e->getError()->internal_error_code
            //                 === 'BAD_REQUEST_VALIDATION_FAILURE');
            assert($e->getMessage()
                === ErrorCode::BAD_REQUEST_PAYMENT_FEES_OR_SERVICE_TAX_TAMPERED);
        }
    }

    // TODO Add tests for create with order
    public function testPaymentWithOrder()
    {
        $orderInput = $this->testData['testCreateOrder'];

        $this->ba->privateAuth();

        $order = $this->runRequestResponseFlow($orderInput);

        $this->ba->publicAuth();

        $payment   = $this->getDefaultPaymentArray();

        $feesArray = $this->testFees($payment);

        $amount = $payment['amount'];

        $payment['order_id'] = $order['id'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'];

        $this->doAuthAndCapturePayment($payment, $amount);

        $payment = $this->getLastPayment();

        assert($payment['order_id'] === $order['id']);

        assert($payment['fee'] === 1150);

        assert($payment['service_tax'] === 154);
    }

    // TODO Fail tests for create with order
    public function testFailedPaymentWithOrder()
    {
        $orderInput = $this->testData['testCreateOrder'];

        $this->ba->privateAuth();

        $order = $this->runRequestResponseFlow($orderInput);

        $this->ba->publicAuth();

        $payment   = $this->getDefaultPaymentArray();

        $payment['amount'] = 30000;

        $feesArray = $this->testFees($payment);

        $payment['order_id'] = $order['id'];

        $payment['amount'] = $payment['amount'] + $feesArray['input']['fee'];

        $payment['fee']    = $feesArray['input']['fee'] + 100;

        try
        {
            $this->doAuthAndCapturePayment($payment);
        }
        catch (\Exception $e)
        {
            // assert($e->getError()->internal_error_code
            //     === 'BAD_REQUEST_VALIDATION_FAILURE');
            assert($e->getMessage()
                === ErrorCode::BAD_REQUEST_PAYMENT_FEES_OR_SERVICE_TAX_TAMPERED);
        }
    }
}
