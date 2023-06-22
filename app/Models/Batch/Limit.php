<?php

namespace RZP\Models\Batch;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Limit
{
    const DEFAULT_LIMIT = 1000;

    const DEFAULT_PAYOUT_LIMIT = 50000;

    /**
     * The keys need to be like <type>_<sub-type>_<gateway>
     * Above is subject to those value not being empty
     */
    const HEADER_MAP = [
        Type::REFUND                                => 5000,
        Type::PAYMENT_LINK                          => 500100,
        Type::INSTANT_ACTIVATION                    => 50001,
        Type::ENTITY_UPDATE_ACTION                  => 50000,
        Type::IRCTC_REFUND                          => 100000,
        Type::IRCTC_DELTA_REFUND                    => 100000,
        Type::IRCTC_SETTLEMENT                      => 100000,
        Type::VIRTUAL_BANK_ACCOUNT                  => 50000,
        Type::BANK_TRANSFER                         => 3000,
        'emandate_register_hdfc'                    => 50000,
        'emandate_register_enach_rbl'               => 10000,
        'emandate_register_enach_npci_netbanking'   => 10000,
        'emandate_register_sbi'                     => 10000,
        'nach_register_nach_citi'                   => 25000,
        'emandate_debit_hdfc'                       => 200000,
        'emandate_debit_axis'                       => 50000,
        'emandate_debit_enach_rbl'                  => 200000,
        'emandate_debit_enach_npci_netbanking'      => 200000,
        'emandate_debit_enach_nb_icici'             => 100000,
        'emandate_debit_sbi'                        => 200000,
        Type::BANKING_ACCOUNT_ACTIVATION_COMMENTS   => 10000,
        Type::ICICI_STP_MIS                         => 10000,
        Type::ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS=> 10000,
        Type::RBL_BULK_UPLOAD_COMMENTS              => 10000,
        Type::ELFIN                                 => 5000,
        Type::PARTNER_SUBMERCHANTS                  => 5000,
        Type::ENTITY_MAPPING                        => 10000,
        Type::RECURRING_CHARGE_BSE                  => 50000,
        Type::AUTH_LINK                             => 500100,
        Type::RECURRING_CHARGE                      => 500100,
        Type::RECURRING_CHARGE_BULK                 => 500100,
        Type::RECURRING_CHARGE_AXIS                 => 500100,
        Type::SUB_MERCHANT                          => 5000,
        Type::MERCHANT_UPLOAD_MIQ                   => 1000,
        Type::SUBMERCHANT_ASSIGN                    => 50000,
        TYPE::IIN_NPCI_RUPAY                        => 50000,
        TYPE::IIN_HITACHI_VISA                      => 500000,
        TYPE::IIN_MC_MASTERCARD                     => 500000,
        TYPE::PRICING_RULE                          => 50000,
        Type::ADMIN_BATCH                           => 5000,
        Type::PAYMENT_LINK_V2                       => 500100,
        Type::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG   => 20000,
        Type::ECOLLECT_ICICI                        => 100000,
        'nach_debit_nach_citi'                      => 100000,
        'nach_debit_nach_icici'                     => 100000,
        Type::ECOLLECT_RBL                          => 100000,
        Type::ECOLLECT_YESBANK                      => 100000,
        Type::BANK_TRANSFER_EDIT                    => 10000,
        Type::MERCHANT_STATUS_ACTION                => 50000,
        Type::FUND_ACCOUNT                          => 50000,
        Type::MPAN                                  => 100000,
        Type::TERMINAL_CREATION                     => 5000,
        Type::MERCHANT_ACTIVATION                   => 5000,
        Type::CAPTURE_SETTING                       => 1100000,
        Type::PARTNER_SUBMERCHANT_INVITE            => 200,
        TYPE::PARTNER_SUBMERCHANT_INVITE_CAPITAL    => 200,
        TYPE::PARTNER_SUBMERCHANT_REFERRAL_INVITE   => 200,
        Type::SUBMERCHANT_LINK                      => 50000,
        Type::SUBMERCHANT_DELINK                    => 50000,
        Type::SUBMERCHANT_PARTNER_CONFIG_UPSERT     => 5000,
        Type::SUBMERCHANT_TYPE_UPDATE               => 50000,
        Type::NACH_MIGRATION                        => 500100,
        Type::LINKED_ACCOUNT_CREATE                 => 50000,
        Type::PAYMENT_TRANSFER                      => 50000,
        Type::TRANSFER_REVERSAL                     => 50000,
        Type::PAYMENT_TRANSFER_RETRY                => 10000,
        Type::PAYOUT_LINK_BULK                      => 50000,
        Type::PAYOUT_LINK_BULK_V2                   => 50000,
        Type::SETTLEMENT_ONDEMAND_FEATURE_CONFIG    => 50000,
        Type::BUY_PRICING_RULE                      => 100000,
        Type::BUY_PRICING_ASSIGN                    => 100000,
        Type::LEDGER_ONBOARD_OLD_ACCOUNT            => 5000,
        Type::VIRTUAL_ACCOUNT_EDIT                  => 10000,
        Type::EZETAP_SETTLEMENT                     => 10000,
        Type::PARTNER_REFERRAL_FETCH                => 150,
        Type::COLLECT_LOCAL_CONSENTS_TO_CREATE_TOKENS => 1000000,
        Type::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST => 1000000,
        Type::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST => 1000000,
        Type::VAULT_MIGRATE_TOKEN_NS                     => 1000000,
        Type::TOKEN_HQ_CHARGE                            => 1000000,
        Type::PAYMENT_PAGE                          => 10000
    ];

    /**
     * Validates that given total count lies between set limits
     * per type of batch.
     *
     * @param string $type
     * @param int    $total
     *
     * @throws BadRequestException
     */
    public static function validate(string $type, int $total)
    {
        // We have shifted the payout limit validations downstream so that we can have merchant level configurations.
        if ($type === Type::PAYOUT || $type === Type::PAYOUT_APPROVAL || $type === Type::TALLY_PAYOUT || $type === Type::RAW_ADDRESS || $type === Type::FULFILLMENT_ORDER_UPDATE)
        {
            return;
        }

        if ($total === 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_EMPTY,
                null,
                [
                    'type'  => $type,
                    'total' => $total,
                ]);
        }

        $limit = self::HEADER_MAP[$type] ?? self::DEFAULT_LIMIT;

        if ($total > $limit)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_EXCEED_LIMIT,
                null,
                [
                    'type'  => $type,
                    'total' => $total,
                ]);
        }
    }
}
