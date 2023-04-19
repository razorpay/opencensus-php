<?php

namespace RZP\Models\NetbankingConfig;

use App;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\VirtualAccount\Entity;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Trace\TraceCode;


class Service extends Base\Service
{
    protected $ba;

    public function __construct($app = null)
    {
        parent::__construct();

        $this->ba = $this->app['basicauth'];
    }

    public function createNetBankingConfiguration($input)
    {
        $this->trace->info(
            TraceCode::NETBANKING_CONFIG_CREATE_REQUEST,
            [
                "input_data" => $input
            ]);

        $fields = $input[Constants::FIELDS];

        foreach ($fields as $key => $value)
        {
            if(!array_key_exists($key, Constants::NETBANKING_CONFIGS)) {
                $this->trace->info(
                    TraceCode::NETBANKING_CONFIG_CREATE_REQUEST,
                    [
                        $key => $value,
                        'const' => Constants::NETBANKING_CONFIGS
                    ]);
            }
        }

        (new Validator())->validateCreateConfig($input);

        $fields = $input[Constants::FIELDS];

        $merchantId = $input[Constants::MERCHANT_ID];

        $this->isAdminAuthorized($merchantId);

        $dcsConfigService = app('dcs_config_service');

        $key = DcsConstants::NetbankingConfigurations;

        $response = [];

        $dcsConfigService->createConfiguration($key, $merchantId, $fields, $this->mode);

        $response["success"] = true;

        return $response;
    }


    public function fetchNetbankingConfigs(array $input)
    {
        $this->trace->info(
            TraceCode::NETBANKING_CONFIG_FETCH_REQUEST,
            [
                "input_data" => $input
            ]);

        $dcsConfigService = app('dcs_config_service');

        (new Validator())->validateFetchConfig($input);

        $key = DcsConstants::NetbankingConfigurations;

        $fields = [Constants::AUTO_REFUND_OFFSET];

        $merchantId = $input[Constants::MERCHANT_ID];

        return $dcsConfigService->fetchConfiguration($key, $merchantId, $fields, $this->mode);
    }

    public function editNetbankingConfigs(array $input)
    {
        $this->trace->info(
            TraceCode::NETBANKING_CONFIG_EDIT_REQUEST,
            [
                "input_data" => $input
            ]);

        (new Validator())->validateEditConfig($input);

        $fields = $input[Constants::FIELDS];

        $merchantId = $input[Constants::MERCHANT_ID];

        $this->isAdminAuthorized($merchantId);

        $dcsConfigService = app('dcs_config_service');

        $key = DcsConstants::NetbankingConfigurations;

        $response = [];

        $dcsConfigService->editConfiguration($key, $merchantId, $fields, $this->mode);

        $response["success"] = true;

        return $response;
    }


    public function isAdminAuthorized($merchantId)
    {
        // admin should only get / set expir for merchants of their org
        $orgId = $this->ba->getOrgId();

        $sessionOrgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $merchant = $this->repo->merchant->findOrFail($merchantId);
        
        if ($sessionOrgId !== $merchant->org->getId())
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }
    }
}
