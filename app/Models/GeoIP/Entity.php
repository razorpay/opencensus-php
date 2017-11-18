<?php

namespace RZP\Models\GeoIP;

use RZP\Models\Base;

class Entity extends Base\Entity
{
    const IP                            = 'ip';
    const CITY                          = 'city';
    const STATE                         = 'state';
    const POSTAL                        = 'postal';
    const COUNTRY                       = 'country';
    const CONTINENT                     = 'continent';
    const LATITUDE                      = 'latitude';
    const LONGITUDE                     = 'longitude';
    const ISP                           = 'isp';

    protected $entity = 'geo_ip';

    protected $fillable = [
        self::IP,
        self::CITY,
        self::STATE,
        self::POSTAL,
        self::COUNTRY,
        self::CONTINENT,
        self::LATITUDE,
        self::LONGITUDE,
        self::ISP,
    ];
}
