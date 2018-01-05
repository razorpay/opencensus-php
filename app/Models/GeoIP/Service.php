<?php

namespace RZP\Models\GeoIP;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

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

            if ($geolocation === null)
            {
                $geoIp->setCountryNone();
            }
            else
            {
                $geoIp->fill($geolocation);

                $response['success']++;
            }

            $geoIp->saveOrFail();
        }

        $this->trace->info(TraceCode::GEOLOCATION_UPDATE_RESPONSE, $response);

        return $response;
    }
}
