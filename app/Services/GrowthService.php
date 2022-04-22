<?php


namespace RZP\Services;

use App;
use Request;
use ApiResponse;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Throwable;

class GrowthService extends Base\Service
{
    const CONTENT_TYPE_JSON = 'application/json';

    const GET_ASSET_URL = 'twirp/rzp.growth.asset.v1.AssetAPI/Get';
    const GET_TEMPLATE_BY_ID_URL = 'twirp/rzp.growth.template.v1.TemplateAPI/Get';
    const GET_PUBLIC_ASSET_URL = 'twirp/rzp.growth.asset.v1.AssetAPI/GetPublic';

    const EDIT_TEMPLATE_URL = 'twirp/rzp.growth.template.v1.TemplateAPI/Update';

    const GET_SUBCAMPAIGN_URL = 'twirp/rzp.growth.subcampaign.v1.SubCampaignAPI/Get';

    const SUBCAMPAIGN_ACTION_URL = 'twirp/rzp.growth.subcampaign.v1.SubCampaignAPI/Action';

    const FILTER_AND_SYNC_URL = '/twirp/rzp.growth.counting.v1.CountingAPI/FilterAndSync';

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
    }

    public function getAssetDetails($parameters)
    {
        return $this->sendRequest($parameters, self::GET_ASSET_URL, Requests::POST);
    }

    public function getTemplateByIdDetails($parameters)
    {
        return $this->sendRequest($parameters, self::GET_TEMPLATE_BY_ID_URL, Requests::POST);
    }

    public function getPublicAssetDetails($parameters)
    {
        return $this->sendRequest($parameters, self::GET_PUBLIC_ASSET_URL, Requests::POST);
    }

    public function editTemplateAndEnableDowntimeNotificationForXDashboard($parameters)
    {
        $templateParameters = ["template" => $parameters['template']];

        $this->sendRequest($templateParameters, self::EDIT_TEMPLATE_URL, Requests::POST);

        $subCampaignGetParams = ["sub_campaign_id" => $parameters['subcampaign']["sub_campaign_id"]];

        $subCampaignGetResponse = $this->sendRequest($subCampaignGetParams, self::GET_SUBCAMPAIGN_URL, Requests::POST);

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

    /**
     * @throws Exception\InvalidPermissionException
     * @throws Exception\ServerErrorException
     */
    public function sendAdminRequest($parameters, $path, $method): array
    {
        $admin = $this->auth->getAdmin();
        if ($admin === null) {
            throw new Exception\InvalidPermissionException('admin authorization required');
        }
        $adminEmail = $admin->getEmail() ?? '';
        $parameters[self::ADMIN_EMAIL_PARAM_NAME] = $adminEmail;
        return $this->sendRequest($parameters, $path, $method);
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

        $headers[self::ADMIN_EMAIL_PARAM_HEADER] = $parameters[self::ADMIN_EMAIL_PARAM_NAME] ?? '';
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

        $this->trace->info(TraceCode::GROWTH_REQUEST, ['url' => $url, 'parameters' => $parameters]);

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

        $growthResponse = ['status_code' => $code, 'response' => $res];

        return $growthResponse;
    }

}
