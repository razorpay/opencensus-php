<?php

namespace RZP\Models\Merchant\Account;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\BankAccount\Entity as BankAccount;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
         Entity::NAME                                                   => 'required|alpha_space_num|max:200',
         Entity::EMAIL                                                  => 'required|email',
         Entity::TNC_ACCEPTED                                           => 'required|boolean|in:1',
         Entity::NOTES                                                  => 'sometimes|array|max:15',
         Entity::ACCOUNT_DETAILS                                        => 'required|array',
         Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_NAME          => 'required|string',
         Entity::ACCOUNT_DETAILS . '.' . Entity::BUSINESS_TYPE          => 'required|string',

         //
         // Only the key presence is validated here. The actual validation happens in
         // BankAccount\Validator class when the BankAccount Entity is formed.
         //
         Entity::BANK_ACCOUNT                                           => 'required|array',
         Entity::BANK_ACCOUNT . '.' . BankAccount::IFSC_CODE            => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::ACCOUNT_NUMBER       => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_NAME     => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_ADDRESS1 => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_ADDRESS2 => 'sometimes|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_ADDRESS3 => 'sometimes|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_ADDRESS4 => 'sometimes|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_EMAIL    => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_MOBILE   => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_CITY     => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_STATE    => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_COUNTRY  => 'required|string',
         Entity::BANK_ACCOUNT . '.' . BankAccount::BENEFICIARY_PIN      => 'required|string',
     ];
}
