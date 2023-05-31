<?php


namespace RZP\Services\Partnerships;

use App;
use Request;
use ApiResponse;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use Throwable;

class PartnershipsService extends Base\Service
{
    const CONTENT_TYPE_JSON           = 'application/json';

    const CREATE_RULE_GROUP           = '/twirp/rzp.commissions.rules.rule_group.v1.RuleGroupAPI/Create';

    const HEALTH_CHECK                = '/twirp/rzp.common.health.v1.HealthCheckAPI/Check';

    const GET_RULE_GROUP_BY_ID        = '/twirp/rzp.commissions.rules.rule_group.v1.RuleGroupAPI/Get';

    const GET_ALL_RULE_GROUP          = '/twirp/rzp.commissions.rules.rule_group.v1.RuleGroupAPI/List';

    const UPDATE_RULE_GROUP           = '/twirp/rzp.commissions.rules.v1.RuleGroupAPI/List';

    const CREATE_RULE                 = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/Create';

    const GET_RULE                    = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/Get';

    const UPDATE_RULE                 = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/Update';

    const GET_RULE_BY_RULE_GROUP      = '/twirp/rzp.commissions.rules.rule.v1.RuleAPI/GetByRuleGroup';

    const CREATE_RULE_CONFIG_MAPPING  = '/twirp/rzp.commissions.rules.rule_config_mapping.v1.RuleConfigMappingAPI/Create';

    const UPDATE_RULE_CONFIG_MAPPING  = '/twirp/rzp.commissions.rules.rule_config_mapping.v1.RuleConfigMappingAPI/Update';

    const CREATE_AUDIT_LOG            = '/twirp/rzp.commissions.audit.v1.AuditLogAPI/Create';

    const LIST_AUDIT_LOG_BY_ENTITY_IDS    = '/twirp/rzp.commissions.audit.v1.AuditLogAPI/ListByEntityIds';

    const LIST_AUDIT_LOG_BY_ENTITY_ID    = '/twirp/rzp.commissions.audit.v1.AuditLogAPI/ListByEntityId';

    const GET_LAST_PARTNER_MIGRATION   = '/twirp/rzp.commissions.partner_migration_audit.v1.PartnerMigrationAuditAPI/GetLastPartnerMigrationAudit';

    const CREATE_PARNTER_MIGRATION_AUDIT = '/twirp/rzp.commissions.partner_migration_audit.v1.PartnerMigrationAuditAPI/CreatePartnerMigrationAudit';

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

    /**
     * @var string
     */
    protected string $baseLiveUrl;

    /**
     * @var string
     */
    protected string $baseTestUrl;

    public function __construct()
    {
        $app = App::getFacadeRoot();
        $this->trace = $app['trace'];
        $this->env = $app['env'];
        $PartnershipsConfig = $app['config']['applications.partnerships'];

        $this->baseLiveUrl = $PartnershipsConfig['url']['live'];
        $this->baseTestUrl = $PartnershipsConfig['url']['test'];

        $this->key    = $PartnershipsConfig['username'];
        $this->secret = $PartnershipsConfig['secret'];

        $this->skipPassport = $PartnershipsConfig['skip_jwt_passport'];
        $this->requestTimeout = $PartnershipsConfig['request_timeout'];
        $this->auth = $app['basicauth'];
    }

    public function createRuleGroup($parameters)
    {
        (new Validator())->validateInput(Validator::CREATE_RULE_GROUP, $parameters);

        return $this->sendRequest($parameters, self::CREATE_RULE_GROUP, Requests::POST);
    }

    public function getAllRuleGroup($parameters)
    {
        return $this->sendRequest($parameters, self::GET_ALL_RULE_GROUP, Requests::POST);
    }

    public function getRuleGroupById($parameters)
    {
        (new Validator())->validateInput(Validator::GET, $parameters);

        return $this->sendRequest($parameters, self::GET_RULE_GROUP_BY_ID, Requests::POST);
    }

    public function updateRuleGroup($parameters)
    {
        return $this->sendRequest($parameters, self::UPDATE_RULE_GROUP, Requests::POST);
    }

    public function createRule($parameters)
    {
        (new Validator())->validateInput(Validator::CREATE_RULE, $parameters);

        return $this->sendRequest($parameters, self::CREATE_RULE, Requests::POST);
    }

    public function getRule($parameters)
    {
        (new Validator())->validateInput(Validator::GET, $parameters);

        return $this->sendRequest($parameters, self::GET_RULE, Requests::POST);
    }

    public function getRuleByRuleGroupId($parameters)
    {
        return $this->sendRequest($parameters, self::GET_RULE_GROUP_BY_ID, Requests::POST);
    }

    public function updateRule($parameters)
    {
        (new Validator())->validateInput(Validator::UPDATE_RULE, $parameters);

        return $this->sendRequest($parameters, self::UPDATE_RULE, Requests::POST);
    }

    public function createRuleConfigMapping($parameters)
    {
        (new Validator())->validateInput(Validator::CREATE_RULE_CONFIG_MAPPING, $parameters);

        return $this->sendRequest($parameters, self::CREATE_RULE_CONFIG_MAPPING, Requests::POST);
    }

    public function updateRuleConfigMapping($parameters)
    {
        (new Validator())->validateInput(Validator::UPDATE_RULE_CONFIG_MAPPING, $parameters);

        return $this->sendRequest($parameters, self::UPDATE_RULE_CONFIG_MAPPING, Requests::POST);
    }

    public function createAuditLog($parameters, $mode)
    {
        (new Validator())->validateInput(Validator::CREATE_AUDIT_LOG, $parameters);

        return $this->sendRequest($parameters, self::CREATE_AUDIT_LOG, Requests::POST, $mode);
    }

    public function listAuditLogByEntityIds($parameters)
    {
        return $this->sendRequest($parameters, self::LIST_AUDIT_LOG_BY_ENTITY_IDS, Requests::POST);
    }

    public function listAuditLogByEntityId($parameters)
    {
        return $this->sendRequest($parameters, self::LIST_AUDIT_LOG_BY_ENTITY_ID, Requests::POST);
    }

    public function createPartnerMigrationAudit($parameters)
    {
        return $this->sendRequest($parameters, self::CREATE_PARNTER_MIGRATION_AUDIT, Requests::POST);
    }

    public function getLastPartnerMigration($parameters)
    {
        return $this->sendRequest($parameters, self::GET_LAST_PARTNER_MIGRATION, Requests::POST);
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

    public function sendRequest($parameters, $path, $method, $mode = null)
    {
        $requestParams = $this->getRequestParams($parameters, $path, $method, $mode);

        try {
            $response = Requests::request(
                $requestParams['url'],
                $requestParams['headers'],
                $requestParams['data'],
                $requestParams['method'],
                $requestParams['options']);

            return $this->parseAndReturnResponse($response);
        } catch (Throwable $e) {
            throw new Exception\ServerErrorException('Error completing the request', ErrorCode::SERVER_ERROR_PARTNERSHIPS_FAILURE, null, $e);
        }
    }

    public function getRequestParams($parameters, $path, $method, $mode = null)
    {
        if ($mode === null)
        {
            $this->mode = $this->app['rzp.mode'];
        }
        else
        {
            $this->mode = $mode;
        }
        $url = $this->getBaseUrl() . $path;

        $headers = [];

        $parameters = json_encode($parameters);

        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;
        $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);

        $headers[self::ADMIN_EMAIL_PARAM_HEADER] = $parameters[self::ADMIN_EMAIL_PARAM_NAME] ?? '';
        $options = [
            'timeout' => $this->requestTimeout,
        ];

        $jwt = null;
        if ($this->skipPassport === false) {
            $jwt = $this->auth->getPassportJwt($this->getBaseUrl());
        }
        if ($jwt == null) {
            $options['auth'] = [$this->key, $this->secret];
        }
        $headers[self::X_PASSPORT_JWT_V1] = $jwt;

        $this->trace->info(TraceCode::PARTNERSHIPS_REQUEST, ['url' => $url, 'parameters' => $parameters]);

        return [
            'url'       => $url,
            'headers'   => $headers,
            'data'      => $parameters,
            'options'   => $options,
            'method'    => $method,
        ];
    }

    private function getBaseUrl()
    {
        // returning live url for now as entities are not sync in live and test mode
        return $this->baseLiveUrl;
        //if($this->mode === Mode::LIVE) {
        //    return $this->baseLiveUrl;
        //}
        //else
        //{
        //    return $this->baseTestUrl;
        //}
    }

    protected function parseAndReturnResponse($res)
    {
        $code = $res->status_code;

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\RuntimeException('Malformed json response');
        }

        $partnershipsServiceResponse = ['status_code' => $code, 'response' => $res];

        $this->trace->info(TraceCode::PARTNERSHIPS_REQUEST, $partnershipsServiceResponse);

        return $partnershipsServiceResponse;
    }
}
