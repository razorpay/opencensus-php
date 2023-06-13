<?php

namespace RZP\Models\Payment;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\{Card, Customer\Token, Terminal, BankTransfer};

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::EMAIL                        => 'sometimes|email',
            Merchant\Entity::COUNTRY_CODE        => 'sometimes|string|max:7',
            Entity::CONTACT                      => 'sometimes|string|max:15|required_with:country_code',
            Entity::ORDER_ID                     => 'sometimes|string|size:20',
            Entity::INVOICE_ID                   => 'sometimes|public_id|size:18',
            Entity::TRANSFERRED                  => 'sometimes|boolean|in:0,1',
            Entity::CUSTOMER_ID                  => 'sometimes|size:19|custom',
            Entity::RECURRING                    => 'sometimes|boolean|in:0,1',
            Entity::STATUS                       => 'sometimes|string',
            Entity::PAYMENT_LINK_ID              => 'filled|public_id|size:17',
            Entity::SUBSCRIPTION_ID              => 'sometimes|string|min:14|max:18',
            Entity::BANK_REFERENCE               => 'sometimes|alpha_num|max:22',
            Entity::TRANSFER_ID                  => 'filled|public_id|size:18',
            Entity::CAPTURED                     => 'sometimes|boolean',
            Entity::BATCH_ID                     => 'sometimes|string|size:20',
            self::EXPAND_EACH                    => 'filled|string|in:card,emi,transaction,transaction.settlement,refunds,offers,token',
            Entity::NOTES                        => 'sometimes|string|max:500',
            Entity::VERIFIED                     => 'sometimes|in:null,0,1,2',
            Entity::REFUND_STATUS                => 'sometimes|in:null,partial,full',
            Entity::TWO_FACTOR_AUTH              => 'sometimes|string',
            Entity::BANK                         => 'sometimes',
            Entity::METHOD                       => 'sometimes',
            Entity::GATEWAY                      => 'sometimes',
            Entity::MERCHANT_ID                  => 'sometimes|alpha_num|size:14',
            Entity::CARD_ID                      => 'sometimes|alpha_num|size:14',
            Entity::WALLET                       => 'sometimes|custom',
            Card\Entity::IIN                     => 'sometimes|integer|digits:6',
            Card\Entity::LAST4                   => 'sometimes|string|digits:4',
            Entity::INTERNATIONAL                => 'sometimes|in:0,1',
            Entity::TOKEN_ID                     => 'sometimes|alpha_num|size:14',
            Entity::GLOBAL_TOKEN_ID              => 'sometimes|alpha_num|size:14',
            Entity::SAVE                         => 'sometimes|in:0,1',
            Entity::LATE_AUTHORIZED              => 'sometimes|in:0,1',
            Entity::AMOUNT                       => 'sometimes|integer',
            Entity::TERMINAL_ID                  => 'sometimes|alpha_num|size:14',
            Token\Entity::RECURRING_STATUS       => 'sometimes|string|max:15',
            Entity::VPA                          => 'sometimes|string|max:100',
            Terminal\Entity::GATEWAY_TERMINAL_ID => 'sometimes',
            Entity::ACQUIRER_DATA                => 'sometimes',
            Entity::VIRTUAL_ACCOUNT_ID           => 'sometimes|string|max:17',
            Entity::VIRTUAL_ACCOUNT              => 'sometimes|in:0,1',
            Entity::VA_TRANSACTION_ID            => 'sometimes|string',
            Entity::SETTLED_BY                   => 'sometimes',
            Entity::INTL_BANK_TRANSFER           => 'sometimes|in:0,1',
            EsRepository::QUERY                  => 'sometimes|string|min:1|max:100',
        ],
        AuthType::PROXY_AUTH => [
            // @codingStandardsIgnoreLine
            self::EXPAND_EACH =>
                'filled|string|in:card,emi,emi_plan,disputes,transfer,token,transfer.recipient_settlement,transaction,transaction.settlement|custom:expand',
        ],
        AuthType::ADMIN_AUTH => [
            Entity::NOTES       => 'sometimes|notes_fetch',
            Entity::TRANSFER_ID => 'sometimes|alpha_num|size:14',
            Entity::CUSTOMER_ID => 'sometimes|alpha_num|size:14',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::EMAIL,
            Entity::CONTACT,
            Entity::ORDER_ID,
            Entity::INVOICE_ID,
            Entity::TRANSFERRED,
            Entity::CUSTOMER_ID,
            Entity::RECURRING,
            self::EXPAND_EACH,
            Entity::NOTES,
            Entity::PAYMENT_LINK_ID,
            Entity::VIRTUAL_ACCOUNT_ID,
            Entity::VIRTUAL_ACCOUNT,
            Entity::VA_TRANSACTION_ID,
            Entity::INTL_BANK_TRANSFER,
        ],
        AuthType::PROXY_AUTH => [
            Entity::STATUS,
            Entity::PAYMENT_LINK_ID,
            Entity::SUBSCRIPTION_ID,
            Entity::BANK_REFERENCE,
            Entity::TRANSFER_ID,
            Entity::CAPTURED,
            Entity::BATCH_ID,
            Entity::TERMINAL_ID,
            Entity::SETTLED_BY,
            Merchant\Entity::COUNTRY_CODE,
            EsRepository::QUERY,
        ],
        AuthType::ADMIN_AUTH => [
            Entity::VERIFIED,
            Entity::REFUND_STATUS,
            Entity::TWO_FACTOR_AUTH,
            Entity::BANK,
            Entity::METHOD,
            Entity::GATEWAY,
            Entity::MERCHANT_ID,
            Entity::CARD_ID,
            Entity::WALLET,
            Card\Entity::IIN,
            Card\Entity::LAST4,
            Entity::INTERNATIONAL,
            Entity::TOKEN_ID,
            Entity::GLOBAL_TOKEN_ID,
            Entity::SAVE,
            Entity::LATE_AUTHORIZED,
            Entity::AMOUNT,
            Entity::TERMINAL_ID,
            Token\Entity::RECURRING_STATUS,
            Entity::VPA,
            Terminal\Entity::GATEWAY_TERMINAL_ID,
        ],
    ];

    const ADMIN_RESTRICTED_ACCESSES = [
        Entity::STATUS,
        Entity::ORDER_ID,
        Entity::ACQUIRER_DATA,
        Terminal\Entity::GATEWAY_TERMINAL_ID,
    ];

    const ES_FIELDS = [
        Entity::NOTES,
        Entity::RECURRING,
        Entity::VA_TRANSACTION_ID,
        EsRepository::QUERY,
    ];

    const SIGNED_IDS = [
        Entity::ORDER_ID,
        Entity::INVOICE_ID,
        Entity::SUBSCRIPTION_ID,
        Entity::CUSTOMER_ID,
        Entity::PAYMENT_LINK_ID,
        Entity::TRANSFER_ID,
        Entity::BATCH_ID,
        Entity::VIRTUAL_ACCOUNT_ID,
    ];

    const COMMON_FIELDS = [
        Entity::RECURRING,
        Entity::TRANSFERRED,
    ];

    protected function validateCustomerId($attribute, $value)
    {
        $merchant = $this->auth->getMerchant();

        if ((empty($merchant) === false) and
            (Merchant\Entity::hascustomerTransactionHistoryEnabled($merchant->getId()) === false))
        {
            throw new Exception\ExtraFieldsException($attribute);
        }
    }

    /**
     * This validates the expand route to allow transfer and settlement expand only for linked account merchants
     * @param $attribute
     * @param $value
     *
     * @throws Exception\ExtraFieldsException
     */
    protected function validateExpand($attribute, $value)
    {
        $merchant = $this->auth->getMerchant();

        if (((empty($merchant) === true) or ($merchant->isLinkedAccount() === false)) and
            (($value === 'transfer') or ($value === 'transfer.settlement')))
        {
            throw new Exception\ExtraFieldsException("expand=transfer");
        }
    }

    protected function validateWallet($attribute, $value)
    {
        Processor\Wallet::validateExists($value);
    }
}
