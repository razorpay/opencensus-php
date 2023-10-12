<?php

namespace RZP\Services\DashboardUI;

use RZP\Constants\Mode;
use RZP\Services\DashboardUI\Constants;
use RZP\Services\Dcs\Configurations\Constants as DcsConstants;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Trace\TraceCode;

class Service
{
    protected $trace;

    protected DcsConfigService $dcsConfigService;

    protected $mode;

    public function __construct($app = null)
    {
        $this->app = $app ?? App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->dcsConfigService = app('dcs_config_service');

        $this->mode = $this->app['rzp.mode'] ?? Mode::LIVE;
    }

    public function editCountryUIConfigs(string $countryCode, $input): void
    {
        $this->trace->info(TraceCode::COUNTRY_CONFIG_EDIT_VIA_DCS,
            [
                'country_code' => $countryCode,
                'input' => $input
            ]);

        $dcsKey = DcsConstants::CountryDashboardConfigurations;
        $configs = [];
        $input = $input[Constants::UI_CONTROLS];

        foreach ($input as $key => $config) {
            foreach ($config as $flag => $status) {
                $configs[$key][$flag] = $status;
            }
        }

        $this->trace->info(TraceCode::COUNTRY_CONFIG_EDIT_VIA_DCS,
            [
                'country_digit_code' => $countryCode,
                'config' => $configs
            ]);

        $this->dcsConfigService->editConfiguration($dcsKey, $countryCode, $configs, $this->mode);
    }

    public function getCountryConfigs(string $countryCode, string $config): array
    {
        $this->trace->info(TraceCode::COUNTRY_CONFIG_GET_VIA_DCS,
            [
                'country_code' => $countryCode,
                'config' => $config,
            ]);

        $fields = [];
        $key = DcsConstants::CountryDashboardConfigurations;

        if (empty($config) === true) {
            foreach (Constants::$uiConfigKeys as $value) {
                $fields[] = $value;
            }
        } else {
            $fields[] = $config;
        }

        $data = $this->dcsConfigService->fetchConfiguration($key, $countryCode, $fields, $this->mode);

        if (empty($config) === false) {
            $data = $data[$config];
        }

        $uiConfig[Constants::UI_CONTROLS] = $data;
        return $uiConfig;
    }
}
