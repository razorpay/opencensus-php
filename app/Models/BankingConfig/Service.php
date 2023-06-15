<?php

namespace RZP\Models\BankingConfig;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;


class Service extends Base\Service
{
    protected $ba;

    public function __construct($app = null)
    {
        parent::__construct();

        $this->ba = $this->app['basicauth'];

        $this->core = new Core;
    }

    public function fetchAllBankingConfigs()
    {
        $this->trace->info(TraceCode::DCS_FETCH_BANKING_CONFIGS, []);

        return Constants::BANKING_CONFIGS;
    }

    public function upsertBankingConfigs($input)
    {
        $this->trace->info(TraceCode::DCS_UPSERT_BANKING_CONFIG, [
            'input' => $input
        ]);

        $orgId = $this->ba->getOrgId();

        $sessionOrgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        return $this->core->upsertBankingConfigs($input, $sessionOrgId);
    }

    public function getBankingConfig($input)
    {
        $this->trace->info(TraceCode::DCS_GET_BANKING_CONFIG, [
            'input' => $input
        ]);

        (new Validator)->validateInput(
            'get',
            $input
        );

        $dcsConfigService = app('dcs_config_service');

        $key = $input[Constants::SHORT_KEY];

        $entityId = $input[Constants::ENTITY_ID];

        $fields = $input[Constants::FIELDS];

        return $dcsConfigService->fetchConfiguration($key, $entityId, $fields, $this->mode);    }


}
