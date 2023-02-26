<?php


namespace RZP\Services;

use App;
use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Mail\System\Trace;
use RZP\Models\Base;
use RZP\Services\UfhService;
use RZP\Trace\TraceCode;
use Throwable;

class GrowthService extends Base\Service
{
    const CONTENT_TYPE_JSON = 'application/json';
    const GET_INVOICE_RECEIPT_URL = 'twirp/rzp.pricing_bundle.subscription.v1.SubscriptionAPI/GetReceiptForInvoice';

    const GET_ASSET_URL = 'twirp/rzp.growth.asset.v1.AssetAPI/Get';
    const CREATE_SUBSCRIPTION_URL = '/twirp/rzp.pricing_bundle.subscription.v1.SubscriptionAPI/Create';
    const GET_SUBSCRIPTION_URL = '/twirp/rzp.pricing_bundle.subscription.v1.SubscriptionAPI/GetByMerchantID';
    const CHECK_SUBSCRIPTION_URL = '/twirp/rzp.pricing_bundle.subscription.v1.SubscriptionAPI/Exists';
    const GET_TEMPLATE_BY_ID_URL = 'twirp/rzp.growth.template.v1.TemplateAPI/Get';
    const GET_PUBLIC_ASSET_URL = 'twirp/rzp.growth.asset.v1.AssetAPI/GetPublic';

    const EDIT_TEMPLATE_URL = 'twirp/rzp.growth.template.v1.TemplateAPI/Update';

    const GET_SUBCAMPAIGN_URL = 'twirp/rzp.growth.subcampaign.v1.SubCampaignAPI/Get';

    const SUBCAMPAIGN_ACTION_URL = 'twirp/rzp.growth.subcampaign.v1.SubCampaignAPI/Action';

    const FILTER_AND_SYNC_URL = '/twirp/rzp.growth.counting.v1.CountingAPI/FilterAndSync';

    const SLACK_CSV_SYNC_URL = '/twirp/rzp.pricing_bundle.subscription.v1.SubscriptionAPI/MerchantListToCSV';

    const ACTIVATED = 'ACTIVATED';

    // Tells the client what the content type of the returned content actually is
    const CONTENT_TYPE = 'Content-Type';

    // Specifies the method or methods allowed when accessing the resource in response to a preflight request.
    const ACCESS_CONTROL_ALLOW_METHODS = 'Access-Control-Allow-Methods';

    // Used in response to a preflight request which includes the Access-Control-Request-Headers to indicate which HTTP headers can be used during the actual request.
    const ACCESS_CONTROL_ALLOW_HEADERS = 'Access-Control-Allow-Headers';

    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';

    // Admin email parameter to be sent in all admin requests
    const ADMIN_EMAIL_PARAM_NAME = 'admin_email';
    const ADMIN_EMAIL_PARAM_HEADER = 'X-Admin-Email';

    // Max allowed file size - 1MB (1024*1024).
    const MAX_FILE_SIZE = 1048576;

    const X_SPLITZ_EXPERIMENT_REUSE      =  'X-Experiment-Reuse';

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $requestTimeout;

    protected $trace;

    protected $env;

    protected $auth;

    protected $skipPassport;

    public function __construct()
    {
        $app = App::getFacadeRoot();
        $this->trace = $app['trace'];
        $this->env = $app['env'];
        $growthConfig = $app['config']['applications.growth'];
        $this->baseUrl = $growthConfig['url'];
        $this->key = $growthConfig['username'];
        $this->secret = $growthConfig['secret'];
        $this->skipPassport = $growthConfig['skip_jwt_passport'];
        $this->requestTimeout = $growthConfig['request_timeout'];
        $this->auth = $app['basicauth'];
        $this->ufh = (new UfhService($app));
    }

    public function getAssetDetails($parameters)
    {
        return $this->sendRequest($parameters, self::GET_ASSET_URL, Requests::POST);
    }

    public function createSubscription($parameters)
    {
        return $this->sendRequest($parameters, self::CREATE_SUBSCRIPTION_URL, Requests::POST);
    }

    public function getSubscriptionByMid($parameters)
    {
        return $this->sendRequest($parameters, self::GET_SUBSCRIPTION_URL, Requests::POST);
    }

    public function checkSubscriptionByMid($parameters)
    {
        return $this->sendRequest($parameters, self::CHECK_SUBSCRIPTION_URL, Requests::POST);
    }

    public function getTemplateByIdDetails($parameters)
    {

        return $this->sendRequest($parameters, self::GET_TEMPLATE_BY_ID_URL, Requests::POST);
    }

    public function getPublicAssetDetails($parameters)
    {
        return $this->sendRequest($parameters, self::GET_PUBLIC_ASSET_URL, Requests::POST);
    }

    public function getReceiptForInvoice($parameters)
    {
        $this->skipPassport = true;
        $body = $this->sendRequest($parameters, self::GET_INVOICE_RECEIPT_URL, Requests::POST);
        if ($body['status_code'] != 200)
        {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE);
        }
        if (empty($body['response']) == true) {
            return [
                'amount' => 0,
                'tax' => 0,
            ];
        }
        return $body['response'];
    }

    /**
     * @throws Exception\ServerErrorException
     */
    public function editTemplateAndEnableDowntimeNotificationForXDashboard($parameters)
    {
        $this->skipPassport = true;
        $templateParameters = ["template" => $parameters['template']];

        $editTemplateResponse = $this->sendRequest($templateParameters, self::EDIT_TEMPLATE_URL, Requests::POST);

        if (!array_key_exists("template", $editTemplateResponse["response"])) {
            return $editTemplateResponse;
        }

        $subCampaignGetParams = ["sub_campaign_id" => $parameters['subcampaign']["sub_campaign_id"]];

        $subCampaignGetResponse = $this->sendRequest($subCampaignGetParams, self::GET_SUBCAMPAIGN_URL, Requests::POST);

        if (!array_key_exists("sub_campaign", $subCampaignGetResponse["response"])) {
            return $subCampaignGetResponse;
        }

        if ($subCampaignGetResponse["response"]["sub_campaign"]["status"] != self::ACTIVATED) {
            $subCampaignActionParams = $parameters['subcampaign'];

            $this->sendRequest($subCampaignActionParams, self::SUBCAMPAIGN_ACTION_URL, Requests::POST);
        }

        return ["status_code" => "200"];
    }

    public function filterAndSyncEventsFromPinot($parameters)
    {
        return $this->sendRequest($parameters, self::FILTER_AND_SYNC_URL, Requests::POST);
    }

    public function sendCsvFile($parameters)
    {
        return $this->sendRequest($parameters, self::SLACK_CSV_SYNC_URL, Requests::POST);
    }

    public function uploadAssets($parameters)
    {
        $file = $parameters['file'];
        $subCampaignId = $parameters['sub_campaign_id'];

        if (empty($file) || empty($subCampaignId)) {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw new Exception\BadRequestValidationFailureException('File Size exceeds max allowed size of 1MB');
        }
        $fileIdentifier = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $fileName = 'growth/' . $subCampaignId . '/' . $fileIdentifier;
        $extension = strtolower($file->getClientOriginalExtension());
        if (empty($extension) === false) {
            $fileName .= '.' . $extension;
        }

        try {
            $response = $this->ufh->uploadFileAndGetUrl($parameters['file'], $fileName, 'growth_asset', null, [], false);
        } catch (\Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the uploadAsset request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
        }
        if (!empty($response)) {
            $response["asset_url"] = "CDN_URL_PREFIX/" . $fileIdentifier . '.' . $extension;
        }
        return $response;
    }

    public function sendRequest($parameters, $path, $method)
    {
        $requestParams = $this->getRequestParams($parameters, $path, $method);

        try {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            return $this->parseAndReturnResponse($response);
        } catch (Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_GROWTH_FAILURE, null, $e);
        }
    }

    public function getRequestParams($parameters, $path, $method)
    {
        $url = $this->baseUrl . $path;

        $headers = [];

        $parameters = json_encode($parameters);

        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;
        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        $headers[self::X_SPLITZ_EXPERIMENT_REUSE] = Request::header(self::X_SPLITZ_EXPERIMENT_REUSE);

        $options = [
            'timeout' => $this->requestTimeout,
        ];

        $jwt = null;
        if ($this->skipPassport == false) {
            $jwt = $this->auth->getPassportJwt($this->baseUrl);
        }
        if ($jwt == null) {
            $options['auth'] = [$this->key, $this->secret];
        }
        $headers[self::X_PASSPORT_JWT_V1] = $jwt;

        if ($this->auth->isAdminAuth()) {
            $headers[self::ADMIN_EMAIL_PARAM_HEADER] = $this->auth->getAdmin()->getEmail() ?? '';
            $this->trace->info(TraceCode::GROWTH_ADMIN_REQUEST, ['url' => $url, 'parameters' => $parameters]);
        } else {
            $this->trace->info(TraceCode::GROWTH_REQUEST, ['url' => $url, 'parameters' => $parameters]);
        }


        return [
            'url' => $url,
            'headers' => $headers,
            'data' => $parameters,
            'options' => $options,
            'method' => $method,
        ];
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\RuntimeException('Malformed json response');
        }

        if ($code != 200)
        {
            $this->trace->info(TraceCode::GROWTH_REQUEST_FAILED, ['code' => $code, "res" => $res]);
        }

        $growthResponse = ['status_code' => $code, 'response' => $res];

        return $growthResponse;
    }

}
