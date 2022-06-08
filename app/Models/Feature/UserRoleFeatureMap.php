<?php

namespace RZP\Models\Feature;

use RZP\Models\User\BankingRole;

class UserRoleFeatureMap
{
    private static $roleFeature;

    private static function init()
    {
        $roleFeature = [
            BankingRole::OWNER => [
                Constants::VIEW_OPFIN_SSO_ANNOUNCEMENT,
                Constants::VIEW_SSL_BANNER,
                Constants::VIEW_ONBOARDING_CARDS,
            ],
            BankingRole::ADMIN => [
                Constants::VIEW_OPFIN_SSO_ANNOUNCEMENT,
                Constants::VIEW_SSL_BANNER,
                Constants::VIEW_ONBOARDING_CARDS,
            ],
            BankingRole::FINANCE_L1 => [
                Constants::VIEW_ONBOARDING_CARDS,
                Constants::CAN_ROLE_VIEW_TRXN_CARDS,
            ],
            BankingRole::OPERATIONS => [
                Constants::VIEW_ONBOARDING_CARDS,
            ],
            BankingRole::CHARTERED_ACCOUNTANT => [
                Constants::VIEW_ONBOARDING_CARDS,
            ],
            BankingRole::VIEW_ONLY => [
                Constants::RX_BLOCK_REPORT_DOWNLOAD_ROLE_CHECK,
                Constants::CAN_ROLE_VIEW_TRXN_CARDS,
            ],
            BankingRole::VENDOR => [
                Constants::VIEW_ONBOARDING_CARDS,
            ],
        ];

        $roleFeature[BankingRole::FINANCE_L2] = $roleFeature[BankingRole::FINANCE_L1];
        $roleFeature[BankingRole::FINANCE_L3] = $roleFeature[BankingRole::FINANCE_L1];

        self::$roleFeature = $roleFeature;
    }

    private static function getFeatureRoleMap()
    {
        if (empty(self::$roleFeature) === true)
        {
            self::init();
        }

        return self::$roleFeature;
    }

    public static function getFeaturesForRole($role)
    {
        if(isset($role) === false or empty($role) === true)
        {
            return [];
        }
        return self::getFeatureRoleMap()[$role] ?? [];
    }
}
