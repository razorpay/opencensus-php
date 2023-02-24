<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Vpa;
use RZP\Models\Contact;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Models\WalletAccount;
use RZP\Models\Payout\Mode as PayoutMode;
use RZP\Models\Payout\BatchHelper as PayoutBatchHelper;
use RZP\Exception\BadRequestValidationFailureException;

class BatchHelper
{
    const ID                    = 'id';
    const TYPE                  = 'account_type';
    const IFSC                  = 'account_IFSC';
    const NUMBER                = 'account_number';
    const NAME                  = 'account_name';
    const VPA                   = 'account_vpa';
    const PHONE_NUMBER          = 'account_phone_number';
    const EMAIL                 = 'account_email';
    const FUND_ACCOUNT          = 'fund';
    const BANK_ACCOUNT_TYPE     = 'bank_account_type';


    // Todo: Include this field in the sample bulk contact file
    const PROVIDER       = 'account_provider';

    // Default Provider to be used if no provider is passed or
    // provider can't be inferred from the data passed
    const DEFAULT_PROVIDER = 'default_provider';

    public static function getFundAccountInput(array $entry, Contact\Entity $contact): array
    {
        $fundAccountType = $entry[self::FUND_ACCOUNT][self::TYPE];

        $input = [
            Entity::ACCOUNT_TYPE => $fundAccountType,
        ];

        $input[FundAccount\Entity::CONTACT_ID] = $contact->getPublicId();

        // Per fund account type, prepares details key input for fund account's core.
        switch ($fundAccountType)
        {
            case Type::BANK_ACCOUNT:
                $input[Entity::BANK_ACCOUNT] = [
                    BankAccount\Entity::IFSC           => $entry[self::FUND_ACCOUNT][self::IFSC],
                    BankAccount\Entity::ACCOUNT_NUMBER => trim($entry[self::FUND_ACCOUNT][self::NUMBER]),
                    BankAccount\Entity::NAME           => $entry[self::FUND_ACCOUNT][self::NAME],
                    BankAccount\Entity::ACCOUNT_TYPE   => $entry[self::FUND_ACCOUNT][self::BANK_ACCOUNT_TYPE],
                ];
                break;

            case Type::VPA:
                $input[Entity::VPA] = [
                    Vpa\Entity::ADDRESS => $entry[self::FUND_ACCOUNT][self::VPA],
                ];
                break;

            case Type::WALLET:
                $walletProvider = self::DEFAULT_PROVIDER;

                // Bulk contacts input contains provider info, but
                // for bulk payouts provider needs to be inferred from mode
                if ((isset($entry[self::FUND_ACCOUNT][self::PROVIDER]) === true) and
                    (empty($entry[self::FUND_ACCOUNT][self::PROVIDER]) === false))
                {
                    $walletProvider = $entry[self::FUND_ACCOUNT][self::PROVIDER];
                }
                else if (isset($entry[PayoutBatchHelper::PAYOUT][PayoutBatchHelper::PAYOUT_MODE]) === true)
                {
                    $walletProvider = self::getWalletProviderFromPayoutMode($entry[PayoutBatchHelper::PAYOUT][PayoutBatchHelper::PAYOUT_MODE]);
                }

                if($walletProvider === self::DEFAULT_PROVIDER)
                {
                    throw new BadRequestValidationFailureException(
                        "Wallet provider is not supported");
                }

                $input[Entity::WALLET] = [
                    WalletAccount\Entity::PHONE        => $entry[self::FUND_ACCOUNT][self::PHONE_NUMBER],
                    WalletAccount\Entity::PROVIDER     => $walletProvider,
                ];

                // Fund Account Email is an optional field for amazonpay.
                // So passing email field only if it is non empty
                if ((isset($entry[self::FUND_ACCOUNT][self::EMAIL])) and
                   (empty($entry[self::FUND_ACCOUNT][self::EMAIL]) === false))
                {
                    $input[Entity::WALLET] += [
                        WalletAccount\Entity::EMAIL    => $entry[self::FUND_ACCOUNT][self::EMAIL],
                    ];
                }
                break;

            default:
                throw new BadRequestValidationFailureException(
                    "Invalid value for fund account type - $fundAccountType",
                    self::TYPE,
                    $input);
        }

        $input[Entity::IDEMPOTENCY_KEY] = $entry[Entity::IDEMPOTENCY_KEY];

        return $input;
    }

    protected static function getWalletProviderFromPayoutMode(string $payoutMode)
    {
        switch ($payoutMode)
        {
            case WalletAccount\Provider::AMAZONPAY_PROVIDER:
                return PayoutMode::AMAZONPAY;

            default:
                return self::DEFAULT_PROVIDER;
        }
    }
}
