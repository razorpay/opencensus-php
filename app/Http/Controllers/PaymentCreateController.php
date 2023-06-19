<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Support\Arr;
use Redirect;
use Response;
use Request;
use App;
use RZP\Http\CheckoutView;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Currency\Currency;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Customer;
use RZP\Models\Settlement\Merchant;
use RZP\Models\Merchant\Preferences;
use View;
use Crypt;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
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
use RZP\Models\Payment\TokenisationConsent;
use RZP\Models\Merchant\Checkout;

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
    public function postCreateRazorpayWalletPayment()
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
        $input = Request::all();

        $merchant = $this->app['basicauth']->getMerchant();

        $tokenisationConsent = new TokenisationConsent();

        if ((isset($input[Payment\Entity::SUBSCRIPTION_ID]) === true) or
            (isset($input[Payment\Entity::RECURRING]) === true))
        {
            if($tokenisationConsent->showRecurringTokenisationConsentView($input, $merchant) === true)
            {
                $tokenisationConsent->logRecurringTokenisationConsentViewRequest($input);

                return $tokenisationConsent->returnTokenisationConsentView($input);
            }

        } elseif ($tokenisationConsent->showTokenisationConsentView($input, $merchant) === true)
        {
            $tokenisationConsent->logTokenisationConsentViewRequest($input);

            return $tokenisationConsent->returnTokenisationConsentView($input);
        }

        $this->addDummyEmailIfApplicable($input, $merchant);

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

        $tokenisationConsent = new TokenisationConsent();

        if($tokenisationConsent->checkIfRequestIsFromTokenisationConsentView($input) === true)
        {
            $input = $tokenisationConsent->decryptCardDetails($input);
        }

        (new Payment\Metric())->pushCheckoutSubmitRequestMetrics($input, $startTime);

        $this->setMerchantCallbackUrlIfApplicable($input);

        $merchant = $this->app['basicauth']->getMerchant();

        $input = $this->setParametersBasedOnConsentToSaveCard($input, $merchant);

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

        $merchant =  $this->app['basicauth']->getMerchant();

        $this->addDummyEmailIfApplicable($input, $merchant);

        $data = $this->service(E::PAYMENT)->process($input);

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        return ApiResponse::json($data);
    }

    public function postCreatePosPayment()
    {
        $input = Request::all();

        $response = $this->service(E::PAYMENT)->postCreatePosPayment($input);

        return ApiResponse::json($response);

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
     * Creates unexpected payment for UPI
     * @return mixed
     */
    public function postCreateUpiUnexpectedPayment()
    {
        $input = Request::all();

        $response = $this->service(E::PAYMENT)->createUpiUnexpectedPayment($input);

        $response['art_request_id'] = $input['meta']['art_request_id'];

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

        $merchant =  $this->app['basicauth']->getMerchant();

        $this->addDummyEmailIfApplicable($input, $merchant);

        $this->addDummyCardIfApplicable($input);

        $data = $this->service(E::PAYMENT)->processAndReturnFees($input);

        // Converts all the amounts to rupees

        $denominationFactor = Currency::DENOMINATION_FACTOR[$data['currency']];

        if(isset($data['customer_fee']) === true)
        {
            $data['razorpay_fee'] = $data['customer_fee'];

            $data['tax'] = $data['customer_fee_gst'];

            $data['fees'] = $data['razorpay_fee'] +  $data['tax'];

            unset($data['customer_fee'], $data['customer_fee_gst']);
        }

        foreach ($data as $key => $value)
        {
            if (is_numeric($value))
            {
                $data[$key] = $value / $denominationFactor;
            }
        }

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        if ($retHtml === true)
        {
            $url = $this->route->getUrlWithPublicAuth('payment_create_checkout');

            $data['flag'] = false;

            if ($merchant->isFeatureEnabled(Feature::FEE_PAGE_TIMEOUT_CUSTOM) === true)
            {
                $data['flag'] = true;
            }

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

    public function getRedirectToAddressCollect($id)
    {
        $response = [];

        $response['data'] = $this->service(E::PAYMENT)->redirectToAddressCollect($id);

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
                'address_collect view create via'   =>  'gateway.gatewayAddressForm',
            ]);

        return View::make('gateway.gatewayAddressForm')
            ->with('data', $response);
    }

    public function postRedirectToAuthorize($id)
    {
        $input = Request::all();

        $input['ip'] = $this->app['request']->getClientIp();

        if (empty($input['rearch']) === false)
        {
            $data = $this->app['pg_router']->paymentAuthenticate($id, [], true);

            if (empty($data['html']) === false)
            {
                return $data['html'];
            }

            return $data;
        }

        //This is to ensure nothing is breaking in existing flows
        //Adding check for 3ds 2.0 second authenticate POST call
        if ((!((isset($input['provider']) === true) and ($input['provider'] === Payment\Gateway::GETSIMPL))) and (!((isset($input['browser']) === true) and (isset($input['auth_step']) === true))))
        {
            $input = [];
        }

        $data = $this->service(E::PAYMENT)->redirectToAuthorize($id, $input);
        if ((is_array($data)) and
            (isset($data['request']) === false))
        {
            if((isset($this->input['provider'])) and ($this->input['provider'] === Payment\Gateway::GETSIMPL) and ($this->app['rzp.mode'] != 'test'))
            {
                assertTrue ($data !== null);

                return $this->returnCheckoutCallbackView($data);
            }
        }

        //return callback view for Frictionless payments
        if ((is_array($data)) and (isset($data['request']) === false))
        {
            if((isset($input['browser']) === true) and (isset($input['auth_step']) === true))
            {
                $this->trace->info(TraceCode::CALLBACK_VIEW_FOR_FRICTIONLESS_FLOW,
                    [
                        'input' => $input,
                        'data'  => $data
                    ]);
                return $this->returnCheckoutCallbackView($data);
            }

            if($this->shouldReturnCallbackViewForNon3ds($id,$data)) {
                return $this->returnCheckoutCallbackView($data);
            }
        }

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

    public function chargeToken()
    {
        $input = Request::all();

        $data = $this->service(E::PAYMENT)->chargeToken($input);

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

    public function handleSihubWebhook()
    {
        $input = Request::getContent();

        $this->trace->info(
            TraceCode::PAYMENT_CREATE_ON_PUBLIC,
            ['sihub webhook input' => $input]);

        $inputData = array(
            "payload" => $input,
            "payment" => array("method"=>""),
        );

        $data = $this->service(E::PAYMENT)->handleSihubWebhook($inputData);

        return $data;
    }

    public function generateCoprotoForRearch()
    {
        $input = Request::all();

        $merchant = $this->repo->merchant->findByPublicId($input['merchant_id']);

        $languageCode = App::getLocale() !== null ?
            App::getLocale() :
            LocaleCore::setLocale($data, $merchant->getId());

        $templateData = [
               'data'          => $input,
               'cdn'           => $this->config->get('url.cdn.production'),
               'production'    => $this->app->environment() === Environment::PRODUCTION,
               'language_code' => $languageCode,
            ];

        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.gatewayOtpPostForm',
                'rearch_coproto_generation' => "rearch_acs_page"
            ]);

       return View::make('gateway.gatewayOtpPostForm')
                            ->with('data', $templateData);
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
    protected function pushForBarricade($data): void
    {
        $barricade_action = 'merchant_integration_s2s_callback';
        $barricade_merchant_integration = 'barricade_merchant_integration_s2s_callback';
        $sqsPush = $this->app->razorx->getTreatment($barricade_action, $barricade_merchant_integration, $this->app['rzp.mode']);

        if ($sqsPush === 'on') {

            $data['action'] = [
                'action' => $barricade_action
            ];

            try {
                $waitTime = 600;
                $queueName = $this->app['config']->get('queue.barricade_verify.' . $this->app['rzp.mode']);
                $this->app['queue']->connection('sqs')->later($waitTime, "Barricade Queue Push", json_encode($data), $queueName);


                $this->trace->info(TraceCode::BARRICADE_SQS_PUSH_SUCCESS,
                    [
                        'queueName' => $queueName,
                        'data' => $data,
                    ]);

            } catch (\Throwable $ex) {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::BARRICADE_SQS_PUSH_FAILURE,
                    [
                        'data' => $data,
                    ]);
            }
        }
    }
    protected function processCoprotoData($data)
    {
        $merchant = $this->app['basicauth']->getMerchant();

        $data += (new CheckoutView())->addOrgInformationInResponse($merchant);

        if ((isset($data['processed_via_pg_router'])) === true and
            ($data['processed_via_pg_router'] === true))
        {
            unset($data['processed_via_pg_router']);

            $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_PG_ROUTER, [
                'is_set_html'               => isset($data['html']),
            ]);

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

                        if((in_array($merchant->getId(), Preferences::MID_IXIGO, true) === true)
                            && ($gateway === Gateway::CARDLESS_EMI))
                        {
                            $url = $data['request']['url'];

                            $parts = parse_url($url);

                            $url_without_params = $parts['scheme'] . '://' . $parts['host'] . $parts['path'];

                            if (isset($parts['query']) === true)
                            {
                                parse_str($parts['query'], $query_params);
                                $data['request']['url'] = $url_without_params;
                                $data['request']['content'] = array_merge($query_params, 	 $data['request']['content'] ?? []);
                            }
                            return $this->redirectToGatewayGetForm($data);
                        }

                        if (Payment\Gateway::isGatewaySupportingGetRedirectForm($gateway) === true)
                        {
                            return $this->redirectToGatewayGetForm($data);
                        }
                    }

                    $response = \Redirect::away($data['request']['url']);
                    $response->headers->set('X-gateway', $data['gateway']);

                    $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_URL, [
                        'type'        => $data['type'],
                        'request_url' => strpos($data['request']['url'],"token=") ? substr_replace($data['request']['url'], '*****', strpos($data['request']['url'],"token=")+6) : $data['request']['url'] ?? '',
                    ]);

                    return $response;
                }
                else if ($data['request']['method'] === 'direct')
                {
                    $response = Response::make($data['request']['content']);
                    $response->headers->set('X-gateway', $data['gateway']);

                    $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_CONTENT, [
                        'type' => $data['type'],
                    ]);

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

                        $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_S2S_OTP, [
                            'type' => $data['type'],
                        ]);

                        return $response;
                    }

                    if (($this->app['basicauth']->isStrictPrivateAuth() === true) and
                        ($merchant->isFeatureEnabled(Feature::S2S_JSON) === true))
                    {
                        $response = $this->generateOtpJson($data);

                        $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_S2S, [
                            'type' => $data['type'],
                        ]);

                        return $response;
                    }

                    $response = Response::make($data['request']['content']);
                    $response->headers->set('X-gateway', $data['gateway']);

                    $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_CONTENT, [
                        'type' => $data['type'],
                    ]);

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
                    $data += (new CheckoutView())->addOrgInformationInResponse($merchant, true);

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

                    $data += (new CheckoutView())->addOrgInformationInResponse($merchant, true);

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
                    $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_APPLICATION, [
                        'type'      => $data['type'],
                        'redirect'  => $data['redirect'] ?? '',
                    ]);

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
            $this->trace->info(TraceCode::CHECKOUT_REDIRECTION_DATA, [
                'type'          => $data['type'] ?? '',
                'payment_id'    => $data['payment_id'] ?? '',
                'extra'         => 'No condition match.',
            ]);
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
            elseif (($data['type'] === 'respawn') and
                (($data['method'] === Payment\Method::CARDLESS_EMI) or ($data['method'] === Payment\Method::PAYLATER)))
            {
                return $this->generatePaylaterCardlessEmiJson($data);
            }
            elseif ($data['type'] === 'application')
            {
                return $this->generateApplicationJson($data);
            }
        }

        return $data;
    }

    protected function generatePaylaterCardlessEmiJson($data){

        $paymentId = $data['payment_id'];

        $strippedPaymentId = Payment\Entity::verifyIdAndStripSign($paymentId);

        $response['razorpay_payment_id'] = $data['payment_id'];

        $redirectUrl = $this->route->getUrl('payment_redirect_to_authenticate_get',['id' => $strippedPaymentId]);

        $next = [
            [
                'action' => 'redirect',
                'url' => $redirectUrl
            ],
        ];

        unset($data['payment_authenticate_url']);

        $response['next'] = $next;

        return $response;

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

                $responseToTrace = preg_replace($pattern, $replacement, $dataToTrace);// nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval

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
        $postFormData += (new CheckoutView())->addOrgInformationInResponse($merchant, true);
        $postFormData['show_independence_image'] = $this->shouldShowIndependenceDayImage();

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
        $postFormData += (new CheckoutView())->addOrgInformationInResponse($merchant, true);
        $postFormData['show_independence_image'] = $this->shouldShowIndependenceDayImage();

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
        $postFormData['show_independence_image'] = $this->shouldShowIndependenceDayImage();

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
        $merchant = $this->app['basicauth']->getMerchant();
        if ($merchant->isFeatureEnabled(Feature::AUTH_SPLIT) !== true)
        {
            $this->pushForBarricade($data);
        }

        if (Payment\Gateway::isNachNbResponseFlow($data) === true)
        {
            return $this->returnNachNbRedirectView($data);
        }

        $paymentId = $data['request']['content']['razorpay_payment_id'] ?? null;

        if (
            $paymentId !== null &&
            $this->service(E::PAYMENT)->isEmailLessCheckoutExperimentEnabled($merchant->getId())
        )
        {
            $paymentDetails = $this->service(E::PAYMENT)->getPaymentDetailsForMerchantRedirectView($paymentId);

            if (empty($paymentDetails) === false)
            {
                $data['payment_details'] = $paymentDetails;
            }
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

        $variantFlag = $this->isNpciFeedbackPopupAllowed();

        if ($variantFlag === 'on')
        {
            $data['allow_feedback'] = '1';
        }

        return View::make('gateway.callbackNachNb')->with('data', $data);
    }

    protected function returnNachNbRedirectView($data)
    {
        $this->trace->info(TraceCode::CHECKOUT_VIEW_CREATION,
            [
                'view create via'   =>  'gateway.callbackNachNb - redirect view',
            ]);

        $variantFlag = $this->isNpciFeedbackPopupAllowed();


        if ($variantFlag === 'on')
        {
            $data['allow_feedback'] = '1';
        }

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

    protected function setParametersBasedOnConsentToSaveCard(array $input, MerchantEntity $merchant): array
    {
        $library = $input['_']['library'] ?? '';
        $allowedLibraries = [Payment\Analytics\Metadata::RAZORPAYJS, Payment\Analytics\Metadata::CUSTOM];

        if (in_array($library, $allowedLibraries, true) === true)
        {
            // If it is 'Add New Card' flow & network tokenisation isn't enabled
            // on custom checkout merchant then ignore save input & do not save cards.
            if (
                Arr::hasAny($input, ['card.number', 'card.cvv']) &&
                !$merchant->isCustomCheckoutNetworkTokenisationEnabled()
            ) {
                if (Arr::hasAny($input, ['save', 'consent_to_save_card']))
                {
                    $this->trace->warning(
                        TraceCode::NETWORK_TOKENIZATION_PAID_FLAG_NOT_ENABLED,
                        [
                            'merchant_id'          => $merchant->getId(),
                            'save'                 => $input['save'] ?? '',
                            'consent_to_save_card' => $input['consent_to_save_card'] ?? '',
                        ]
                    );

                    unset($input['save'], $input['consent_to_save_card']);
                }
            }
            elseif (isset($input['consent_to_save_card']) === true)
            {
                if (isset($input['card']['number']) === true)
                {
                    $input['save'] = $input['consent_to_save_card'];
                }
                elseif ((isset($input['token']) === true) and
                    (isset($input['card']['cvv']) === true))
                {
                    $input['user_consent_for_tokenisation'] = $input['consent_to_save_card'];
                }
            }
        }

        // In case when recurring feature flag for consent is enabled we don't show consent page for fresh card.
        // this means we have to explicitly collect consent as per our contract with merchant for saving that card
        // because this is mandatory for recurring card payments even if consent_to_save_card is not passed

        if((in_array($library, $allowedLibraries, true) === true) and
            ((isset($input['subscription_id'])) or
                (isset($input['recurring']) and
                    (($input['recurring'] === '1') or ($input['recurring'] === 'preferred')))))
        {
            $this->trace->info(
                TraceCode::EXPLICIT_CONSENT_COLLECTED_RECURRING,
                    [
                        'merchant_id' => $this->app['basicauth']->getMerchantId(),
                        'library' => $library
                    ]
            );

            $input['save'] = '1';
        }

        return $input;
    }

    /**
     * show Independence day image till 20th aug 2022 , will remove after 20th aug
     */
    protected function shouldShowIndependenceDayImage()
    {
        if (Carbon::now()->getTimestamp() < 1661020200)
        {
            return true;
        }
        return false;
    }


    /**
     * Email less checkout: DUMMY_EMAIL addition to bypass payment create validations.
     *
     * Based on following conditions -
     * Standard/hosted checkout library and email empty and international payment check and
     * not optimizer merchant i.e. 'raas' feature flag disabled on the merchant and
     * (no show_email_on_checkout feature  or email_optional_on_checkout) => email required
     * Email customizations on std/hosted checkout based on feature flags -
     * show_email_on_checkout => false and email_optional_on_checkout => false ==> email-less checkout
     * show_email_on_checkout => true  and email_optional_on_checkout => false ==> email is mandatory on checkout
     * show_email_on_checkout => true  and email_optional_on_checkout => true  ==> email is optional on checkout
     * show_email_on_checkout => false and email_optional_on_checkout => true  ==> email-less checkout
     *
     * @param array          &$input
     * @param MerchantEntity $merchant
     *
     * @return void
     */
    protected function addDummyEmailIfApplicable(array &$input, MerchantEntity $merchant): void
    {
        $library = $input['_']['library'] ?? '';

        if (in_array($library, Checkout::EMAIL_LESS_CHECKOUT_ALLOWED_LIBRARIES, true) &&
            (!$merchant->isEmailShownOnCheckout() || $merchant->isEmailOptionalOnCheckout()) &&
            !isset($input['email']) &&
            $merchant->isRazorpayOrgId() &&
            (!isset($input['currency']) || ($input['currency'] === Currency::INR)) &&
            !$merchant->isFeatureEnabled(Feature::RAAS))
        {
            $input['email'] = Payment\Entity::DUMMY_EMAIL;

            $request = Request::instance();

            $request->merge(['email' => Payment\Entity::DUMMY_EMAIL]);
        }
    }

    //*CVVLess Payments for Visa and Amex Saved Card Payments
    protected function addDummyCardIfApplicable(array &$input): void
    {
        if (($input[Payment\Entity::METHOD]) == Payment\Method::CARD and (isset($input[Payment\Entity::TOKEN]) === true) and ((array_key_exists('card', $input) === false) or (!isset($input['card']))) ){

            $tokenId = $input[Payment\Entity::TOKEN];

            if (isset($input[Payment\Entity::CUSTOMER_ID]) === true) {
                $customerId = $input[Payment\Entity::CUSTOMER_ID];

                Customer\Entity::verifyIdAndStripSign($customerId);

                $token = (new Customer\Token\Core)->getByTokenIdAndCustomerId($tokenId, $customerId);

            } else {

                $token = (new Customer\Token\Core)->getByTokenId($tokenId);
            }

            if ($this->isCardAbsentforTokenisedPayment($token)) {
                $input['card'] = [];
            }

            $this->trace->info(TraceCode::TRACK_CARD_OPTIONAL_CFB_FLOW, [
                'token' => $token->getId(),
            ]);
        }
    }

    // set dummy card for amex and visa payments which do not have card object
    protected function  isCardAbsentforTokenisedPayment($token) : bool
    {
        if(($token->card->isAmex() || $token->card->isVisa()) || $token->card->isRuPay() || $token->card->isMasterCard())
        {
            return true;
        }
        return false;
    }

    protected function shouldReturnCallbackViewForNon3ds($id,$data): bool {
        try {

            $merchant = $this->app['basicauth']->getMerchant();

            $response = $this->service(E::PAYMENT)->GetPaymentDetailsForCallbackView($id);
            /** removing check on libraries for now
            $libraries = [Payment\Analytics\Metadata::CHECKOUTJS,Payment\Analytics\Metadata::HOSTED];
            **/
            if ((isset($data['razorpay_payment_id']) === true) and
                (isset($response['is_international']) === true) and
                (isset($response['method']) === true) and
                ($response['is_international'] === true) and
                ($response['method'] === Payment\Method::CARD)) {
                $this->trace->info(
                    TraceCode::CALLBACK_VIEW_ON_3DS_PAYMENT, [
                        "payment_id" => $id,
                        "merchant_id" => $merchant->id
                    ]
                );
                return true;
            }
        } catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::CALLBACK_VIEW_ON_3DS_PAYMENT_ERROR,
                [
                    "payment_id" => $id,
                    "merchant_id" => $merchant->id
                ]
            );
        }

        return false;
    }

    protected function isNpciFeedbackPopupAllowed()
    {
        try
        {
            $merchant = $this->app['basicauth']->getMerchant();

            $variantFlag = $this->app['razorx']->getTreatment($merchant->getId(),
                RazorxTreatment::ALLOW_NPCI_FEEDBACK_POPUP,
                $this->app['rzp.mode']);

            $this->trace->info(
                TraceCode::EMANDATE_ALLOW_NPCI_FEEDBACK_RAZORX_SUCCESS,
                [
                    'variant' => $variantFlag
                ]);

            return $variantFlag;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::EMANDATE_ALLOW_NPCI_FEEDBACK_RAZORX_FAILURE,
                [
                    'error' => $e,
                ]);

            return 'control';
        }
    }
}
