<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;

// this is a map that, maps repo class to SDK wrapper class and returns the wrapper class instance

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite as MerchantWebsiteSDKWrapper;
use RZP\Models\Merchant\Website\Repository as MerchantWebsiteRepository;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail as MerchantEmailSDKWrapper;
use RZP\Models\Merchant\Email\Repository as MerchantEmailRepository;


final class RepoToSdkWrapperMap {
    public const MAP = array(
        MerchantWebsiteRepository::class => MerchantWebsiteSDKWrapper::class,
        MerchantEmailRepository::class => MerchantEmailSDKWrapper::class,
    );

    /**
     * @throws \Exception
     */
    public static function getWrapperInstance($repoClass) {
        if (array_key_exists($repoClass, self::MAP)) {
            $wrapperClass = self::MAP[$repoClass];
            return new $wrapperClass();
        }

        throw new \Exception( ErrorCode::ASV_MAPPING_NOT_PRESENT_ERROR);
    }
}
