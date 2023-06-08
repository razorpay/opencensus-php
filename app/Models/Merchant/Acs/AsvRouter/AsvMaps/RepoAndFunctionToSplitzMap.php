<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;

// this is a map that, maps repo class to SDK wrapper class and returns the wrapper class instance

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Website\Repository as MerchantWebsiteRepository;
use RZP\Models\Merchant\Email\Repository as MerchantEmailRepository;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as AsvConstant;

final class RepoAndFunctionToSplitzMap {
    public const MAP = array(
        MerchantWebsiteRepository::class => array(
            FunctionConstant::FIND_AND_FAIL => SplitzConstant::SPLITZ_WEBSITE_READ_FIND,
            FunctionConstant::FIND_AND_FAIL_PUBLIC => SplitzConstant::SPLITZ_WEBSITE_READ_FIND,
        ),
        MerchantEmailRepository::class => array(
            FunctionConstant::GET_BY_MERCHANT_ID => SplitzConstant::SPLITZ_EMAIL_GET_BY_MERCHANT_ID,
            FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID => SplitzConstant::SPLITZ_EMAIL_GET_BY_TYPE_AND_MERCHANT_ID,
            FunctionConstant::FIND_AND_FAIL => SplitzConstant::SPLITZ_EMAIL_GET_BY_ID,
            FunctionConstant::FIND_AND_FAIL_PUBLIC => SplitzConstant::SPLITZ_EMAIL_GET_BY_ID,
        )
    );

    public static function getExperimentName(string $repoClass, string $functionName): string {
        if (isset(self::MAP[$repoClass]) === true) {
            if (isset(self::MAP[$repoClass][$functionName]) === true) {
                return self::MAP[$repoClass][$functionName];
            }
        }

        throw new \Exception(ErrorCode::ASV_MAPPING_NOT_PRESENT_ERROR);
    }
}
