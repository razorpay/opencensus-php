<?php

namespace RZP\Services;


use GuzzleHttp\Client;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\MethodInstrumentsTerminalsSyncException;
use RZP\Http\Controllers\InstrumentRequestController;
use RZP\Models\Admin\Org;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use  RZP\Models\Base\Service;
use RZP\Http\Request\Requests;
use RZP\Constants\Environment;
use RZP\Models\Admin\Group\Core as core;
use RZP\Models\Customer\Token;
use RZP\Models\Admin\Admin\Service as AdminService;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Exception\BadRequestValidationFailureException;

class TerminalsService
{
    protected $app;

    protected $auth;

    protected $config;

    protected $trace;

    protected $baseUrl;

    protected $request;

    const X_RAZORPAY_TASKID         = 'X-Razorpay-TaskId';
    const X_RZP_TESTCASE_ID         = 'X-RZP-TESTCASE-ID';
    const X_DASHBOARD_MERCHANT_ID   = 'X-Dashboard-Merchant-Id';
    const X_TRUNCATE_TERMINAL_RESPONSE  = 'X-Truncate-Terminal-Response';


    const GATEWAY           = 'gateway';
    const TERMINAL_ID       = 'terminal_id';
    const GATEWAY_ACQUIRER  = 'gateway_acquirer';
    const MERCHANT_ID       = 'merchant_id';
    const IDENTIFIERS       = 'identifiers';
    const FEATURES          = 'features';
    const SECRETS           = 'secrets';
    const CURRENCY          = 'currency';
    const URL               = 'url';
    const CONTENT           = 'content';
    const CONTENT_TYPE      = 'content_type';
    const EXCEPTION         = 'exception';
    const STATUS_CODE       = 'status_code';
    const PATH              = 'path';
    const METHOD            = 'method';
    const HEADERS           = 'headers';
    const RESPONSE          = 'response';
    const DATA              = 'data';
    const TIMEOUT           = 'timeout';
    const CONNECT_TIMEOUT   = 'connect_timeout';
    const OPTIONS           = 'options';
    const ORG_ID            = 'org_id';
    const GATEWAY_MERCHANT_ID = 'gateway_merchant_id';

    const CODE              = 'code';
    const CAPTURE_INFO_UPFRONT_ERROR = 'CAPTURE_INFO_UPFRONT_ERROR';

    const DEFAULT_TIMEOUT   = 0.1;

    const INITIATE_ONBOARDING                  = 'initiate_onboarding';
    const EDIT_ONBOARDED_TERMINAL              = 'Edit_Onboarded_Terminal';
    const CREATE_TERMINAL                      = 'create_terminal';
    const FETCH_TERMINAL_BY_ID                 = 'fetch_terminal_by_id';
    const DELETE_TERMINAL_BY_ID                = 'delete_terminal_by_id';
    const FETCH_TERMINALS_FOR_MERCHANT         = 'fetch_terminals_for_merchant';
    const ADD_MERCHANT_TO_TERMINAL             = 'add_merchant_to_terminal';
    const REMOVE_MERCHANT_FROM_TERMINAL        = 'remove_merchant_from_terminal';
    const FETCH_MERCHANT_TERMINAL_BY_ID        = 'fetch_merchant_terminal_by_id';
    const FETCH_TERMINALS_FOR_MERCHANT_GATEWAY = 'fetch_terminals_for_merchant_gateway';
    const TERMINAL_ONBOARD_CALLBACK            = 'terminal_onboard_callback';
    const SYNC_DELETED_TERMINALS               = 'sync_deleted_terminals';
    const FETCH_TOKENISATION_TERMINALS         = 'fetch_tokenisation_terminals';
    const INSTRUMENT_RULES_EVENT               = 'instrument_rules_event';

    // terminals service error descriptions
    const MERCHANT_HAS_ALREADY_COMPLETED_PAYPAL_ONBOARDING         = 'Merchant has already completed PayPal onboarding';
    const PAYPAL_ONBOARDING_NOT_ALLOWED_FOR_YOUR_ACCOUNT           = 'PayPal Onboarding is not allowed for your account.';
    const DUPLICATE_TERMINAL_EXIST                                 = "Duplicate Terminal Exist";

    // terminals service error descriptions mapped with exception that needs to be raised by api
    const TERMINALS_API_ERROR_CODE_MAPPING     =    [
        self::MERCHANT_HAS_ALREADY_COMPLETED_PAYPAL_ONBOARDING      =>  ErrorCode::BAD_REQUEST_TERMINAL_ONBOARDING_ALREADY_REQUESTED,
        self::PAYPAL_ONBOARDING_NOT_ALLOWED_FOR_YOUR_ACCOUNT        =>  ErrorCode::BAD_REQUEST_PAYPAL_ONBOARDING_NOT_ALLOWED,
        self::DUPLICATE_TERMINAL_EXIST                              =>  ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_DUPLICATE_TERMINAL_EXISTS,
    ];

    const PARAMS = [
        self::CREATE_TERMINAL       =>   [
            self::PATH   => 'v1/terminals',
            self::METHOD => Requests::POST,
            self::OPTIONS => [
                self::TIMEOUT         => 5, // 5 seconds
                self::CONNECT_TIMEOUT => 5, // 5 seconds
            ],
        ],
        self::FETCH_TERMINAL_BY_ID  =>   [
            self::PATH   => 'v1/terminals/%s',
            self::METHOD => Requests::GET,
        ],
        self::DELETE_TERMINAL_BY_ID => [
            self::PATH   => 'v1/terminals/%s',
            self::METHOD => Requests::DELETE,
        ],
        self::INITIATE_ONBOARDING      =>  [
            self::PATH      =>  'v2/terminals',
            self::METHOD    =>  Requests::POST,
            self::OPTIONS => [
                self::TIMEOUT         => 70, // 70 seconds ref: https://razorpay.slack.com/archives/CNP473LRF/p1666880191950449
                self::CONNECT_TIMEOUT => 70, // 70 seconds
            ],
        ],
        self::EDIT_ONBOARDED_TERMINAL      =>  [
            self::PATH      =>  'v1/terminals/%s',
            self::METHOD    =>  Requests::PATCH,
            self::OPTIONS => [
                self::TIMEOUT         => 70, // 70 seconds ref: https://razorpay.slack.com/archives/CNP473LRF/p1666880191950449
                self::CONNECT_TIMEOUT => 70, // 70 seconds
            ],
        ],
        self::FETCH_TERMINALS_FOR_MERCHANT  => [
            self::PATH   => 'v1/merchants/%s/terminals',
            self::METHOD => Requests::GET,
        ],
        self::FETCH_TERMINALS_FOR_MERCHANT_GATEWAY  => [
            self::PATH   => 'v1/merchants/%s/terminals?gateway=%s',
            self::METHOD => Requests::GET,
        ],
        self::ADD_MERCHANT_TO_TERMINAL => [
            self::PATH   => 'v1/terminal/submerchant',
            self::METHOD => Requests::POST,
        ],
        self::REMOVE_MERCHANT_FROM_TERMINAL => [
            self::PATH   => 'v1/terminal/submerchant',
            self::METHOD => Requests::DELETE,
        ],
        self::FETCH_MERCHANT_TERMINAL_BY_ID => [
            self::PATH   => 'v2/terminal/submerchant',
            self::METHOD => Requests::GET,
        ],
        self::TERMINAL_ONBOARD_CALLBACK => [
            self::PATH   => 'v2/terminal/onboard/%s/callback',
            self::METHOD => Requests::POST,
            self::OPTIONS => [
                self::TIMEOUT         => 5, // 5 seconds
                self::CONNECT_TIMEOUT => 5, // 5 seconds
            ],
        ],
        self::SYNC_DELETED_TERMINALS => [
            self::PATH   => 'v2/terminal/sync/deleted',
            self::METHOD => Requests::POST,
        ],
        self::FETCH_TOKENISATION_TERMINALS => [
            self::PATH      => 'v1/merchants/terminals',
            self::METHOD    => Requests::POST,
        ],
        self::INSTRUMENT_RULES_EVENT => [
            self::PATH      => 'v2/instrument_rules/event',
            self::METHOD    => Requests::POST,
        ]
    ];

    protected array $terminal_admin_dashboard_routes = [
        'merchant_delete_terminal',
        'merchant_create_terminal',
        'terminal_delete',
        'terminal_edit',
        'terminal_edit_god_mode',
        'terminal_update_bulk',
        'terminal_reassign_merchant',
        'terminal_toggle',
        'terminals_proxy_create_gateway_credential',
        'terminals_proxy_delete_gateway_credential',
        'terminal_add_merchant',
        'terminal_remove_merchant',
        'terminal_set_banks',
        'terminal_set_wallets',
        'terminal_bank_bulk',
        'terminal_restore',
        'action_request_execute',
        'action_checker_create',
    ];

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $this->app['trace'];

        $this->request = $app['request'];

        $this->auth = $this->app['basicauth'];

        $this->adminOrgId = $this->app['basicauth']->getAdminOrgId();
    }

    public function migrateTerminal(Terminal\Entity $terminal, array $additionalOptions = array()): array
    {
        $terminalArr = $terminal->toArrayWithPassword(false);

        if(isset($additionalOptions[Constants::SYNC_INSTRUMENTS])) {
            $terminalArr[Constants::SYNC_INSTRUMENTS] = $additionalOptions[Constants::SYNC_INSTRUMENTS];
        }

        $content = json_encode($terminalArr);

        $params = self::PARAMS[self::CREATE_TERMINAL];

        try
        {
            $headers = $this->getTerminalServiceOrgHeaders();
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::TERMINAL_ORG_HEADERS_EXCEPTION, [
                'message' => $e->getMessage(),
                'function' => 'migrateTerminal',
            ]);
        }

        $options = [];

        $options[self::CONNECT_TIMEOUT] = 2;
        $options[self::TIMEOUT] = 2;

        $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD], $options, $headers);

        $parsedResponse = $this->parseAndReturnResponse($response)['data']??[];

        if($parsedResponse['status_code'] === 202) {
            throw new Exception\MethodInstrumentsTerminalsSyncException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR,
                $parsedResponse,202, "Method/Instruments need to be updated for the terminal change");
        }

        return $parsedResponse;
    }

    public function fetchTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::FETCH_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $options = [self::TIMEOUT=> 0.5];

        $response = $this->sendRequest($path, '', $params[self::METHOD], $options);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function getTerminalsByMerchantId(string $merchantId)
    {
        $params = self::PARAMS[self::FETCH_TERMINALS_FOR_MERCHANT];

        $path = sprintf($params[self::PATH], $merchantId);


        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function deleteTerminalById(string $terminalId): array
    {
        $params = self::PARAMS[self::DELETE_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function addMerchantToTerminal(Terminal\Entity $terminal, Merchant\Entity $merchant) : array
    {
        $params = self::PARAMS[self::ADD_MERCHANT_TO_TERMINAL];

        $content = [
            Terminal\Entity::TERMINAL_ID => $terminal->getId(),
            Merchant\Entity::MERCHANT_ID => $merchant->getId(),
        ];

        $response = $this->sendRequest($params[self::PATH], json_encode($content), $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function proxyTerminalService($input, $method, $path, $options = [], $headers = []) : array
    {
        if ($method !== "DELETE")
        {

            if ($input === [] || $input == '')
            {
                $input = '';
            }
            else
            {
                $input = json_encode($input);
            }
        }

        $fetchTerminalsPath = "v1/merchants/terminals";

        if (($path === $fetchTerminalsPath) and (isset($options[self::TIMEOUT]) === false))
        {
            $options[self::TIMEOUT] = 0.5;
            $options[self::CONNECT_TIMEOUT] = 0.5;
        }

        if ((strpos($path, "v2/collect_info/merchant") !== false) && (strpos($path, "details") !== false)){
            $options[self::TIMEOUT] = 3.0;
            $options[self::CONNECT_TIMEOUT] = 3.0;
        }

        $additionalHeaders = $this->addHeaderForExternalOrg();

        $headers = array_merge($headers, $additionalHeaders);

        // For merchant dashboard requests : RaaS
        if ((strpos($path, "/mid/provider") !== false) || ((strpos($path, "optimizer/merchant/mid/methods") !== false)))
        {
            $mid = $this->app['basicauth']->getMerchant()->getId();

            $path =  str_replace_first('/mid/', "/" . $mid . "/", $path);
        }

        $response = $this->sendRequest($path, $input, $method, $options, $headers);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    protected function addHeaderForExternalOrg():array
    {
        $additionalHeaders = [];

        if (!is_null($this->app['basicauth']) && $this->app['basicauth']->isAdminAuth() === true ) {

            $orgId = $this->app['basicauth']->getOrgId();

            if (empty($orgId) === false) {
                $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

                $validateOrgHasFeature = (new Org\Service)->validateOrgIdWithFeatureFlag($orgId, 'axis_org');

                if ($validateOrgHasFeature === true) {
                    $additionalHeaders = $this->getTruncateResponseHeaders();
                }
            }
        }
        return $additionalHeaders;
    }

    public function proxyTerminalServiceFormRequest($input, $method, $path, $options = [], $headers = [])
    {
        $response = $this->sendFormRequest($path, $input, $method, $options, $headers);

        return json_decode($response->getBody()->getContents());
    }

    public function removeMerchantFromTerminal(Terminal\Entity $terminal, Merchant\Entity $merchant) : array
    {
        $params = self::PARAMS[self::REMOVE_MERCHANT_FROM_TERMINAL];

        $content = [
            Terminal\Entity::TERMINAL_ID => $terminal->getId(),
            Merchant\Entity::MERCHANT_ID => $merchant->getId(),
        ];

        $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function fetchMerchantTerminalById(string $terminalId, string $merchantId)
    {
        $params = self::PARAMS[self::FETCH_MERCHANT_TERMINAL_BY_ID];

        $path = sprintf($params[self::PATH], $terminalId, $merchantId);

        $content = [
            Terminal\Entity::TERMINAL_ID => $terminalId,
            Merchant\Entity::MERCHANT_ID => $merchantId,
        ];

        $response = $this->sendRequest($path, $content, $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA][0] ?? [];
    }

    public function initiateOnboarding(string $merchantId, string $gateway, $identifiers = null, $features = null, array $currency = [], array $otherInputs = []): array
    {
        $params = self::PARAMS[self::INITIATE_ONBOARDING];

        $path = $params[self::PATH];

        $content = [
            self::MERCHANT_ID   =>  $merchantId,
            self::GATEWAY       =>  $gateway,
            self::CURRENCY      =>  $currency,
            self::IDENTIFIERS   =>  $identifiers,
            self::FEATURES      =>  $features,
        ];

        try
        {
            $headers = $this->getTerminalServiceOrgHeaders();
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::TERMINAL_ORG_HEADERS_EXCEPTION, [
                'message' => $e->getMessage(),
                'function' => 'initiateOnboarding',
            ]);
        }
        // for network tokenization
        if (isset($otherInputs[self::ORG_ID]) === true)
        {
            $content[self::ORG_ID] = $otherInputs[self::ORG_ID];
        }

        $headers = $this->getTerminalServiceOrgHeaders();

        if(isset($otherInputs[self::GATEWAY_MERCHANT_ID]) === true)
        {
            $content[self::IDENTIFIERS][self::GATEWAY_MERCHANT_ID] = $otherInputs[self::GATEWAY_MERCHANT_ID];
        }

        // for paysecure
        if (isset($otherInputs[self::GATEWAY_ACQUIRER]) === true)
        {
            $content[self::GATEWAY_ACQUIRER] = $otherInputs[self::GATEWAY_ACQUIRER];
        }
        if (isset($otherInputs[self::SECRETS]) === true)
        {
            $content[self::SECRETS] = $otherInputs[self::SECRETS];
        }

        $content = json_encode($content);

        $response = $this->sendRequest($path, $content, $params[self::METHOD], $params[self::OPTIONS],$headers);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function EditOnboardedTerminal(string $terminalId, string $gateway, $identifiers = null, $features = null, array $currency = [], array $otherInputs = []):array
    {
        $params = self::PARAMS[self::EDIT_ONBOARDED_TERMINAL];

        $path = sprintf($params[self::PATH], $terminalId);

        $content = [
            self::TERMINAL_ID   =>  $terminalId,
            self::GATEWAY       =>  $gateway,
            self::CURRENCY      =>  $currency,
            self::IDENTIFIERS   =>  $identifiers,
            self::FEATURES      =>  $features,
        ];
        try
        {
            $headers = $this->getTerminalServiceOrgHeaders();
        }
        catch (\Exception $e)
        {
            $this->app['trace']->traceException($e, Trace::ERROR, TraceCode::TERMINAL_ORG_HEADERS_EXCEPTION, [
                'message' => $e->getMessage(),
                'function' => 'EditOnboardedTerminal',
            ]);
        }


        if(isset($otherInputs[self::GATEWAY_MERCHANT_ID]) === true)
        {
            $content[self::IDENTIFIERS][self::GATEWAY_MERCHANT_ID] = $otherInputs[self::GATEWAY_MERCHANT_ID];
        }


        if (isset($otherInputs[self::GATEWAY_ACQUIRER]) === true)
        {
            $content[self::GATEWAY_ACQUIRER] = $otherInputs[self::GATEWAY_ACQUIRER];
        }
        if (isset($otherInputs[self::SECRETS]) === true)
        {
            $content[self::SECRETS] = $otherInputs[self::SECRETS];
        }

        $content = json_encode($content);

        $response = $this->sendRequest($path, $content, $params[self::METHOD], $params[self::OPTIONS],$headers);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function getTerminalsByMerchantIdAndGateway(string $merchantId, string $gateway)
    {
        $params = self::PARAMS[self::FETCH_TERMINALS_FOR_MERCHANT_GATEWAY];

        $path = sprintf($params[self::PATH], $merchantId, $gateway);

        $response = $this->sendRequest($path, '', $params[self::METHOD]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function terminalOnboardCallback(string $gateway, array $input)
    {
        $params = self::PARAMS[self::TERMINAL_ONBOARD_CALLBACK];

        $path = sprintf($params[self::PATH], $gateway);

        $response = $this->sendRequest($path, json_encode($input), $params[self::METHOD], $params[self::OPTIONS]);

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function syncDeletedTerminalsOnTerminalService(array $input)
    {
        $params = self::PARAMS[self::SYNC_DELETED_TERMINALS];

        $path = $params[self::PATH];

        $response = $this->sendRequest($path, json_encode($input));

        return $this->parseAndReturnResponse($response)[self::DATA] ?? [];
    }

    public function getMerchantInstruments(array $input, array $headers): array
    {
        $dashboardOrgId = $this->auth->getAdmin()->getPublicOrgId();

        $input = $this->validateMIDs($input,$dashboardOrgId);
        $merchantIds = $input['merchant_ids'];

       if ($this->areMerchantIdsAccessible($merchantIds))
       {
           $response = $this->proxyTerminalService(
               $input,
               \Requests::POST,
               'v2/composite_instrument_request',
               ['data_format' => 'body'],
               $headers
           );
           return $response;
       }
       else
       {
           $this->trace->error(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENTS_REQUEST_FAILED, [
               'merchant_ids' => $merchantIds
           ]);
           return [];
       }

    }

    public function validateMIDs($input, string $dashboardOrgId): array
    {
        $merchantIds = $input['merchant_ids'];

        if ($dashboardOrgId === "" || $dashboardOrgId === "org_".Org\Entity::RAZORPAY_ORG_ID)
        {
            return $input;
        }

        $input['ids'] = $input['merchant_ids'];
        unset($input['merchant_ids']);

        $merchantsInfo = (new Merchant\Service())->getMerchantBulk($input);

        for ($i=0; $i< sizeof($merchantsInfo['items']); $i++)
        {
            $merchant = $merchantsInfo['items'][$i];
            if ("org_".$merchant['org_id'] !== $dashboardOrgId)
            {
                unset($merchantIds[$i]);
            }
        }

        $input['merchant_ids'] = array_values($merchantIds);
        unset($input['ids']);

        return $input;
    }

    private function areMerchantIdsAccessible(array $merchantIds):bool
    {
        $admin = $this->app['basicauth']->getAdmin();

        $merchant = (new Merchant\Entity);

        foreach ($merchantIds as $merchantId)
        {
            $merchant->setId($merchantId);

            if (!(new core)->groupCheck($admin, $merchant))
            {
                return false;
            }

        }

        return count($merchantIds) > 0;

    }


    public function reRequestInternalInstrumentRequestsOnActivationFormSubmit(string $merchantId)
    {
        try
        {
            if ($this->isMerchantRampedForInstrumentRequests($merchantId) === false)
            {
                return;
            }

            $input = [
                'status'  => 'requested',
            ];

            $query = 'v2/internal_instrument_request?status=action_required&merchant_ids=' . $merchantId;

            $headers = $this->getMerchantHeadersForInstrumentRequest($merchantId);

            $headers['activation_form_submit'] = true;


            $response = $this->proxyTerminalService($input, Requests::PATCH, $query, [], $headers);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENT_RE_REQUEST_RESPONSE, $response);

        }
        catch (\Throwable $throwable)
        {
            $data = [
                Merchant\Entity::MERCHANT_ID => $merchantId,
                'message'                    => $throwable->getMessage(),
                'code'                       => $throwable->getCode(),
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_MERCHANT_INSTRUMENT_RE_REQUEST_FAILED, $data);
        }
    }

    public function requestDefaultMerchantInstruments(string $merchantId)
    {
        try
        {
            // check whether merchant is in db
            (new Merchant\Repository)->findOrFail($merchantId);

            if ($this->isMerchantRampedForInstrumentRequests($merchantId) === false)
            {
                return;
            }

            $input = [
                Merchant\Entity::MERCHANT_ID => $merchantId,
            ];

            $headers = $this->getMerchantHeadersForInstrumentRequest($merchantId);

            $response = $this->proxyTerminalService($input, Requests::POST, 'v2/default_merchant_instrument_requests', ['timeout' => 1], $headers);

            $this->trace->info(TraceCode::TERMINALS_SERVICE_MERCHANT_DEFAULT_INSTRUMENTS_REQUEST_RESPONSE, $response);
        }
        catch (\Throwable $throwable)
        {
            $data = [
                Merchant\Entity::MERCHANT_ID => $merchantId,
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_MERCHANT_DEFAULT_INSTRUMENTS_REQUEST_FAILED, $data);
        }

    }

    public function fetchMerchantTokenisationOnboardedNetworks(string $merchantId): ?array
    {
        try
        {
            $this->trace->info(TraceCode::FETCH_TOKENISATION_TERMINALS, ['merchantId' => $merchantId]);

            $params = self::PARAMS[self::FETCH_TOKENISATION_TERMINALS];

            $content = json_encode([
                Terminal\Entity::TYPE   => Terminal\Type::TOKENISATION,
                'merchant_ids'          => [$merchantId],
                Terminal\Entity::STATUS => Terminal\Status::ACTIVATED,
            ]);

            $response = $this->sendRequest($params[self::PATH], $content, $params[self::METHOD]);

            $tokenisationActivatedTerminalList = $this->parseAndReturnResponse($response)[self::DATA] ?? [];

            $onboardedNetworks = [];

            foreach ($tokenisationActivatedTerminalList as $terminal)
            {
                $tokenisationGateway = $terminal[self::GATEWAY];

                $network = Token\Core::TokenisationGatewayToNetworkMapping[$tokenisationGateway] ?? '';

                $issuer = Token\Core::TokenisationGatewayToIssuerMapping[$tokenisationGateway] ?? '';

                if (empty($network) === false)
                {
                    $onboardedNetworks[] = $network;
                }
                if(empty($issuer) === false)
                {
                    $onboardedNetworks[] = $issuer;
                }
            }

            $this->trace->info(TraceCode::FETCH_TOKENISATION_TERMINALS_SUCCESS, [
                'merchantId'        => $merchantId,
                'onboardedNetworks' => $onboardedNetworks
            ]);

            return $onboardedNetworks;
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::CRITICAL, TraceCode::FETCH_TOKENISATION_TERMINALS_ERROR, [
                'merchantId' => $merchantId,
            ]);

            return null;
        }
    }

    /**
     * @throws Exception\IntegrationException
     * @throws Exception\BadRequestException
     */
    protected function sendRequest(string $path, $content = '', string $method = Requests::POST, array $additionalOptions = [],
                                   array  $additionalHeaders = [])
    {
        if(in_array($this->app['api.route']->getCurrentRouteName(), $this->terminal_admin_dashboard_routes) === true){
            $timeout = $this->getAdminDashboardRequestTimeout();
            $additionalOptions[self::TIMEOUT] = $timeout;
            $additionalOptions[self::CONNECT_TIMEOUT] = $timeout;
        }
        return $this->handleRequestAndResponse($path, $content, $method, $additionalOptions, $additionalHeaders);
    }

    /*
     * Need this as a wrapper method around sendRequest as the sendRequest mocks added in multiple tests are not on basis of
     * strict checks of a particular call, like params sent to sendRequest, this leads to incorrect assertions in case if there
     * are multiple calls to sendRequest in a test. Ideally they should be mocked on basis of strict params checks, though it's
     * not done in correct way, would need test cases refactoring to fix.
     * Ref: https://docs.mockery.io/en/latest/reference/expectations.html#declaring-method-argument-expectations
     * Ref: mockTerminalsServiceHandleRequestAndResponse method in RZP\Tests\Functional\Helpers\TerminalsTrait.php
     * */
    protected function handleRequestAndResponse(string $path, $content = '', string $method = Requests::POST, array $additionalOptions = [],
                                                array $additionalHeaders = [])
    {

        $url = $this->getBaseUrl() . $path;

        $headers = $this->getHeaders($additionalHeaders);

        $options = $this->getOptions($additionalOptions);

        $data = [
            self::URL       => $url,
            self::METHOD    => $method,
        ];

        if (isset($content[Terminal\Entity::TERMINAL_ID]) === true)
        {
            $data[Terminal\Entity::TERMINAL_ID] = $content[Terminal\Entity::TERMINAL_ID];
        }
        if (isset($content[Terminal\Entity::MERCHANT_ID]) === true)
        {
            $data[Terminal\Entity::MERCHANT_ID] = $content[Terminal\Entity::MERCHANT_ID];
        }
        if (isset($content[Terminal\Entity::ID]) === true)
        {
            $data[Terminal\Entity::ID] = $content[Terminal\Entity::ID];
        }

        try
        {

            $response = $this->makeRequest($url, $headers, $content, $method, $options);

            $parsedResponse = $this->parseAndReturnResponse($response);

            $errorDescription = isset($parsedResponse['error']['description']) ? $parsedResponse['error']['description'] : null;

            if ($response->status_code >= 500)
            {
                throw new Exception\IntegrationException('Terminals service request failed with status code : ' . $response->status_code,
                    ErrorCode::SERVER_ERROR_TERMINALS_SERVICE_INTEGRATION_ERROR,
                    [
                        self::RESPONSE      => $parsedResponse,
                        self::STATUS_CODE   => $response->status_code,
                    ]);
            }

            if ($response->status_code >= 400)
            {
                $data = [
                    self::RESPONSE    => $parsedResponse,
                    self::STATUS_CODE => $response->status_code,
                ];

                if ((is_array($errorDescription) === false) and
                    (array_key_exists($errorDescription, self::TERMINALS_API_ERROR_CODE_MAPPING) === true))
                {
                    throw new Exception\BadRequestException(
                        self::TERMINALS_API_ERROR_CODE_MAPPING[$errorDescription], null, $data, $errorDescription);
                }

                //For sending 400 Error to FE, we need to throw BadRequestException(not possible to send capture_info json), so send status_code explicitly with capture info json in response
                if(empty($parsedResponse) == false && isset($parsedResponse[SELF::DATA]) &&
                    isset($parsedResponse[SELF::DATA][SELF::CODE]) &&
                    $parsedResponse[SELF::DATA][SELF::CODE] === SELF::CAPTURE_INFO_UPFRONT_ERROR) {
                    return $response;
                }

                if (is_array($errorDescription) === true)
                {
                    $errorDescription = implode_assoc_array($errorDescription);
                }


                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TERMINALS_SERVICE_ERROR, null, $data, $errorDescription);
            }

            return $response;
        }
        catch(\WpOrg\Requests\Exception $exception)
        {
            $data = [
                self::EXCEPTION => $exception->getMessage(),
                self::URL       => $url,
                'data'          => $exception,
            ];

            if ( (empty($exception->getData()) === false) and
                (curl_errno($exception->getData()) === CURLE_OPERATION_TIMEDOUT))
            {

                try
                {
                    $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_RETRY,
                        ['route'=>$this->app['request.ctx']->getRoute()]);

                    $this->trace->error(TraceCode::TERMINAL_PROXY_CALL_ERROR_RETRY_ATTEMPT, $data);

                    $response = $this->makeRequest($url, $headers, $content, $method, $options);

                    return $response;
                }
                catch (\Exception $exception)
                {
                    $data = [
                        self::EXCEPTION => $exception->getMessage(),
                        self::URL       => $url,
                        'data'          => $exception,
                    ];

                    $this->trace->error(TraceCode::TERMINALS_SERVICE_PROXY_TIMEOUT_RETRY_ERROR, $data);

                    $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_RETRY_ERROR,
                        ['route'=>$this->app['request.ctx']->getRoute()]);

                    throw $exception;
                }

            }
            else
            {
                throw $exception;
            }
        }
        catch (\Exception $exception)
        {
            $data = [
                self::EXCEPTION => $exception->getMessage(),
                self::URL       => $url,
                'data'          => $exception,
            ];

            $this->trace->error(TraceCode::TERMINALS_SERVICE_INTEGRATION_ERROR, $data);

            throw $exception;
        }
    }

    protected function sendFormRequest($url, $content, $method, $options, $headers)
    {
        $mode = $this->app['rzp.mode'];

        $tsConfigs = $this->app['config']['applications']['terminals_service'];

        $baseUrl = $tsConfigs[$mode]['url'];

        $multipart = $this->getRequestMultipart($content);

        $client   = new Client([
            'base_uri'        => $baseUrl,
            'connect_timeout' => $options['timeout'],
            'headers'         => $headers,
        ]);

        $response = $client->request($method, $url, [
            'multipart' => $multipart,
            'auth'      => ['api_user', $tsConfigs[$mode]['password']]
        ]);

        return $response;
    }

    protected function makeRequest($url, $headers, $content, $method, $options)
    {
        return Requests::request($url, $headers, $content, $method, $options);
    }

    protected function parseAndReturnResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        if ($responseArray === null)
        {
            return [];
        }

        return $responseArray;
    }

    protected function getBaseUrl()
    {
        $urlConfig = 'applications.terminals_service.' . $this->getMode() . '.url';

        return $this->app['config']->get($urlConfig);
    }

    protected function getTimeout()
    {
        $timeoutConfig = 'applications.terminals_service.timeout';

        return $this->app['config']->get($timeoutConfig);
    }

    protected function getAdminDashboardRequestTimeout(){
        $timeoutConfig = 'applications.terminals_service.dashboard_timeout';

        return $this->app['config']->get($timeoutConfig);
    }

    protected function getHeaders(array $additionalHeaders)
    {
        $defaultHeaders = [
            self::CONTENT_TYPE      => 'application/json',
            self::X_RAZORPAY_TASKID => $this->request->getTaskId()
        ];

        if ($this->app->environment('production') === false)
        {
            $testCaseId = $this->app['request']->header(self::X_RZP_TESTCASE_ID);

            if (empty($testCaseId) === false)
            {
                $defaultHeaders[self::X_RZP_TESTCASE_ID] = $testCaseId;
            }
        }

        return array_merge($defaultHeaders, $additionalHeaders);
    }

    protected function getOptions(array $additionalOptions = [])
    {
        $auth = [
            'api_user',
            $this->getPassword(),

        ];

        $defaultOptions =  [
            'auth'            => $auth,
            'timeout'         => self::getTimeout(),
            'connect_timeout' => self::getTimeout(),
            'show_trace'      => true,
        ];

        $env = $this->app->environment();

        $options =  array_merge($defaultOptions, $additionalOptions);

        if(Environment::isEnvironmentQA($env) === true)
        {
            $options['timeout'] = self::getTimeout();
            $options['connect_timeout'] = self::getTimeout();
        }

        return $options;
    }

    protected function getPassword()
    {
        $passwordConfig = 'applications.terminals_service.' . $this->getMode() . '.password';

        return $this->app['config']->get($passwordConfig);
    }

    protected function getMode()
    {

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;
        return $mode;
    }

    protected function isMerchantRampedForInstrumentRequests(string $merchantId) : bool
    {
        return true;
    }

    protected function getMerchantHeadersForInstrumentRequest(string $merchantId) : array
    {
        return [
            self::X_DASHBOARD_MERCHANT_ID => $merchantId,
        ];
    }

    public function getTerminalServiceOrgHeaders():array
    {
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            $orgId = $this->app['basicauth']->getOrgId();

            $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

            return [
                'X-Dashboard-Admin-OrgId'   => $orgId,
            ];
        }
        else if($this->app['basicauth']->isProxyAuth() === true)
        {
            $merchant = $this->app['basicauth']->getMerchant();

            return [
                'X-Dashboard-Merchant-OrgId'    => $merchant->getOrgId(),
            ];
        }
        return [];
    }

    protected function getTruncateResponseHeaders() : array
    {
        return [
            self::X_TRUNCATE_TERMINAL_RESPONSE => 'gateway',
        ];
    }

    protected function getRequestMultipart($input)
    {
        $multipart = [
            [
                'name'     => 'data',
                'contents' => $input['data'],
            ]
        ];

        unset($input['data']);

        foreach ($input as $key => $value)
        {
            $ext = strtolower($value->getClientOriginalExtension());

            $storageFileName = pathinfo($value->getClientOriginalName(), PATHINFO_FILENAME);

            $movedFile = $value->move(storage_path('files/filestore'), $storageFileName . '.' . $ext);

            $multipart[] = [
                'name'     => $key,
                'contents' => fopen($movedFile->getPathname(), 'r')
            ];
        }

        return $multipart;
    }

    /**
     * @throws Exception\IntegrationException
     * @throws Exception\BadRequestException
     */
    public function consumeInstrumentRulesEvaluationEvent($input): array
    {
        $content = json_encode($input);

        $params = self::PARAMS[self::INSTRUMENT_RULES_EVENT];

        $options = [self::TIMEOUT=> 3]; // 3 secs.

        $this->trace->info(TraceCode::TEMPORARY_INSTRUMENT_EVENT_CONSUME_LOG, [
            'input'   => $input,
            'content' => $content,
            'options' => $options,
        ]);

        $response = $this->handleRequestAndResponse($params[self::PATH], $content, $params[self::METHOD], $options);

        return $this->parseAndReturnResponse($response)['data'] ?? [];
    }
}
