<?php

namespace RZP\Constants;

/**
 * Environment Constants
 */
final class Environment
{
    const PRODUCTION     = 'production';
    const TESTING        = 'testing';
    const TESTING_DOCKER = 'testing_docker';
    const FUNC           = 'func';
    const DEV            = 'dev';
    const AUTOMATION     = 'automation';
    const PERF           = 'perf';
    const PERF1          = 'perf1';
    const PERF2          = 'perf2';
    const BVT            = 'bvt';
    const AVAILABILITY   = 'availability';
    const AXIS           = 'axis';
    const BETA           = 'beta';
    const QA_ENVS        = [self::PERF, self::FUNC, self::AUTOMATION, self::BVT, self::AVAILABILITY, self::PERF1, self::PERF2];

    const LOWER_ENVS     = [self::DEV, self::BETA, self::AXIS];
    const PERF_ENV       = [self::PERF, self::AVAILABILITY, self::PERF1, self::PERF2];

    public static function isEnvironmentQA(string $env): bool
    {
        return in_array($env, self::QA_ENVS, true);
    }

    public static function isEnvironmentPerf(string $env): bool
    {
        return in_array($env, self::PERF_ENV, true);
    }

    public static function isLowerEnvironment(string $env): bool
    {
        return in_array($env, self::LOWER_ENVS, true);
    }
}
