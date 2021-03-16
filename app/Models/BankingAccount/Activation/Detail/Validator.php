<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Admin\Permission;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\BankingAccount\Activation\Detail\Entity;

class Validator extends Base\Validator
{
    // Types of Business Categories
    const PRIVATE_PUBLIC_LIMITED_COMPANY = 'private_public_limited_company';
    const SOLE_PROPRIETORSHIP = 'sole_proprietorship';
    const LIMITED_LIABILITY_PARTNERSHIP = 'limited_liability_partnership';
    const PARTNERSHIP = 'partnership';

    // Types of Accounts for RBL
    const INSIGNIA = 'insignia';
    const PREMIUM = 'premium';
    const BUSINESS_PLUS = 'business_plus';
    const ZERO_BALANCE = 'zero_balance';

    // Types of Sales teams
    const GROWTH = 'growth';
    const DIRECT_SALES = 'direct_sales';
    const KEY_ACCOUNT = 'key_account';
    const SME = 'sme';
    const CAPITAL_SME = 'capital_sme';
    const CAPITAL_GROWTH = 'capital_growth';
    const CAPITAL_KAM = 'capital_kam';
    const CAPITAL_DIRECT_SALES = 'capital_direct_sales';
    const PG_SME = 'pg_sme';
    const PG_GROWTH = 'pg_growth';
    const PG_KAM = 'pg_kam';
    const PG_DIRECT_SALES = 'pg_direct_sales';
    const SELF_SERVE = 'self_serve';

    // placeholder create rules to allow both the below flows
    protected static $createRules = [
        Entity::BANKING_ACCOUNT_ID                  => 'required|string|size:14',
        Entity::MERCHANT_POC_NAME                   => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_DESIGNATION            => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_EMAIL                  => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_PHONE_NUMBER           => 'sometimes|string|max:255',
        Entity::MERCHANT_CITY                       => 'sometimes|string|max:255',
        Entity::MERCHANT_DOCUMENTS_ADDRESS          => 'sometimes|string|max:255',
        Entity::MERCHANT_REGION                     => 'sometimes|string|max:255',
        Entity::EXPECTED_MONTHLY_GMV                => 'sometimes|integer|min:0',
        Entity::INITIAL_CHEQUE_VALUE                => 'sometimes|integer|min:0',
        Entity::BUSINESS_CATEGORY                   => 'sometimes|string|max:255|custom',
        Entity::AVERAGE_MONTHLY_BALANCE             => 'sometimes|integer|min:0',
        Entity::ACCOUNT_TYPE                        => 'sometimes|string|max:255',
        Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'sometimes|boolean',
        Entity::SALES_TEAM                          => 'sometimes|string|max:255|custom',
        Entity::SALES_POC_PHONE_NUMBER              => 'sometimes|string|max:255',
        Entity::COMMENT                             => 'sometimes|string',
        Entity::ASSIGNEE_TEAM                       => 'sometimes|string|in:ops,bank,sales'
    ];

    // for older BankingAccounts, entity will not be created (as this was recently made mandatory for BankingAccountCreation)
    //. Creating empty entities so that assignee_team works for older entities.
    protected static $createNullRules = [
        Entity::BANKING_ACCOUNT_ID                  => 'required|string|size:14',
    ];

    // main flow via admin dashboard
    protected static $createNormalRules = [
        Entity::BANKING_ACCOUNT_ID                  => 'required|string|size:14',
        Entity::MERCHANT_POC_NAME                   => 'required|string|max:255',
        Entity::MERCHANT_POC_DESIGNATION            => 'required|string|max:255',
        Entity::MERCHANT_POC_EMAIL                  => 'required|string|max:255',
        Entity::MERCHANT_POC_PHONE_NUMBER           => 'required|string|max:255',
        Entity::MERCHANT_CITY                       => 'required|string|max:255',
        Entity::MERCHANT_DOCUMENTS_ADDRESS          => 'required|string|max:255',
        Entity::MERCHANT_REGION                     => 'required|string|max:255',
        Entity::EXPECTED_MONTHLY_GMV                => 'required|integer|min:0',
        Entity::INITIAL_CHEQUE_VALUE                => 'required|integer|min:0',
        Entity::BUSINESS_CATEGORY                   => 'required|string|max:255|custom',
        Entity::AVERAGE_MONTHLY_BALANCE             => 'required|integer|min:0',
        Entity::ACCOUNT_TYPE                        => 'required|string|max:255',
        Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'required|boolean',
        Entity::SALES_TEAM                          => 'required|string|max:255|custom',
        Entity::SALES_POC_PHONE_NUMBER              => 'required|string|max:255',
        Entity::COMMENT                             => 'required|string',
        Entity::ASSIGNEE_TEAM                       => 'sometimes|string|in:ops,bank,sales' // if created via InitiateOnboarding, default assignee is chosen. If adding for existing CAs, then it will be empty.
    ];

    protected static $editRules = [
        Entity::MERCHANT_POC_NAME                   => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_DESIGNATION            => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_EMAIL                  => 'sometimes|string|max:255',
        Entity::MERCHANT_POC_PHONE_NUMBER           => 'sometimes|string|max:255',
        Entity::MERCHANT_CITY                       => 'sometimes|string|max:255',
        Entity::MERCHANT_DOCUMENTS_ADDRESS          => 'sometimes|string|max:255',
        Entity::MERCHANT_REGION                     => 'sometimes|string|max:255',
        Entity::EXPECTED_MONTHLY_GMV                => 'sometimes|integer|min:0',
        Entity::INITIAL_CHEQUE_VALUE                => 'sometimes|integer|min:0',
        Entity::BUSINESS_CATEGORY                   => 'sometimes|string|max:255|custom',
        Entity::AVERAGE_MONTHLY_BALANCE             => 'sometimes|integer|min:0',
        Entity::ACCOUNT_TYPE                        => 'sometimes|string|max:255',
        Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'sometimes|boolean',
        Entity::SALES_TEAM                          => 'sometimes|string|max:255|custom',
        Entity::SALES_POC_PHONE_NUMBER              => 'sometimes|string|max:255',
        Entity::ASSIGNEE_TEAM                       => 'sometimes|string|nullable|in:ops,bank,sales',
        Entity::RM_NAME                             => 'sometimes|string|max:255',
        Entity::RM_PHONE_NUMBER                     => 'sometimes|string|max:255',
        Entity::ACCOUNT_OPEN_DATE                   => 'sometimes|epoch|nullable',
        Entity::ACCOUNT_LOGIN_DATE                  => 'sometimes|epoch|nullable',
    ];

    protected static $allowedBusinessCategories = [
        self::PRIVATE_PUBLIC_LIMITED_COMPANY,
        self::SOLE_PROPRIETORSHIP,
        self::LIMITED_LIABILITY_PARTNERSHIP,
        self::PARTNERSHIP
    ];

    protected static $allowedAccountTypesForRBL = [
        self::INSIGNIA,
        self::PREMIUM,
        self::BUSINESS_PLUS,
        self::ZERO_BALANCE
    ];

    protected static $allowedSalesTeams = [
        self::DIRECT_SALES,
        self::GROWTH,
        self::KEY_ACCOUNT,
        self::SME,
        self::CAPITAL_SME,
        self::CAPITAL_GROWTH,
        self::CAPITAL_KAM,
        self::CAPITAL_DIRECT_SALES,
        self::PG_SME,
        self::PG_GROWTH,
        self::PG_KAM,
        self::PG_DIRECT_SALES,
        self::SELF_SERVE
    ];

    public function validateBusinessCategory($attribute, $value)
    {
        if (in_array($value, self::$allowedBusinessCategories) === false)
        {
            throw new BadRequestValidationFailureException(
                'The business category field is invalid.',
                Entity::BUSINESS_CATEGORY);
        }
    }

    public function validateSalesTeam($attribute, $value)
    {
        if (in_array($value, self::$allowedSalesTeams) === false)
        {
            throw new BadRequestValidationFailureException(
                'The Sales team field is invalid.',
                Entity::SALES_TEAM);
        }
    }

    public function validateAccountTypeForChannel(\RZP\Models\BankingAccount\Entity $bankingAccount, $input)
    {
        // TODO make this channel generic
        if ((isset($input[Entity::ACCOUNT_TYPE])) and ($bankingAccount->getChannel() === Channel::RBL))
        {
            if (in_array($input[Entity::ACCOUNT_TYPE], self::$allowedAccountTypesForRBL) === false)
            {
                throw new BadRequestValidationFailureException(
                    'The account type field is invalid.',
                    Entity::BUSINESS_CATEGORY);
            }
        }
    }

    /*
     * If there is a change in the assignee team, a comment is mandatory
     */
    public function validateCommentOnAssigneeTeamChange(Entity $activationDetail, $input, $commentInput)
    {
        $newAssigneeTeam = $input[Entity::ASSIGNEE_TEAM] ?? null;

        if ((empty($newAssigneeTeam) === false)
           and ($activationDetail->getAssigneeTeam() !== $newAssigneeTeam)
           and (empty($commentInput) === true))
        {
           throw new BadRequestValidationFailureException("Comment is required when changing Assignee team");
        }
    }
}
