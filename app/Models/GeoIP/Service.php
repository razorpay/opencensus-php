<?php

namespace RZP\Models\GeoIP;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Services\Geolocation\Service as GeoLocation;

class Service extends Base\Service
{
    /**
     * Fills details about IPs which are left
     *
     * @param $input
     * @return array
     */
    public function updateGeoIps($input): array
    {
        $geoLocationService = $this->app['geolocation'];

        $geoLocationService->validateAndSetInput($input);

        $params = $geoLocationService->removeServiceFieldsFromInput($input);

        $geoIps = $this->repo
                       ->geo_ip
                       ->getGeoIpsWithoutCountry($params);

        $response = [
            'total'   => $geoIps->count(),
            'success' => 0
        ];

        foreach ($geoIps as $geoIp)
        {
            $geolocation = $geoLocationService->getGeoLocation($geoIp->getIp());

            if (empty($geolocation) === true)
            {
                continue;
            }

            $geoIp->fill($geolocation);

            $geoIp->saveOrFail();

            $response['success']++;
        }

        return $response;
    }
}
