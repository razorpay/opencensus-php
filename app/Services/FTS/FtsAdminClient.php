<?php

namespace RZP\Services\FTS;

use App;
use RZP\Http\Request\Requests;

class FtsAdminClient extends Base
{

    const TRANSFERS          = 'transfers';

    const FUND_ACCOUNTS      = 'fund_accounts';

    const BENEFICIARY_STATUS = 'beneficiary_status';

    const ATTEMPTS           = 'attempts';

    const SOURCE_ACCOUNTS    = 'source_accounts';

    const CHANNEL_HEALTH_EVENTS = "channel_health_events";

    const SOURCE_ACCOUNT_MAPPINGS = "source_account_mappings";

    const DIRECT_ACCOUNT_ROUTING_RULES = 'direct_account_routing_rules';

    const PREFERRED_ROUTING_WEIGHTS = "preferred_routing_weights";

    const ACCOUNT_TYPE_MAPPINGS = "account_type_mappings";

    const SCHEDULES = "schedules";

    const TRIGGER_STATUS_LOGS = "trigger_status_logs";

    const CHANNEL_INFORMATION_STATUS_LOGS = "channel_information_status_logs";

    const MERCHANT_CONFIGURATIONS = "merchant_configurations";

    const HOLIDAY = "holiday";

    const FAIL_FAST_STATUS_LOGS = 'fail_fast_status_logs';

    const KEY_VALUE_STORE_LOGS = 'key_value_store_logs';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        parent::__construct($app);

        $this->setAdminHeader();
    }

    public function fetchMultiple(string $entity, array $input)
    {
        return $this->getEntity($entity, $input);
    }

    public function getTransfers(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_FETCH_URI,
            Requests::GET,
            $input)['body'][self::TRANSFERS];
    }

    public function getFundAccounts(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_ACCOUNT_FETCH_URI,
            Requests::GET,
            $input)['body'][self::FUND_ACCOUNTS];
    }

    public function getBeneficiaryStatus(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_ACCOUNT_STATUS_FETCH_URI,
            Requests::GET,
            $input)['body'][self::BENEFICIARY_STATUS];
    }

    public function getAttempts(array $input)
    {
        return $this->createAndSendRequest(
            parent::FUND_TRANSFER_STATUS_FETCH_URI,
            Requests::GET,
            $input)['body'][self::ATTEMPTS];
    }

    public function getSourceAccounts(array $input)
    {
        return $this->createAndSendRequest(
            parent::SOURCE_ACCOUNT,
            Requests::GET,
            $input)['body'][self::SOURCE_ACCOUNTS];
    }

    public function getSourceAccountMappings(array $input)
    {
        return $this->createAndSendRequest(
            parent::SOURCE_ACCOUNT_MAPPING,
            Requests::GET,
            $input)['body'][self::SOURCE_ACCOUNT_MAPPINGS];
    }

    public function getDirectAccountRoutingRules(array $input)
    {
        return $this->createAndSendRequest(
            parent::DIRECT_ACCOUNT_ROUTING_RULES,
            Requests::GET,
            $input)['body'][self::DIRECT_ACCOUNT_ROUTING_RULES];
    }

    public function getPreferredRoutingWeights(array $input)
    {
        return $this->createAndSendRequest(
            parent::PREFERRED_ROUTING_WEIGHT,
            Requests::GET,
            $input)['body'][self::PREFERRED_ROUTING_WEIGHTS];
    }

    public function getAccountTypeMappings(array $input)
    {
        return $this->createAndSendRequest(
            parent::ACCOUNT_TYPE_MAPPING,
            Requests::GET,
            $input)['body'][self::ACCOUNT_TYPE_MAPPINGS];
    }

    public function getSchedules(array $input)
    {
        return $this->createAndSendRequest(
            parent::SCHEDULE_GET_ROUTE,
            Requests::GET,
            $input)['body'][self::SCHEDULES];
    }

    public function getTriggerStatusLogs(array $input)
    {
        return $this->createAndSendRequest(
            parent::TRIGGER_STATUS_LOG_GET_ROUTE,
            Requests::GET,
            $input)['body'][self::TRIGGER_STATUS_LOGS];
    }

    public function getChannelInformationStatusLogs(array $input)
    {
        return $this->createAndSendRequest(
            parent::CHANNEL_INFORMATION_STATUS_LOG_GET_ROUTE,
            Requests::GET,
            $input)['body'][self::CHANNEL_INFORMATION_STATUS_LOGS];
    }

    public function getMerchantConfigurations(array $input)
    {
        return $this->createAndSendRequest(
            parent::FTS_MERCHANT_CONFIGURATIONS_URL,
            Requests::GET,
            $input)['body'][self::MERCHANT_CONFIGURATIONS];
    }

    public function getFailFastStatusLogs(array $input)
    {
        return $this->createAndSendRequest(
            parent::FTS_FAIL_FAST_STATUS_LOGS_GET_URL,
            Requests::GET,
            $input)['body'][self::FAIL_FAST_STATUS_LOGS];
    }

    public function getKeyValueStoreLogs(array $input)
    {
        return $this->createAndSendRequest(
            parent::FTS_KEY_VALUE_STORE_LOGS_GET_URL,
            Requests::GET,
            $input)['body'][self::KEY_VALUE_STORE_LOGS];
    }


    public function fetch(string $entity, string $id, array $input)
    {
        $input += [ 'id' => $id ];

        return $this->getEntity($entity, $input)[0];
    }

    protected function getEntity(string $entity, array $input)
    {
        $methodName = 'get' . studly_case($entity);

        if (method_exists($this, $methodName) === true)
        {
            return $this->$methodName($input);
        }
    }
}
