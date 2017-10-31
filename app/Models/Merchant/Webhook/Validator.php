<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::URL     => 'required|string|url|max:255|min:3',
        Entity::EVENTS  => 'required|array',
        Entity::SECRET  => 'sometimes|string|max:255',
    ];

    protected static $createValidators = [
        'events',
        'url',
    ];

    protected static $editRules = [
        Entity::URL     => 'sometimes|filled|string|url|max:255|min:3',
        Entity::EVENTS  => 'sometimes|array',
        Entity::ACTIVE  => 'sometimes|in:0,1',
        Entity::SECRET  => 'sometimes|string|max:255',
    ];

    protected static $editValidators = [
        'events',
        'url',
    ];

    // Refer: http://www-archive.mozilla.org/projects/netlib/PortBanning.html#portlist
    const RESTRICTED_PORTS = [
        1, 7, 9, 11, 13, 15, 17, 19, 20, 21, 22, 23, 25, 37, 42, 43,
        53, 77, 79, 87, 95, 101, 102, 103, 104, 109, 110, 111, 113,
        115, 117, 119, 123, 135, 139, 143, 179, 389, 465, 512, 513,
        514, 515, 526, 530, 531, 532, 540, 556, 563, 587, 601, 636,
        993, 995, 2049, 4045, 6000
    ];

    private function validatePublicIp($url)
    {
        if ($this->validatePublicIpAddress($url) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'URL must point to a public IP address');
        }
    }

    /**
     * Do not allow internal or reserved IP addresses
     *
     * Fails validation for the following private IPv4 ranges:
     *     10.0.0.0/8
     *     172.16.0.0/12
     *     192.168.0.0/16.
     * Fails validation for the IPv6 addresses starting with FD or FC.
     *
     * Fails validation for the following reserved IPv4 ranges:
     *     0.0.0.0/8
     *     169.254.0.0/16
     *     127.0.0.0/8
     *     240.0.0.0/4.
     * Fails validation for the following reserved IPv6 ranges:
     *     ::1/128
     *     ::/128
     *     ::ffff:0:0/96
     *     fe80::/10.
     */
    public function validatePublicIpAddress(string $url)
    {
        $components = parse_url($url);

        $host = $components['host'];

        // In case merchant isn't using a hostname (http://1.2.3.4/hello)
        // this will return the hostname as the IP, and work as expected
        $ip = gethostbyname($host);

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            [
                'flags' => $flags,
            ]
        );
    }

    protected function validateUrl($input)
    {
        if (isset($input[Entity::URL]) === false)
        {
            return;
        }

        $this->validatePublicIp($input[Entity::URL]);

        $components = parse_url($input[Entity::URL]);

        if (isset($components['scheme']) === true)
        {
            $scheme = strtolower($components['scheme']);

            if (($scheme !== 'http') and
                ($scheme !== 'https'))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Only http or https schemes are allowed in webhook url.');
            }
        }

        if (isset($components['port']) === true)
        {
            $port = $components['port'];

            // Not strict check on purpose.
            if (in_array($port, self::RESTRICTED_PORTS))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'The provided port is restricted and cannot be used in a webhook URL.');
            }
        }
    }

    protected function validateEvents($input)
    {
        if (isset($input[Entity::EVENTS]) === false)
        {
            return;
        }

        $events = $input[Entity::EVENTS];

        foreach ($events as $event => $value)
        {
            if (Event::validateEventName($event) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid event name: ' . $event,
                    Entity::EVENTS);
            }

            if (($value !== '0') and ($value !== '1'))
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Not a valid event value',
                    Entity::EVENTS);
            }
        }
    }
}
