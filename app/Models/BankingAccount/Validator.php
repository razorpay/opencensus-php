<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Models\Pincode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Gateway\Rbl\Fields;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const PRE_PROCESS           = 'pre_process';
    const PRE_PROCESS_DASHBOARD = 'pre_process_dashboard';
    const CREATE_LEAD_FROM_RBL  = 'create_lead_from_rbl';
    const INTERNAL_EDIT         = 'internal_edit';
    const PROCESSED_STATUS      = 'processed_status';
    const SERVICEABLE_PINCODE   = 'serviceable_pincode';
    const SHARED_CREATE         = 'shared_create';
    const CORP_CARD_CREATE      = 'corp_card_create';
    const INTERNAL_EDIT_STATUS  = 'internal_edit_status';
    const ACTIVATED_STATUS      = 'activated_status';

    const FETCH_GATEWAY_BALANCE    = 'fetch_gateway_balance';
    const DISPATCH_GATEWAY_BALANCE = 'dispatch_gateway_balance';

    const FETCH_BANKING_ACCOUNT_PAYOUT_SERVICE   = 'fetch_banking_account_payout_service';
    const FETCH_BANKING_ACCOUNT_USING_BALANCE_ID = 'fetch_banking_account_using_balance_id';

    const FETCH_BANKING_ACCOUNT_IFSC_SERVICE  = 'fetch_banking_account_ifsc_service';

    const ARCHIVE_ACCOUNT = 'archive_account';

    /**
     * Regular expression for valid names:
     * - Must start with a-z/A-Z/0-9
     * - Must end with a-z/A-Z/0-9/./)
     * - Can have anything from a-z/A-Z/0-9/'/-/&/–/./_/(/)/\/space in between
     */
    const NAME_REGEX = '/(^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+[a-zA-Z0-9.)]$)/';

    protected static $preProcessRules = [
        Entity::CHANNEL => 'required|string|custom',
    ];

    protected static $preProcessDashboardRules = [
        Entity::CHANNEL => 'required|string|custom',
        Entity::PINCODE => 'required_if:channel,rbl'
    ];

    protected static $sharedCreateRules = [
        Entity::CHANNEL                         => 'required|string',
        Entity::ACCOUNT_NUMBER                  => 'required|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'required|alpha_num|size:11',
        Entity::FTS_FUND_ACCOUNT_ID             => 'sometimes|nullable|string|size:14',
        Entity::ACCOUNT_TYPE                    => 'sometimes|string|in:nodal',
        Entity::STATUS                          => 'sometimes|in:created',
        Entity::BENEFICIARY_PIN                 => 'sometimes|nullable|integer|digits:6',
        Entity::BENEFICIARY_CITY                => 'sometimes|nullable|max:30|alpha_space',
        Entity::BENEFICIARY_COUNTRY             => 'sometimes|nullable|in:IN',
        Entity::BENEFICIARY_STATE               => 'sometimes|nullable|max:2',
        Entity::BENEFICIARY_ADDRESS1            => 'sometimes|nullable|max:30',
        Entity::BENEFICIARY_ADDRESS2            => 'sometimes|nullable|max:30',
        Entity::BENEFICIARY_ADDRESS3            => 'sometimes|nullable|max:60',
        Entity::BENEFICIARY_MOBILE              => 'sometimes|nullable|numeric|digits_between:10,12',
        Entity::BENEFICIARY_EMAIL               => 'sometimes|nullable|email',
        Entity::BENEFICIARY_NAME                => 'sometimes|nullable|between:1,120|custom',
    ];

    protected static $corpCardCreateRules = [
        Entity::CHANNEL        => 'required|string|in:m2p',
        Entity::ACCOUNT_NUMBER => 'required|string|size:14',
        Entity::ACCOUNT_TYPE   => 'sometimes|string|in:corp_card',
        Entity::STATUS         => 'sometimes|in:created',
    ];

    protected static $createRules = [
        Entity::CHANNEL                         => 'required|string|custom',
        Entity::STATUS                          => 'sometimes|in:created',
        Entity::BANK_REFERENCE_NUMBER           => 'required_if:channel,rbl',
        Entity::PINCODE                         => 'required_if:channel,rbl',
        Entity::ACCOUNT_IFSC                    => 'sometimes|nullable|string|size:11',
        Entity::FTS_FUND_ACCOUNT_ID             => 'sometimes|nullable|string|size:14',
        Entity::ACCOUNT_TYPE                    => 'required|string|custom',
        Entity::ACCOUNT_NUMBER                  => 'sometimes|string|max:40',
        Entity::BENEFICIARY_PIN                 => 'sometimes|nullable|integer|digits:6',
        Entity::BENEFICIARY_CITY                => 'sometimes|nullable|string',
        Entity::BENEFICIARY_COUNTRY             => 'sometimes|nullable|string',
        Entity::BENEFICIARY_STATE               => 'sometimes|nullable|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'sometimes|integer',
        Entity::BENEFICIARY_ADDRESS1            => 'sometimes|nullable|string',
        Entity::BENEFICIARY_ADDRESS2            => 'sometimes|nullable|string',
        Entity::BENEFICIARY_ADDRESS3            => 'sometimes|nullable|string',
        Entity::BENEFICIARY_MOBILE              => 'sometimes|nullable|string',
        Entity::BENEFICIARY_EMAIL               => 'sometimes|nullable|string',
        Entity::BENEFICIARY_NAME                => 'sometimes|nullable|custom',
    ];

    protected static $createLeadFromRblRules = [
        Fields::NEO_BANKING_LEAD_REQUEST                                                               => 'required',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY                                          => 'required|array',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::HEADER . '.' . Fields::TRAN_ID                => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::HEADER . '.' . Fields::CO_CREATED_CORP_ID     => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::LEAD_ID                  => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::EMAIL_ADDRESS            => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::CUSTOMER_CITY            => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::CUSTOMER_PINCODE         => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::CUSTOMER_ADDRESS         => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::CUSTOMER_MOBILE_NUMBER   => 'required|string',
        Fields::NEO_BANKING_LEAD_REQUEST . '.' . Fields::BODY . '.' . Fields::CO_CREATED_CUSTOMER_NAME => 'required|string',
    ];

    protected static $rblCreateRules = [
        Entity::CHANNEL               => 'required|string|custom',
        Entity::BANK_REFERENCE_NUMBER => 'required|integer|digits:5',
        Entity::PINCODE               => 'required|string',
    ];

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER                  => 'filled|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'filled|alpha_num|size:11',
        Entity::BANK_INTERNAL_STATUS            => 'sometimes|string',
        Entity::STATUS                          => 'filled|string|custom',
        Entity::SUB_STATUS                      => 'string|nullable|custom',
        Entity::BANK_REFERENCE_NUMBER           => 'filled|string',
        Entity::BANK_INTERNAL_REFERENCE_NUMBER  => 'filled|string',
        Entity::BENEFICIARY_PIN                 => 'filled|string',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'sometimes|integer|nullable',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|custom',
        Entity::USERNAME                        => 'filled|string',
        Entity::PASSWORD                        => 'filled|string',
        Entity::REFERENCE1                      => 'filled|string',
        Entity::INTERNAL_COMMENT                => 'sometimes|max:255',
        Entity::DETAILS                         => 'sometimes|array',
        Entity::OPS_MX_POC_ID                   => 'sometimes|string',
    ];

    // For Current Account form on dashboard
    protected static $editDashboardRules = [
        Entity::ACCOUNT_NUMBER                  => 'filled|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'filled|alpha_num|size:11',
        Entity::PINCODE                         => 'filled|string',
        Entity::BENEFICIARY_PIN                 => 'filled|string',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'sometimes|integer|nullable',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|custom',
        Entity::INTERNAL_COMMENT                => 'sometimes|max:255',
        Entity::DETAILS                         => 'sometimes|array',
    ];

    protected static $internalEditRules = [
        Entity::ACCOUNT_NUMBER                  => 'filled|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'filled|alpha_num|size:11',
        Entity::STATUS                          => 'filled|string',
        Entity::SUB_STATUS                      => 'string|nullable|custom',
        Entity::BENEFICIARY_PIN                 => 'filled|string',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'sometimes|integer|nullable',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|custom',
        Entity::INTERNAL_COMMENT                => 'sometimes|max:255',
        Entity::DETAILS                         => 'sometimes|array',
        Entity::ACTIVATION_DETAIL               => 'sometimes|array',
    ];

    protected static $internalEditValidators = [
        self::INTERNAL_EDIT_STATUS,
    ];

    // TODO: handle cases when some fields are already present in the model when the status is processed.
    protected static $processedStatusRules = [
        Entity::ACCOUNT_NUMBER                  => 'required|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'required|alpha_num|size:11',
        Entity::STATUS                          => 'required|string|custom',
        Entity::BENEFICIARY_PIN                 => 'required|string',
        Entity::BENEFICIARY_CITY                => 'required|string',
        Entity::BENEFICIARY_COUNTRY             => 'required|string',
        Entity::BENEFICIARY_STATE               => 'required|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'required|integer',
        Entity::BENEFICIARY_ADDRESS1            => 'required|string',
        Entity::BENEFICIARY_ADDRESS2            => 'required|string',
        Entity::BENEFICIARY_ADDRESS3            => 'required|string',
        Entity::BENEFICIARY_MOBILE              => 'required|string',
        Entity::BENEFICIARY_EMAIL               => 'required|string',
        Entity::BENEFICIARY_NAME                => 'required|string',
        Entity::INTERNAL_COMMENT                => 'sometimes|max:255',
    ];

    protected static $activatedStatusRules = [
        Entity::BENEFICIARY_PIN                 => 'sometimes|string',
        Entity::BENEFICIARY_CITY                => 'sometimes|string',
        Entity::BENEFICIARY_COUNTRY             => 'sometimes|string',
        Entity::BENEFICIARY_STATE               => 'sometimes|string',
        Entity::BENEFICIARY_ADDRESS1            => 'sometimes|string',
        Entity::BENEFICIARY_ADDRESS2            => 'sometimes|string',
        Entity::BENEFICIARY_ADDRESS3            => 'sometimes|string',
        Entity::BENEFICIARY_MOBILE              => 'sometimes|string',
        Entity::BENEFICIARY_EMAIL               => 'sometimes|email',
        Entity::INTERNAL_COMMENT                => 'sometimes|max:255',
    ];

    protected static $serviceablePincodeRules = [
        Entity::CHANNEL         => 'required|string|custom',
        Entity::ACTION          => 'required|string|in:add,delete',
        Entity::PINCODES        => 'required|array|filled',
    ];

    protected static $serviceablePincodeValidators = [
        Entity::PINCODES,
    ];

    protected static $bulkAssignReviewerRules = [
        Entity::REVIEWER_ID                     => 'required|public_id|size:20',
        Entity::BANKING_ACCOUNT_IDS             => 'required|array',
        Entity::BANKING_ACCOUNT_IDS . '*'       => 'required|public_id|size:19',
    ];

    protected static $fetchGatewayBalanceRules = [
    Entity::CHANNEL     => 'required|string|custom',
    Entity::MERCHANT_ID => 'required|string',
];

    protected static $fetchBankingAccountPayoutServiceRules = [
        Entity::MERCHANT_ID    => 'required|alpha_num|size:14',
        Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,40'
    ];

    protected static $fetchBankingAccountUsingBalanceIdRules = [
        Entity::BALANCE_ID => 'required|alpha_num|size:14',
    ];

    protected static $fetchBankingAccountIfscServiceRules = [
        Entity::ACCOUNT_NUMBER => 'required|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC   => 'required|alpha_num|size:11'
    ];

    protected static $dispatchGatewayBalanceRules = [
        Entity::CHANNEL => 'required|string|custom',
    ];

    protected static $archiveAccountRules = [
        Entity::CHANNEL     => 'required|string|custom',
        Entity::MERCHANT_ID => 'required|alpha_num|size:14',
    ];

    protected static $fetchGatewayBalanceValidators = [
        'direct_channel'
    ];

    protected static $dispatchGatewayBalanceValidators = [
        'direct_channel'
    ];

    protected static $archiveAccountValidators = [
        'direct_channel'
    ];

    protected static $basServiceabilityResponseRules = [
        'city'   => 'required|filled',
        'state'  => 'required|filled',
        'region' => 'required|filled',
        'error'  => 'sometimes'
    ];


    public function validatePincodes(array $input)
    {
        foreach ($input[Entity::PINCODES] as $pincode)
        {
            $pincodeValidator = new Pincode\Validator(Pincode\Pincode::IN);

            if ($pincodeValidator->validate($pincode) === false)
            {
                throw new BadRequestValidationFailureException(
                    'Pincode is not valid',
                    Entity::PINCODE,
                    [
                        Entity::PINCODE => $pincode,
                    ]
                );
            }
        }
    }

    protected function validateChannel($attribute, $channel)
    {
        Channel::validateChannel($channel);
    }

    /**
     * @param string $attribute
     * @param string $status
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateStatus(string $attribute, string $status = null)
    {
        Status::validate($status);
    }

    protected function validateSubStatus(string $attribute, string $subStatus = null)
    {
        Status::validateSubStatus($subStatus);
    }

    protected function validateAccountType(string $attribute, string $accountType)
    {
        if (AccountType::isValid($accountType) === false)
        {
            throw new BadRequestValidationFailureException(
                'Banking account type is invalid',
                Entity::ACCOUNT_TYPE,
                [Entity::ACCOUNT_TYPE => $accountType]);
        }
    }

    protected function validateInternalEditStatus(array $input)
    {
        if (isset($input[Entity::STATUS]) === true)
        {
            $status = $input[Entity::STATUS];

            if (in_array($status, Status::$internallyEditStatuses, true) === false)
            {
                throw new BadRequestValidationFailureException(
                    'Given status is not an allowed status',
                    Entity::STATUS,
                    [
                        Entity::STATUS => $status,
                    ]);
            }
        }
    }

    protected function validateDirectChannel($input)
    {
        if (empty($input[Entity::CHANNEL]) === true)
        {
            return;
        }

        $channel = $input[Entity::CHANNEL];

        $valid = Channel::isValidDirectTypeChannel($input[Entity::CHANNEL]);

        if ($valid === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid direct type channel: ' . $channel,
                Entity::CHANNEL,
                [
                    Entity::CHANNEL => $channel,
                ]);
        }
    }

    protected function validateBeneficiaryName($attribute, $value)
    {
        if (empty($value) === false)
        {
            $match = preg_match(self::NAME_REGEX, trim($value));

            if ($match !== 1)
            {
                throw new BadRequestValidationFailureException(
                    'The beneficiary name field is invalid.',
                    Entity::BENEFICIARY_NAME);
            }
        }
    }

    public function validateAuditorTypeForDailyUpdates(string $auditorType)
    {
        if (in_array($auditorType, ['spoc']) === false)
        {
            throw new BadRequestValidationFailureException(
                'Daily Updates not supported for '. $auditorType);
        }
    }

    public function checkFosLeadCities(string $merchantCity): bool
    {
        foreach (Constants::FOS_CITIES as $city)
        {
            if (strtolower($city) === strtolower($merchantCity))
            {
                return true;
            }
        }

        // adding a fix for city regex match
        foreach (Constants::FOS_CITIES_REGEX as $city)
        {
            if (str_contains(strtolower($merchantCity), strtolower($city)))
            {
                return true;
            }
        }

        return false;
    }

    public function validateUpdatePermissions(Entity $bankingAccount, $admin)
    {
        // Admin auth or Batch auth with $admin entity passed
        $isAdminUpdate = ($admin !== null);

        $core = new Core();

        if ($core->isAdminRequestFromMOB())
        {
            // This check is not required in Admin request from master-onboarding-service (MOB)
            // Execution comes here only in 1 scenario: banking_account status is moved from created to picked
            // after FreshDesk ticket is created in RBL CA onboarding
            // Admins invoking this flow are not expected to have banking_update_account permission.
            return;
        }

        if ($isAdminUpdate === true)
        {
            $adminPermissions = $admin->getPermissionsList();

            $dirtyUpdates = $bankingAccount->getDirty();

            if (empty($dirtyUpdates) === false)
            {
                if (in_array(Permission\Name::BANKING_UPDATE_ACCOUNT, $adminPermissions) === true)
                {
                    // this permission is okay
                    return;
                }
                else
                {
                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_ACCESS_DENIED,
                        null,
                        [
                            'admin_id'             => $admin->getPublicId(),
                            'required_permissions' => [Permission\Name::BANKING_UPDATE_ACCOUNT]
                        ]);
                }
            }

        }
    }

    /**
     * @throws BadRequestException
     */
    public function validateAccountNotTerminated(Entity $bankingAccount, string $errorCode)
    {
        if ($bankingAccount->getStatus() === Status::TERMINATED)
        {
            throw new BadRequestException($errorCode);
        }
    }
}
