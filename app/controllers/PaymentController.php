<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Payment;
use Models\Card;

class PaymentController extends BaseController
{
    protected $payment;

    public function __construct()
    {
        $this->payment = new Payment\Service();
    }

    public function getPayment($id)
    {
        $payment = $this->payment->fetch($id);

        return ApiResponse::json($payment);
    }

    /**
     * Retrieves payment details
     */
    public function getPayments()
    {
        $input = Input::all();

        $payments = $this->payment->fetchMultiple($input);

        return ApiResponse::json($payments);
    }

    /**
     * Create a new payment
     */
    public function postCreatePayment()
    {
        $input = Input::all();

        if (isset($input['callback_url']))
        {
            $app = App::getFacadeRoot();
            $app['rzp.merchant_callback_url'] = $input['callback_url'];
        }

        $data = $this->payment->process($input);

        //
        // Check for call from API
        //
        if (isset($data['request']))
        {
            if ($data['type'] === 'first')
            {
                if ($data['request']['method'] === 'post')
                {
                    return View::make('gateway.gatewayPostForm')
                               ->with('data', $data);
                }
                else if ($data['request']['method'] === 'get')
                {
                    $response = Redirect::away($data['request']['url']);
                    $response->headers->set('X-gateway', $data['gateway']);
                    return $response;
                }
            }
            else if ($data['type'] === 'return')
            {
                return View::make('gateway.callbackReturnUrl')
                           ->with('data', $data);
            }
        }
        else
        {
            return ApiResponse::json($data);
        }
    }

    /**
     * Creates a new payment on a JSONP Request
     */
    public function getJSONP()
    {
        $input = Input::all();

        unset($input['callback']);
        // jQuery inserts underscore var with timestamp
        // when cache is set to false. See jQuery docs for details
        unset($input['_']);

        $data = $this->payment->process($input);

        return ApiResponse::json($data);
    }

    public function getVerify($id)
    {
        $data = $this->payment->verify($id);

        return ApiResponse::json($data);
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

    public function postCancel($id)
    {
        $input = Input::all();

        $data = $this->payment->cancel($id, $input);

        return ApiResponse::json($data);
    }

    public function postAutoCapture()
    {
        $data = $this->payment->autoCaptureOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function postCallback($id, $hash)
    {
        $input = Input::all();

        $data = null;

        $data = $this->payment->callback($id, $hash, $input);

        if (isset($data['type']))
        {
            $type = $data['type'];

            if ($type === 'return')
            {
                return View::make('gateway.callbackReturnUrl')->with('data', $data);
            }
        }

        assert ($data !== null);

        return View::make('gateway.callback')->with('data', $data);
    }

    public function getRefundsForPayment($paymentId)
    {
        $refunds = $this->payment->retrieveRefundsForPayment($paymentId);

        return ApiResponse::json($refunds);
    }

    public function getRefund($id)
    {
        $refunds = (new Payment\Refund\Service)->fetch($id);

        return ApiResponse::json($refunds);
    }

    public function getRefunds()
    {
        $input = Input::all();

        $refunds = (new Payment\Refund\Service)->fetchMultiple($input);

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

    public function postTimeout()
    {
        $data = $this->payment->timeoutOldPayments();

        return ApiResponse::json($data);
    }

    public function getCard($id)
    {
        $data = (new Card\Service)->fetchById($id);

        return ApiResponse::json($data);
    }

    public function getCards()
    {
        $input = Input::all();

        $data = (new Card\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }

    public function getAuthNotify()
    {
        $data = $this->payment->notifyAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function getAutoCaptureEmail()
    {
        $data = $this->payment->deliverAutoCaptureEmail();

        return ApiResponse::json($data);
    }

    public function getVerifyPayments()
    {
        $data = $this->payment->verifyAllPayments();

        return ApiResponse::json($data);
    }

    public function postDummyReturnCallback()
    {
        $input = Input::all();

        return ApiResponse::json($input);
    }
}
