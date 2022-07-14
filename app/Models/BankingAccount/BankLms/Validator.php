<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use \RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;

/**
 * Class Validator
 *
 * @package RZP\Models\BankingAccount\BankLms
 *
 * @property BankingAccount\Entity $entity
 */
class Validator extends Base\Validator
{
    const CREATE_BANK_CA_ONBOARDING_PARTNER_TYPE = 'create_bank_ca_onboarding_partner_type';

    const ATTACH_CA_MERCHANT_TO_BANK_PARTNER = 'attach_ca_merchant_to_bank_partner';

    const DETACH_CA_MERCHANT_FROM_BANK_PARTNER = 'detach_ca_merchant_from_bank_partner';

    const ASSIGN_BANK_PARTNER_POC_TO_APPLICATION = 'assign_bank_partner_poc_to_application';

    protected static $createBankCaOnboardingPartnerTypeRules = [
        PublicEntity::MERCHANT_ID                 => 'required|alpha_num|size:14',
        \RZP\Models\Merchant\Entity::PARTNER_TYPE => 'required|string|in:bank_ca_onboarding_partner',
    ];

    protected static $attachCaMerchantToBankPartnerRules     = [
        BankingAccount\Entity::BANKING_ACCOUNT_ID => 'required|string|size:19',
    ];

    protected static $detachCaMerchantFromBankPartnerRules   = [
        BankingAccount\Entity::BANKING_ACCOUNT_ID => 'required|string|size:19',
    ];

    protected static $assignBankPartnerPocToApplicationRules = [
        BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID => 'required|string|size:14'
    ];

    /**
     * @throws Exception\BadRequestException
     */
    public function validateOnlyOneCaBankPartnerAndReturn(): ?string
    {
        $merchantIds = (new Feature\Repository())->findMerchantIdsHavingFeatures([Feature\Constants::RBL_BANK_LMS_DASHBOARD]);

        if (count($merchantIds) > 1)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, "More than one CA Bank Partner Merchant Found");
        }

        return count($merchantIds) == 1 ? $merchantIds[0] : null;
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateMerchantIsAttachedToPartner(Merchant\Entity $merchant, Merchant\Entity $partnerBank)
    {
        $subMerchantIds = (new Repository())->fetchSubMerchantIdsForPartnerBank($partnerBank);

        if (!in_array($merchant->getId(), $subMerchantIds))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, "Merchant is Not attached to CA Bank Partner Merchant");
        }
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function validateUserBelongsToPartnerBankMerchant(string $userId, Merchant\Entity $partnerBank)
    {
        $mapping = (new Merchant\Repository())->getMerchantUserMapping($partnerBank->getId(), $userId, null, 'banking');

        if (empty($mapping) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }
    }

}
