<?php

namespace RZP\Models\BankAccount;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Constants\Mode;
use Razorpay\IFSC\IFSC;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Bank\IFSC as BankIFSC;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    //temporary fix as this part has to be shifted to shield
    private const BLACKLISTED_ACCOUNTS = [
        '4104115000012344', '1921238323624830', '31260200000646', '201002552973'
    ];

    const INVALID_IFSC_CODE_MESSAGE         = 'Invalid IFSC Code in Bank Account';
    const INVALID_ADDRESS_PROOF_URL_MESSAGE = 'Invalid Address Proof File in Details or Invalid Auth';

    protected static $fileUploadRules = [
        Detail\Entity::ADDRESS_PROOF_URL => 'required|file|max:50000|mime_types:'
                                            . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                                            . 'application/msword,'
                                            . 'application/pdf,'
                                            . 'application/x-pdf,'
                                            . 'image/png,'
                                            . 'image/jpg,'
                                            . 'text/xml,'
                                            . 'application/xml,'
                                            . 'application/octet-stream,'
                                            . 'text/csv,'
                                            . 'text/plain,'
                                            . 'video/3gpp2,'
                                            . 'video/3gpp,'
                                            . 'video/x-msvideo,'
                                            . 'video/x-flv,'
                                            . 'video/mp4,'
                                            . 'video/m4v,'
                                            . 'video/x-matroska,'
                                            . 'video/quicktime,'
                                            . 'video/mp4,'
                                            . 'video/mpeg,'
                                            . 'video/mpeg,'
                                            . 'application/vnd.rn-realmedia,'
                                            . 'video/x-ms-wmv,'
                                            . 'image/jpeg,'
    ];

    protected static $addBankAccountRules = [
        Detail\Entity::ADDRESS_PROOF_URL        => 'sometimes',
        Entity::ENTITY_ID                       => 'sometimes',
        Entity::IFSC_CODE                       => 'required|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER                  => 'required|regex:/^[a-zA-Z0-9]+$/|between:5,35|custom',
        Entity::BENEFICIARY_NAME                => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+/|between:4,120|string',
        Entity::ACCOUNT_TYPE                    => 'sometimes|nullable|string|custom',
        Entity::BENEFICIARY_ADDRESS1            => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS2            => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS3            => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS4            => 'sometimes|max:30',
        Entity::MOBILE_BANKING_ENABLED          => 'sometimes|in:0,1',
        Entity::MPIN                            => 'sometimes|max:6',
        Entity::BENEFICIARY_CITY                => 'sometimes|max:30|alpha_space',
        Entity::BENEFICIARY_STATE               => 'sometimes|max:2',
        Entity::BENEFICIARY_PIN                 => 'sometimes|integer|digits:6',
        Entity::BENEFICIARY_COUNTRY             => 'sometimes|in:IN',
        Entity::BENEFICIARY_EMAIL               => 'sometimes|email',
        Entity::BENEFICIARY_MOBILE              => 'sometimes|max:15|contact_syntax',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::TYPE                            => 'sometimes|string',
    ];

    protected static $addInternationalBankAccountRules = [
        Detail\Entity::ADDRESS_PROOF_URL        => 'sometimes',
        Entity::ENTITY_ID                       => 'sometimes',
        Entity::IFSC_CODE                       => 'required|alpha_num|between:8,11',
        Entity::ACCOUNT_NUMBER                  => 'required|regex:/^[a-zA-Z0-9]+$/|between:5,35|custom',
        Entity::BENEFICIARY_NAME                => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+/|between:4,120|string',
        Entity::ACCOUNT_TYPE                    => 'sometimes|nullable|string|custom',
        Entity::BENEFICIARY_ADDRESS1            => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS2            => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS3            => 'sometimes|max:30',
        Entity::BENEFICIARY_ADDRESS4            => 'sometimes|max:30',
        Entity::MOBILE_BANKING_ENABLED          => 'sometimes|in:0,1',
        Entity::MPIN                            => 'sometimes|max:6',
        Entity::BENEFICIARY_CITY                => 'sometimes|max:30|alpha_space',
        Entity::BENEFICIARY_STATE               => 'sometimes|max:2',
        Entity::BENEFICIARY_PIN                 => 'sometimes|max:6',
        Entity::BENEFICIARY_COUNTRY             => 'required',
        Entity::BENEFICIARY_EMAIL               => 'sometimes|email',
        Entity::BENEFICIARY_MOBILE              => 'sometimes|max:15|contact_syntax',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::TYPE                            => 'sometimes|string',
        Entity::BANK_NAME                       => 'sometimes|string',
    ];

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER      => 'sometimes|regex:/^[a-zA-Z0-9]+$/|between:5,35|custom',
        Entity::BENEFICIARY_NAME    => 'sometimes|between:4,120|string|custom',
    ];

    protected static $addVirtualBankAccountRules = [
        Entity::IFSC_CODE             => 'sometimes|alpha_num|nullable|max:13',
        Entity::ACCOUNT_NUMBER        => 'required|regex:/^[a-zA-Z0-9-]+$/|between:4,20',
        Entity::BENEFICIARY_NAME      => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]*/|max:40|string',
    ];

    protected static $editVirtualBankAccountRules = [
        Entity::IFSC_CODE             => 'sometimes|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER        => 'sometimes|regex:/^[a-zA-Z0-9-]+$/|max:20',
        Entity::BENEFICIARY_NAME      => 'sometimes|string|max:100',
    ];

    protected static $addPayoutDestinationRules = [
        Entity::IFSC_CODE             => 'required|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER        => 'required|regex:/^[a-zA-Z0-9]+$/|between:5,35',
        Entity::BENEFICIARY_NAME      => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+/|max:40|string',
    ];

    protected static $addBankTransferRules = [
        Entity::IFSC_CODE             => 'required|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER        => 'required|regex:/^[a-zA-Z0-9]+$/|between:5,35',
        Entity::BENEFICIARY_NAME      => 'sometimes|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+/|max:40|string',
    ];

    /*
     * RZP\Models\FundAccount\Core::REGEX_FOR_REMOVING_WHITE_SPACES_AND_SPECIAL_CHARACTERS_FROM_BANK_ACCOUNT_NAME
     * needs to be updated accordingly if the validation regex for name field is changed in
     * $addFundAccountBankAccountRules.
     */
    protected static $addFundAccountBankAccountRules = [
        Entity::IFSC           => 'required|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER => 'required|regex:/^[a-zA-Z0-9]+$/|between:5,35',
        Entity::NAME           => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+/|between:3,120|string',
        Entity::ACCOUNT_TYPE   => 'sometimes|nullable|string|custom',
    ];

    protected static $addTpvBankAccountRules = [
        Entity::IFSC            => 'required|alpha_num|size:11',
        Entity::ACCOUNT_NUMBER  => 'required|regex:/^[a-zA-Z0-9]+$/|between:5,35',
        Entity::NAME            => 'sometimes|regex:/^[a-zA-Z0-9][a-zA-Z0-9-&\'._()\s–\/]+/|max:120|string',
    ];

    protected static $addTpvBankAccountForVaRules = [
        Entity::IFSC                => 'required|alpha_num|size:11|custom',
        Entity::ACCOUNT_NUMBER      => 'required|alpha_num|between:5,35',
        Entity::BENEFICIARY_NAME    => 'sometimes|string',
    ];

    protected static $addBankAccountValidators = [
        Entity::BENEFICIARY_STATE,
    ];

    protected static $beneficiaryStateCodes = [
        'AN', 'AP', 'AR', 'AS', 'BI', 'CH', 'CT', 'DN',
        'DD', 'GO', 'GJ', 'HA', 'HP', 'JK', 'JH', 'KA',
        'KE', 'LD', 'MP', 'MH', 'MA', 'ME', 'MI', 'NA',
        'DL', 'OR', 'PO', 'PB', 'RJ', 'SK', 'TG', 'TN',
        'TR', 'UP', 'UT', 'WB'
    ];

    protected static $merchantBeneficiaryRegisterRules = [
        'merchant_ids'      => 'sometimes|array',
        'merchant_ids.*'    => 'sometimes|string|size:14',
    ];

    protected static $beneficiaryRegisterRules = [
        Entity::ON                      => 'sometimes|epoch',
        Entity::TO                      => 'required_with:from|epoch',
        Entity::FROM                    => 'required_with:to|epoch',
        Entity::RECIPIENT_EMAILS        => 'sometimes|array',
        Entity::RECIPIENT_EMAILS . '*'  => 'sometimes|email',
    ];

    protected static $beneficiaryRegisterApiRules = [
        Entity::ALL                     => 'sometimes|boolean',
        Entity::DURATION                => 'sometimes|integer',
        'failed_response'               => 'sometimes|int',
        'send_email'                    => 'sometimes|boolean',
    ];

    protected function validateBeneficiaryState($input)
    {
        if ((isset($input[Entity::BENEFICIARY_STATE]) === true) and
            (in_array($input[Entity::BENEFICIARY_STATE], self::$beneficiaryStateCodes, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid state code');
        }
    }

    protected function validateBeneficiaryName(string $attribute, string $value)
    {
        $currentBeneficiaryName = $this->entity->getBeneficiaryName();

        if (empty($currentBeneficiaryName) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Can edit only empty beneficiary names',
                Entity::BENEFICIARY_NAME,
                [
                    'current_beneficiary_name'  => $currentBeneficiaryName,
                    'new_beneficiary_name'      => $value
                ]);
        }
    }

    public function validateIfscCode(array $input, $mode = 'test')
    {
        $ifsc = $input[Entity::IFSC_CODE] ?? ($input[Entity::IFSC] ?? '');

        $ifsc = strtoupper($ifsc);

        // We allow a special IFSC code to pass through
        if ($this->isSpecialIfscCode($ifsc, $mode))
        {
            return;
        }

        if (!IFSC::validate($ifsc))
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_IFSC_CODE_MESSAGE);
        }
    }

    public function validateAddressProofUploadOverProxyAuth()
    {
        // Address proof URL will be passed only when merchant
        // is requesting for bank account change which will only
        // happen over proxy auth
        $app = App::getFacadeRoot();

        if ($app['basicauth']->isProxyAuth() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_ADDRESS_PROOF_URL_MESSAGE);
        }
    }

    /**
     * We allow using the special IFSC code only
     * for the test mode
     * @param  string  $ifsc IFSC code, uppercase
     */
    protected function isSpecialIfscCode($ifsc, $mode)
    {
        return ((($mode === Mode::TEST) or ($mode === null)) and
                (($ifsc === Entity::SPECIAL_IFSC_CODE) or
                 ($ifsc === 'RAZR0000001')));
    }

    public function validateRefundIsAllowed()
    {
        $bankAccount = $this->entity;

        // If payer bank account exists, but without an IFSC, it means we
        // did not have the bank-code-to-IFSC mapping for an IMPS payment.
        $ifsc = $bankAccount->getIfscCode();

        if ($ifsc === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                $bankAccount);
        }
    }

    public function validateAccountType($attribute, $value)
    {
        if (AccountType::isAccountTypeValid($value) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid Account type',
                Entity::ACCOUNT_TYPE,
                [
                    'field'        => Entity::ACCOUNT_TYPE,
                    'account_type' => $value
                ]);
        }
    }

    public function validateAccountNumber($attribute, $bankAccountNumber)
    {
        if(self::isBlacklistedAccountNumber($bankAccountNumber))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT);
        }
    }

    public static function isBlacklistedAccountNumber($bankAccountNumber): bool
    {
        return (
            in_array($bankAccountNumber, self::BLACKLISTED_ACCOUNTS, true) === true
        );
    }

    protected function validateIfsc($key, $value)
    {
        $bankCode = substr($value, 0, 4);

        if (BankIFSC::exists($bankCode) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_PAYER_IFSC,
                null,
                $value,
                $bankCode . ' is not a valid bank code.'
            );
        }
    }
}
