<?php

namespace Unit\Models\PaymentLink\NocodeCustomUrl;

use RZP\Tests\Unit\Models\PaymentLink\BaseTest;
use RZP\Models\PaymentLink\NocodeCustomUrl\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class EntityTest extends BaseTest
{
    const DOMAIN_MAP = [
        "https://domain.com"                                    => "domain.com",
        "https://domain.co.in"                                  => "domain.co.in",
        "https://abc.domain.com"                                => "abc.domain.com",
        "https://abc.domain.co.in"                              => "abc.domain.co.in",
        "https://domain.com/asdasdad/asdadasd/asdsadasd"        => "domain.com",
        "https://domain.co.in/asdsadasd/asdasdsad/asdasd"       => "domain.co.in",
        "https://abc.domain.com/asdadasd/asdasdsad/asdasd"      => "abc.domain.com",
        "https://abc.domain.co.in/asdasdsad/asdasdsad/asdasd"   => "abc.domain.co.in",

        "domain.com"                                    => "domain.com",
        "domain.co.in"                                  => "domain.co.in",
        "abc.domain.com"                                => "abc.domain.com",
        "abc.domain.co.in"                              => "abc.domain.co.in",
        "domain.com/asdasdad/asdadasd/asdsadasd"        => "domain.com",
        "domain.co.in/asdsadasd/asdasdsad/asdasd"       => "domain.co.in",
        "abc.domain.com/asdadasd/asdasdsad/asdasd"      => "abc.domain.com",
        "abc.domain.co.in/asdasdsad/asdasdsad/asdasd"   => "abc.domain.co.in",
    ];
    public function testDetermineDomainFromUrl()
    {
        foreach (self::DOMAIN_MAP as $url => $domain)
        {
            $op = Entity::determineDomainFromUrl($url);

            $this->assertEquals($domain, $op);
        }

        $this->expectException(BadRequestValidationFailureException::class);
        Entity::determineDomainFromUrl("asdasdasdasdasdas");

        $this->expectException(BadRequestValidationFailureException::class);
        Entity::determineDomainFromUrl("asdadsad.com asdadsadsad");
    }
}
