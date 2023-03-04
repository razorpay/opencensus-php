<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use \RZP\Models\Merchant;
use RZP\Models\BankingAccount;
use RZP\Models\BankingAccount\Activation\Detail\Entity as ActivationDetail;
use RZP\Models\Base\PublicEntity;

/**
 * Class Validator
 *
 * @package RZP\Models\BankingAccount\BankLms
 *
 * @property BankingAccount\Entity $entity
 */
class Validator extends BankingAccount\Validator
{
    const CREATE_BANK_CA_ONBOARDING_PARTNER_TYPE = 'create_bank_ca_onboarding_partner_type';

    const ATTACH_CA_MERCHANT_TO_BANK_PARTNER = 'attach_ca_merchant_to_bank_partner';

    const DETACH_CA_MERCHANT_FROM_BANK_PARTNER = 'detach_ca_merchant_from_bank_partner';

    const ASSIGN_BANK_PARTNER_POC_TO_APPLICATION = 'assign_bank_partner_poc_to_application';

    const DOWNLOAD_MIS_FROM_PARTNER_BANK = 'download_mis_from_partner_bank';

    const REQUEST_MIS_FROM_PARTNER_BANK = 'request_mis_from_partner_bank';

    const PARTNER_LMS_EDIT = 'partner_lms_edit';

    const EDIT_ACTIVATION_DETAIL_BY_BANK = 'edit_activation_detail_by_bank';

    const RBL_ACTIVATION_DETAILS = 'rbl_activation_details';

    const ADD_COMMENT = 'add_comment';

    const ALLOWED_STRING_PATTERN = 'regex:/^[0-9A-Za-z_.\s,\'-?\/]*$/';

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
        BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID => 'required|alpha_num|size:14'
    ];

    protected static $downloadMisFromPartnerBankRules = [
        Constants::MIS_TYPE => 'required|string|in:leads',
        BankingAccount\Entity::STATUS => 'sometimes|string',
        BankingAccount\Entity::SUB_STATUS => 'sometimes|string',
        BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID => 'sometimes|alpha_num|size:14',
        Entity::BUSINESS_CATEGORY                => 'sometimes|string',
        BankingAccount\Entity::BANK_ACCOUNT_TYPE => 'sometimes|string',
        Constants::LEAD_RECEIVED_FROM_DATE       => 'required_with:lead_received_to_date|integer',
        Constants::LEAD_RECEIVED_TO_DATE         => 'required_with:lead_received_from_date|integer',
        Constants::IS_GREEN_CHANNEL              => 'sometimes|in:yes,no',
        BankingAccount\Activation\Detail\Entity::REVIVED_LEAD => 'sometimes|in:yes,no',
        BankingAccount\Entity::BANK_REFERENCE_NUMBER     => 'sometimes|string',
        BankingAccount\Entity::MERCHANT_BUSINESS_NAME    => 'sometimes|string',
        BankingAccount\ENTITY::ASSIGNEE_TEAM                => 'sometimes|in:rzp,bank',
        BankingAccount\Activation\Detail\Entity::RM_NAME    => 'sometimes|string',
        BankingAccount\Activation\Detail\Entity::API_ONBOARDING_FTNR     => 'sometimes|in:0,1',
        BankingAccount\Activation\Detail\Entity::ACCOUNT_OPENING_FTNR    => 'sometimes|in:0,1',
        Constants::DUE_ON               => 'sometimes|epoch',
        Constants::IS_OVERDUE           => 'sometimes|epoch',
        Constants::FEET_ON_STREET       => 'sometimes|in:yes,no',
    ];

    protected static $requestMisFromPartnerBankRules = [
        Fetch::EXPAND                            => 'sometimes|array',
        BankingAccount\Activation\Detail\Entity::BANK_POC_USER_ID => 'sometimes|alpha_num|size:14',
        Entity::BUSINESS_CATEGORY                => 'sometimes|string',
        BankingAccount\Entity::BANK_ACCOUNT_TYPE => 'sometimes|string',
        Constants::LEAD_RECEIVED_FROM_DATE       => 'required_with:lead_received_to_date|integer',
        Constants::LEAD_RECEIVED_TO_DATE         => 'required_with:lead_received_from_date|integer',
        Constants::IS_GREEN_CHANNEL              => 'sometimes|in:yes,no',
        BankingAccount\Activation\Detail\Entity::REVIVED_LEAD => 'sometimes|in:yes,no',
        BankingAccount\ENTITY::ASSIGNEE_TEAM                => 'sometimes|in:rzp,bank',
        BankingAccount\Activation\Detail\Entity::RM_NAME    => 'sometimes|string',
        BankingAccount\Activation\Detail\Entity::API_ONBOARDING_FTNR     => 'sometimes|in:0,1',
        BankingAccount\Activation\Detail\Entity::ACCOUNT_OPENING_FTNR    => 'sometimes|in:0,1',
        Constants::DUE_ON               => 'sometimes|epoch',
        Constants::IS_OVERDUE           => 'sometimes|epoch',
        Constants::FEET_ON_STREET       => 'sometimes|in:yes,no',
    ];

    protected static $partnerLmsEditRules = [
        Entity::STATUS                          => 'filled|string|in:initiated,verification_call,doc_collection,account_opening,api_onboarding,account_activation,archived',
        Entity::SUB_STATUS                      => 'string|nullable|custom',
        Entity::ACTIVATION_DETAIL               => 'sometimes|array',
    ];

    protected static $addCommentRules = [
        BankingAccount\Activation\Comment\Entity::COMMENT             => 'sometimes|'.self::ALLOWED_STRING_PATTERN,
        BankingAccount\Activation\Comment\Entity::NOTES               => 'sometimes|array|max:3',
        BankingAccount\Activation\Comment\Entity::SOURCE_TEAM_TYPE    => 'sometimes|max:255|in:internal,external',
        BankingAccount\Activation\Comment\Entity::SOURCE_TEAM         => 'sometimes|max:255|in:product,sales,ops,bank',
        BankingAccount\Activation\Comment\Entity::TYPE                => 'sometimes|max:64|in:internal,external,external_resolved',
        BankingAccount\Activation\Comment\Entity::ADDED_AT            => 'sometimes|epoch'
    ];

    protected static $editActivationDetailByBankRules = [

        ActivationDetail::RBL_ACTIVATION_DETAILS                  => 'sometimes',
        ActivationDetail::CUSTOMER_APPOINTMENT_DATE               => 'sometimes|epoch|nullable',
        ActivationDetail::BRANCH_CODE                             => 'sometimes|alpha_dash|max:6',
        ActivationDetail::RM_ASSIGNMENT_TYPE                      => 'sometimes|alpha_dash|in:branch,pcarm,insignia',
        ActivationDetail::RM_EMPLOYEE_CODE                        => 'sometimes|alpha_dash|max:6',
        ActivationDetail::DOC_COLLECTION_DATE                     => 'sometimes|epoch|nullable',
        ActivationDetail::ACCOUNT_OPENING_IR_CLOSE_DATE           => 'sometimes|epoch|nullable',
        ActivationDetail::ACCOUNT_OPENING_FTNR                    => 'sometimes|boolean',
        ActivationDetail::ACCOUNT_OPENING_FTNR_REASONS            => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::API_IR_CLOSED_DATE                      => 'sometimes|epoch|nullable',
        ActivationDetail::API_ONBOARDING_FTNR                     => 'sometimes|boolean',
        ActivationDetail::API_ONBOARDING_FTNR_REASONS             => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::ASSIGNEE_TEAM                           => 'sometimes|alpha_dash|in:ops,bank',
        ActivationDetail::COMMENT                                 => 'sometimes|array',
        ActivationDetail::ADDITIONAL_DETAILS                      => 'sometimes',
        ActivationDetail::ACCOUNT_OPEN_DATE                       => 'sometimes|epoch|nullable',
        ActivationDetail::ACCOUNT_LOGIN_DATE                      => 'sometimes|epoch|nullable',
        ActivationDetail::MERCHANT_CITY                           => 'sometimes|alpha_dash|max:255',
        ActivationDetail::ASSIGNEE_TEAM                           => 'sometimes|alpha_dash|nullable|in:ops,bank,sales',
        ActivationDetail::RM_NAME                                 => 'sometimes|regex:/^[\pL\s\-]+$/u|max:255',
        ActivationDetail::RM_PHONE_NUMBER                         => 'sometimes|alpha_dash|max:255',
    ];

    protected static $rblActivationDetailsRules = [

        ActivationDetail::API_SERVICE_FIRST_QUERY               => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::API_BEYOND_TAT                        => 'sometimes|boolean|nullable',
        ActivationDetail::API_BEYOND_TAT_DEPENDENCY             => 'sometimes|in:client,branch,razorpay,internal_approvals,cibil,legal,compliance',
        ActivationDetail::FIRST_CALLING_TIME                    => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::SECOND_CALLING_TIME                   => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::WA_MESSAGE_SENT_DATE                  => 'sometimes|epoch|nullable',
        ActivationDetail::WA_MESSAGE_RESPONSE_DATE              => 'sometimes|epoch|nullable',
        ActivationDetail::API_DOCKET_RELATED_ISSUE              => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::AOF_SHARED_WITH_MO                    => 'sometimes|boolean|nullable',
        ActivationDetail::AOF_SHARED_DISCREPANCY                => 'sometimes|boolean|nullable',
        ActivationDetail::AOF_NOT_SHARED_REASON                 => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::CA_BEYOND_TAT_DEPENDENCY              => 'sometimes|in:client,branch,razorpay,internal_approvals,cibil,legal,compliance',
        ActivationDetail::CA_BEYOND_TAT                         => 'sometimes|boolean|nullable',
        ActivationDetail::CA_SERVICE_FIRST_QUERY                => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::LEAD_IR_STATUS                        => 'sometimes|in:ir_raised,ir_in_discrepancy,ir_closed',

        ActivationDetail::IR_NUMBER                             => 'sometimes|alpha_num|nullable',
        ActivationDetail::LEAD_IR_NUMBER                        => 'sometimes|alpha_num|nullable',
        ActivationDetail::IP_CHEQUE_VALUE                       => 'sometimes|integer|nullable',
        ActivationDetail::OFFICE_DIFFERENT_LOCATIONS            => 'sometimes|boolean|nullable',
        ActivationDetail::API_DOCS_RECEIVED_WITH_CA_DOCS        => 'sometimes|boolean|nullable',
        ActivationDetail::API_DOCS_DELAY_REASON                 => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::ACCOUNT_OPENING_IR_NUMBER             => 'sometimes|alpha_num|nullable',
        ActivationDetail::CASE_LOGIN_DIFFERENT_LOCATIONS        => 'sometimes|boolean|nullable',
        ActivationDetail::SR_NUMBER                             => 'sometimes|alpha_num|nullable',
        ActivationDetail::REVISED_DECLARATION                   => 'sometimes|boolean|nullable',
        ActivationDetail::API_IR_NUMBER                         => 'sometimes|alpha_num|nullable',
        ActivationDetail::UPI_CREDENTIAL_NOT_DONE_REMARKS       => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::PCARM_MANAGER_NAME                    => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable|max:255',

        ActivationDetail::PROMO_CODE                            => 'sometimes|alpha_num|nullable',
        ActivationDetail::LEAD_REFERRED_BY_RBL_STAFF            => 'sometimes|boolean|nullable',
        ActivationDetail::ACCOUNT_OPENING_TAT_EXCEPTION         => 'sometimes|boolean|nullable',
        ActivationDetail::ACCOUNT_OPENING_TAT_EXCEPTION_REASON  => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
        ActivationDetail::API_ONBOARDING_TAT_EXCEPTION          => 'sometimes|boolean|nullable',
        ActivationDetail::API_ONBOARDING_TAT_EXCEPTION_REASON   => 'sometimes|'.self::ALLOWED_STRING_PATTERN.'|nullable',
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
        $subMerchantIds = (new Repository())->fetchSubMerchantForPartnerAndSubMerchantId($partnerBank, $merchant);

        if (count($subMerchantIds) === 0)
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
