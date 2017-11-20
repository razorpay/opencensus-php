<?php

namespace RZP\Models\GeoIP;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'geo_ip';

    /**
     * Returns Geo IPs where country is not set,
     * which is current logic to pick geo_ips for update.
     *
     * @param array $ips
     * @param int $limit
     * @return mixed
     */
    public function getGeoIpsWithoutCountry($params): Base\PublicCollection
    {
        if (empty($params[Entity::COUNTRY]) === true)
        {
            $params[Entity::COUNTRY] = 'null';
        }

        if (empty($params[self::COUNT]) === true)
        {
            $params[self::COUNT] = 300;
        }

        $this->processFetchParams($params);

        $query = $this->newQuery();

        $this->buildFetchQuery($query, $params);

        $geoIps = $query->get();

        return $geoIps;
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::IP, 'desc');
    }
}
