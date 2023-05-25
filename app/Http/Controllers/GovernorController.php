<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Permission;
use RZP\Services\GovernorService;

class GovernorController extends Controller
{
    const GET                                   = 'GET';
    const PUT                                   = 'PUT';
    const POST                                  = 'POST';
    const RULES                                 = 'rules';
    const DELETE                                = 'DELETE';
    const GOVERNOR_RULE_EDIT_ENTITY             = 'governor_rule_edit';
    const GOVERNOR_RULE_CREATE_ENTITY           = 'governor_rule_create';
    const GOVERNOR_RULE_DELETE_ENTITY           = 'governor_rule_delete';
    const GOVERNOR_SCORECARD_RULE_EDIT_ENTITY   = 'governor_scorecard_rule_edit';
    const GOVERNOR_SCORECARD_RULE_CREATE_ENTITY = 'governor_scorecard_rule_create';
    const GOVERNOR_SCORECARD_RULE_DELETE_ENTITY = 'governor_scorecard_rule_delete';
    public static array $scorecardNamespaces = [
        'ItWxsLvhhq93CY', // keystone_scorecard
        'ItWysAHVbOBumr', // prescorecard_check
        'ItWxTFG290SShe', // eligibility_check
        'JATCjn66kFSuSd', // bureau_loc_scorecard
        'ItWyJSOQfa3xGn', // pg_scorecard
        'JkAypLh5ULNK3z', // open_policy_business_scorecard
        'JkB0kS7inS91VW', // open_policy_uw_scorecard
        'ItWwIhRcFt7s9D', // banking_scorecard
        'ItWwy43bQaPa32', // bureau_scorecard
        'Jy0W9t8nyRDswN', // biz_vintage_beta_scorecard
        //  stage namespaces
        'IcyRQfQHTbGUUD',
        'JvXjzYRc3cHNGn',
        'IcyQcopEG884ou',
        'IcyVO7ZYmygz66',
        'IcyTRy9naBClEH',
        'IcyVykudCVc68Z',
        'JhoCVztHEacXQn',
        'Jho7szKSX1gCMO',
        'IcyUqpcLlvDve3',
        'JAF2g5R1gBw0D2',
        'Jq1FStYMjGjpI1',
    ];

    public function createNamespace($source)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_NAMESPACE, $input, $source);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getDomainModels($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::DOMAIN_MODEL_LIST, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createDomainModel($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_DOMAIN_MODEL, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateDomainModel($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_DOMAIN_MODEL, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRule($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULE, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULES, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateRule($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_RULE, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function updateRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_RULES, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRules($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::RULE_LIST, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRule($source, $namespace, $rulename)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::GET_RULE, $input, $source, $namespace, $rulename);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function createRuleChain($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::CREATE_RULE_CHAIN, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }


    public function updateRuleChain($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::UPDATE_RULE_CHAIN, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function getRuleChains($source, $namespace)
    {
        $input = Request::all();

        $response = $this->app['governor']->sendRequest(GovernorService::RULE_CHAIN_LIST, $input, $source, $namespace);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    public function executeChains($source, $namespace)
    {
        $input = Request::json()->all();

        $queryParams = Request::query();

        $response = $this->app['governor']->sendRequest(GovernorService::EXECUTE_CHAINS, $input, $source, $namespace, null , $queryParams);

        return response()->json($response['response_body'])->setStatusCode($response['response_code']);
    }

    // Gets the query params sent from dashboard in input body and sends them as query params to governor
    public function proxyWithQueryParams()
    {
        $input = Request::all();

        $query = "?";

        foreach ($input as $key => $value) {
            $query = $query . $key . '=' . $value . '&';
        }

        $query = substr($query, 0, -1);

        $path = Request::path() . $query;

        $method = Request::method();

        $response = $this->app['governor']->sendRequestV1($method, $path, $input);

        return ApiResponse::json($response);
    }

    public function proxy()
    {
        $input = Request::all();

        $method = Request::method();

        $path = Request::path() . '?' . Request::getQueryString();

        // handling workflow here
        if ($this->app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            $this->routeToGovernorViaWorkflow($method, $path, $input);
        }
        // if call is coming for a scorecard namespace then trigger scorecard governor workflow
        else if ($this->string_contains_array($path, self::$scorecardNamespaces) === true)
        {
            $this->routeToScorecardWorkflowIfApplicable($method, $path, $input);
        }
        else
        {
            $this->routeToWorkflowIfApplicable($method, $path, $input);
        }

        $response = $this->app['governor']->sendRequestV1($method, $path, $input);

        return ApiResponse::json($response);
    }

    protected function routeToGovernorViaWorkflow(&$method, &$path, $input)
    {
        $method = $input['governor_method'];

        $path = $input['governor_path'];

        $this->app['trace']->info(TraceCode::GOVERNOR_CONTROLLER_WORKFLOW_REQUEST, [
            'path'              => $path,
            'method'            => $method,
            'input'             => $input,
        ]);
    }

    protected function routeToWorkflowIfApplicable($method, $path, $body)
    {
        // checking for rules related url
        if (strpos($path, self::RULES) !== false)
        {
            // Checking for create/edit/delete rules here
            if ($method === self::POST)
            {
                $this->app['trace']->info(TraceCode::GOVERNOR_CREATE_RULE_REQUEST_VIA_WORKFLOW, $body);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_RULE_CREATE_ENTITY, substr($this->app['request']->getId(),0,12))
                    ->handle([], $body);
            }
            elseif ($method === self::PUT)
            {
                $this->app['trace']->info(TraceCode::GOVERNOR_EDIT_RULE_REQUEST_VIA_WORKFLOW, $body);

                $originalRule  = [];

                // checking old rule key exist or not
                if (isset($body['old_rule']) === true)
                {
                    $originalRule = $body['old_rule'];

                    // removing old rule from body, required to populate workflow diff properly
                    unset($body['old_rule']);
                }

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_RULE_EDIT_ENTITY, substr($this->app['request']->getId(),0,12))
                    ->handle([self::RULES => $originalRule], [self::RULES => $body]);
            }
            elseif ($method === self::DELETE)
            {
                // fetching rule from governor for populating workflow diff
                $rule = $this->app['governor']->sendRequestV1('GET', $path, $body);

                $originalRule = $rule ?? [];

                // not indexing mode in case of deletion
                if ( isset($originalRule['mode']) ){

                    unset($originalRule['mode']);
                }
                $this->app['trace']->info(TraceCode::GOVERNOR_DELETE_RULE_REQUEST_VIA_WORKFLOW, $originalRule);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_RULE_DELETE_ENTITY, substr($this->app['request']->getId(),0,12))
                    ->handle($originalRule, []);
            }
        }
    }

    protected function routeToScorecardWorkflowIfApplicable($method, $path, $body)
    {
        if (strpos($path, self::RULES) !== false) {
            if ($method === self::POST)
            {
                $this->app['trace']->info(TraceCode::GOVERNOR_SCORECARD_CREATE_RULE_REQUEST_VIA_WORKFLOW, $body);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_SCORECARD_RULE_CREATE_ENTITY, substr($this->app['request']->getId(), 0, 12))
                    ->setPermission(Permission\Name::EDIT_SCORECARD_GOVERNOR_CONF)
                    ->handle([], $body);
            }
            elseif ($method === self::PUT)
            {
                $this->app['trace']->info(TraceCode::GOVERNOR_EDIT_RULE_REQUEST_VIA_WORKFLOW, $body);

                $originalRule = [];

                // checking old rule key exist or not
                if (isset($body['old_rule']) === true) {
                    $originalRule = $body['old_rule'];

                    // removing old rule from body, required to populate workflow diff properly
                    unset($body['old_rule']);
                }
                $this->app['trace']->info(TraceCode::GOVERNOR_SCORECARD_EDIT_RULE_REQUEST_VIA_WORKFLOW, $body);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_SCORECARD_RULE_EDIT_ENTITY, substr($this->app['request']->getId(), 0, 12))
                    ->setPermission(Permission\Name::EDIT_SCORECARD_GOVERNOR_CONF)
                    ->handle($originalRule, $body);
            }
            elseif ($method === self::DELETE)
            {
                // fetching rule from governor for populating workflow diff
                $rule = $this->app['governor']->sendRequestV1('GET', $path, $body);

                $originalRule = $rule ?? [];

                // not indexing mode in case of deletion
                if (isset($originalRule['mode'])) {
                    unset($originalRule['mode']);
                }

                $this->app['trace']->info(TraceCode::GOVERNOR_SCORECARD_DELETE_RULE_REQUEST_VIA_WORKFLOW, $originalRule);

                $this->app['workflow']
                    ->setEntityAndId(self::GOVERNOR_SCORECARD_RULE_DELETE_ENTITY, substr($this->app['request']->getId(), 0, 12))
                    ->setPermission(Permission\Name::EDIT_SCORECARD_GOVERNOR_CONF)
                    ->handle($originalRule, []);
            }
        }
    }

    public function string_contains_array(string $str, array $arr): bool {
        foreach($arr as $a){
            if(str_contains($str, $a) === true) return true;
        }
        return false;
    }
}
