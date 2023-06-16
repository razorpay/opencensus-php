<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;


use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite as MerchantWebsiteSDKWrapper;
use RZP\Models\Merchant\BusinessDetail\Repository as MerchantBusinessDetailRepository;
use RZP\Models\Merchant\Website\Repository as MerchantWebsiteRepository;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail as MerchantEmailSDKWrapper;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\BusinessDetail as BusinessDetailSdkWrapper;
use RZP\Models\Merchant\Email\Repository as MerchantEmailRepository;
use RZP\Models\Merchant\Document\Repository as MerchantDocumentRepository;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument as MerchantDocumentSDKWrapper;


final class RepoToSdkWrapperMap {

    // this is a map that, maps repo class to SDK wrapper class and returns the wrapper class instance
    public const MAP = array(
        MerchantWebsiteRepository::class => MerchantWebsiteSDKWrapper::class,
        MerchantEmailRepository::class => MerchantEmailSDKWrapper::class,
        MerchantBusinessDetailRepository::class => BusinessDetailSdkWrapper::class,
        MerchantDocumentRepository::class => MerchantDocumentSDKWrapper::class,
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
