<?php

namespace RZP\Models\PaperMandate;

use Carbon\Carbon;

use RZP\Base;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const MAX_AMOUNT_LIMIT = 1000000000;

    protected static $createRules = [
        Entity::AMOUNT                   => 'filled|mysql_unsigned_int|min:500|max:'. self::MAX_AMOUNT_LIMIT .'|custom',
        Entity::TYPE                     => 'filled|string|custom',
        Entity::DEBIT_TYPE               => 'filled|string|custom',
        Entity::TERMINAL_ID              => 'filled|string',
        Entity::BANK_ACCOUNT             => 'required|array',
        Entity::FREQUENCY                => 'filled|string|custom',
        Entity::REFERENCE_1              => 'sometimes|string|max:50',
        Entity::REFERENCE_2              => 'sometimes|string|max:50',
        Entity::START_AT                 => 'filled|integer|custom|nullable',
        Entity::END_AT                   => 'sometimes|epoch|nullable',
        Entity::SECONDARY_ACCOUNT_HOLDER => 'sometimes|string|max:22|nullable',
        Entity::TERTIARY_ACCOUNT_HOLDER  => 'sometimes|string|max:22|nullable',
    ];

    protected static $bankAccountRules = [
        BankAccount\Entity::BENEFICIARY_NAME   => 'required|between:4,120|string',
        BankAccount\Entity::BENEFICIARY_EMAIL  => 'sometimes|email',
        BankAccount\Entity::BENEFICIARY_MOBILE => 'sometimes|numeric|digits_between:10,12',
    ];

    protected static $createValidators = [
        Entity::END_AT,
    ];

    public function validateType($attribute, $value)
    {
        if (Type::isValidType($value) === false)
        {
            throw new BadRequestValidationFailureException(
                $value . ' is not valid type of paper mandate'
            );
        }

        if ($value !== Type::CREATE)
        {
            throw new BadRequestValidationFailureException(
                $value . ' is currently not supported type of paper mandate'
            );
        }
    }

    public function validateDebitType($attribute, $value)
    {
        if (DebitType::isValidType($value) === false)
        {
            throw new BadRequestValidationFailureException(
                $value . ' is not valid debit type of paper mandate'
            );
        }
    }

    public function validateFrequency($attribute, $value)
    {
        if (Frequency::isValidType($value) === false)
        {
            throw new BadRequestValidationFailureException(
                $value . ' is not valid frequency of paper mandate'
            );
        }
    }

    protected function validateStartAt($attribute, $value)
    {
        $currentTime = Carbon::now()->getTimestamp();

        if ($value < $currentTime)
        {
            throw new BadRequestValidationFailureException(
                'paper mandate start at cannot be lesser than the current time.',
                $attribute,
                [
                    'start_at'      => $value,
                    'current_time'  => $currentTime,
                ]);
        }
    }

    protected function validateEndAt(array $input)
    {
        if (isset($input[Entity::END_AT]) === false)
        {
            return;
        }

        $currentTime = Carbon::now()->getTimestamp();

        $endAt = $input[Entity::END_AT];

        if ($endAt < $currentTime)
        {
            throw new BadRequestValidationFailureException(
                'paper mandate end at cannot be lesser than the current time.',
                Entity::END_AT,
                [
                    'end_at'      => $endAt,
                    'current_time'  => $currentTime,
                ]
            );
        }

        if ((isset($input[Entity::START_AT]) === true) and
            ($input[Entity::START_AT] > $endAt))
        {
            throw new BadRequestValidationFailureException(
                'end at cannot be lesser than start at.',
                Entity::END_AT,
                [
                    'end_at'      => $endAt,
                    'start_at'  => $input[Entity::START_AT],
                ]
            );
        }
    }

    /**
     * @param  string   $attribute
     * @param  int|null $amount
     * @throws BadRequestValidationFailureException
     */
    public function validateAmount(string $attribute, int $amount = null)
    {
        if ($amount === null)
        {
            return;
        }

        if ($amount < 500)
        {
            throw new BadRequestValidationFailureException(
                'Minimum transaction amount allowed is Re. 5',
                Entity::AMOUNT,
                [Entity::AMOUNT => $amount]
            );
        }

        if ($amount % 100 !== 0)
        {
            throw new BadRequestValidationFailureException(
                'Amount should not contain paise',
                Entity::AMOUNT,
                [Entity::AMOUNT => $amount]
            );
        }

        $amountInWord = (new HyperVerge)->getFormattedAmountInWords($amount);

        if (strlen($amountInWord) > 65)
        {
            throw new BadRequestValidationFailureException(
                'converting the given amount exceeds the 65 characters, please try to give a whole number',
                Entity::AMOUNT,
                [
                    Entity::AMOUNT => $amount,
                    'amount_in_word' => $amountInWord
                ]
            );
        }
    }

    public function validateToAuthenticate()
    {
        if ($this->entity->getStatus() !== Status::CREATED)
        {
            throw new BadRequestValidationFailureException(
                'form has already been uploaded successfully'
            );
        }
    }

    public function validatePaymentCreation()
    {
        if (empty($this->entity->getUploadedFileID()) === true)
        {
            throw new BadRequestValidationFailureException(
                'payment can\'t be created without nach form submission'
            );
        }
    }

    public function validateBankAccount(BankAccount\Entity $bankAccount)
    {
        $input = [
            BankAccount\Entity::BENEFICIARY_NAME   => $bankAccount->getBeneficiaryName(),
            BankAccount\Entity::BENEFICIARY_EMAIL  => $bankAccount->getBeneficiaryEmail(),
            BankAccount\Entity::BENEFICIARY_MOBILE => $bankAccount->getBeneficiaryMobile(),
        ];

        $this->validateInput('bank_account', $input);
    }
}
