<?php

namespace RZP\Base;

use App;
use Illuminate\Support\Arr;

use RZP\Http\BasicAuth;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Models\{Payment, Admin\Org};
use RZP\Exception\BadRequestValidationFailureException;

class Fetch
{
    const DEFAULTS             = 'defaults';
    const EXPAND               = 'expand';
    const EXPAND_EACH          = 'expand.*';
    const FROM                 = 'from';
    const TO                   = 'to';
    const COUNT                = 'count';
    const SKIP                 = 'skip';
    const DELETED              = 'deleted';
    const SOURCE_TYPE_EXCLUDE  = 'source_type_exclude';

    //
    // Different constants used in AdminFetch response to dashboard
    //
    const LABEL                = 'label';
    const TYPE                 = 'type';
    const VALUES               = 'values';

    const TYPE_STRING          = 'string';
    const TYPE_NUMBER          = 'number';
    const TYPE_BOOLEAN         = 'boolean';
    const TYPE_ARRAY           = 'array';
    const TYPE_OBJECT          = 'object';

    const FIELD_MERCHANT_ID         = 'merchant_id';
    const FIELD_BALANCE_ID          = 'balance_id';
    const FIELD_GATEWAY             = 'gateway';
    const FIELD_PAYMENT_ID          = 'payment_id';
    const FIELD_PAYMENT_STATUS      = 'payment_status';
    const FIELD_METHOD              = 'method';
    const FIELD_WALLET              = 'wallet';
    const FIELD_UPI                 = 'upi';
    const FIELD_SUBSCRIPTION_ID     = 'subscription_id';
    const FIELD_REFUND_ID           = 'refund_id';
    const FIELD_NOTES               = 'notes';

    /**
     * Key for list of rules specific for restricted orgs like SBI,
     * in the Default_Rules and Rules arrays.
     */
    const ADMIN_RESTRICTED = 'admin_restricted';

    /**
     * List of attributes allowed to restricted orgs like SBI, in the This is
     * the complete whitelist and is not appended to lists of public, etc. attributes
     */
    const ADMIN_RESTRICTED_ACCESSES = [];

    /**
     * Validation rules for all fields in Entity, is an multi-dimensional array
     * having default rules and then rules specific to auth type.
     *
     * @var array
     *
     */
    const RULES         = [];

    /**
     * Until now query parameters were only expected in 'fetch'
     * routes (e.g. GET /invoices) and so we have validation
     * rule sets on it based on authentication type. But now
     * with new use cases (e.g. expands) we would need validations
     * on 'find' routes (e.g. GET /invoices/{id}).
     *
     * Also observation is allowed 'find' parameters set is always
     * going to be subset of 'fetch' parameters set. Following is
     * a set of query parameters to be allowed in 'find' routes.
     * We use this to validate the query parameters in those routes.
     * Also as of now no need of segregating this one per authentication type.
     *
     * @var array
     */
    const RULES_KEYS_FIND_ROUTE = [
        self::EXPAND,
        self::EXPAND_EACH,
        self::DELETED,
        self::SOURCE_TYPE_EXCLUDE,
    ];

    /**
     * List of fields accessible to different auth types. Final list is formed
     * by merging cascade-ingly per auth type.
     *
     * @var array
     */
    const ACCESSES      = [];

    /**
     * List of signed ids fields, to be stripped before db fetch query
     *
     * @var array
     */
    const SIGNED_IDS    = [];

    /**
     * List of fields only to be queried from ES
     *
     * @var array
     */
    const ES_FIELDS     = [];

    /**
     * List of fields which may be queried/fetched from MySQL or ES
     *
     * @var array
     */
    const COMMON_FIELDS = [];

    const DEFAULT_RULES = [

        self::DEFAULTS => [
            self::FROM          => 'filled|epoch',
            self::TO            => 'filled|epoch',
            self::COUNT         => 'filled|integer|min:1|max:100',
            self::SKIP          => 'filled|integer|min:0',
            self::EXPAND        => 'sometimes|array|max:5',
            self::EXPAND_EACH   => 'filled|string|in:',
        ],

        BasicAuth\Type::PRIVATE_AUTH  => [],

        BasicAuth\Type::PROXY_AUTH     => [],

        BasicAuth\Type::PRIVILEGE_AUTH => [
            self::COUNT         => 'filled|integer|min:1|max:1000',
            self::DELETED       => 'filled|string|in:0,1',
        ],

        BasicAuth\Type::ADMIN_AUTH => [],

        BasicAuth\Type::DEVICE_AUTH => [],

        self::ADMIN_RESTRICTED => [],
    ];

    /**
     * List of all keys to be masked when passed as extra parameter in GET calls
     *
     * @var array
     */
    const ALL_KEYS_TO_BE_MASKED_WHILE_LOGGING = ["headers.Authorization"];

    /**
     * @var BasicAuth\BasicAuth
     */
    protected $auth;

    protected $trace;

    /**
     * Cached value for default rules.
     * Should be accessed via getDefaultFetchRules() because if this is null
     * it sets and keeps the instance value and then return.
     *
     * @var array
     */
    protected $defaultRules;

    /**
     * Cached value for rules.
     * Should be accessed via getAllFetchRules() because if this is null it sets
     * and keeps the instance value and then return.
     *
     * @var array
     */
    protected $rules;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->auth = $app['basicauth'];

        $this->trace = $app['trace'];
    }

    /**
     * Do a bunch of processing on the fetch parameters:
     *
     * @param array $params
     */
    public function processFetchParams(array & $params)
    {
        $this->unsetEmptyParams($params);

        $this->validateFetchParams($params);

        $this->addDefaultParams($params);

        $this->validateAndStripSignedIds($params);
    }

    public function processFindParams(array & $params)
    {
        $this->ignoreExtraFindParams($params);

        $this->validateFindParams($params);
    }

    // -------------------- Protected methods starts --------------------------

    protected function unsetEmptyParams(array & $params)
    {
        foreach ($params as $key => $value)
        {
            if ($params[$key] === '')
            {
                unset($params[$key]);
            }
        }
    }

    protected function validateFetchParams(array $params)
    {
        (new JitValidator)->rules($this->getAllFetchRules())
                          ->caller($this)
                          ->input($params)
                          ->validate();

        $this->validateAdditional($params);
    }

    protected function validateAdditional(array $param)
    {
        //
    }

    protected function addDefaultParams(array & $params)
    {
        $this->addDefaultParamCountByAuth($params);
    }

    protected function validateAndStripSignedIds(array & $params)
    {
        $signIds = array_flip(static::SIGNED_IDS);

        if (count($signIds) === 0)
        {
            return;
        }

        $keys = array_keys(array_intersect_key($params, $signIds));

        foreach ($keys as $key)
        {
            $entityKey = $key;

            // Remove '_id' prefix at end.
            if (substr($key, -3) === '_id')
            {
                $entityKey = substr($key, 0, -3);
            }

            // If not valid entity, then continue the loop
            if (Entity::isValidEntity($entityKey) === false)
            {
                continue;
            }

            // Gets entity class
            $entityClass = Entity::getEntityClass($entityKey);

            $value = $params[$key];

            if (($this->auth->isAdminAuth() === true) or
                ($this->auth->isPrivilegeAuth() === true))
            {
                // In case of admin auth, don't throw exception
                // if sign is not there
                $entityClass::verifyIdAndSilentlyStripSign($value);
            }
            else
            {
                $entityClass::verifyIdAndStripSign($value);
            }

            $params[$key] = $value;
        }
    }

    protected function addDefaultParamCountByAuth(array & $params)
    {
        if (isset($params[self::COUNT]) === true)
        {
            return;
        }

        $params[self::COUNT] = ($this->auth->isPrivilegeAuth() === true) ? 1000 : 10;
    }

    /**
     * Temporary:
     *
     * There are clients(including Dashboard) which is sending
     * skip,count like extra parameters in GET routes. For now
     * everything which is not expected would be ignored. We'll
     * keep trace of violations and act on it later.
     *
     * @param array $params
     */
    protected function ignoreExtraFindParams(array & $params)
    {
        $filtered = array_only($params, self::RULES_KEYS_FIND_ROUTE);

        if (count($filtered) !== count($params))
        {
            $this->ignoreExtraFindParamsForLogging($params);

            $this->trace->info(TraceCode::EXTRA_QUERY_PARAM_IN_GET_ROUTE, $params);
        }

        $params = $filtered;
    }

    /**
     * Occasionally the client would send extra parameters
     * in GET Call. We log such parameters. We use this method
     * to ignore logging for parameters that are sensitive.
     *
     * @param array $params
     */
    public function ignoreExtraFindParamsForLogging(array & $params)
    {
        $params = Arr::except($params, self::ALL_KEYS_TO_BE_MASKED_WHILE_LOGGING);
    }

    /**
     * Validates query parameters passed during GET by id endpoints
     * E.g. GET /invoices/inv_123?expand[]=payments
     *
     * @param array $params
     */
    protected function validateFindParams(array $params)
    {
        $fetchParamRules = $this->getAllFetchRules();

        $findParamRules = array_only($fetchParamRules, self::RULES_KEYS_FIND_ROUTE);

        (new JitValidator)->rules($findParamRules)
                          ->caller($this)
                          ->input($params)
                          ->validate();
    }

    /**
     * Validation is run against these rules
     *
     * @return array
     */
    protected function getAllFetchRules() : array
    {
        if ($this->rules === null)
        {
            $this->setCascadedRulesForCurrentAuth();
        }

        return $this->rules;
    }

    /**
     * Required separately as to distinguish
     *
     * @return array
     */
    protected function getDefaultFetchRules() : array
    {
        if ($this->defaultRules === null)
        {
            $this->setCascadedRulesForCurrentAuth();
        }

        return $this->defaultRules;
    }

    /**
     * Build cascaded rules and accesses
     */
    protected function setCascadedRulesForCurrentAuth()
    {
        $defaultRules    = self::DEFAULT_RULES[self::DEFAULTS];

        $rules           = array_get(static::RULES, self::DEFAULTS, []);
        $accesses        = array_get(static::ACCESSES, self::DEFAULTS, []);

        if ($this->auth->isPublicAuth() === false)
        {
            $defaultRules    = array_merge($defaultRules, self::DEFAULT_RULES[BasicAuth\Type::PRIVATE_AUTH]);

            $privateRules    = array_get(static::RULES, BasicAuth\Type::PRIVATE_AUTH, []);
            $privateAccesses = array_get(static::ACCESSES, BasicAuth\Type::PRIVATE_AUTH, []);

            $rules    = array_merge($rules, $privateRules);
            $accesses = array_merge($accesses, $privateAccesses);
        }

        if ($this->auth->isProxyOrPrivilegeAuth() === true)
        {
            $defaultRules    = array_merge($defaultRules, self::DEFAULT_RULES[BasicAuth\Type::PROXY_AUTH]);

            $proxyRules    = array_get(static::RULES, BasicAuth\Type::PROXY_AUTH, []);
            $proxyAccesses = array_get(static::ACCESSES, BasicAuth\Type::PROXY_AUTH, []);

            $rules    = array_merge($rules, $proxyRules);
            $accesses = array_merge($accesses, $proxyAccesses);
        }

        if ($this->auth->isPrivilegeAuth() === true)
        {
            $defaultRules    = array_merge($defaultRules, self::DEFAULT_RULES[BasicAuth\Type::PRIVILEGE_AUTH]);

            $privilegeRules    = array_get(static::RULES, BasicAuth\Type::PRIVILEGE_AUTH, []);
            $privilegeAccesses = array_get(static::ACCESSES, BasicAuth\Type::PRIVILEGE_AUTH, []);

            $rules    = array_merge($rules, $privilegeRules);
            $accesses = array_merge($accesses, $privilegeAccesses);
        }

        if ($this->auth->isAdminAuth() === true)
        {
            if ($this->auth->getOrgType() === Org\Entity::RESTRICTED)
            {
                $defaultRules = array_merge($defaultRules, self::DEFAULT_RULES[self::ADMIN_RESTRICTED]);

                $adminRules    = array_get(static::RULES, self::ADMIN_RESTRICTED, []);
                $adminAccesses = static::ADMIN_RESTRICTED_ACCESSES;

                //
                // In case of restricted orgs, we reset default accesses to original defaults without
                // accesses from other auths like private, proxy (which are allowed for regular admin
                // auth) and then merge only the ones allowed to restricted orgs
                //
                $accesses      = array_get(static::ACCESSES, self::DEFAULTS, []);
            }
            else
            {
                $defaultRules = array_merge($defaultRules, self::DEFAULT_RULES[BasicAuth\Type::ADMIN_AUTH]);

                $adminRules    = array_get(static::RULES, BasicAuth\Type::ADMIN_AUTH, []);
                $adminAccesses = array_get(static::ACCESSES, BasicAuth\Type::ADMIN_AUTH, []);
            }

            $rules    = array_merge($rules, $adminRules);
            $accesses = array_merge($accesses, $adminAccesses);
        }

        if ($this->auth->isDeviceAuth() === true)
        {
            $defaultRules = array_merge($defaultRules, self::DEFAULT_RULES[BasicAuth\Type::DEVICE_AUTH]);

            $deviceRules = array_get(static::RULES, BasicAuth\Type::DEVICE_AUTH, []);
            $deviceAccesses = array_get(static::ACCESSES, BasicAuth\Type::DEVICE_AUTH, []);

            $rules = array_merge($rules, $deviceRules);
            $accesses = array_merge($accesses, $deviceAccesses);
        }

        // Finally get rules for keys to which access is allowed
        $rules = array_only($rules, $accesses);

        // Override Defaults with rules
        $rules = array_merge($defaultRules, $rules);

        $this->rules = $rules;
        $this->defaultRules = $defaultRules;
    }

    /**
     * Splits parameters for query into MySQL and ES
     *
     * @param array $params
     *
     * @return array [[MySQL Fields], [ES Fields]]
     *
     * @throws BadRequestValidationFailureException
     */
    public function groupMysqlAndEsParams(array $params): array
    {
        $esFetchKeys     = static::ES_FIELDS;
        $commonFetchKeys = static::COMMON_FIELDS;

        //
        // If there is no ES Field defined in Fetch class then
        // we direct all the fields to MySQL
        //
        if ((empty($esFetchKeys) === true) and (empty($commonFetchKeys) === true))
        {
            return [$params, []];
        }

        //
        // Following is list of keys common to Es & MySQL, only in ES, only in
        // MySQL respectively. These do not include default keys(e.g. skip, count).
        //
        $mysqlFetchKeys  = array_values(array_diff(
                               array_keys($this->getAllFetchRules()),
                               array_keys($this->getDefaultFetchRules()),
                               $esFetchKeys,
                               $commonFetchKeys));

        //
        // Get input params which are not a part of the:
        // - Default param rules (see $fetchParamRules definition above), plus
        // - Common keys defined in EsRepo.
        //
        // The remainder/filtered list has to be a subset of either MySQL or ES keys
        // exclusively, otherwise an error will be raised. Hence both MySQL and
        // ES cannot be searched together in a single fetch operation.
        //
        $filteredParamsKeys = array_values(array_diff(
                                  array_keys($params),
                                  array_keys($this->getDefaultFetchRules()),
                                  $commonFetchKeys));

        if (empty(array_diff($filteredParamsKeys, $mysqlFetchKeys)) === true)
        {
            return [$params, []];
        }
        else if (empty(array_diff($filteredParamsKeys, $esFetchKeys)) === true)
        {
            return [[], $params];
        }
        else
        {
            $extraKeys = array_values(array_diff($filteredParamsKeys, $esFetchKeys));

            $message = implode(', ', $extraKeys) . ' not expected with other params sent';

            throw new BadRequestValidationFailureException(
                $message,
                null,
                [
                    'params_keys'       => array_keys($params),
                    'common_fetch_keys' => $commonFetchKeys,
                    'es_fetch_keys'     => $esFetchKeys,
                    'mysql_fetch_keys'  => $mysqlFetchKeys,
                    'extra_keys'        => $extraKeys,
                ]);
        }
    }

    public function validateCustom($func, $attribute, $value, $parameters)
    {
        assertTrue(strpos($func, 'validate') === 0);

        $this->$func($attribute, $value, $parameters);
    }

    /**
     *
     * Current:
     *
     * Referring to \RZP\Constants\AdminFetch, we now return list of
     * entities with details such as it's field, filed data type, allowed values
     * etc. to dashboard. This way any new change in API will get reflected in
     * dashboard view.
     *
     * Refer to AdminFetch to understand the data structure of response sent
     * to Dashboard.
     *
     * Now, following method returns map for common (to dashboard) fields (their type
     * allowed values etc) and in the big response(as in AdminFetch) we use just
     * reference to minimize response content size.
     *
     * Later:
     *
     * Plan is to get away with AdminFetch file. All these map should be extracted
     * from admin fetch rules (controlled via Fetch class). With that following
     * method will get factored as well.
     *
     * @return array
     */
    public static function getCommonFields(): array
    {
        $gatewayList = config('gateway.available');
        $statusList  = Payment\Status::getStatusList();
        $methodList  = Payment\Method::getMethodsNamesMap();
        $walletList  = Payment\Processor\Wallet::getWalletNetworkNamesMap();
        $upiList     = Payment\Processor\Upi::getFullBankNamesMap();

        return [
            self::FIELD_MERCHANT_ID => [
                self::LABEL     => 'Merchant Id',
                self::TYPE      => self::TYPE_STRING
            ],
            self::FIELD_BALANCE_ID => [
                self::LABEL     => 'Balance Id',
                self::TYPE      => self::TYPE_STRING,
            ],
            self::FIELD_GATEWAY => [
                self::LABEL     => 'Gateway',
                self::TYPE      => self::TYPE_ARRAY,
                self::VALUES    => $gatewayList
            ],
            self::FIELD_PAYMENT_STATUS => [
                self::LABEL     => 'Status',
                self::TYPE      => self::TYPE_ARRAY,
                self::VALUES    => $statusList
            ],
            self::FIELD_PAYMENT_ID => [
                self::LABEL     => 'Payment Id',
                self::TYPE      => self::TYPE_STRING,
            ],
            self::FIELD_METHOD => [
                self::LABEL     => 'Method',
                self::TYPE      => self::TYPE_OBJECT,
                self::VALUES    => $methodList
            ],
            self::FIELD_WALLET => [
                self::LABEL     => 'Wallet',
                self::TYPE      => self::TYPE_OBJECT,
                self::VALUES    => $walletList
            ],
            self::FIELD_UPI => [
                self::LABEL     => 'UPI',
                self::TYPE      => self::TYPE_OBJECT,
                self::VALUES    => $upiList
            ],
            self::FIELD_SUBSCRIPTION_ID => [
                self::LABEL     => 'Subscription Id',
                self::TYPE      => self::TYPE_STRING,
            ],
            self::FIELD_REFUND_ID => [
                self::LABEL     => 'Refund Id',
                self::TYPE      => self::TYPE_STRING
            ],
            self::FIELD_NOTES => [
                Fetch::LABEL  => 'Notes',
                Fetch::TYPE   => Fetch::TYPE_STRING,
            ],
        ];
    }
}
