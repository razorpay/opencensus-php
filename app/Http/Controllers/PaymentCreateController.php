<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use Redirect;
use Response;
use Request;
use App;
use View;

class PaymentCreateController extends Controller
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
     * Creates an S2S payment
     */
    public function postCreateS2SPayment()
    {
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
        $input = Request::all();

        //
        // For payment creation via api and s2s call, if it's on private
        // auth then we should return json response instead of redirecting
        // to callback url.
        //
        if ((empty($input['callback_url']) === false) and
            ($this->app['basicauth']->isPublicAuth()))
        {
            $this->app['rzp.merchant_callback_url'] = $input['callback_url'];
        }
        else if ($this->app['basicauth']->isPrivateAuth())
        {
            $input = (new Payment\Analytics\Service)->setMetadataForS2SPayment($input);
        }

        $data = $this->payment->process($input);

        return $this->processCoprotoData($data);
    }

    /**
     * Creates a new payment on a JSONP Request
     *
     * @returns \Illuminate\Http\JsonResponse
     */
    public function getCreatePaymentJsonp()
    {
        $input = Request::all();

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
     *
     * @returns \Illuminate\Http\JsonResponse
     */
    public function postAJAX()
    {
        $input = Request::all();

        unset($input['callback']);

        $data = $this->payment->process($input);

        return ApiResponse::json($data);
    }

    /**
     * Creates a wallet payment
     */
    public function postCreateWalletPayment()
    {
        $input = Request::all();

        $data = $this->payment->processWallet($input);

        if (isset($data['request']))
        {
            $data = [
                'request' => [
                    'url'       => $data['request']['url'],
                    'method'    => $data['request']['method']
                ]
            ];

            return ApiResponse::json($data);
        }

        assertTrue(false, 'Shouldn\'t reach here');
    }

    /**
     * Creates a dummy payment and
     * returns corresponding fees and service_tax
     * Used where customer is the fee-bearer and the
     * fee needs to be displayed to the user on the checkout.
     *
     * @return \Illuminate\View\View displaying the fees and
     *                              submit button to proceed to payment
     */
    public function postCreatePaymentFees()
    {
        $input = Request::all();

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

        $url = $this->route->getUrlWithPublicAuth('payment_create_checkout');

        return $this->returnConvenienceFeesView($input, $data, $url);
    }

    /**
     * Resend OTP for a payment
     */
    public function postOtpResend($id)
    {
        $input = Request::all();

        $payment = $this->payment->otpResend($id, $input);

        return ApiResponse::json($payment);
    }

    /*
     * Topup Wallet for a payment
     */
    public function postTopupAjax($id)
    {
        $input = Request::all();

        $data = $this->payment->topup($id, $input);

        return ApiResponse::json($data);
    }

    /*
     * Topup Wallet for a payment
     */
    public function postTopup($id)
    {
        $input = Request::all();

        $data = $this->payment->topup($id, $input);

        return $this->processCoprotoData($data);
    }

    public function postAutoCapture()
    {
        $data = $this->payment->autoCaptureOldAuthorizedPayments();

        return ApiResponse::json($data);
    }

    /**
     * It's hit when banks/networks redirect back to gateway
     * on the callback url. Mostly gets hit after two-factor auth.
     *
     * @param $id
     * @param $hash
     * @return mixed
     */
    public function postCallback($id, $hash)
    {
        $input = Request::all();

        $data = $this->payment->callback($id, $hash, $input);

        return $this->returnCallbackResponse($data);
    }

    public function postOtpSubmit($id, $hash)
    {
        $input = Request::all();

        // Type should be OTP since it's an OTP callback
        $input['type'] = 'otp';

        $data = $this->payment->callback($id, $hash, $input);

        return ApiResponse::json($data);
    }

    public function postRedirectCallback($id)
    {
        $data = $this->payment->redirectCallback($id);

        return $this->returnCallbackResponse($data);
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

    protected function processCoprotoData($data)
    {
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
                    $response = \Redirect::away($data['request']['url']);
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
            else if ($data['type'] === 'async')
            {
                return View::make('gateway.gatewayAsyncForm')
                           ->with('data', $data);
            }
            else
            {
                assertTrue(false, 'Should not reach here');
            }
        }
        else
        {
            return $data;
        }
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
