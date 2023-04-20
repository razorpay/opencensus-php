<?php

namespace RZP\Http\Controllers;

use App;
use RZP\Constants\Tracing;
use RZP\Http\Request\Requests;
use ApiResponse;
use Request as Req;
use Illuminate\Http\Request;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Order\Core;
use RZP\Models\Payment\Entity;
use RZP\Trace\TraceCode;
use RZP\Services\CredcaseSigner;

class PlinkController extends Controller
{
    const CONTENT_TYPE_JSON = 'application/json';

    const MERCHANT_ID = 'merchant_id';

    const PUBLIC_KEY = 'public_key';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $key;

    /*
     * @var string
     */
    protected $secret;

    protected $ba;

    protected $timeOut;

    public function __construct()
    {
        parent::__construct();

        $plinkConfig  = $this->config->get('applications.payment_links');
        $this->baseUrl = $plinkConfig['url'];
        $this->key     = $plinkConfig['username'];
        $this->secret  = $plinkConfig['secret'];
        $this->timeOut = $plinkConfig['timeout'];
        $this->ba      = $this->app['basicauth'];
    }

    public function sendRequest(Request $request, $param = null)
    {
        $params = $this->getRequestParams($request);

        try {
            $response = Requests::request(
                $params['url'],
                $params['headers'],
                $params['data'],
                $params['method'],
                $params['options']);

            return $this->parseAndReturnResponse($response);
        }
        catch(\Throwable $e)
        {
            throw new Exception\ServerErrorException(
                'Error on payment link service',
                ErrorCode::SERVER_ERROR_PAYMENT_LINK_SERVICE_FAILURE,
                null,
                $e
            );
        }

        return ApiResponse::json($res);
    }

    public function plDemo(Request $request)
    {
        $input = $request->post();

        $this->validateCaptcha($input);

        $request->request->remove('captcha');

        return $this->sendRequest($request);
    }

    protected function validateCaptcha(array $input)
    {
        $app = App::getFacadeRoot();

        if ($app->environment('production') === false)
        {
            return;
        }

        if(isset($input['captcha']) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_TOKEN_NOT_PRESENT
            );
        }

        $captchaResponse = $input['captcha'];

        $clientIpAddress = $_SERVER['HTTP_X_IP_ADDRESS'] ?? $app['request']->ip();

        $noCaptchaSecret = config('app.pl_demo.nocaptcha_secret');

        $input = [
            'secret'   => $noCaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $clientIpAddress,
        ];

        $captchaQuery = http_build_query($input);

        $url = 'https://www.google.com/recaptcha/api/siteverify?'. $captchaQuery;

        $response = \Requests::get($url);

        $output = json_decode($response->body);

        if($output->success !== true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CAPTCHA_FAILED,
                null,
                [
                    'output_from_google'        => (array)$output,
                ]
            );
        }
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $contentType  = $res->headers['content-type'];

        if ($res->is_redirect() === true)
        {
           $location = $res->headers['Location'];

            return \Redirect::to($location);
        }

        if (str_contains($contentType, self::CONTENT_TYPE_JSON) === true)
        {
            $res = json_decode($res->body, true);

            return ApiResponse::json($res, $code);
        }
        else
        {
            $res = $res->body;

            return \Response::make($res, $code);
        }
    }

    protected function getRequestParams(Request $request)
    {
        $path = $request->path();

        $url = $this->baseUrl . $path;

        $urlAppend = '?';

        if ($request->getQueryString() !== null)
        {
            $url .= $urlAppend . $request->getQueryString();

            $urlAppend = '&';
        }

        $method = $request->method();

        $headers = $this->getHeaders($request);

        $requestBody = [];

        $body = $request->post();

        if ((empty($body) === false) && ($request->method() !== Request::METHOD_GET))
        {
            $requestBody = json_encode($body);
        }

        //needed because dashboard backend passes the get params in request body
        if ((empty($body) === false) && ($request->method() === Request::METHOD_GET))
        {
            $extraParams = http_build_query( $body );

            $url .= $urlAppend .$extraParams;
        }

        $followRedirects = true;

        // this is a hack. for intent links on mobile, pl service sends 301 redirect and we have to handle that.
        // hence passing follow_redirects as false for hosted page and in the response we return redirect
        // currently only hosted page as hosted in the url
        // we need for only get requests
        if((strpos($path, 'hosted') !== false) and
            ($request->method() === Request::METHOD_GET))
        {
            $followRedirects = false;
        }

        $options = [
            'timeout'          => $this->timeOut,
            'auth'             => [$this->key, $this->secret],
            'follow_redirects' => $followRedirects,
        ];

        $this->trace->info(TraceCode::PAYMENT_LINK_SERVICE_REQUEST, ['url' => Tracing::maskUrl($url)]);

        $response = [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $requestBody,
            'options' => $options,
            'method'  => $method,
        ];

        return $response;
    }

    protected function getHeaders(Request $request): array
    {
        $headers = [
            'Accept'            => self::CONTENT_TYPE_JSON,
            'Content-Type'      => self::CONTENT_TYPE_JSON,
            'X-Razorpay-TaskId' => $this->app['request']->getTaskId(),
        ];

        if ($this->ba->getMerchantId() !== null)
        {
            $headers['X-Razorpay-MerchantId'] = $this->ba->getMerchantId();

            $merchant = $this->ba->getMerchant();

            $enabledFeatures = $merchant->getEnabledFeatures();

            $headers['X-Razorpay-Merchant-Features'] = json_encode($enabledFeatures);
        }

        $user = $this->ba->getUser();

        if ($user !== null)
        {
            $headers['X-Razorpay-UserId'] = $user->getId();

            $role = $this->ba->getUserRole();

            $headers['X-Razorpay-UserRole'] = $role;
        }

        $headers['X-Razorpay-Mode'] = $this->ba->getMode();

        $headers['X-Razorpay-Auth'] = $this->ba->getAuthType();

        $headers['User-Agent'] = $request->userAgent();

        $headers['X-User-Agent'] = $request->header('X-User-Agent');

        $headers['X-Razorpay-Public-Key'] = (new Core())->getOrderPublicKey($this->ba->getMerchant());

        $headers['X-Razorpay-Application-Id'] = $this->ba->getOAuthApplicationId();

        $requester = $request->header('X-Razorpay-Requester');

        if (empty($requester) === false)
        {
            $headers['X-Razorpay-Requester'] = $requester;
        }

        $partnerMerchantId = $this->ba->getPartnerMerchantId();

        if (empty($partnerMerchantId) === false)
        {
            $headers['X-Razorpay-Partner-Merchant-Id'] = $partnerMerchantId;
        }

        return $headers;
    }

    public function plDemoCors()
    {
        $response = ApiResponse::json([]);

        $response->headers->set('Access-Control-Allow-Origin', $this->app['config']->get('app.razorpay_website_url'));

        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }

    // This function would generate the razorpay signature for a given payload
    // This route will be removed once pl service is using the new internal auth one
	public function signPayload()
	{
        if ($this->ba->isPaymentLinkServiceApp() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND
            );
        }

	    $input = Req::all();

        ksort($input);

        $str = implode('|', $input);

        $merchant = $this->ba->getMerchant();

        $key = $this->repo->key->getFirstActiveKeyForMerchant($merchant->getId());

        $this->ba->authCreds->setKeyEntity($key);

        $response['razorpay_signature'] = (new CredcaseSigner)->sign($str);

        return $response;
	}

    // This function would generate the razorpay signature for a given payload
    // Internal auth route. Merchant id will be passed in input
    public function signPayloadInternal()
    {
        if ($this->ba->isPaymentLinkServiceApp() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_URL_NOT_FOUND
            );
        }

        $input = Req::all();

        $merchant = null;

        if (isset($input[self::MERCHANT_ID]) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT
            );
        }

        $merchant = $this->repo->merchant->findByPublicId($input[self::MERCHANT_ID]);

        // Set merchantId for the current request
        $this->ba->setMerchantById($input[self::MERCHANT_ID]);

        unset($input[self::MERCHANT_ID]);

        ksort($input);

        if (isset($input[self::PUBLIC_KEY]) === true)
        {
            $key = $input[self::PUBLIC_KEY];

            unset($input[self::PUBLIC_KEY]);

            $str = implode('|', $input);

            $response['razorpay_signature'] = (new CredcaseSigner)->sign($str, $key);

            return $response;
        }

        $str = implode('|', $input);

        $key = $this->repo->key->getFirstActiveKeyForMerchant($merchant->getId());

        $this->ba->authCreds->setKeyEntity($key);

        $response['razorpay_signature'] = (new CredcaseSigner)->sign($str);

        return $response;
    }

    public function fetchPaymentDetails($id)
    {
        $id = Entity::stripSignWithoutValidation($id);

        $merchant = $this->ba->getMerchant();
        $payment  = $this->repo->payment->findByIdAndMerchant($id, $merchant);

        $response = [
            'payment'  => $payment->toArray(),
            'discount' => isset($payment->discount) ? $payment->discount->toArrayPublic() : null,
        ];

        $response['payment']['fee_in_mcc'] = $payment->getFeeInMcc() ?? 0;

        return ApiResponse::json($response);
    }
}

