<?php

namespace RZP\Models\GeoIP;

use RZP\Models\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Entity extends Base\PublicEntity
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

    protected $primaryKey = self::IP;

    protected $fillable = [
        self::CITY,
        self::STATE,
        self::POSTAL,
        self::COUNTRY,
        self::CONTINENT,
        self::LATITUDE,
        self::LONGITUDE,
        self::ISP,
    ];

    public function getIp()
    {
        return $this->getAttribute(self::IP);
    }

    /**
     * Overriding default behavior as to validate IP
     * IP could be both IPv4 or IPv6
     *
     * @param $ip
     * @param bool $throw
     */
    public static function verifyUniqueId($ip, $throw = true): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false)
        {
            if ($throw === true)
            {
                throw new BadRequestValidationFailureException($ip . ' is not a valid IP');
            }

            return false;
        }

        return true;
    }
}
