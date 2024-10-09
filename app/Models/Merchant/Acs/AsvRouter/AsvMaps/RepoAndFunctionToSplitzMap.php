<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;

// this is a map that, maps repo class to SDK wrapper class and returns the wrapper class instance

use RZP\Error\ErrorCode;
use RZP\Models\Address\Repository as AddressRepository;
use RZP\Models\Merchant\Account\Repository as AccountRepository;
use RZP\Models\Merchant\Stakeholder\Repository as StakeholderRepository;
use RZP\Models\Merchant\Website\Repository as MerchantWebsiteRepository;
use RZP\Models\Merchant\Email\Repository as MerchantEmailRepository;
use RZP\Models\Merchant\Document\Repository as MerchantDocumentRepository;
use RZP\Models\Merchant\BusinessDetail\Repository as MerchantBusinessDetailRepository;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as AsvConstant;
use RZP\Models\Merchant\Detail\Repository as MerchantDetailRepository;

final class RepoAndFunctionToSplitzMap
{
    public const MAP = array(
        MerchantWebsiteRepository::class => array(
            FunctionConstant::FIND_OR_FAIL => SplitzConstant::SPLITZ_WEBSITE_READ_FIND,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_WEBSITE_READ_FIND,
            FunctionConstant::FIND         =>  SplitzConstant::SPLITZ_WEBSITE_FIND,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_WEBSITE_SAVE_OR_FAIL,
            FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_IMPLICIT_JOIN_WEBSITE_BY_MERCHANTID,
        ),
        MerchantEmailRepository::class => array(
            FunctionConstant::GET_BY_MERCHANT_ID => SplitzConstant::SPLITZ_EMAIL_GET_BY_MERCHANT_ID,
            FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID => SplitzConstant::SPLITZ_EMAIL_GET_BY_TYPE_AND_MERCHANT_ID,
            FunctionConstant::FIND         =>  SplitzConstant::SPLITZ_EMAIL_FIND,
            FunctionConstant::FIND_OR_FAIL => SplitzConstant::SPLITZ_EMAIL_GET_BY_ID,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_EMAIL_GET_BY_ID,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_EMAIL_SAVE_OR_FAIL,
        ),
        MerchantBusinessDetailRepository::class => array(
            FunctionConstant::GET_BY_MERCHANT_ID => SplitzConstant::SPLITZ_BUSINESS_DETAIL_GET_BY_MERCHANT_ID,
            FunctionConstant::FIND_OR_FAIL => SplitzConstant::SPLITZ_BUSINESS_DETAIL_GET_BY_ID,
            FunctionConstant::FIND => SplitzConstant::SPLITZ_BUSINESS_DETAIL_FIND,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_BUSINESS_DETAIL_GET_BY_ID,
            FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_IMPLICIT_JOIN_BUSINESS_DETAIL_BY_MERCHANTID,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_BUSINESS_DETAIL_SAVE_OR_FAIL,
        ),
        MerchantDocumentRepository::class => array(
            FunctionConstant::GET_BY_ID => SplitzConstant::SPLITZ_DOCUMENT_GET_BY_ID,
            FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID => SplitzConstant::SPLITZ_DOCUMENT_GET_BY_TYPE_AND_MERCHANT_ID,
            FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_IMPLICIT_JOIN_DOCUMENT_BY_MERCHANTID,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_DOCUMENT_SAVE_OR_FAIL,
            FunctionConstant::FIND => SplitzConstant::SPLITZ_MERCHANT_DOCUMENT_FIND,
            FunctionConstant::DELETE_OR_FAIL=>SplitzConstant::SPLITZ_MERCHANT_DOCUMENT_DELETE_OR_FAIL
        ),
        MerchantRepository::class => array(
            FunctionConstant::FIND_OR_FAIL =>  SplitzConstant::SPLITZ_MERCHANT_GET_BY_ID,
            FunctionConstant::FIND         =>  SplitzConstant::SPLITZ_MERCHANT_FIND,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_MERCHANT_GET_BY_ID,
            FunctionConstant::FIND_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_MERCHANT_FIND_FOR_IMPLICIT_JOIN,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_SAVE_OR_FAIL,
        ),
        AccountRepository::class => array(
            FunctionConstant::FIND_OR_FAIL =>  SplitzConstant::SPLITZ_MERCHANT_GET_BY_ID,
            FunctionConstant::FIND         =>  SplitzConstant::SPLITZ_MERCHANT_FIND,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_MERCHANT_GET_BY_ID,
            FunctionConstant::FIND_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_MERCHANT_FIND_FOR_IMPLICIT_JOIN,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_SAVE_OR_FAIL,
        ),
        AddressRepository::class => array(
            FunctionConstant::GET_BY_STAKEHOLDER_ID => SplitzConstant::SPLITZ_ADDRESS_GET_BY_STAKEHOLDER_ID,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_ADDRESS_SAVE_OR_FAIL,
            FunctionConstant::FIND => SplitzConstant::SPLITZ_ADDRESS_FIND,
        ),
        StakeholderRepository::class => array(
            FunctionConstant::FIND_OR_FAIL => SplitzConstant::SPLITZ_STAKEHOLDER_GET_BY_ID,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_STAKEHOLDER_GET_BY_ID,
            FunctionConstant::GET_BY_MERCHANT_ID => SplitzConstant::SPLITZ_STAKEHOLDER_GET_BY_MERCHANT_ID,
            FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_IMPLICIT_JOIN_STAKEHOLDER_BY_MERCHANTID,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_STAKEHOLDER_SAVE_OR_FAIL,
            FunctionConstant::FIND => SplitzConstant::SPLITZ_STAKEHOLDER_FIND,
        ),
        MerchantDetailRepository::class => array(
            FunctionConstant::FIND_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_DETAIL_GET_BY_ID,
            FunctionConstant::FIND_OR_FAIL_PUBLIC => SplitzConstant::SPLITZ_MERCHANT_DETAIL_GET_BY_ID,
            FunctionConstant::FIND_FOR_IMPLICIT_JOIN => SplitzConstant::SPLITZ_MERCHANT_DETAIL_FIND_FOR_IMPLICIT_JOIN,
            FunctionConstant::SAVE_OR_FAIL => SplitzConstant::SPLITZ_MERCHANT_DETAIL_SAVE_OR_FAIL,
            FunctionConstant::FIND => SplitzConstant::SPLITZ_MERCHANT_DETAIL_FIND,
        ),
    );

    public const SPLITZ_REMOVAL_MAP = array(
        SplitzConstant::SPLITZ_WEBSITE_READ_FIND => true,
        SplitzConstant::SPLITZ_BUSINESS_DETAIL_GET_BY_MERCHANT_ID => true,
        SplitzConstant::SPLITZ_BUSINESS_DETAIL_GET_BY_ID => true,
        SplitzConstant::SPLITZ_DOCUMENT_GET_BY_ID => true,
        SplitzConstant::SPLITZ_DOCUMENT_GET_BY_TYPE_AND_MERCHANT_ID => true,
        SplitzConstant::SPLITZ_EMAIL_GET_BY_MERCHANT_ID => true,
        SplitzConstant::SPLITZ_EMAIL_GET_BY_TYPE_AND_MERCHANT_ID => true,
        SplitzConstant::SPLITZ_EMAIL_GET_BY_ID => true,
        SplitzConstant::SPLITZ_STAKEHOLDER_GET_BY_ID => true,
        SplitzConstant::SPLITZ_STAKEHOLDER_GET_BY_MERCHANT_ID => true,
        SplitzConstant::SPLITZ_ADDRESS_GET_BY_STAKEHOLDER_ID => true,

    );

    public const SPLITZ_REMOVED_FILTER = array(
        "countAccountCodeForMerchant",
        "FeatureCoreCreate_fetchSubmerchantsFromAppIds",
        "fetchActivatedSubMerchantIdsForPartner",
        "fetchAffiliatedPartnersForSubmerchant",
        "fetchEntitiesForReport",
        "fetchLinkedAccountIdsForParentMerchantIds",
        "fetchLinkedAccountMids",
        "fetchLinkedAccountMidsSuspendedDueToParentMerchantSuspension",
        "fetchLinkedAccountsCount",
        "fetchMerchantsByLegalEntityId",
        "fetchMerchantsCreatedBetween",
        "fetchSubMerchantForPartnerAndSubMerchantId",
        "fetchSubMerchantIdsForPartnerBank",
        "fetchUnsuspendedLinkedAccountMids",
        "findByAccountIdAndParent",
        "findDocumentByFileStoreId",
        "findDocumentsForEntityTypeAndEntityId",
        "findDocumentsForMerchantIdAndDocumentTypeAndDate",
        "findDocumentsForMerchantIdAndValidationId",
        "findDocumentsForMerchantIds",
        "findMerchantIdsByExternalIds",
        "findNonDeletedDocumentForMerchantIdAndValidationId",
        "findNonDeletedDocumentsForMerchantId",
        "getAccountCodeById",
        "getDetailsUnified",
        "getEmailByMerchantId",
        "getEmailsByMerchantIdsAndTypes",
        "getIdByAccountCodeAndParent",
        "getMerchantDetailsForPayroll",
        "getMerchantsForSettlementsEventsCron",
        "getMerchantUserMapping",
        "getNonPurePlatformPartnerMapping",
        "getNonSuspendedMerchantsFromIds",
        "getSubMerchantsForPartnerAndApplication",
        "InvitationCoreAccept",
        "UserCoreGet",
        "validateExternalIdForPartnerSubmerchant",
        "countAccountCodeForMerchant",
        "findAllDocumentsForMerchant",
        "getAllWebsiteDetailsForMerchantId",
        "isAnExistingUserOnVendorPortal",
        "Mailable_getUserOrgData",
        "revokeTokenOnPasswordChange",
        "fetchMerchantsCountWithPricingPlanId",
        "checkAccessForMerchant",
        "fetchQueuedPayoutsForBalanceId",
        "filterOnHoldMerchants",
        "fetchPartnerRelatedEntitiesForPRTS",
        "MerchantEsSync",
        "isSecondFactorAuthEnabledForUserMerchants",
        "findOrFailPublicWithRelations",
        "findByIdAndOrgId",
        "fetchByEmailAndOrgId",
        "fetchMerchantsCreatedBetweenOfOrg",
        "fetchLiveEnabledLinkedAccountMids",
        "fetchLinkedAccountMidsLiveDisabledToParentMerchantLiveDisabled",
        "accountFindByIdAndMerchant",
        "findMerchantsByIds",
        "findMany",
        "documentFindByIdAndMerchant",
        "stakeholderFindByIdAndMerchantId",
        "getFailedSettlementsForRetry",
        "fetchLinkedAccountsForParentMerchantId",
    );

    public const ROUTE_WRITE_FLOW_TO_ASV = SplitzConstant::SPLITZ_SEND_WRITE_TO_ASV;
    public const ROUTE_FILTER_REQUEST_TO_ASV = SplitzConstant::SPLITZ_SEND_FILTER_TO_ASV;
    public const ROUTE_TRANSACTION_FLOW_TO_ASV = SplitzConstant::SPLITZ_SEND_TRANSACTION_FLOW_TO_ASV;
    public const ROUTE_RELOAD_REQUEST_TO_ASV = SplitzConstant::SPLITZ_SEND_RELOAD_TO_ASV;
    public const ENABLE_EXCLUSION_FLOW = SplitzConstant::SPLITZ_ENABLE_EXCLUSION_FLOW;
    public const HANDLE_OPEN_TRANSACTION = SplitzConstant::SPLITZ_HANDLE_OPEN_TRANSACTION;

    public static function getExperimentName(string $repoClass, string $functionName): string
    {
        if (isset(self::MAP[$repoClass]) === true) {
            if (isset(self::MAP[$repoClass][$functionName]) === true) {
                return self::MAP[$repoClass][$functionName];
            }
        }

        throw new \Exception(ErrorCode::ASV_MAPPING_NOT_PRESENT_ERROR);
    }

    public static function isExperimentRemoved(string $experimentName): bool
    {
        return self::SPLITZ_REMOVAL_MAP[$experimentName] ?? false;
    }


    public static function getExperimentNameForFilterMigration(): string {
        return self::ROUTE_FILTER_REQUEST_TO_ASV;
    }

    public static function getExperimentNameForReloadMigration(): string {
        return self::ROUTE_RELOAD_REQUEST_TO_ASV;
    }

    public static function getExperimentNameForTransactionFlow(): string {
        return self::ROUTE_TRANSACTION_FLOW_TO_ASV;
    }

    public static function getExperimentNameForEnableExclusionFlow(): string {
        return self::ENABLE_EXCLUSION_FLOW;
    }

    public static function getExperimentNameForHandlingOpenTransaction(): string {
        return self::HANDLE_OPEN_TRANSACTION;
    }
}
