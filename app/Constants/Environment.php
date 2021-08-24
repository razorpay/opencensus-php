<?php

namespace RZP\Constants;

/**
 * Environment Constants
 */
final class Environment
{
    const PRODUCTION        = 'production';
    const TESTING           = 'testing';
    const TESTING_DOCKER    = 'testing_docker';
    const FUNC              = 'func';
    const DEV               = 'dev';
    const AUTOMATION        = 'automation';
    const PERF              = 'perf';
    const BVT               = 'bvt';
    const AXIS              = 'axis';
    const BETA              = 'beta';
    const QA_ENVS           = [self::PERF, self::FUNC, self::AUTOMATION, self::BVT];

    public static function isEnvironmentQA(string $env): bool
    {
        return in_array($env, self::QA_ENVS, true);
    }
}
