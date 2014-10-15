<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Payment;

class PaymentController extends BaseController
{
    protected $payment;

    public function __construct()
    {
        $this->payment = new Payment\Service();
    }

    public function getPayment($id)
    {
        $payment = $this->payment->retrievePayment($id);

        return ApiResponse::json($payment);
    }

    /**
     * Retrieves payment details
     */
    public function getPayments()
    {
        $input = Input::all();

        $payments = $this->payment->retrieveMultiple($input);

        return ApiResponse::json($payments);
    }

    /**
     * Create a new payment
     */
    public function postCreatePayment()
    {
        $input = Input::all();

        $data = $this->payment->process($input);

        //
        // Check for call from API
        //
        if (isset($data['callbackUrl']))
        {
        	return View::make('hdfc.enrollResponse')
                ->with('data', $data['data'])
                ->with('callbackUrl',$data['callbackUrl']);
        }

        return ApiResponse::json($data);
    }

    /**
     * Creates a new payment on a JSONP Request
     */
    public function getJSONP()
    {
        $input = Input::all();

        unset($input['callback']);
        unset($input['_']);

        $payment = $this->payment->process($input);

        return ApiResponse::json($payment);
    }

    /**
     * Refund a payment.
     */
    public function postRefund($id)
    {
        $input = Input::all();

        $payment = $this->payment->refund($id, $input);

        return ApiResponse::json($payment);
    }

    /**
     * Captures an authorized payment
     */
    public function postCapture($id)
    {
        $input = Input::all();

        $payment = $this->payment->capture($id, $input);

        return ApiResponse::json($payment);
    }

    public function postCallback($id)
    {
        $input = Input::all();

        $data = null;

        try
        {
            $data = $this->payment->bankAcsCallback($id, $input);
        }
        catch (RecoverableException $exception)
        {
            if (App::runningUnitTests())
            {
                throw $exception;
            }

            $error = $exception->getError();

            $data = $error->toPublicArray();
            $data['http_status_code'] = $error->getHttpStatusCode();
        }
        finally
        {
            if ($data !== null)
            {
                return View::make('gateway.callback')->with('data', $data);
            }
        }
    }

    public function getRefundsForPayment($paymentId)
    {
        $refunds = $this->payment->retrieveRefundsForPayment($paymentId);

        return ApiResponse::json($refunds);
    }

    public function getRefund($id)
    {
        $refunds = $this->payment->retrieveRefund($id);

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Input::all();

        $refunds = $this->payment->retrieveMultipleRefunds($input);

        return ApiResponse::json($refunds);
    }

    public function getRefundByRefundAndPaymentId($paymentId, $rfndId)
    {
        $refunds = $this->payment->retrieveRefundByIdAndPaymentId($paymentId, $rfndId);

        return ApiResponse::json($refunds);
    }

    public function postAuthExpire()
    {
        $data = $this->payment->expireAuthorizations();

        return ApiResponse::json($data);
    }
}
