<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Models\FundAccount;
use RZP\Models\Merchant;
use RZP\Models\Merchant\RazorxTreatment;

class Utils
{
    /**
     * Check if ledger reverse shadow is enabled for validation
     *
     * @param Entity $validation
     * @return bool
     */
    public static function isLedgerReverseShadowEnabled(Entity $validation): bool
    {
        return (($validation->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) and
                ($validation->isBalanceTypeBanking() === true));
    }

    /**
     * Check if FAV should go through ledger reverse shadow flow
     *
     * @param Entity $validation
     * @return bool
     */
    public static function shouldFavGoThroughLedgerReverseShadow(Entity $validation): bool
    {
        if (self::isLedgerReverseShadowEnabled($validation) === false) {
            return false;
        }

        // For bank account type, directly return true
        if ($validation->getFundAccountType() === FundAccount\Type::BANK_ACCOUNT) {
            return true;
        }

        // For VPA type, check if ledger fee deduction experiment is enabled
        if ($validation->getFundAccountType() === FundAccount\Type::VPA)
        {
            return self::isExperimentEnabled(
                $validation->merchant->getId(),
                RazorxTreatment::FAV_LEDGER_FEE_DEDUCTION_FOR_VPA_ENABLE);
        }

        return false;
    }

    /**
     * Generic method to check if an experiment is enabled for a merchant
     *
     * @param string $merchantId
     * @param string $experimentName
     * @return bool
     */
    public static function isExperimentEnabled(string $merchantId, string $experimentName): bool
    {
        $requestPayload = [
            "id" => $merchantId,
            "experiment_name" => $experimentName,
            'request_data' => json_encode(['id' => $merchantId])
        ];

        return (new Merchant\Core)->isSplitzExperimentEnable(
            $requestPayload,
            RazorxTreatment::VARIANT_ENABLE
        );
    }

    /**
     * Check if FAV service forwarding is applicable for a merchant
     *
     * @param Merchant\Entity $merchant
     * @return bool
     */
    public static function isFavServiceForwardingApplicable(Merchant\Entity $merchant): bool
    {
        $isFavServiceFlagEnabled = $merchant->isFeatureEnabled(Feature\Constants::FAV_SERVICE_ENABLED);

        $isFavServiceExperimentEnabled = self::isExperimentEnabled(
            $merchant->getId(),
            RazorxTreatment::FAV_COMPOSITE_SERVICE_FORWARDING);

        if (($isFavServiceExperimentEnabled === true) and
            ($isFavServiceFlagEnabled === true))
        {
            return true;
        }

        return false;
    }

    /**
     * Check if VPA FAV should bypass transaction creation due to ledger fee deduction being disabled
     *
     * @param Entity $fav
     * @return bool
     */
    public static function shouldVpaFavBypassTransactionCreation(Entity $fav): bool
    {
        return (($fav->getFundAccountType() === FundAccount\Type::VPA) and
                (self::isExperimentEnabled(
                    $fav->getMerchantId(),
                    RazorxTreatment::FAV_LEDGER_FEE_DEDUCTION_FOR_VPA_ENABLE) === false));
    }

    /**
     * Validate if ledger reverse shadow is enabled for fav or not
     * Throws exception if not enabled
     *
     * @param Entity $fav
     * @return void
     * @throws Exception\LogicException
     */
    public static function validateLedgerReverseShadowEnabled(Entity $fav): void
    {
        if (self::isLedgerReverseShadowEnabled($fav) === false) {
            $fundAccountType = $fav->getFundAccountType();

            if (($fundAccountType === FundAccount\Type::BANK_ACCOUNT) or
                ($fundAccountType === FundAccount\Type::VPA and
                    self::isExperimentEnabled(
                        $fav->getMerchantId(),
                        RazorxTreatment::FAV_LEDGER_FEE_DEDUCTION_FOR_VPA_ENABLE) === true))
            {
                throw new Exception\LogicException(
                    'Merchant does not have the ledger reverse shadow feature flag enabled',
                    ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
                    [
                        'merchant_id' => $fav->getMerchantId()
                    ]
                );
            }
        }
    }
}
