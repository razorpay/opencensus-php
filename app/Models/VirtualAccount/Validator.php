<?php

namespace RZP\Models\VirtualAccount;

use App;
use RZP\Base;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    // close by while creating a va should be atleast 15 mins ahead of current time
    const MIN_CLOSE_BY_DIFF = 120;

    const DEFAULT_CLOSE_BY_DIFF = 900;

    protected static $createRules = [
        Entity::NAME                            => 'filled|string|max:40',
        Entity::AMOUNT_EXPECTED                 => 'filled|integer|min:0',
        Entity::DESCRIPTION                     => 'sometimes|nullable|string|max:2048',
        Entity::CUSTOMER_ID                     => 'filled|public_id|size:19',
        'usage'                                 => 'sometimes|in:single_use,multiple_use',
        Entity::ORDER_ID                        => 'filled|public_id|size:20',
        Entity::RECEIVERS                       => 'bail|required|array|custom',
        Entity::RECEIVERS . '.' . Entity::TYPES => 'present|array|min:1',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::CLOSE_BY                        => 'filled|epoch|custom',
        Entity::CUSTOMER                        => 'sometimes|array',
        Entity::ALLOWED_PAYERS                  => 'sometimes|array|min:1|max:10',
    ];

    protected static $createForBankingRules = [
        Entity::NAME                            => 'filled|string|max:40',
    ];

    protected static $editRules = [
        Entity::NAME            => 'filled|string|max:40',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CLOSE_BY        => 'filled|epoch|custom',
    ];

    protected static $editVARules = [
        Entity::NAME            => 'filled|string|max:40',
        Entity::DESCRIPTION     => 'sometimes|nullable|string|max:2048',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CLOSE_BY        => 'required|string',
    ];

    protected static $editForOrderRules = [
        Entity::STATUS          => 'sometimes',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CLOSE_BY        => 'filled|epoch|custom',
        Entity::AMOUNT_EXPECTED => 'filled|integer|min:0',
        Entity::AMOUNT_PAID     => 'filled|integer|min:0',
        Entity::AMOUNT_RECEIVED => 'filled|integer|min:0',
    ];

    protected static $bankAccountReceiverOptionRules = [
        Entity::NUMERIC    => 'sometimes|boolean',
        Entity::DESCRIPTOR => 'sometimes|alpha_num|max:10',
        Entity::NAME       => 'filled|string|max:40',
    ];

    protected static $vpaReceiverOptionRules = [
        Entity::DESCRIPTOR => 'filled|regex:/^[A-Za-z0-9\.\-]{3,}$/|max:20',
    ];

    protected static $createOfflineQrRules = [
        'amount'                   => 'filled|integer|min:100',
        'receipt'                  => 'required|string|max:40',
        'currency'                 => 'required|string|size:3|in:INR',
        'notifications'            => 'array',
        'notifications.device_id'  => 'filled|string|public_id|size:18',
        Entity::DESCRIPTION        => 'sometimes|nullable|string|max:2048',
        Entity::NOTES              => 'sometimes|notes',
    ];

    public static $validateVpaIciciRules = [
        'Source'       => 'required|string',
        'SubscriberId' => 'required|string',
        'TxnId'        => 'required|string',
        'MerchantKey'  => 'nullable|string',
    ];

    public static $addReceiversRules = [
        Entity::TYPES                                   => 'required|array|in:bank_account,vpa,qr_code',
        Entity::BANK_ACCOUNT                            => 'sometimes',
        Entity::VPA                                     => 'sometimes',
        Entity::QR_CODE                                 => 'sometimes',
    ];

    public static $defaultVAExpiryRules = [
        Constant::VA_EXPIRY_OFFSET => 'required|integer',
        Entity::MERCHANT_ID => 'sometimes|string'
    ];

    public static $addCustomAccountNumberSettingRules = [
        'merchant_id'          =>   'required|integer',
        'key'                  =>   'required|string',
        'value'                =>   'required|string'
    ];

    public static $bulkCloseVirtualAccountRules = [
        'merchant_ids'          => 'filled|array|max:10|min:1',
        'virtual_account_ids'   => 'filled|array|max:100|min:1'
    ];

    public static $autoCloseInactiveVirtualAccountRules = [
        'merchant_ids'          =>  'sometimes|array|max:100|min:1',
        'exclude_mids'          =>  'sometimes|array',
        'end_date_delta'        =>  'sometimes|integer',
        'virtual_account_ids'   =>  'sometimes|array|min:1',
        'start_date'            =>  'sometimes|date_format:Y-m-d',
        'end_date'              =>  'sometimes|date_format:Y-m-d',
        'gateway'               =>  'sometimes|string',
        'expiry_delta'          =>  'sometimes|numeric',
        'limit'                 =>  'sometimes|numeric'
    ];

    protected static $createValidators = [
        Entity::RECEIVER_TYPES,
    ];

    protected static $offlineChallanGenericRules = [
        'challan_number'                =>  'required|string|size:16',
        'client_code'                   =>  'required|string',
        'identification_id'             =>  'required|string',
        'amount'                        =>  'sometimes|required|integer'
    ];

    public static $offlineChallanHdfcRules = [
        'challan_no'                    =>  'required|string|size:16',
        'client_code'                   =>  'required|string',
        'identification_id'             =>  'required|string',
        'expected_amount'               =>  'sometimes|required|integer'
    ];

    protected function validateReceivers(string $key, array $value, array $data)
    {
        if ((isset($value[Entity::TYPES]) === true) and
            (is_array($value[Entity::TYPES]) === true) and
            (Receiver::areTypesValid($value[Entity::TYPES]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_type',
                $data);
        }
    }

    /**
     * Currently only validating the the receivers.qr_code
     *
     * @param array $input
     * @throws Exception\BadRequestException
     */
    protected function validateReceiverTypes(array $input)
    {
        if (isset($input[Entity::RECEIVERS][Receiver::QR_CODE][Payment\Entity::METHOD]) === false)
        {
            // This case is already validated separately
            return;
        }

        if ((isset($input[Entity::RECEIVERS][Receiver::QR_CODE][Payment\Entity::METHOD]) === true) and
            (isset($input[Entity::RECEIVERS][Entity::TYPES])) and
            (is_array($input[Entity::RECEIVERS][Entity::TYPES])))
        {
            // QR code should not be passed if receiver types has bank account
            if (in_array(Receiver::QR_CODE, $input[Entity::RECEIVERS][Entity::TYPES], true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                    'receiver_type',
                    $input);
            }
        }

        // All we want to make sure if receivers.qr_code.method is passes
        $method = $input[Entity::RECEIVERS][Receiver::QR_CODE][Payment\Entity::METHOD];

        if (is_array($method) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_qr_code_method',
                $method);
        }

        $upi  = filter_var(array_get($method, Payment\Method::UPI), FILTER_VALIDATE_BOOLEAN);
        $card = filter_var(array_get($method, Payment\Method::CARD), FILTER_VALIDATE_BOOLEAN);

        $onlyUpi = (($upi === true) and ($card === false));

        // Currently no other combination is allowed
        if ($onlyUpi === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_INVALID_RECEIVER_TYPES,
                'receiver_method',
                $method);
        }

        if ($onlyUpi === true)
        {
            // Order support is not provided for Only UPI QR
            if (isset($input[Entity::ORDER_ID]) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DISALLOWED_FOR_ORDER,
                    'receiver_qr_code_method',
                    $method);
            }

            $merchant = app('basicauth')->getMerchant();
            // Amount expected is required for UPI QR
            if (((isset($input[Entity::AMOUNT_EXPECTED]) === false) and
                // For more than one receivers, we will allow no amount_expected
                    (count($input[Entity::RECEIVERS][Entity::TYPES]) === 1)) and
                $merchant->isFeatureEnabled(Feature\Constants::UPIQR_V1_HDFC) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Amount expected is required for UPI QR receivers',
                    Entity::AMOUNT_EXPECTED,
                    $input);
            }
        }
    }

    /**
     * @param array $receivers
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateReceiversForBanking(array $receivers)
    {
        /** @var Entity $virtualAccount */
        $virtualAccount = $this->entity;

        if ($virtualAccount->isBalanceTypeBanking() === false)
        {
            return;
        }

        // Must only have types as [bank_account] for banking balance case.
        if ((count($receivers[Entity::TYPES]) !== 1) or
            ($receivers[Entity::TYPES][0] !== Receiver::BANK_ACCOUNT))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Receiver of type bank_account must only exist',
                Entity::RECEIVERS,
                compact('receivers'));
        }
    }

    public function validateOfPrimaryBalance()
    {
        if ($this->entity->isBalanceTypePrimary() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Operation is not allowed for this specific virtual account',
                null,
                [
                    Entity::ID => $this->entity->getId(),
                ]);
        }
    }

    public function validateOfBankingBalance()
    {
        if ($this->entity->isBalanceTypeBanking() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Operation is not allowed for this specific virtual account',
                null,
                [
                    Entity::ID => $this->entity->getId(),
                ]);
        }
    }

    public function validateCloseBy(string $attribute, int $closeBy)
    {
        $now = Carbon::now(Timezone::IST);

        $minCloseBy = $now->copy()->addSeconds(self::MIN_CLOSE_BY_DIFF);

        if ($closeBy < $minCloseBy->getTimestamp())
        {
            $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateDefaultCloseBy($input)
    {
        if (isset($input[Entity::CLOSE_BY]) === false)
        {
            return;
        }

        $closeBy = $input[Entity::CLOSE_BY];

        $now = Carbon::now(Timezone::IST);

        $minCloseBy = $now->copy()->addSeconds(self::DEFAULT_CLOSE_BY_DIFF);

        if ($closeBy < $minCloseBy->getTimestamp())
        {
            $message = 'close_by should be at least ' . $minCloseBy->diffForHumans($now) . ' current time';

            throw new BadRequestValidationFailureException($message);
        }
    }

    public function validateSource($attribute, $value)
    {
        SourceType::checkSourceType($value);
    }

    public function validateMerchantIdAndVirtualAccountId(array $input)
    {
        if (isset($input['merchant_ids']) and isset($input['virtual_account_ids']))
        {
            throw new BadRequestValidationFailureException('Please pass either merchant ids or virtual account ids');
        }
    }

    public function validateAndSetGateway(array &$input)
    {
        if (isset($input['gateway']) === true)
        {
            if (isset(Provider::IFSC[$input['gateway']]) === false)
            {
                throw new BadRequestValidationFailureException('Invalid gateway provided');
            }

            $input['gateway'] = Provider::IFSC[$input['gateway']];
        }
    }

    public function validateStartAndEndDate(array $input, $expiryDelta)
    {
        $expiryDeltaTimestamp = Carbon::now(Timezone::IST)->subDays($expiryDelta)->getTimestamp();

        if (isset($input['start_date']) === true)
        {
            $startDate = Carbon::createFromFormat('Y-m-d', $input['start_date'])->getTimestamp();

             if ($startDate > $expiryDeltaTimestamp)
             {
                 throw new BadRequestValidationFailureException("Start date cannot be less than $expiryDelta days away from current day");
             }
        }

        if (isset($input['end_date']) === true)
        {
            $endDate = Carbon::createFromFormat('Y-m-d', $input['end_date'])->getTimestamp();

            if ($endDate > $expiryDeltaTimestamp)
            {
                throw new BadRequestValidationFailureException("End date cannot be less than $expiryDelta days away from current day");
            }
        }
    }
}
