<?php

namespace RZP\Models\Merchant;

use RZP\Constants\TLD;
use RZP\Error\P2p\ErrorCode;
use RZP\Exception\BadRequestException;

class TLDExtract
{

    /**
     * @param string $url
     *
     * @return string|null
     */
    public function getEffectiveTLDPlusOne(string $url)
    {
        if (empty(trim($url)) === true)
        {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (empty($host) === true)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_WEBSITE);
        }

        $host = strtolower($host);

        list($registeredDomain, $tld) = $this->extract($host);

        $lastDot = strrpos($registeredDomain, '.');

        $domain = $lastDot !== false ? substr($registeredDomain, $lastDot + 1) : $registeredDomain;

        return $domain . '.' . $tld;

    }

    public function extract($host)
    {
        $parts = explode('.', $host);

        for ($dotPos = 0; $dotPos < count($parts); $dotPos++)
        {
            $maybeTld = join('.', array_slice($parts, $dotPos));

            $wildcardTld = '*.' . join('.', array_slice($parts, $dotPos + 1));

            if ($this->ruleExists($wildcardTld)
                or $this->ruleExists($maybeTld))
            {

                return array(join('.', array_slice($parts, 0, $dotPos)), $maybeTld);
            }
        }

        return array($host, '');
    }

    private function ruleExists($tld)
    {
        return in_array($tld, TLD::TLDS);
    }
}
