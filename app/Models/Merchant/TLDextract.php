<?php

namespace RZP\Models\Merchant;

use RZP\Constants\TLD;

class TLDExtract
{

    public function getEffectiveTLDPlusOne(string $url)
    {
        $host = parse_url($url, PHP_URL_HOST);

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
