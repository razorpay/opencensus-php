<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Country;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\InvalidArgumentException;
use RZP\Services\DashboardUI;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Org;

class DashboardUIController extends Controller
{
    const COUNTRY = 'country';

    /**
     * Returns all Dashboard UI Config associated to a countryCode
     * $countryCode - 2 character alpha country code - Ex: INDIA - IN, Malaysia - MY in case-insensitive.
     * @throws BadRequestException
     * @throws InvalidArgumentException
     */
    public function getAllCountryDashboardUIConfigs(string $countryCode)
    {
        $this->trace->info(TraceCode::COUNTRY_CONFIG_GET_VIA_DCS, [
            'country_code' => $countryCode,
            'config' => 'all'
        ]);

        if (Country::checkIfValidCountry($countryCode) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $countryDigitCode = Country::getCountryNumericCode($countryCode);

        $data = $this->getConfigs( self::COUNTRY, $countryDigitCode, '');

        return ApiResponse::json($data);
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException
     */
    public function getCountryDashboardUIConfig(string $countryCode, string $config)
    {
        $this->trace->info(TraceCode::COUNTRY_CONFIG_GET_VIA_DCS, [
            'country_code' => $countryCode,
            'config' => $config
        ]);

        if (Country::checkIfValidCountry($countryCode) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $countryDigitCode = Country::getCountryNumericCode($countryCode);

        $data = $this->getConfigs(self::COUNTRY, $countryDigitCode, $config);
        return ApiResponse::json($data);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getConfigs(string $entityType, string $entityId, string $config): array
    {
        if ($entityType === self::COUNTRY) {
            $data = (new DashboardUI\Service($this->app))->getCountryConfigs($entityId, $config);
        } else {
            throw new InvalidArgumentException(ErrorCode::BAD_REQUEST_ENTITY_NOT_SUPPORTED);
        }

        return $data;
    }

    /**
     * @throws BadRequestException
     * @throws InvalidArgumentException
     */
    public function editCountryConfigs(string $countryCode)
    {
        $input = Request::all();

        $this->trace->info(TraceCode::COUNTRY_CONFIG_EDIT_VIA_DCS, [
            'country' => $countryCode,
            'input' => $input
        ]);

        if (Country::checkIfValidCountry($countryCode) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $countryDigitCode = Country::getCountryNumericCode($countryCode);

        $this->editConfigs(self::COUNTRY, $countryDigitCode, $input);

        return ApiResponse::json([]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function editConfigs(string $entityType, string $entityId, $input): void
    {
        if ($entityType === self::COUNTRY) {
            (new DashboardUI\Service($this->app))->editCountryUIConfigs($entityId, $input);
        } else {
            throw new InvalidArgumentException(ErrorCode::BAD_REQUEST_ENTITY_NOT_SUPPORTED);
        }
    }
}
