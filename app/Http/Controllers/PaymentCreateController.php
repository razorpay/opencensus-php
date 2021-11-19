<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Redirect;
use Response;
use Request;
use App;
use RZP\Http\CheckoutView;
use RZP\Models\Payment\Method;
use RZP\Models\Settlement\Merchant;
use View;
use Crypt;

use RZP\Exception;
use RZP\Models\Feature\Constants as Feature;
use RZP\Constants\Entity as E;
use RZP\Constants\Environment;
use RZP\Diag\EventCode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Trace\Tracer;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Models\Locale\Core as LocaleCore;

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
     * Creates an S2S Nach register payment
     */
    public function postCreateS2SNachRegisterPayment()
    {
        $input = Request::all();

        $traceInput = $input;

        if (empty($traceInput['file']))
        {
            unset($traceInput['file']);
        }

        $this->logPaymentRequestEvent($traceInput);

        $data = $this->service(E::PAYMENT)->processNachRegister($input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoJsonData($data);

        $this->logResponseIfApplicable($response);

        return ApiResponse::json($response);
    }

    /**
     * Creates an S2S payment and return json response
     */
    public function postCreateS2SJsonPayment()
    {
        $input = Request::all();

        $this->logPaymentRequestEvent($input);

        $data = $this->createPaymentWihoutCoproto($input);

        if ((isset($data['processed_via_pg_router'])) === true and
            ($data['processed_via_pg_router'] === true))
        {
            unset($data['processed_via_pg_router']);

            return ApiResponse::json($data);
        }

        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoJsonData($data);

        $this->logResponseIfApplicable($response);

        return ApiResponse::json($response);
    }

     /**
     * Creates an checkout json payment and return json response
     */
    public function postCreateCheckoutJsonPayment()
    {
        $input = Request::all();

        $this->logPaymentRequestEvent($input);

        $startTime = microtime(true);

        (new Payment\Metric())->pushCheckoutSubmitRequestMetrics($input, $startTime);

        $data = $this->createPaymentWihoutCoproto($input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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
        $merchant =  $this->app['basicauth']->getMerchant();

        $templateData = (new CheckoutView())->addOrgInformationInResponse($merchant);

        $templateData['data']['nobranding'] = $merchant->isFeatureEnabled(Feature::PAYMENT_NOBRANDING);

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.gatewayAsyncForm',
            ]);

        return View::make('gateway.gatewayAsyncForm')
                ->with('data', $templateData);
    }

    protected function coreCreatePayment()
    {
        $input = Request::all();

        $startTime = microtime(true);

        (new Payment\Metric())->pushCheckoutSubmitRequestMetrics($input, $startTime);

        $this->setMerchantCallbackUrlIfApplicable($input);

        $merchant = $this->app['basicauth']->getMerchant();

        if (($merchant->isFeeBearerCustomerOrDynamic() === true) and
            (isset($input['fee']) === false))
        {
            $input['view'] = 'html';

            $this->logPaymentRequestEvent($input, true);

            return $this->createFeeBearerCustomerPayment($input);
        }

        $this->logPaymentRequestEvent($input);

        $data = $this->service(E::PAYMENT)->process($input);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
    }

    /*
     * Wrap core logic of `coreCreatePayment` with tracing instrumentation
     */
    protected function createPayment()
    {
        return Tracer::inSpan(['name' => 'payment.create'], function() {
           return $this->coreCreatePayment();
        });
    }

    protected function corecreatePaymentWihoutCoproto($input)
    {
        return $this->service(E::PAYMENT)->process($input);
    }

    protected function createPaymentWihoutCoproto($input)
    {
        return Tracer::inSpan(['name' => 'payment.create'], function() use ($input) {
           return $this->corecreatePaymentWihoutCoproto($input);
        });
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

        $data = $this->createPaymentWihoutCoproto($input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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

        $startTime = microtime(true);

        (new Payment\Metric())->pushCheckoutSubmitRequestMetrics($input, $startTime);

        $data = $this->service(E::PAYMENT)->process($input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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

        // Adding extra param to segregate payment_create events from calculate fees events

        $this->logPaymentRequestEvent($input, true);

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

        // Adding extra param to segregate payment_create events from calculate fees events

        $this->logPaymentRequestEvent($input, true);

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

        $data = $this->service(E::PAYMENT)->processAndReturnFees($input);

        $merchant =  $this->app['basicauth']->getMerchant();

        // Converts all the amounts to rupees
        foreach ($data as $key => $value)
        {
            $data[$key] = $value / 100;
        }

        if(isset($data['customer_fee']) === true)
        {
            $data['razorpay_fee'] = $data['customer_fee'];

            $data['tax'] = $data['customer_fee_gst'];

            $data['fees'] = $data['razorpay_fee'] +  $data['tax'];

            unset($data['customer_fee'], $data['customer_fee_gst']);
        }

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        unset($data['originalAmount']);

        return ApiResponse::json($data);
    }

    /**
     * Resend OTP for a payment
     */
    public function postOtpResend($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->otpResend($id, $input);

        return ApiResponse::json($data);
    }

    public function postOtpResendS2SJson($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->otpResend($id, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoJsonData($data);

        return ApiResponse::json($response);
    }

    /**
     * Generate OTP for a payment
     */

    public function postOtpGenerate($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->otpGenerate($id, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoJsonData($data);

        $this->logResponseIfApplicable($response);

        return ApiResponse::json($response);
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

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return ApiResponse::json($data);
    }

    /*
     * Topup Wallet for a payment
     */
    public function postTopup($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->topup($id, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return ApiResponse::json($data);
    }

    public function postOtpSubmitPrivate($id)
    {
        $hash = $this->route->getHashOf($id);

        $input = Request::all();

        // Type should be OTP since it's an OTP callback
        $input['type'] = 'otp';

        $data = $this->service(E::PAYMENT)->processOtpSubmitPrivate($id, $hash, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return ApiResponse::json($data);
    }

    public function postOtpSubmit($id, $hash)
    {
        $input = Request::all();

        // Type should be OTP since it's an OTP callback
        $input['type'] = 'otp';

        $data = $this->service(E::PAYMENT)->callback($id, $hash, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return ApiResponse::json($data);
    }

    public function postRedirectCallback($id)
    {
        $data = $this->service(E::PAYMENT)->redirectCallback($id);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return $this->returnCallbackResponse($data);
    }

    public function postRedirect3ds($id)
    {
        $data = $this->service(E::PAYMENT)->redirectTo3ds($id);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
    }

    public function getRedirectToDCCInfo($id)
    {
        $response = [];

        $response['data'] = $this->service(E::PAYMENT)->redirectToDCCInfo($id);

        $merchant =  $this->app['basicauth']->getMerchant();

        $response['org_info'] = (new CheckoutView())->addOrgInformationInResponse($merchant);

        $languageCode = App::getLocale() !== null ?
            App::getLocale() :
            LocaleCore::setLocale($response, $this->app['basicauth']->getMerchant()->getId());

        $response['production'] = $this->app->environment() === Environment::PRODUCTION;
        $response['cdn'] = $this->config->get('url.cdn.production');
        $response['language_code'] = $languageCode;

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'dcc view create via'   =>  'gateway.gatewayDccSelectorForm',
            ]);

        return View::make('gateway.gatewayDccSelectorForm')
            ->with('data', $response);
    }

    public function postRedirectToAuthorize($id)
    {
        $data = $this->service(E::PAYMENT)->redirectToAuthorize($id);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
    }

    public function postUpdateAndRedirectToAuthorize($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->updateAndRedirectToAuthorize($id, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
    }

    public function redirectToAuthorizeFromMandateHQ($id, $hash)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->redirectToAuthorizeFromMandateHQ($id, $hash, $input);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView)->addOrgInformationInResponse($merchant);

        $response = $this->processCoprotoData($data);

        $this->logResponseIfApplicable($response);

        return $response;
    }

    public function postAuthorize($id)
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->authorizePayment($input, $id);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return ApiResponse::json($data);
    }

    public function getAuthenticateUrl($id)
    {
        $data = $this->service(E::PAYMENT)->getAuthenticateUrl($id);

        $merchant =  $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return $data;
    }

    public function handleMandateHQCallback()
    {
        $rawContent = Request::getContent();

        $headers = Request::header();

        $env = $this->app->environment();

        if ($env !== 'testing')
        {
            $receivedSignature = $headers['x-razorpay-signature'][0] ?? '';

            $expectedSignature = hash_hmac(HashAlgo::SHA256,  $rawContent, config('applications.mandate_hq.webhook_secret'));

            if ($receivedSignature !== $expectedSignature)
            {
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
            }
        }

        $input = Request::all();

        $data = $this->service(E::PAYMENT)->handleMandateHQCallback($input);

        return $data;
    }

    protected function returnCallbackResponse($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

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
        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        if ((isset($data['processed_via_pg_router'])) === true and
            ($data['processed_via_pg_router'] === true))
        {
            unset($data['processed_via_pg_router']);

            if (isset($data['html']) === true)
            {
                return $data['html'];
            }

            return $data;
        }

        $languageCode = App::getLocale() !== null ?
            App::getLocale() :
            LocaleCore::setLocale($data, $this->app['basicauth']->getMerchant()->getId());

        //
        // Check for call from API
        //
        if (isset($data['request']))
        {
            $data['language_code'] = $languageCode;

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
                    if (isset($data['gateway']) === true)
                    {
                        list($gateway, $time) = explode('__', \Crypt::decrypt($data['gateway']));

                        if (Payment\Gateway::isGatewayPhonepeSwitch($gateway) === true)
                        {
                            return $this->redirectToPhonepeSwitchGetUrl($data);
                        }

                        if (Payment\Gateway::isGatewaySupportingGetRedirectForm($gateway) === true)
                        {
                            return $this->redirectToGatewayGetForm($data);
                        }
                    }

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

                    if (($this->app['basicauth']->isStrictPrivateAuth() === true) and
                        ($merchant->isFeatureEnabled(Feature::S2S_JSON) === true))
                    {
                        $response = $this->generateOtpJson($data);
                        return $response;
                    }

                    $response = Response::make($data['request']['content']);
                    $response->headers->set('X-gateway', $data['gateway']);

                    return $response;
                }

                $templateData = [
                   'data'          => $data,
                   'cdn'           => $this->config->get('url.cdn.production'),
                   'production'    => $this->app->environment() === Environment::PRODUCTION,
                   'language_code' => $languageCode,
                ];

                $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                    [
                        'view create via'   =>  'gateway.gatewayOtpPostForm',
                    ]);

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
                $merchant = $this->app['basicauth']->getMerchant();
                $merchantLogoUrl = $merchant->getFullLogoUrlWithSize();
                $data['nobranding'] = $merchant->isFeatureEnabled(Feature::PAYMENT_NOBRANDING);

                if (isset($merchantLogoUrl) === true)
                {
                    $data['merchant_logo_url'] = $merchantLogoUrl;
                }

                $templateData = [
                    'data' => $data,
                    'api'  => $this->config->get('url.api.production')
                ];

                $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                    [
                        'view create via'   =>  'gateway.gatewayAsyncForm',
                    ]);

                return View::make('gateway.gatewayAsyncForm')
                           ->with('data', $templateData);
            }
            else if ($data['type'] === 'respawn')
            {
                if ($data['method'] === Payment\Method::WALLET)
                {
                    $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                        [
                            'view create via'   =>  'gateway.gatewayWalletForm',
                        ]);

                    return View::make('gateway.gatewayWalletForm')
                               ->with('data', $data);
                }
                else if ($data['method'] === Payment\Method::EMANDATE)
                {
                    $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                        [
                            'view create via'   =>  'emandate.form',
                        ]);

                    return View::make('emandate.form')
                               ->with('data', $data);
                }
                else if ($data['method'] === Payment\Method::UPI)
                {
                    $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                        [
                            'view create via'   =>  'gateway.gatewayUpiForm',
                        ]);

                    return View::make('gateway.gatewayUpiForm')
                               ->with('data', [
                                    'key'          => $this->ba->getPublicKey(),
                                    'data'         => $data,
                                    'cdn'          => $this->config->get('url.cdn.production'),
                                    'language_code' => $languageCode
                               ]);
                }
                else if (($data['method'] === Payment\Method::CARDLESS_EMI) or
                         ($data['method'] === Payment\Method::PAYLATER))
                {
                    if ((isset($data['missing']) === true) and
                        (in_array('contact' , $data['missing'], true) === true))
                    {
                        $data['cdn'] = $this->config->get('url.cdn.production');

                        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                            [
                                'view create via'   =>  'gateway.gatewayCardlessEmiForm',
                            ]);

                        return View::make('gateway.gatewayCardlessEmiForm')
                                   ->with('data', $data);
                    }
                    $templateData = [
                       'data'          => $data,
                       'cdn'           => $this->config->get('url.cdn.production'),
                       'production'    => $this->app->environment() === Environment::PRODUCTION,
                       'language_code' => $languageCode,
                    ];

                    $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                        [
                            'view create via'   =>  'gateway.gatewayOtpPostForm',
                        ]);

                    return View::make('gateway.gatewayOtpPostForm')
                               ->with('data', $templateData);
                }
                else if ($data['method'] === Payment\Method::EMI)
                {
                    if ((isset($data['missing']) === true) and
                        (in_array('contact', $data['missing'], true) === true)) {
                        $data['cdn'] = $this->config->get('url.cdn.production');

                        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
                            [
                                'view create via'   =>  'gateway.gatewayCardlessEmiForm',
                            ]);

                        // Here, we use the same view as we use for cardless EMI form
                        // for accepting OTP for EMI payments
                        return View::make('gateway.gatewayCardlessEmiForm')
                            ->with('data', $data);
                    }
                }
            }
            else if ($data['type'] === 'application')
            {
                unset($data['language_code']);

                if ((isset($data['application_name']) === true) and
                    ($data['application_name'] === 'google_pay'))
                {
                    if ($data['redirect'] === true)
                    {
                        return $this->generateApplicationRedirectResponse($data);
                    }

                    return $data;
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
                $merchant = $this->app['basicauth']->getMerchant();

                if ($merchant->isFeatureEnabled(Feature::JSON_V2) === true)
                {
                    return $this->generateOtpJsonV2($data);
                }

                return $this->generateOtpJson($data);
            }
            elseif (($data['type'] === 'intent') or
                    ($data['type'] === 'async'))
            {
                return $this->generateUpiJson($data);
            }

            elseif ($data['type'] === 'application')
            {
                return $this->generateApplicationJson($data);
            }
        }

        return $data;
    }

    protected function generateUpiJson($data)
    {
        $response = [];

        $response['razorpay_payment_id'] = $data['payment_id'];

        $next = [];

        if ($data['type'] === 'intent')
        {
            array_push($next,
                [
                "action" => "intent",
                "url"    => $data['data']['intent_url'],
                ]);
        }

        $pollUrl = $this->route->getUrl('payment_fetch_by_id', ['id' => $data['payment_id']]);

        array_push($next,
            [
                "action" => "poll",
                "url"    => $pollUrl,
            ]);

        $response['next'] = $next;

        return $response;
    }


    protected function generateRedirectJson($data)
    {
        $metadata = 'metadata';

        $response = [];

        $response['razorpay_payment_id'] = $data['payment_id'];

        $next = [
            [
                'action' => 'redirect',
                'url'    => $data['request']['url'],
            ],
        ];

        if (empty($data['request']['otp_generate_url']) === false)
        {
            array_push($next, [
                'action' => 'otp_generate',
                'url'    => $data['request']['otp_generate_url'],
            ]);

            if (empty($data[$metadata]) === false)
            {
                $response[$metadata] = $data[$metadata];
            }
        }

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

    protected function generateOtpJsonV2($data)
    {
        $otpResend = 'otp_resend';
        $otpSubmit = 'otp_submit';
        $metadata  = 'metadata';

        $response = [];

        $response['razorpay_payment_id'] =  $data['payment_id'];

        if (in_array($otpSubmit, $data['next'], true) === true)
        {
            $response['next'][] = [
                'action' => $otpSubmit,
                'url'    => $data['submit_url'],
            ];
        }

        if (in_array($otpResend, $data['next'], true) === true)
        {
            $response['next'][] = [
                'action' => $otpResend,
                'url'    => $data['resend_url_json'],
            ];
        }

        if (empty($data[$metadata]) === false)
        {
            $response[$metadata] = $data[$metadata];
        }

        return $response;
    }

    protected function generateApplicationJson($data)
    {
        $response = [];

        $response['razorpay_payment_id'] = $data['payment_id'];

        $next = [];

        if ((isset($data['application_name']) === false))
        {
            return ;
        }

        if ($data['application_name'] === 'google_pay')
        {
            $next = $this->generateGooglePayNextActionList($data);
        }

        $response['next'] = $next;

        return $response;
    }

    protected function generateGooglePayNextActionList($data)
    {
        $request = $data['request'];

        $next = [];

        if(isset($request['method']) && $request['method'] === 'sdk')
        {
            array_push($next,
                [
                    "action"       => "invoke_sdk",
                    "provider"     => "google_pay",
                    "data"         => $this->generateGooglePayS2sData($request['content'][0]['allowedPaymentMethods']),
                ],
                [
                    "action"       => "poll",
                    "url"          => $this->route->getUrl('payment_fetch_by_id', ['id' => $data['payment_id']]),
                ]
            );
        }

        return $next;
    }

    protected function generateGooglePayS2sData(array $methodsData)
    {
        $data = [];

        foreach ($methodsData as $methodData)
        {
            $type                           = strtolower($methodData['type']);
            $parameters                     = $methodData['parameters'];
            $tokenizationSpecification      = $methodData['tokenizationSpecification'];

            $typeData = [];

            switch ($type)
            {
                case Method::CARD:
                    $typeData = [
                        'supported_networks'            => $parameters['allowedCardNetworks'],
                        'gateway_reference_id'          => $tokenizationSpecification['parameters']['gatewayTransactionId'],
                    ];

                    break;

                case Method::UPI:
                    $typeData = [
                        'payee_vpa'                     => $parameters['payeeVpa'],
                        'mcc'                           => $parameters['mcc'],
                        'gateway_reference_id'          => $parameters['transactionReferenceId'],
                    ];

                    break;
            }

            $data = array_add($data, $type, $typeData);
        }

        $googlePayData['google_pay'] = $data;

        return $googlePayData;
    }

    protected function generateApplicationRedirectResponse($data)
    {
        $request = $data['request'];

        $response = [];

        if(isset($request['method']) && $request['method'] === 'sdk')
        {
            $response = [
                'razorpay_payment_id'   => $data['payment_id'],
                'provider'              => $data['application_name'],
                'data'                  => $this->generateGooglePayS2sData($request['content'][0]['allowedPaymentMethods']),
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
                    $traceContext = [
                            'response'      => $responseToTrace,
                            'merchant_id'   => $merchant->getId(),
                            'route_name'    => $this->app['router']->currentRouteName(),
                        ];
                    $this->trace->info(
                        TraceCode::PAYMENT_CREATED_RESPONSE,
                        $traceContext
                    );
                    foreach ($traceContext as $key => $value) {
                        Tracer::addAttribute($key, $value);
                    }
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
        $postFormData['language_code'] = $data['language_code'];

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.gatewayPostForm',
            ]);

        return View::make('gateway.gatewayPostForm')
                   ->with('data', $postFormData);
    }

    protected function redirectToGatewayGetForm($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();
        $postFormData = $data;
        $postFormData['theme']['color'] = $merchant->getBrandColorElseDefault();
        $postFormData['name'] = $merchant->getBillingLabel();
        $postFormData['nobranding'] = $merchant->isFeatureEnabled(Feature::PAYMENT_NOBRANDING);

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.gatewayGetForm',
            ]);

        return View::make('gateway.gatewayGetForm')
            ->with('data', $postFormData);
    }

    protected function redirectToPhonepeSwitchGetUrl($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();
        $postFormData = $data;
        $postFormData['production'] = $this->app->environment() === Environment::PRODUCTION;
        $postFormData['merchant_id'] = $merchant->getId();

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.phonepeSwitchFormSubmit',
            ]);

        return View::make('gateway.phonepeSwitchFormSubmit')
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

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'public.paymentRedirectPostForm',
            ]);

        return View::make('public.paymentRedirectPostForm')
                   ->with('data', $postFormData);
    }

    /**
     * This contains the json response and does a call to the parent/checkout
     * window.
     */
    protected function returnCheckoutCallbackView($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        if (Payment\Gateway::isNachNbResponseFlow($data) === true)
        {
            return $this->returnNachNbCallbackView($data);
        }

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.callback',
            ]);

        return View::make('gateway.callback')->with('data', $data);
    }

    /**
     * Redirect to the url provided by the merchant.
     */
    protected function returnMerchantFullRedirectView($data)
    {
        if (Payment\Gateway::isNachNbResponseFlow($data) === true)
        {
            return $this->returnNachNbRedirectView($data);
        }

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.callbackReturnUrl',
            ]);

        return View::make('gateway.callbackReturnUrl')->with('data', $data);
    }

    protected function returnNachNbCallbackView($data)
    {
        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.callbackNachNb - callback view',
            ]);

        return View::make('gateway.callbackNachNb')->with('data', $data);
    }

    protected function returnNachNbRedirectView($data)
    {
        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.callbackNachNb - redirect view',
            ]);

        return View::make('gateway.callbackNachNb')->with('data', $data);
    }

    protected function returnConvenienceFeesView($input, $data, $url)
    {
        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.gatewayFeesForm',
            ]);

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

            if($this->shouldSkipCallbackValidation($input) === true)
            {
                return;
            }
            // This will throw bad request validation error
            (new Payment\Validator)->validateInput('callback_url_validation', $callbackInput);

            $this->app['rzp.merchant_callback_url'] = $input['callback_url'];
        }
    }

    protected function shouldSkipCallbackValidation(array $input)
    {
        if((empty($input['method']) === false) and ($input['method'] == Method::APP) and
            (empty($input['provider']) === false) and ($input['provider'] == Payment\Gateway::CRED))
        {
            return true;
        }
        return false;
    }

    protected function logPaymentRequestEvent(array $input, bool $customerFeeBearer = false)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $properties = [
            'payment' => $input,
            'merchant' => [
                'id'        => $merchant->getId(),
                'name'      => $merchant->getBillingLabel(),
                'mcc'       => $merchant->getCategory(),
                'category'  => $merchant->getCategory2(),
            ],
        ];

        if($customerFeeBearer === true)
        {
            $properties['customer_fee_bearer'] = true;
        }

        $metaDetails =[
            'metadata'  => $properties,
            'read_key'  => array() ,
            'write_key' => 'trackId',
        ];

        $metaDetails['metadata']['trackId'] = $this->app['req.context']->getTrackId();

        $this->trace->info(TraceCode::PAYMENT_CREATION_STARTED,[
            "merchant_id" => $merchant->getId(),
        ]);

        $this->app['diag']->trackPaymentEventV2(EventCode::PAYMENT_CREATION_INITIATED, null, null, $metaDetails, $properties);
    }
}
