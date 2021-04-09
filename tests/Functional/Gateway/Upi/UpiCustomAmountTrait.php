<?php

namespace RZP\Tests\Functional\Gateway\Upi;

use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Terminal;

/**
 * Trait UpiCustomAmountTrait
 * @package RZP\Tests\Functional\Gateway\Upi
 *
 * @property array $payment
 * @property Terminal\Entity $sharedTerminal
 * @property Order\Entity $order
 */
trait UpiCustomAmountTrait
{
    public function testAmountDeficitOnSuccessfulWithCallback()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 49000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $this->makeAsyncCallbackGatewayCall($upi, $payment);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'        => 49000,
            'base_amount'   => 49000,
            'status'        => 'authorized',
        ], $payment->toArray(), true);

        $this->assertArraySubset([
            'gateway_amount'            => 49000,
            'mismatch_amount'           => 1000,
            'mismatch_amount_reason'    => 'credit_deficit',
        ], $payment->paymentMeta->toArray(), true);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountSuplusOnSuccessfulWithCallback()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 51000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $this->makeAsyncCallbackGatewayCall($upi, $payment);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'        => 51000,
            'base_amount'   => 51000,
            'status'        => 'authorized',
        ], $payment->toArray(), true);

        $this->assertArraySubset([
            'gateway_amount'            => 51000,
            'mismatch_amount'           => 1000,
            'mismatch_amount_reason'    => 'credit_surplus',
        ], $payment->paymentMeta->toArray(), true);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountDeficitOnFailureWithCallback()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::FAILED, 49000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $this->makeAsyncCallbackGatewayCall($upi, $payment, false);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'                => 50000,
            'base_amount'           => 50000,
            'status'                => 'failed',
            'internal_error_code'   => 'BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED',
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountSurplusOnFailureWithCallback()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::FAILED, 51000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $this->makeAsyncCallbackGatewayCall($upi, $payment, false);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'                => 50000,
            'base_amount'           => 50000,
            'status'                => 'failed',
            'internal_error_code'   => 'BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED',
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountDeficitExceptionOnSuccessfulWithCallback()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 49000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        $this->makeAsyncCallbackGatewayCall($upi, $payment, false);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'                => 50000,
            'base_amount'           => 50000,
            'status'                => 'failed',
            'internal_error_code'   => 'SERVER_ERROR_AMOUNT_TAMPERED'
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountSurplusExceptionOnSuccessfulWithCallback()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 51000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $upi = $this->getDbLastUpi();

        $this->makeAsyncCallbackGatewayCall($upi, $payment, false);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'                => 50000,
            'base_amount'           => 50000,
            'status'                => 'failed',
            'internal_error_code'   => 'SERVER_ERROR_AMOUNT_TAMPERED'
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountDeficitOnSuccessfulWithVerify()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 49000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->save();

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 1,
            'success'       => 0,
            'error'         => 0,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 49000,
            'base_amount'       => 49000,
            'status'            => 'authorized',
            'verified'          => 0,
            'vpa'               => 'user@icici',
            'late_authorized'   => true,
        ], $payment->toArray(), true);

        $this->assertArraySubset([
            'gateway_amount'            => 49000,
            'mismatch_amount'           => 1000,
            'mismatch_amount_reason'    => 'credit_deficit',
        ], $payment->paymentMeta->toArray(), true);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountSurplusOnSuccessfulWithVerify()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 51000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->save();

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 1,
            'success'       => 0,
            'error'         => 0,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 51000,
            'base_amount'       => 51000,
            'status'            => 'authorized',
            'verified'          => 0,
            'vpa'               => 'user@icici',
            'late_authorized'   => true,
        ], $payment->toArray(), true);

        $this->assertArraySubset([
            'gateway_amount'            => 51000,
            'mismatch_amount'           => 1000,
            'mismatch_amount_reason'    => 'credit_surplus',
        ], $payment->paymentMeta->toArray(), true);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountDeficitOnFailureWithVerify()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::FAILED, 49000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->save();

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 0,
            'success'       => 1,
            'error'         => 0,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 50000,
            'base_amount'       => 50000,
            'status'            => 'created',
            'verified'          => 1,
            'late_authorized'   => false,
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountSurplusOnFailureWithVerify()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::FAILED, 51000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->save();

        $upi = $this->getDbLastUpi();

        config()->set('app.amount_difference_allowed_authorized', [$payment->getMerchantId()]);

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 0,
            'success'       => 1,
            'error'         => 0,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 50000,
            'base_amount'       => 50000,
            'status'            => 'created',
            'verified'          => 1,
            'late_authorized'   => false,
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountDeficitExceptionOnSuccessfulWithVerify()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 49000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->save();

        $upi = $this->getDbLastUpi();

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 0,
            'success'       => 0,
            'error'         => 1,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 50000,
            'base_amount'       => 50000,
            'status'            => 'created',
            'verified'          => 0,
            'late_authorized'   => false,
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }

    public function testAmountSurplusExceptionOnSuccessfulWithVerify()
    {
        $this->setUpUpiCustomAmountTest(Payment\Status::AUTHORIZED, 51000);

        $payment = $this->getDbLastPayment();

        $this->assertArraySubset([
            'amount'        => 50000,
            'base_amount'   => 50000,
            'status'        => 'created',
        ], $payment->toArray(), true);

        $payment->setVerifyAt(now()->getTimestamp());
        $payment->save();

        $upi = $this->getDbLastUpi();

        $response = $this->verifyAllPayments($payment->getPublicId());

        $this->assertArraySubset([
            'authorized'    => 0,
            'success'       => 0,
            'error'         => 1,
            'unknown'       => 0,
        ], $response);

        $payment->refresh();

        $this->assertArraySubset([
            'amount'            => 50000,
            'base_amount'       => 50000,
            'status'            => 'created',
            'verified'          => 0,
            'late_authorized'   => false,
        ], $payment->toArray(), true);

        $this->assertNull($payment->paymentMeta);

        $this->tearDownUpiCustomAmountTest();
    }
}
