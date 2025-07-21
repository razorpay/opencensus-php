<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Feature;
use RZP\Models\FundAccount;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;

class Utils
{
    /**
     * Check if VPA FAV should go through ledger reverse shadow flow
     *
     * @param Entity $validation
     * @return bool
     */
    public static function shouldVpaFavGoThroughLedgerReverseShadowFlow(Entity $validation): bool
    {
        if (($validation->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) and
            ($validation->isBalanceTypeBanking() === true) and
            ($validation->getFundAccountType() === FundAccount\Type::VPA) and
            (self::isLedgerFeeDeductionForVpaTypeFavEnabled($validation->merchant->getId()) === true))
        {
            return true;
        }

        return false;
    }

    /**
     * Check if ledger fee deduction for VPA type FAV is enabled for a merchant
     *
     * @param string $merchantId
     * @return bool
     */
    public static function isLedgerFeeDeductionForVpaTypeFavEnabled(string $merchantId): bool
    {
        $requestPayload = [
            "id" => $merchantId,
            "experiment_name" => RazorxTreatment::FAV_LEDGER_FEE_DEDUCTION_FOR_VPA_ENABLE,
            'request_data' => json_encode(['id' => $merchantId])
        ];

        return (new Merchant\Core)->isSplitzExperimentEnable(
            $requestPayload,
            RazorxTreatment::VARIANT_ENABLE
        );
    }

    /**
     * Check if VPA FAV should bypass transaction creation due to ledger fee deduction being disabled
     *
     * @param Entity $fav
     * @return bool
     */
    public static function shouldVpaFavBypassTransactionCreation($fav): bool
    {
        return (($fav->getFundAccountType() === FundAccount\Type::VPA) and
                (self::isLedgerFeeDeductionForVpaTypeFavEnabled($fav->getMerchantId()) === false));
    }
} 