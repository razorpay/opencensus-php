<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Redirect;
use Response;
use Request;
use App;
use View;

use RZP\Models\Feature\Constants as Feature;
use RZP\Constants\Entity as E;
use RZP\Constants\Environment;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
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
            if((isset($this->input['provider'])) and ($this->input['provider'] === Payment\Gateway::GETSIMPL) and ($this->app['rzp.mode'] != 'test'))
            {
                assertTrue ($ret !== null);
                return $this->returnCheckoutCallbackView($ret);
            }
            else {
                return ApiResponse::json($ret);
            }
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
     * Creates an S2S payment and return json response
     */
    public function postCreateS2SJsonPayment()
    {
        $input = Request::all();

        $this->logPaymentRequestEvent($input);

        $data = $this->service(E::PAYMENT)->process($input);

        $response = $this->processCoprotoJsonData($data);

        $this->logResponseIfApplicable($response);

        return ApiResponse::json($response);
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

    public function getCreatePaymentCheckoutCallback()
    {
        return View::make('gateway.gatewayAsyncForm')
                ->with('data', $templateData);
    }

    protected function createPayment()
    {
        $input = Request::all();

        $this->logPaymentRequestEvent($input);

        $this->setMerchantCallbackUrlIfApplicable($input);

        if (($this->app['basicauth']->getMerchant()->isFeeBearerCustomerOrDynamic() === true) and
            (isset($input['fee']) === false))
        {
            $input['view'] = 'html';

            return $this->createFeeBearerCustomerPayment($input);
        }

        $data = $this->service(E::PAYMENT)->process($input);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
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

        $this->logPaymentRequestEvent($input);

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

        $this->logPaymentRequestEvent($input);

        $data = $this->service(E::PAYMENT)->process($input);

        return ApiResponse::json($data);
    }

    /**
     * Creates a wallet payment
     */
    public function postCreateWalletPayment()
    {
        $input = Request::all();

        $this->logPaymentRequestEvent($input);

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

        // @todo: Add flow in the payment entity
        if (isset($input['flow']) === true)
        {
            $input['_']['flow'] = $input['flow'];

            unset($input['flow']);
        }

        $this->logPaymentRequestEvent($input);

        $data = $this->service(E::PAYMENT)->processUpi($input);

        $response = ['razorpay_payment_id' => $data['payment_id']];

        if (isset($data['data']['intent_url']) === true)
        {
            $response['link'] = $data['data']['intent_url'];
        }

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

        $this->logPaymentRequestEvent($input);

        $this->setMerchantCallbackUrlIfApplicable($input);

        return $this->createFeeBearerCustomerPayment($input);
    }

    public function postCalculatePaymentFees()
    {
        $input = Request::all();


        /*
         * A possible value of $input['view'] is 'html'. This is  used by createFeeBearerCustomerPayment()
         *  to send the response in html. However *this* route is json only.
         * So we unset the view parameter before sending to createFeeBearerCustomerPayment().
         * This is to ensure only json ever gets returned.
         */
        if (isset($input['view']) === true)
        {
            unset($input['view']);
        }

        $this->logPaymentRequestEvent($input);

        $this->setMerchantCallbackUrlIfApplicable($input);

        return $this->createFeeBearerCustomerPayment($input);
    }

    protected function createFeeBearerCustomerPayment($input)
    {
        $retHtml = false;

        if (isset($input['view']) === true)
        {
            if ($input['view'] === 'html')
            {
                $retHtml = true;
            }

            unset($input['view']);
        }

        $this->logPaymentRequestEvent($input);

        $data = $this->service(E::PAYMENT)->processAndReturnFees($input);

        // Converts all the amounts to rupees
        foreach ($data as $key => $value)
        {
            $data[$key] = $value / 100;
        }

        if ($retHtml === true)
        {
            $url = $this->route->getUrlWithPublicAuth('payment_create_checkout');

            return $this->returnConvenienceFeesView($input, $data, $url);
        }

        return ApiResponse::json(['input' => $input, 'display' => $data]);
    }

    public function postPaymentFees()
    {
        $input = Request::all();

        $this->logPaymentRequestEvent($input);

        $this->setMerchantCallbackUrlIfApplicable($input);

        $data = $this->service(E::PAYMENT)->processAndReturnFees($input);

        unset($data['originalAmount']);

        return ApiResponse::json($data);
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

    public function postOtpResendPrivate($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->otpResend($id, $input);

        return $this->processCoprotoData($data);
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

    public function postAJAXCallback($id, $hash)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->callback($id, $hash, $input);

        return ApiResponse::json($data);
    }

    public function postOtpSubmitPrivate($id)
    {
        $hash = $this->route->getHashOf($id);

        return $this->postOtpSubmit($id, $hash);
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

    public function postRedirect3ds($id)
    {
        $data = $this->service(E::PAYMENT)->redirectTo3ds($id);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
    }

    public function postRedirectToAuthorize($id)
    {
        $data = $this->service(E::PAYMENT)->redirectToAuthorize($id);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
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

        assertTrue ($data !== null);

        return $this->returnCheckoutCallbackView($data);
    }

    protected function processCoprotoData($data)
    {
        //
        // Check for call from API
        //
        if (isset($data['request']))
        {
            if (empty($data['request']['method']) === false)
            {
                $data['request']['method'] = strtolower($data['request']['method']);
            }

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
                else if ($data['request']['method'] === 'redirect')
                {
                    return $this->redirectToPaymentPostForm($data);
                }
            }
            else if ($data['type'] === 'otp')
            {
                if ($data['request']['method'] === 'direct')
                {
                    $merchant = $this->app['basicauth']->getMerchant();
                    //
                    // For S2S headless_otp payments we return the JSON data
                    // instead of the normal view
                    //
                    if (($this->app['basicauth']->isStrictPrivateAuth() === true) and
                        ($merchant->isFeatureEnabled(Feature::S2S_OTP_JSON) === true))
                    {
                        $response = [
                            'next'                => $data['next'],
                            'razorpay_payment_id' => $data['payment_id'],
                        ];

                        return $response;
                    }

                    $response = Response::make($data['request']['content']);
                    $response->headers->set('X-gateway', $data['gateway']);

                    return $response;
                }

                $templateData = [
                   'data' => $data,
                   'cdn'  => $this->config->get('url.cdn.production'),
                   'production' => $this->app->environment() === Environment::PRODUCTION,
                ];

                return View::make('gateway.gatewayOtpPostForm')
                           ->with('data', $templateData);
            }
            else if ($data['type'] === 'return')
            {
                return $this->returnMerchantFullRedirectView($data);
            }
            else if (($data['type'] === 'async') or
                     ($data['type'] === 'intent'))
            {
                $merchantLogoUrl = $this->app['basicauth']->getMerchant()->getLogoUrl();

                if(isset($merchantLogoUrl))
                {
                    $data['merchant_logo_url'] = $merchantLogoUrl;
                }

                $templateData = [
                    'data' => $data,
                    'api'  => $this->config->get('url.api.production')
                ];

                return View::make('gateway.gatewayAsyncForm')
                           ->with('data', $templateData);
            }
            else if ($data['type'] === 'respawn')
            {
                if ($data['method'] === Payment\Method::WALLET)
                {
                    return View::make('gateway.gatewayWalletForm')
                               ->with('data', $data);
                }
                else if ($data['method'] === Payment\Method::EMANDATE)
                {
                    return View::make('emandate.form')
                               ->with('data', $data);
                }
                else if ($data['method'] === Payment\Method::UPI)
                {
                    return View::make('gateway.gatewayUpiForm')
                               ->with('data', [
                                    'key'  => $this->ba->getPublicKey(),
                                    'data' => $data,
                                    'cdn'  => $this->config->get('url.cdn.production')
                               ]);
                }
                else if (($data['method'] === Payment\Method::CARDLESS_EMI) or
                         ($data['method'] === Payment\Method::PAYLATER))
                {
                    if ((isset($data['missing']) === true) and
                        (in_array('contact' , $data['missing'], true) === true))
                    {
                        $data['cdn'] = $this->config->get('url.cdn.production');

                        return View::make('gateway.gatewayCardlessEmiForm')
                                   ->with('data', $data);
                    }
                    $templateData = [
                       'data'       => $data,
                       'cdn'        => $this->config->get('url.cdn.production'),
                       'production' => $this->app->environment() === Environment::PRODUCTION,
                    ];

                    return View::make('gateway.gatewayOtpPostForm')
                               ->with('data', $templateData);
                }
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

    protected function processCoprotoJsonData($data)
    {
        if (isset($data['request']) === true)
        {
            if (($data['type'] === 'first') and
                ($data['request']['method'] === 'redirect'))
            {
               return $this->generateRedirectJson($data);
            }
            elseif ($data['type'] === 'otp')
            {
              return $this->generateOtpJson($data);
            }
        }

        return $data;
    }

    protected function generateRedirectJson($data)
    {
        $response = [];

        $response['razorpay_payment_id'] = $data['payment_id'];

        $next = [
            [
                "action" => "redirect",
                "url"    => $data['request']['url'],
            ],
        ];

        $response['next'] = $next;

        return $response;
    }

    protected function generateOtpJson($data)
    {
        // TODO: Create Contant file
        $otpResend = 'otp_resend';
        $otpSubmit = 'otp_submit';
        $redirect  = 'redirect';

        $response = [];

        $response['razorpay_payment_id'] =  $data['payment_id'];

        if (in_array($otpSubmit, $data['next'], true) === true)
        {
            $response['next'][] = [
                'action' => $otpSubmit,
                'url'    => $data['submit_url_private'],
            ];
        }

        if (in_array($otpResend, $data['next'], true) === true)
        {
            $response['next'][] = [
                'action' => $otpResend,
                'url'    => $data['resend_url_private'],
            ];
        }

        if (empty($data['redirect']) === false)
        {
            $response['next'][] = [
                'action' => $redirect,
                'url'    => $data['redirect'],
            ];
        }

        return $response;
    }

    protected function logResponseIfApplicable($ret)
    {
        try
        {
            $merchant = $this->app['basicauth']->getMerchant();

            if ($merchant->isFeatureEnabled(Feature::LOG_RESPONSE) === true)
            {
                $dataToTrace = [];

                if (is_array($ret) === true)
                {
                    $dataToTrace = json_encode($ret);
                }
                else
                {
                    $responseClassName = get_class($ret);

                    switch ($responseClassName)
                    {
                        case 'Illuminate\View\View':
                            $dataToTrace = $ret->render();
                            break;

                        case 'Illuminate\Http\Response':
                        case 'Illuminate\Http\RedirectResponse':
                            $dataToTrace = $ret->getContent();
                            break;

                        default:
                            $dataToTrace = $ret;
                    }
                }

                $pattern = ['/\"[0-9]{14,19}\"/', '/\"[0-9]{3}\"/'];

                $replacement = '"**redacted**"';

                $responseToTrace = preg_replace($pattern, $replacement, $dataToTrace);

                if ($responseToTrace !== null)
                {
                    $this->trace->info(
                        TraceCode::PAYMENT_CREATED_RESPONSE,
                        [
                            'response'      => $responseToTrace,
                            'merchant_id'   => $merchant->getId(),
                            'route_name'    => $this->app['router']->currentRouteName(),
                        ]);
                }
            }
        }
        catch (\Throwable $e)
        {
            // Ignore
        }
    }

    protected function redirectToGatewayPostForm($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();
        $postFormData = $data;
        $postFormData['theme']['color'] = $merchant->getBrandColorElseDefault();
        $postFormData['name'] = $merchant->getBillingLabel();
        $postFormData['nobranding'] = $merchant->isFeatureEnabled(Feature::PAYMENT_NOBRANDING);
        $postFormData['production'] = $this->app->environment() === Environment::PRODUCTION;
        $postFormData['merchant_id'] = $merchant->getId();

        return View::make('gateway.gatewayPostForm')
                   ->with('data', $postFormData);
    }

    protected function redirectToPaymentPostForm($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();
        $postFormData = $data;
        $postFormData['theme']['color'] = $merchant->getBrandColorElseDefault();
        $postFormData['name'] = $merchant->getBillingLabel();
        $postFormData['nobranding'] = $merchant->isFeatureEnabled(Feature::PAYMENT_NOBRANDING);
        $postFormData['production'] = $this->app->environment() === Environment::PRODUCTION;
        $postFormData['merchant_id'] = $merchant->getId();

        return View::make('public.paymentRedirectPostForm')
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
                   ->with('input', array_assoc_flatten($input, "%s[%s]"))
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
            $callbackInput['callback_url'] = $input['callback_url'];

            // This will throw bad request validation error
            (new Payment\Validator)->validateInput('callback_url_validation', $callbackInput);

            $this->app['rzp.merchant_callback_url'] = $input['callback_url'];
        }
    }

    protected function logPaymentRequestEvent(array $input)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $properties = [
            'payment' => $input,
            'merchant' => [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
                'mcc'       => $merchant->getCategory(),
                'category'  => $merchant->getCategory2(),
            ]
        ];

        $this->app['diag']->trackPaymentEvent(EventCode::PAYMENT_CREATION_INITIATED, null, null, $properties);
    }
}
