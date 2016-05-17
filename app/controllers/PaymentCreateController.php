<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Payment;
use Models\Card;
use Trace\TraceCode;

class PaymentCreateController extends BaseController
{
    protected $payment;

    public function __construct()
    {
        parent::__construct();

        $this->payment = new Payment\Service();
    }

    /**
     * Create a new payment
     */
    public function postCreatePayment()
    {
        if ($this->app['basicauth']->isPublicAuth())
        {
            $this->trace->info(
                TraceCode::PAYMENT_CREATE_ON_PUBLIC,
                ['merchant_id' => $this->app['basicauth']->getMerchantId()]);

        }

        $ret = $this->createPayment();

        if ((is_array($ret)) and
            (isset($ret['request']) === false))
        {
            return ApiResponse::json($ret);
        }

        return $ret;
    }

    /**
     * In this case, we ensure that for direct response cases like
     * international credit cards with no 3dsecure, we give back the
     * parent callback page instead of just json.
     * The way to do that is to ensure that return is an array and
     * 'request' field is not set.
     */
    public function postCreatePaymentCheckoutCallback()
    {
        $ret = $this->createPayment();

        if ((is_array($ret)) and
            (isset($ret['request'])) === false)
        {
            return $this->returnCheckoutCallbackView($ret);
        }

        return $ret;
    }

    protected function createPayment()
    {
        $input = Input::all();

        if (empty($input['callback_url']) === false)
        {
            $app = App::getFacadeRoot();
            $app['rzp.merchant_callback_url'] = $input['callback_url'];
        }
        else
        {
            // It could be just blank or an empty array. Hence unset it here only.
            unset($input['callback_url']);
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
                else if ($data['request']['method'] === 'direct')
                {
                    $response = Response::make($data['request']['content']);
                    $response->headers->set('X-gateway', $data['gateway']);

                    return $response;
                }
            }
            else if ($data['type'] === 'otp')
            {
                return View::make('gateway.gatewayOtpPostForm')
                           ->with('data', $data);
            }
            else if ($data['type'] === 'return')
            {
                return $this->returnMerchantFullRedirectView($data);
            }
            else
            {
                assert(false, 'Should not reach here');
            }
        }
        else
        {
            return $data;
        }
    }

    /**
     * Creates a new payment on a JSONP Request
     */
    public function getCreatePaymentJsonp()
    {
        $input = Input::all();

        unset($input['callback']);
        // jQuery inserts underscore var with timestamp
        // when cache is set to false. See jQuery docs for details
        unset($input['_']);

        $data = $this->payment->process($input);

        return ApiResponse::json($data);
    }

    /**
     * Creates a new payment with an AJAX Request
     * Sets the proper CORS headers
     */
    public function postAJAX()
    {
        $input = Input::all();

        unset($input['callback']);

        $data = $this->payment->process($input);

        return ApiResponse::json($data);
    }

    /**
     * Creates a wallet payment
     */
    public function postCreateWalletPayment($wallet)
    {
        $input = Input::all();

        $data = $this->payment->processWallet($input, $wallet);

        if (isset($data['request']) or isset($data['error']))
        {
            if (isset($data['request']))
            {
                $data = [
                    'request' => [
                        'url'       => $data['request']['url'],
                        'method'    => $data['request']['method']
                    ]
                ];
            }

            return ApiResponse::json($data);
        }

        new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
    }

    /**
     * Creates a dummy payments and
     * return corresponding fees and service_tax
     */
    public function postCreatePaymentFees()
    {
        $input = Input::all();

        $retJson = false;

        if (isset($input['view']) and ($input['view'] === 'json'))
        {
            unset($input['view']);

            $retJson = true;
        }

        $data = $this->payment->processAndReturnFees($input);

        if ($retJson)
        {
            return ApiResponse::json(['input' => $input,'display' => $data]);
        }

        $url = Http\Route::getUrlWithPublicAuth('payment_create_checkout');

        return $this->returnConvenienceFeesView($input, $data, $url);
    }

    public function postAutoCapture()
    {
        $data = $this->payment->autoCaptureOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    public function postCallback($id, $hash)
    {
        $input = Input::all();

        $data = $this->payment->callback($id, $hash, $input);

        return $this->returnCallbackResponse($data);
    }

    public function postOtpSubmit($id, $hash)
    {
        $input = Input::all();

        // Type should be OTP since it's an OTP callback
        $input['type'] = 'otp';

        $data = $this->payment->callback($id, $hash, $input);

        return ApiResponse::json($data);
    }

    protected function returnCallbackResponse($data)
    {
        if (isset($data['type']))
        {
            $type = $data['type'];

            if ($type === 'return')
            {
                return $this->returnMerchantFullRedirectView($data);
            }
        }

        assert ($data !== null);

        return $this->returnCheckoutCallbackView($data);
    }

    /**
     * This contains the json response and does a call to the parent/checkout
     * window.
     */
    protected function returnCheckoutCallbackView($data)
    {
        return View::make('gateway.callback')->with('data', $data);
    }

    /**
     * Redirect to the url provided by the merchant.
     */
    protected function returnMerchantFullRedirectView($data)
    {
        return View::make('gateway.callbackReturnUrl')->with('data', $data);
    }

    protected function returnConvenienceFeesView($input, $data, $url)
    {
        return View::make('gateway.gatewayFeesForm')
                   ->with('data', $data)
                   ->with('input', $input)
                   ->with('url', $url);
    }
}
