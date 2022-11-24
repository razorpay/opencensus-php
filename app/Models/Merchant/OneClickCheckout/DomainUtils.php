<?php

namespace RZP\Models\Merchant\OneClickCheckout;

use Requests;
use \WpOrg\Requests\Response;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class DomainUtils
{

    const AWS_META_DATA_IPv4 = '169.254.169.254';
    const AWS_META_DATA_IPv6 = 'fe80::a9fe:a9fe';

    // https://write.razorpay.com/doc/domains-jmYrrRiMZp
    const RZP_DOMAINS = [
        'howupi.works'            => 1,
        'indiastack.co'           => 1,
        'razorpay.com'            => 1,
        'razorpay.in'             => 1,
        'razorpay.me'             => 1,
        'razorpay.wtf'            => 1,
        'razorpaymail.com'        => 1,
        'razorpaymailer.com'      => 1,
        'razorpayx.com'           => 1,
        'rzp.io'                  => 1,
        'sfpayments.com'          => 1,
        'tazorpay.com'            => 1,
        'your-awesome-site.com'   => 1,
        'magiccheckout.online'    => 1,
        'magiccheckout.live'      => 1,
        'magiccheckout.club'      => 1,
        'magicalcheckout.in'      => 1,
        'magiccheckouts.com'      => 1,
        'magiccheckout.pro'       => 1,
        'magiccheckoutonline.com' => 1,
        'checkoutmagic.in'        => 1,
        'checkoutmagic.co'        => 1,
        'checkoutmagic.live'      => 1,
        'checkoutmagic.club'      => 1,
        'checkoutmagic.net'       => 1,
        'magicpayment.co'         => 1,
        'magicpayment.club'       => 1,
        'magiccheckout.in'        => 1,
        'razorpay.tech'           => 1,
        'razorpay.dev'            => 1,
        'thirdwatch.co.in'        => 1,
        'thirdwatch.ai'           => 1,
        'opfin.com'               => 1,
        'payrollfixed.com'        => 1,
        'cardhq.com'              => 1,
    ];

    public static function isHostPublic($host): bool
    {
        // Get IPv4 addresses
        $ips = gethostbynamel($host);
        if ($ips === false)
        {
            # Get IPv6 addresses
            $records = dns_get_record($host, DNS_AAAA);
            if ($records === false)
            {
                return true;
            }
            $ips = array_map(function ($val)
            {
                return $val['ipv6'];
            }, $records);
        }
        // Checking for AWS metadata endpoint
        if (in_array(self::AWS_META_DATA_IPv4, $ips))
        {
            return false;
        }

        $awsIPv6 = inet_pton(self::AWS_META_DATA_IPv6);

        $awsIp = array_first($ips, function ($val) use($awsIPv6)
        {
            return $awsIPv6 === inet_pton($val);
        });

        $privateIp = array_first($ips, function ($val)
        {
            // Get the first privateIp
            return (
                filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) &&
                filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)
            ) === false;
        });

        return $awsIp === null && $privateIp === null;
    }

    public static function verifyNonRZPDomain($url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $domain = self::getDomainFromSubdomain($host);
        return isset(self::RZP_DOMAINS[$domain]) === false;
    }

    protected static function getDomainFromSubdomain(string $host): string
    {
        $parts = explode('.', $host);
        $tld = end($parts);
        $domain = prev($parts);
        if ($tld == 'in' && $domain === 'co')
        {
            // Only verifying for co.in as rzp domains only use these if it's a 2 part tld
            $tld = 'co.in';
            $domain = prev($parts);
        }

        return $domain . '.' . $tld;
    }

    /**
     * @throws Requests_Exception
     * @throws BadRequestException
     */
    public static function sendExternalRequest($url, $headers = [], $data = [], $type = Requests::GET,
                                               $options = [])
    {
        // Verify $url points to public IPs (IPv4)
        if (self::isHostPublic(parse_url($url, PHP_URL_HOST)) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        return Requests::request($url, $headers, $data, $type, $options);
    }
}
