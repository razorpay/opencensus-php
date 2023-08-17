<?php

namespace RZP\Tests\Functional\Helpers\Edge;

use DateTimeZone;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use RZP\Constants\Mode;
use Razorpay\Edge\Passport\Tests\GeneratesTestPassportJwts;


trait PassportTrait
{
    use GeneratesTestPassportJwts;

    protected function samplePassportJwtBuilder(
        array $consumer = [],
        array $credential = [],
        string $mode = Mode::TEST,
        array $impersonation = [],
        array $oauth = [],
        array $roles = [],
        bool $identified = true,
        bool $authenticated = true
    ): string {
        $sysClock = new SystemClock(new DateTimeZone('UTC'));
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $builder =  $builder
            ->issuedBy('https://edge.razorpay.com')
            ->permittedFor('https://api.razorpay.com')
            ->identifiedBy('per-req-uuid', true)
            ->issuedAt($sysClock->now())
            ->canOnlyBeUsedAfter($sysClock->now())
            ->expiresAt($sysClock->now()->add(new \DateInterval('P15M')))
            ->withHeader('kid', 'edgev1')
            // Custom claims follows.
            ->withClaim('identified', $identified)
            ->withClaim('authenticated', $authenticated)
            ->withClaim('mode', $mode)
            ->withClaim('domain', 'razorpay')
            ->withClaim('consumer', $consumer)
            ->withClaim('credential', $credential);

        if (!empty($impersonation)) {
            $builder = $builder->withClaim('impersonation', $impersonation);
        }

        if (!empty($oauth)) {
            $builder = $builder->withClaim('oauth', $oauth);
        }

        if (!empty($roles)) {
            $builder = $builder->withClaim('roles', $roles);
        }

        return $this->samplePassportJwt($builder);
    }
}
