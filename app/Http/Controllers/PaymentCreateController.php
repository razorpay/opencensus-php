<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Redirect;
use Response;
use Request;
use App;
use View;

use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class PaymentCreateController extends Controller
{
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

        $this->setMerchantCallbackUrlIfApplicable($input);

        if ($this->app['basicauth']->isPrivateAuth())
        {
            $input = $this->service(E::PAYMENT_ANALYTICS)->setMetadataForS2SPayment($input);
        }

        $data = $this->service(E::PAYMENT)->process($input);

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

        $data = $this->service(E::PAYMENT)->process($input);

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

        $data = $this->service(E::PAYMENT)->process($input);

        return ApiResponse::json($data);
    }

    /**
     * Creates a wallet payment
     */
    public function postCreateWalletPayment()
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->processWallet($input);

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
     * Creates a upi payment
     */
    public function postCreateUpiPayment()
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->processUpi($input);

        $response = ['razorpay_payment_id' => $data['payment_id']];

        return ApiResponse::json($response);
    }

    /**
     * Creates a dummy payment and
     * returns corresponding fees and tax
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

        $this->setMerchantCallbackUrlIfApplicable($input);

        $data = $this->service(E::PAYMENT)->processAndReturnFees($input);

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

        $payment = $this->service(E::PAYMENT)->otpResend($id, $input);

        return ApiResponse::json($payment);
    }

    /*
     * Topup Wallet for a payment
     */
    public function postTopupAjax($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->topup($id, $input);

        return ApiResponse::json($data);
    }

    /*
     * Topup Wallet for a payment
     */
    public function postTopup($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->topup($id, $input);

        return $this->processCoprotoData($data);
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

        $data = $this->service(E::PAYMENT)->callback($id, $hash, $input);

        return $this->returnCallbackResponse($data);
    }

    public function postOtpSubmit($id, $hash)
    {
        $input = Request::all();

        // Type should be OTP since it's an OTP callback
        $input['type'] = 'otp';

        $data = $this->service(E::PAYMENT)->callback($id, $hash, $input);

        return ApiResponse::json($data);
    }

    public function postRedirectCallback($id)
    {
        $data = $this->service(E::PAYMENT)->redirectCallback($id);

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
                    return $this->redirectToGatewayPostForm($data);
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
                $templateData = [
                   'data' => $data,
                   'cdn'  => $this->config->get('url.cdn.production')
                ];

                return View::make('gateway.gatewayOtpPostForm')
                           ->with('data', $templateData);
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
            else if ($data['type'] === 'wallet')
            {
                return View::make('gateway.gatewayWalletForm')
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

    protected function redirectToGatewayPostForm($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();
        $postFormData = $data;
        $postFormData['theme']['color'] = $merchant->getBrandColorElseDefault();
        $postFormData['name'] = $merchant->getBillingLabel();

        return View::make('gateway.gatewayPostForm')
                   ->with('data', $postFormData);
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

    protected function setMerchantCallbackUrlIfApplicable(array $input)
    {
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
    }
}
