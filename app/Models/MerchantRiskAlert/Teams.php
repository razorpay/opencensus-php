<?php

namespace RZP\Models\MerchantRiskAlert;

class Teams {

    const OPS_TEAMS = [
        Constants::MERCHANT_RISK_TEAM_NAME,
        Constants::MERCHANT_RISK_FUNDS_ON_HOLD_TEAM_NAME,
        Constants::MERCHANT_RISK_BANKING_TEAM_NAME,
        Constants::RISK_ONBOARDING_TEAM_NAME,
        Constants::CPV_CHECK_TEAM_NAME,
        Constants::TRANSACTION_MONITORING_TEAM_NAME,
    ];

    public static function isValidTeam(string $team): bool {
        if(in_array($team, self::OPS_TEAMS) === true){
            return true;
        }
        return false;
    }
}

