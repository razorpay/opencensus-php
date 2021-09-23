<?php

namespace RZP\Constants;

use RZP\Base\Fetch;
use RZP\Models\Payout;
use RZP\Models\Dispute;
use RZP\Models\External;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Emi\Type;
use RZP\Trace\TraceCode;
use RZP\Gateway\Billdesk;
use RZP\Models\FeeRecovery;
use RZP\Models\FundTransfer;
use RZP\Models\BankingAccount;
use RZP\Models\Partner\Config;
use RZP\Models\Admin\Validator;
use RZP\Models\NodalBeneficiary;
use RZP\Gateway\Upi\Base as Upi;
use RZP\Models\Merchant\Product;
use RZP\Models\Partner\Activation;
use RZP\Models\Settlement\Channel;
use RZP\Models\Partner\Commission;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\MerchantUser;
use RZP\Reconciliator\RequestProcessor;
use RZP\Models\Merchant\Invoice\EInvoice;
use RZP\Models\Partner\Commission\Invoice;
use RZP\Models\Partner\Commission\Component;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Services\FTS\Constants as FtsConstants;
use RZP\Models\P2p\Transaction\Status as TransactionStatus;
use RZP\Models\P2p\Transaction\Concern\Status as ConcernStatus;

/**
 * Class AdminFetch
 *
 * Its a temporary class to server Fetch Entities API.
 * We will be removing this by overriding each entity one by one.
 * Class only serves fields, validation is handled by Repository.
 */
class AdminFetch
{
    /**
     * Entities allowed to restricted orgs' admins
     *
     * @var array
     */
    public static $restrictedEntities = [
        Entity::PAYMENT,
    ];

    const EXTERNAL_ADMIN_FETCH_MULTIPLE_ENTITIES_MAX_COUNT = 5;

    public static $externalAdminEntityAllowedAttributesMap = [
        Entity::PAYMENT => [
            Payment\Entity::ID,
            Payment\Entity::MERCHANT_ID,
            Payment\Entity::AMOUNT,
            Payment\Entity::BASE_AMOUNT,
            Payment\Entity::METHOD,
            Payment\Entity::STATUS,
            Payment\Entity::CREATED_AT,
            Payment\Entity::UPDATED_AT,
            Payment\Entity::REFUNDS,
            Payment\Entity::GATEWAY,
            Payment\Entity::GATEWAY_CAPTURED,
            Payment\Entity::CAPTURED_AT,
            Payment\Entity::AUTHORIZED_AT,
            Payment\Entity::LATE_AUTHORIZED,
        ],
        Entity::REFUND  => [
            Payment\Refund\Entity::ID,
            Payment\Refund\Entity::PAYMENT_ID,
            Payment\Refund\Entity::MERCHANT_ID,
            Payment\Refund\Entity::AMOUNT,
            Payment\Refund\Entity::BASE_AMOUNT,
            Payment\Refund\Entity::GATEWAY,
            Payment\Refund\Entity::REFERENCE1,
            Payment\Refund\Entity::LAST_ATTEMPTED_AT,
            Payment\Refund\Entity::PROCESSED_AT,
            Payment\Refund\Entity::CREATED_AT,
            Payment\Refund\Entity::UPDATED_AT,
            Payment\Refund\Entity::STATUS,
            Payment\Refund\Entity::SPEED_PROCESSED,
            Payment\Refund\Entity::SPEED_REQUESTED,
            Payment\Refund\Entity::GATEWAY_REFUNDED,
        ],
        Entity::UPI     => [
            Upi\Entity::ID,
            Upi\Entity::PAYMENT_ID,
            Upi\Entity::MERCHANT_ID,
            Upi\Entity::AMOUNT,
            Upi\Entity::CREATED_AT,
            Upi\Entity::UPDATED_AT,
            Upi\Entity::GATEWAY,
        ],
        Entity::DISPUTE => [
            Dispute\Entity::ID,
            Dispute\Entity::PAYMENT_ID,
            Dispute\Entity::MERCHANT_ID,
            Dispute\Entity::AMOUNT,
            Dispute\Entity::BASE_AMOUNT,
            Dispute\Entity::GATEWAY_DISPUTE_ID,
            Dispute\Entity::STATUS,
            Dispute\Entity::CREATED_AT,
            Dispute\Entity::UPDATED_AT,
        ],
        Entity::NETBANKING => [
            \RZP\Gateway\Netbanking\Base\Entity::PAYMENT_ID,
            \RZP\Gateway\Netbanking\Base\Entity::AMOUNT,
            \RZP\Gateway\Netbanking\Base\Entity::BANK,
            \RZP\Gateway\Netbanking\Base\Entity::STATUS,
            \RZP\Gateway\Netbanking\Base\Entity::CREATED_AT,
            \RZP\Gateway\Netbanking\Base\Entity::UPDATED_AT,
            \RZP\Gateway\Netbanking\Base\Entity::REFUND_ID,
        ],
        Entity::BANK_TRANSFER => [
            \RZP\Models\BankTransfer\Entity::ID,
            \RZP\Models\BankTransfer\Entity::PAYMENT_ID,
            \RZP\Models\BankTransfer\Entity::MERCHANT_ID,
            \RZP\Models\BankTransfer\Entity::UTR,
            \RZP\Models\BankTransfer\Entity::AMOUNT,
            \RZP\Models\BankTransfer\Entity::CREATED_AT,
            \RZP\Models\BankTransfer\Entity::UPDATED_AT,

        ],
        Entity::BILLDESK => [
            Billdesk\Entity::ID,
            Billdesk\Entity::PAYMENT_ID,
            'MerchantID',
            'CustomerID',
            'TxnAmount',
            'CurrencyType',
            Billdesk\Entity::CREATED_AT,
            Billdesk\Entity::UPDATED_AT,
        ],
        Entity::MERCHANT => [
            Merchant\Entity::ID,
            Merchant\Entity::NAME,
            Merchant\Entity::WEBSITE,
            Merchant\Entity::BILLING_LABEL,
            Merchant\Entity::LIVE,
            Merchant\Entity::HOLD_FUNDS,
            Merchant\Entity::AUTO_REFUND_DELAY,
            Merchant\Entity::BALANCE,
            Merchant\Entity::FEE_CREDITS_THRESHOLD,
            Merchant\Entity::ACCOUNT_STATUS,
            Merchant\Entity::TRANSACTION_REPORT_EMAIL,
        ],
        Entity::ATOM => [
            \RZP\Gateway\Atom\Entity::ID,
            \RZP\Gateway\Atom\Entity::PAYMENT_ID,
            \RZP\Gateway\Atom\Entity::REFUND_ID,
            \RZP\Gateway\Atom\Entity::BANK_PAYMENT_ID,
            \RZP\Gateway\Atom\Entity::GATEWAY_PAYMENT_ID,
            \RZP\Gateway\Atom\Entity::AMOUNT,
            \RZP\Gateway\Atom\Entity::STATUS,
            \RZP\Gateway\Atom\Entity::METHOD,
            \RZP\Gateway\Atom\Entity::CREATED_AT,
            \RZP\Gateway\Atom\Entity::UPDATED_AT,
        ],
        Entity::MERCHANT_DETAIL => [
            Merchant\Detail\Entity::MERCHANT_ID,
            Merchant\Detail\Entity::ACTIVATION_PROGRESS,
            Merchant\Detail\Entity::ACTIVATION_STATUS,
        ],
        Entity::BALANCE => [
             Merchant\Balance\Entity::ID,
             Merchant\Balance\Entity::MERCHANT_ID,
             Merchant\Balance\Entity::TYPE,
             Merchant\Balance\Entity::CURRENCY,
             Merchant\Balance\Entity::NAME,
             Merchant\Balance\Entity::BALANCE,
             Merchant\Balance\Entity::LOCKED_BALANCE,
             Merchant\Balance\Entity::ON_HOLD,
             Merchant\Balance\Entity::AMOUNT_CREDITS,
             Merchant\Balance\Entity::FEE_CREDITS,
             Merchant\Balance\Entity::REWARD_FEE_CREDITS,
             Merchant\Balance\Entity::CREATED_AT,
             Merchant\Balance\Entity::UPDATED_AT,
        ],
        Entity::CREDITS => [
            Merchant\Credits\Entity::ID,
            Merchant\Credits\Entity::MERCHANT_ID,
            Merchant\Credits\Entity::VALUE,
            Merchant\Credits\Entity::TYPE,
            Merchant\Credits\Entity::EXPIRED_AT,
            Merchant\Credits\Entity::USED,
            Merchant\Credits\Entity::BALANCE_ID,
        ],
    ];

    public static function fields()
    {
        return Fetch::getCommonFields();
    }

    public static function filterEntitiesForExternalAdmin($entities)
    {
        $result = [];

        foreach ($entities as $entityType => $searchFilters)
        {
            if (in_array($entityType, array_keys(self::$externalAdminEntityAllowedAttributesMap), true) === false)
            {
                continue;
            }

            $result[$entityType] = self::filterSearchFiltersForExternalAdmin($entityType, $searchFilters);
        }

        return $result;
    }

    public static function filterAttributesForExternalAdminFetchEntityById(string $entityType, array $entity)
    {
        $allowedAttributes = self::$externalAdminEntityAllowedAttributesMap[$entityType];

        return array_filter($entity, function ($attribute) use ($entity, $allowedAttributes)
        {
            if (in_array($attribute, $allowedAttributes, true) === true)
            {
                return true;
            }

            return false;

        }, ARRAY_FILTER_USE_KEY);
    }

    public static function filterAttributesForExternalAdminFetchMultiple($entityType, $response)
    {
        $response[PublicCollection::ITEMS] = array_map(function ($entity) use ($entityType) {
            return self::filterAttributesForExternalAdminFetchEntityById($entityType, $entity);
        }, $response[PublicCollection::ITEMS] ?? []);

        return $response;
    }

    public static function externalEntities()
    {
        return [
            Entity::UFH_FILES => [
                'status'          => [
                    Fetch::LABEL        => 'status',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'created',
                        'failed',
                        'uploaded',
                    ],
                ],
                'type'            => [
                    Fetch::LABEL        => 'type',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'entity_id'       => [
                    Fetch::LABEL        => 'entity_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'merchant_id'           => [
                    Fetch::LABEL        => 'merchant_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'entity_type'     => [
                    Fetch::LABEL        => 'entity_type',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'bucket_config_name' => [
                    Fetch::LABEL        => 'bucket_config_name',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
            ],
            Entity::REPORTING_LOGS => [
                'consumer'         => Fetch::FIELD_MERCHANT_ID,
                'config_id'        => [
                    Fetch::LABEL        => 'Config Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::REPORTING_CONFIGS => [
                'consumer'          => Fetch::FIELD_MERCHANT_ID,
                'report_type'       => [
                    Fetch::LABEL        => 'Report Type',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::REPORTING_SCHEDULES => [
                'consumer'          => Fetch::FIELD_MERCHANT_ID,
                'config_id'        => [
                    Fetch::LABEL        => 'Config Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::AUTH_SERVICE_APPLICATIONS => [
                'merchant_id'           => Fetch::FIELD_MERCHANT_ID,
                'type' => [
                    Fetch::LABEL        => 'Type',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'partner',
                    ],
                ],
            ],
            Entity::AUTH_SERVICE_CLIENTS => [
                'merchant_id'           => Fetch::FIELD_MERCHANT_ID,
                'application_id'        => [
                    Fetch::LABEL        => 'Application Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::AUTH_SERVICE_TOKENS => [
                'merchant_id'           => Fetch::FIELD_MERCHANT_ID,
                'client_id'        => [
                    Fetch::LABEL        => 'Client Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::AUTH_SERVICE_REFRESH_TOKENS => [
                'token_id'        => [
                    Fetch::LABEL        => 'Token Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::SHIELD_RULES => [
                'is_active'         => [
                    Fetch::LABEL        => 'active',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        '0',
                        '1',
                    ],
                ],
                'action'            => [
                    Fetch::LABEL        => 'action',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'block',
                        'review',
                        'allow'
                    ]
                ],
                'ruleset'           => [
                    Fetch::LABEL        => 'ruleset',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'merchant_id'       => [
                    Fetch::LABEL        => 'merchant_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
            ],
            Entity::SHIELD_RULE_ANALYTICS => [
                'entity_id'         => [
                    Fetch::LABEL        => 'entity_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'email'             => [
                    Fetch::LABEL        => 'email',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'contact'           => [
                    Fetch::LABEL        => 'contact',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'card_iin'          => [
                    Fetch::LABEL        => 'card_iin',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'merchant_id'       => [
                    Fetch::LABEL        => 'merchant_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'action'            => [
                    Fetch::LABEL        => 'action',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'block',
                        'review',
                        'allow'
                    ]
                ],
                'ruleset'           => [
                    Fetch::LABEL        => 'ruleset',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'ip'                => [
                    Fetch::LABEL        => 'ip',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'card_hash'         => [
                    Fetch::LABEL        => 'card_hash',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'triggered_count'   => [
                    Fetch::LABEL        => 'triggered_count',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'rule_id'           => [
                    Fetch::LABEL        => 'rule_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ]
            ],
            Entity::SHIELD_LISTS => [
                'reference'         => [
                    Fetch::LABEL        => 'reference',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'type'            => [
                    Fetch::LABEL        => 'type',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'string',
                        'contact',
                        'email',
                        'country',
                        'iin',
                        'domain',
                        'ip'
                    ]
                ],
                'merchant_id'       => [
                    Fetch::LABEL        => 'merchant_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
            ],
            Entity::SHIELD_LIST_ITEMS => [
                'list_id'         => [
                    Fetch::LABEL        => 'list_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'reference'         => [
                    Fetch::LABEL        => 'reference',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'type'            => [
                    Fetch::LABEL        => 'type',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'string',
                        'contact',
                        'email',
                        'country',
                        'iin',
                        'domain',
                        'ip'
                    ]
                ],
                'value'         => [
                    Fetch::LABEL        => 'value',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'merchant_id'       => [
                    Fetch::LABEL        => 'merchant_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
            ],
            Entity::SHIELD_RISKS => [
                'entity_id'         => [
                    Fetch::LABEL        => 'entity_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'entity_type'       => [
                    Fetch::LABEL        => 'entity_type',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'merchant_id'       => [
                    Fetch::LABEL        => 'merchant_id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'action'            => [
                    Fetch::LABEL        => 'action',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'block',
                        'review',
                        'allow'
                    ]
                ],
            ],
            Entity::SHIELD_RISK_LOGS => [
                'payment_id'    => [
                    Fetch::LABEL        => 'Payment Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING
                ],
                'vendor'        => [
                    Fetch::LABEL        => 'Vendor',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'sift',
                        'maxmind'
                    ]
                ],
                'vendor_mode' => [
                    Fetch::LABEL        => 'Vendor Mode',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        'live',
                        'shadow'
                    ]
                ],
            ],
            Entity::BATCH_SERVICE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'processing',
                        'failed',
                        'completed',
                    ],
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'payment_link',
                        'reconciliation',
                        'merchant_status_action',
                        'admin_batch',
                        'merchant_activation',
                        'submerchant_link',
                        'submerchant_type_update',
                        'submerchant_delink',
                        'banking_account_activation_comments',
                        'icici_lead_account_activation_comments',
                        'partner_submerchant_invite',
                        'nach_debit_nach_citi',
                        'website_checker',
                        'emandate_debit_hdfc',
                    ],
                ],
            ],
            Entity::BATCH_FILE_STORE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'batch_id'    => [
                    Fetch::LABEL  => 'Batch Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::PAYMENTS_NBPLUS_NETBANKING => [
                'payment_id'   => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::PAYMENTS_NBPLUS_CARDLESS_EMI_GATEWAY => [
                'payment_id'   => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::PAYMENTS_NBPLUS_APP_GATEWAY => [
                'payment_id'   => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::NBPLUS_EMANDATE_REGISTRATION => [],

            Entity::NBPLUS_EMANDATE_DEBIT => [],

            Entity::CAPITAL_COLLECTIONS_PLAN => [
                'merchant_id'   => Fetch::FIELD_MERCHANT_ID,
                'credit_id'     => [
                    Fetch::LABEL  => 'Credit Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'product_type'  => [
                    Fetch::LABEL  => 'Product type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'loc',
                        'cards',
                        'loans',
                    ],
                ],
                'product_entity_reference_id'   => [
                    Fetch::LABEL  => 'Product entity reference id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'product_entity_type'   => [
                    Fetch::LABEL  => 'Product entity type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'withdrawal',
                        'disbursement',
                        'statement',
                    ],
                ],
                'status'    => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'completed',
                        'archived',
                    ],
                ],
            ],

            Entity::CAPITAL_COLLECTIONS_LEDGER_BALANCE => [
                'plan_id'   => [
                    Fetch::LABEL  => 'plan id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CAPITAL_COLLECTIONS_INSTALLMENT => [
                'merchant_id'   => Fetch::FIELD_MERCHANT_ID,

                'plan_id'   => [
                    Fetch::LABEL  => 'plan id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status'    => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'pending',
                        'partially_paid',
                        'paid',
                        'overdue',
                        'archived',
                    ],
                ],
            ],

            Entity::CAPITAL_COLLECTIONS_REPAYMENT => [
                'merchant_id'   => Fetch::FIELD_MERCHANT_ID,
                'credit_id'     => [
                    Fetch::LABEL  => 'Credit Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'product_type'  => [
                    Fetch::LABEL  => 'Product type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'loc',
                        'cards',
                        'loans',
                    ],
                ],
                'product_entity_reference_id'   => [
                    Fetch::LABEL  => 'Product entity reference id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'product_entity_type'   => [
                    Fetch::LABEL  => 'Product entity type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'withdrawal',
                        'disbursement',
                        'statement',
                    ],
                ],
                'status'    => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'pending',
                        'failed',
                        'collected',
                        'settled',
                    ],
                ],
                'payment_mode' => [
                    Fetch::LABEL  => 'payment mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'manual',
                        'autocollection',
                    ],
                ],
                'payment_reference_id' => [
                    Fetch::LABEL  => 'payment reference id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_reference_type' => [
                    Fetch::LABEL  => 'payment reference type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'order',
                        'credit_repayment',
                        'payment_link',
                        'manual_adjustment',
                    ],
                ],
            ],

            Entity::CAPITAL_COLLECTIONS_REPAYMENT_BREAKUP => [
                'plan_id'   => [
                    Fetch::LABEL  => 'plan id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'repayment_id'   => [
                    Fetch::LABEL  => 'repayment id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'breakup_entity_id'   => [
                    Fetch::LABEL  => 'breakup entity id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'breakup_entity_type'    => [
                    Fetch::LABEL  => 'breakup entity type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'installment',
                        'charge',
                    ],
                ],
            ],

            Entity::CAPITAL_COLLECTIONS_CREDIT_REPAYMENT => [
                'merchant_id'   => Fetch::FIELD_MERCHANT_ID,
                'transaction_id'   => [
                    Fetch::LABEL  => 'transaction id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status'    => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'pending',
                        'collected',
                        'failed',
                    ],
                ],
            ],
            Entity::LINE_OF_CREDIT_ACCOUNT_BALANCES => [
                'id'                            => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'owner_id'                      => [
                    Fetch::LABEL => 'OwnerId',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'owner_type'           => [
                    Fetch::LABEL => 'OwnerType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'RZP_MERCHANT',
                        'LOS_APPLICANT',
                    ],
                ],
            ],
            Entity::LINE_OF_CREDIT_ONBOARDINGS => [
                'id'                  => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'owner_id'            => [
                    Fetch::LABEL => 'OwnerId',
                        Fetch::TYPE  => Fetch::TYPE_STRING,
                    ],
                'owner_type'           => [
                    Fetch::LABEL => 'OwnerType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'RZP_MERCHANT',
                        'LOS_APPLICANT',
                    ]
                ],
                'external_ref_id'     => [
                    Fetch::LABEL => 'ExternalRefId',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                    ],
                'partner_id'          => [
                    Fetch::LABEL => 'PartnerId',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                    ],
            ],
            Entity::LINE_OF_CREDIT_REPAYMENTS => [
                'id' => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'withdrawal_id' => [
                    Fetch::LABEL => 'WithdrawalId',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                    ],
                'transaction_ref_id' => [
                    Fetch::LABEL => 'TransactionRefId',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],
            Entity::LINE_OF_CREDIT_WITHDRAWAL_CONFIGS => [
                'id' => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'destination_account_id' => [
                    Fetch::LABEL => 'DestinationAccountId',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                    ],
                'source_account_id' => [
                    Fetch::LABEL => 'SourceAccountId',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                    ],
                'owner_id' => [
                    Fetch::LABEL => 'OwnerId',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                    ],
                'owner_type'           => [
                    Fetch::LABEL => 'OwnerType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'RZP_MERCHANT',
                        'LOS_APPLICANT',
                    ],
                ],
                'automated_loc' => [
                    Fetch::LABEL => 'AutomatedLOC',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                    ],
                'status'               => [
                    Fetch::LABEL => 'Status',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'ACTIVE',
                        'ARCHIVE',
                        'ONHOLD',
                        ],
                    ],
            ],
            Entity::LINE_OF_CREDIT_DESTINATION_ACCOUNTS => [
                'id'                   => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'owner_id'             => [
                    Fetch::LABEL => 'OwnerId',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'owner_type'           => [
                    Fetch::LABEL => 'OwnerType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'RZP_MERCHANT',
                        'LOS_APPLICANT',
                    ]
                ],
                'account_type'         => [
                    Fetch::LABEL => 'AccountType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'SAVINGS',
                        'CURRENT',
                        'OD',
                    ],
                ],
                'ifsc_code'            => [
                    Fetch::LABEL => 'IfscCode',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'status'               => [
                    Fetch::LABEL => 'Status',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'ACTIVE',
                        'INACTIVE',
                        'CLOSED',
                    ],
                ],
            ],
            Entity::LINE_OF_CREDIT_REPAYMENT_BREAKDOWNS => [
                'id'           => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'repayment_id' => [
                    Fetch::LABEL => 'RepaymentId',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'category'     => [
                    Fetch::LABEL => 'Category',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'INTEREST',
                        'PRINCIPAL',
                        'PROCESSING_FEE',
                        'LATE_FEE_CHARGES',
                    ],
                ],
            ],
            Entity::LINE_OF_CREDIT_SOURCE_ACCOUNTS => [
                'id'                   => [
                    Fetch::LABEL => 'Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'owner_id'             => [
                    Fetch::LABEL => 'OwnerId',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'owner_type'           => [
                    Fetch::LABEL => 'OwnerType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'RZP_MERCHANT',
                        'LOS_APPLICANT',
                    ],
                ],
                'account_type'         => [
                    Fetch::LABEL => 'AccountType',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'SAVINGS',
                        'CURRENT',
                        'OD',
                    ],
                ],
                'ifsc_code'            => [
                    Fetch::LABEL => 'IfscCode',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'status'               => [
                    Fetch::LABEL => 'Status',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'ACTIVE',
                        'INACTIVE',
                        'CLOSED',
                    ],
                ],
            ],
            Entity::LINE_OF_CREDIT_WITHDRAWALS => [
                'id'                   => [
                        Fetch::LABEL => 'Id',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'transaction_ref_id'   => [
                        Fetch::LABEL => 'TransactionRefId',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'withdrawal_config_id' => [
                        Fetch::LABEL => 'WithdrawalConfigId',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'owner_id'             => [
                        Fetch::LABEL => 'OwnerId',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'owner_type'           => [
                        Fetch::LABEL => 'OwnerType',
                        Fetch::TYPE => Fetch::TYPE_ARRAY,
                        Fetch::VALUES => [
                            'RZP_MERCHANT',
                            'LOS_APPLICANT',
                        ],
                ],
                'status'               => [
                        Fetch::LABEL => 'Status',
                        Fetch::TYPE => Fetch::TYPE_ARRAY,
                        Fetch::VALUES => [
                            'CREATED',
                            'INITIATED',
                            'PENDING',
                            'PROCESSED',
                            'REJECTED',
                            'FAILED',
                            'REPAID',
                            'PARTIALLY_REPAID',
                        ],
                ],
                'disbursal_utr'        => [
                        Fetch::LABEL => 'DisbursalUtr',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'partner_utr'          => [
                        Fetch::LABEL => 'PartnerUtr',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'plan_id'              => [
                        Fetch::LABEL => 'PlanId',
                        Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_APPLICATION_CREDIT_POLICY_MAPPINGS => [
                'application_id'              => [
                    Fetch::LABEL => 'application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'credit_policy_id'              => [
                    Fetch::LABEL => 'credit policy id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],

            ],
            Entity::CAPITAL_LOS_APPLICATIONS => [
                'owner_id'              => [
                    Fetch::LABEL => 'owner id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'status'               => [
                    Fetch::LABEL  => 'status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'CREATED',
                        'CREDIT_PULL_PENDING',
                        'OFFLINE_DOCUMENT_COLLECTION_PENDING',
                        'PREVERIFICATION_UPLOAD_PENDING',
                        'PREVERIFICATION_IN_PROGRESS',
                        'PREVERIFICATION_FAILED',
                        'SCORE_GENERATION_PENDING',
                        'CREDIT_OFFER_PENDING',
                        'CREDIT_OFFER_GENERATED',
                        'CONTRACT_PENDING',
                        'NACH_CREATION_PENDING',
                        'NACH_UPLOAD_PENDING',
                        'SLOT_SELECTION_PENDING',
                        'DOCUMENT_COLLECTION_INITIATED',
                        'DOCUMENT_COLLECTION_FAILED',
                        'DOCUMENTS_UNDER_REVIEW',
                        'RZP_APPROVED',
                        'CREDIT_DISBURSED',
                        'CLOSED',
                        'RZP_REJECTED',
                        'LOC_OFFER_GENERATED',
                        'LOC_OFFER_PENDING',
                    ],
                ],
            ],
            Entity::CAPITAL_LOS_BUSINESS_APPLICANTS => [],
            Entity::CAPITAL_LOS_BUSINESSES => [
                'reference_id'              => [
                    Fetch::LABEL => 'reference id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_CARD_OFFERS => [
                'application_id'              => [
                    Fetch::LABEL => 'application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_CONTRACTS => [
                'application_id'              => [
                    Fetch::LABEL => 'application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_CREDIT_OFFERS => [
                'application_id'              => [
                    Fetch::LABEL => 'application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_CREDIT_POLICIES => [],
            Entity::CAPITAL_LOS_D2C_BUREAU_REPORTS => [
                'merchant_id'                   => [
                Fetch::LABEL => 'merchant id',
                Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_DISBURSALS => [
                'application_id'              => [
                    Fetch::LABEL => 'application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_DOC_SIGN_FILES => [],
            Entity::CAPITAL_LOS_DOCUMENT_GROUPS => [],
            Entity::CAPITAL_LOS_DOCUMENT_MASTERS => [],
            Entity::CAPITAL_LOS_DOCUMENT_MASTERS_GROUPS => [],
            Entity::CAPITAL_LOS_DOCUMENT_SIGNS => [],
            Entity::CAPITAL_LOS_DOCUMENTS => [
                'application_id'              => [
                    Fetch::LABEL => 'application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_LEAD_TYPES => [],
            Entity::CAPITAL_LOS_LENDERS => [],
            Entity::CAPITAL_LOS_LOC_OFFERS => [],
            Entity::CAPITAL_LOS_NACH_APPLICATIONS => [
                'loan_application_id'              => [
                    Fetch::LABEL => 'loan application id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::CAPITAL_LOS_NACH_MANDATES => [],
            Entity::CAPITAL_LOS_OFFER_VERIFICATION_TASKS => [],
            Entity::CAPITAL_LOS_PRODUCT_LENDERS => [],
            Entity::CAPITAL_LOS_PRODUCTS => [],
            Entity::CAPITAL_LOS_SIGN_INVITEES => [],
            Entity::CAPITAL_LOS_VENDORS => [],
            Entity::PAYMENTS_CARDS_AUTHORIZATION  => [
                'payment_id'   => Fetch::FIELD_PAYMENT_ID,
                'merchant_id'  => Fetch::FIELD_MERCHANT_ID,
                'gateway'      => Fetch::FIELD_GATEWAY,
                'status'       => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'authorized',
                        'captured',
                    ],
                ],
            ],
            Entity::PAYMENTS_CARDS_AUTHENTICATION => [
                'payment_id'   => Fetch::FIELD_PAYMENT_ID,
                'merchant_id'  => Fetch::FIELD_MERCHANT_ID,
                'gateway'      => Fetch::FIELD_GATEWAY,
                'status'       => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'success',
                        'failed',
                    ],
                ],
            ],

            Entity::PAYMENTS_CARDS_CAPTURE  => [
                'payment_id'   => Fetch::FIELD_PAYMENT_ID,
                'merchant_id'  => Fetch::FIELD_MERCHANT_ID,
                'gateway'      => Fetch::FIELD_GATEWAY,
                'status'       => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'authorized',
                        'captured',
                    ],
                ],
            ],

            Entity::SUBSCRIPTIONS_SUBSCRIPTION => [
                'auth_attempts' => [
                    Fetch::LABEL  => 'Auth Attempts',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_email' => [
                    Fetch::LABEL  => 'Customer Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'error_status' => [
                    Fetch::LABEL  => 'Error Status',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'notes' => Fetch::FIELD_NOTES,
                'plan_id' => [
                    Fetch::LABEL  => 'Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'schedule_id' => [
                    Fetch::LABEL  => 'Schedule Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'authenticated',
                        'active',
                        'pending',
                        'halted',
                        'cancelled',
                        'completed',
                        'expired'
                    ]
                ],
                'token_id' => [
                    Fetch::LABEL  => 'Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::SUBSCRIPTIONS_PLAN => [
                'interval' => [
                    Fetch::LABEL  => 'Interval',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'item_id' => [
                    Fetch::LABEL  => 'Item Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'period' => [
                    Fetch::LABEL  => 'Period',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],
            // Service: Stork
            Entity::STORK_WEBHOOK => [
                'owner_id' => [
                    Fetch::LABEL => 'Owner id',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],
            Entity::FTS_TRANSFERS => [
                'source_type'         => [
                    Fetch::LABEL    => 'Source Type',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'source_id'         => [
                    Fetch::LABEL        => 'Source id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'channel' => [
                    Fetch::LABEL    => 'Channel',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => Channel::getFtsSupportedChannels(),
                ],
                'bank_status_code' => [
                    Fetch::LABEL    => 'Bank Status Code',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL    => 'Status',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FundTransfer\Attempt\Status::STATUSES,
                ],
                'merchant_id'       => [
                    Fetch::LABEL        => 'Merchant Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::FTS_ATTEMPTS     => [
                'transfer_id'       => [
                    Fetch::LABEL        => 'Transfer Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'gateway_ref_no' => [
                    Fetch::LABEL  => 'Gateway Ref No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'channel' => [
                    Fetch::LABEL    => 'Channel',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => Channel::getFtsSupportedChannels(),
                ],
                'status' => [
                    Fetch::LABEL    => 'Status',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FundTransfer\Attempt\Status::STATUSES,
                ],
            ],
            Entity::FTS_FUND_ACCOUNT => [],
            Entity::FTS_BENEFICIARY_STATUS => [
                'fund_account_id' => [
                    Fetch::LABEL    => 'Fund Account Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'source_account_id'       => [
                    Fetch::LABEL        => 'Source Account Id',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                'gateway_ref_no' => [
                    Fetch::LABEL => 'Gateway Ref No',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],
            Entity::FTS_SOURCE_ACCOUNT => [
                'fund_account_id' => [
                    Fetch::LABEL    => 'Fund Account Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'channel' => [
                    Fetch::LABEL    => 'Channel',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => Channel::getFtsSupportedChannels(),
                ],
                'product' => [
                    Fetch::LABEL    => 'Product',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FtsConstants::getProducts(),
                ],
                'is_deleted' => [
                    Fetch::LABEL => 'Deleted',
                    Fetch::TYPE  => Fetch::TYPE_BOOLEAN
                ],
            ],
            Entity::FTS_CHANNEL_HEALTH_EVENTS => [
                'operation' => [
                    Fetch::LABEL => 'Operation',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'channel' => [
                    Fetch::LABEL  => 'Channel',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Channel::getFtsSupportedChannels(),
                ],
                'mozart_identifier' => [
                    Fetch::LABEL =>'Mozart Identifier',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'mode' => [
                    Fetch::LABEL => 'Transfer Mode',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],
            Entity::FTS_SOURCE_ACCOUNT_MAPPING => [
                'source_account_id' => [
                    Fetch::LABEL    => 'Source Account Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'product' => [
                    Fetch::LABEL    => 'Product',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FtsConstants::getProducts(),
                ],
                'merchant_id'       => [
                    Fetch::LABEL    => 'Merchant Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'mode' => [
                    Fetch::LABEL    => 'Transfer Mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => FundTransfer\Mode::getAllFTSModes(),
                ],
            ],
            Entity::FTS_DIRECT_ACCOUNT_ROUTING_RULES => [
                'source_account_id' => [
                    Fetch::LABEL    => 'Source Account Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'product' => [
                    Fetch::LABEL    => 'Product',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FtsConstants::getProducts(),
                ],
                'merchant_id'       => [
                    Fetch::LABEL    => 'Merchant Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'mode' => [
                    Fetch::LABEL    => 'Transfer Mode',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FundTransfer\Mode::getAllFTSModes(),
                ],
            ],
            Entity::FTS_PREFERRED_ROUTING_WEIGHTS => [
                'source_account_id' => [
                    Fetch::LABEL    => 'Source Account Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'product' => [
                    Fetch::LABEL    => 'Product',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FtsConstants::getProducts(),
                ],
                'mode' => [
                    Fetch::LABEL    => 'Transfer Mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => FundTransfer\Mode::getAll(),
                ],
            ],
            Entity::FTS_ACCOUNT_TYPE_MAPPINGS => [
                'merchant_id'       => [
                    Fetch::LABEL    => 'Merchant Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'product' => [
                    Fetch::LABEL    => 'Product',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FtsConstants::getProducts(),
                ],
                'mode' => [
                    Fetch::LABEL    => 'Transfer Mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => FundTransfer\Mode::getAll(),
                ],
                'account_type'      => [
                    Fetch::LABEL    => 'Account Type',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
            ],
        ];
    }

    public static function entities()
    {
        $entities = [
            Entity::ADDON => [
                'deleted' => [
                    Fetch::LABEL    => 'Deleted',
                    Fetch::TYPE     => Fetch::TYPE_BOOLEAN
                ],
                'invoice_id' => [
                    Fetch::LABEL    => 'Invoice Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'merchant_id'       => Fetch::FIELD_MERCHANT_ID,
                'subscription_id'   => Fetch::FIELD_SUBSCRIPTION_ID,
            ],

            Entity::ADJUSTMENT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::AMEX => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::APP_TOKEN => [
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'device_token' => [
                    Fetch::LABEL  => 'Device Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::ATOM => [
                'payment_id'         => Fetch::FIELD_PAYMENT_ID,
                'refund_id'          => Fetch::FIELD_REFUND_ID,
                'bank_payment_id'    => [
                    Fetch::LABEL => 'Bank Payment Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'gateway_payment_id' => [
                    Fetch::LABEL => 'Gateway Payment Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'received'           => [
                    Fetch::LABEL => 'Received',
                    Fetch::TYPE  => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::PAYSECURE => [
                'payment_id'         => Fetch::FIELD_PAYMENT_ID,
                'gateway_transaction_id'    => [
                    Fetch::LABEL => 'Gateway Transaction Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'apprcode' => [
                    Fetch::LABEL => 'Bank reference number',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'flow' => [
                    Fetch::LABEL => 'Payment flow',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'redirect',
                        'iframe',
                    ],
                ],
                'rrn' => [
                    Fetch::LABEL => 'RRN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],

                'received' => [
                    Fetch::LABEL => 'Received',
                    Fetch::TYPE  => Fetch::TYPE_BOOLEAN
                ],
                'status' => [
                    Fetch::LABEL => 'Status',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::AXIS_GENIUS => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::AXIS_MIGS => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'vpc_ReceiptNo' => [
                    Fetch::LABEL  => 'Receipt No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_ShopTransactionNo' => [
                    Fetch::LABEL  => 'Shop Transaction No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_TransactionNo' => [
                    Fetch::LABEL  => 'Transaction No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_TxnResponseCode' => [
                    Fetch::LABEL  => 'Txn Response Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vpc_3DSstatus' => [
                    Fetch::LABEL  => 'Vpc 3DSstatus',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'Y',
                        'N',
                        'U',
                        'A',
                    ],
                ],
            ],

            Entity::BALANCE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'account_number' => [
                    Fetch::LABEL => 'Account Number',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL => 'Type',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::BALANCE_CONFIG => [
                'balance_id' => Fetch::FIELD_BALANCE_ID,
            ],

            Entity::MERCHANT_REMINDERS => [
                'merchant_id'        => Fetch::FIELD_MERCHANT_ID,
                'reminder_namespace' => [
                    Fetch::LABEL  => 'Reminder Namespace',
                    Fetch::TYPE   => Fetch::TYPE_STRING
                ],
            ],

            Entity::MERCHANT_FRESHDESK_TICKETS => [
                'merchant_id'        => Fetch::FIELD_MERCHANT_ID,
                'ticket_id'          => [
                    Fetch::LABEL     => 'Ticket Id',
                    Fetch::TYPE      => Fetch::TYPE_STRING
                ],
                'type'               => [
                    Fetch::LABEL     => 'Type',
                    Fetch::TYPE      => Fetch::TYPE_STRING
                ],
            ],

            Entity::MERCHANT_DETAIL => [

            ],

            Entity::BANK_ACCOUNT => [
                'deleted' => [
                    Fetch::LABEL  => 'Deleted',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'beneficiary_code' => [
                    Fetch::LABEL => 'Beneficiary Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'customer',
                        'merchant',
                    ],
                ],
                'ifsc_code' => [
                    Fetch::LABEL => 'IFSC',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'account_number' => [
                    Fetch::LABEL => 'Account Number',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],

            Entity::BANKING_ACCOUNT => [
                'merchant_id'     => Fetch::FIELD_MERCHANT_ID,
                'account_number'  => [
                    Fetch::LABEL  => 'Account Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => BankingAccount\Status::getAll(),
                ],
                'channel' => [
                    Fetch::LABEL  => 'Channel',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => BankingAccount\Channel::getAll(),
                ],
                'bank_internal_status' => [
                    Fetch::LABEL  => 'Bank Internal Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => BankingAccount\Gateway\Rbl\Status::getAll(),
                ],
                'balance_id'  => [
                    Fetch::LABEL  => 'Balance Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'bank_reference_number'  => [
                    Fetch::LABEL  => 'Bank Reference Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'fts_fund_account_id'  => [
                    Fetch::LABEL  => 'FTS Fund Account Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::BANKING_ACCOUNT_STATEMENT => [
                BAS\Entity::MERCHANT_ID => Fetch::FIELD_MERCHANT_ID,
                BAS\Entity::TRANSACTION_ID => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                BAS\Entity::ACCOUNT_NUMBER => [
                    Fetch::LABEL  => 'Account Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                BAS\Entity::BANK_TRANSACTION_ID => [
                    Fetch::LABEL  => 'Bank Txn Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                BAS\Entity::UTR => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                BAS\Entity::ENTITY_ID => [
                    Fetch::LABEL  => 'Payout Id/ Reversal Id/ External Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                BAS\Entity::ENTITY_TYPE => [
                    Fetch::LABEL => 'Entity Type',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [Entity::PAYOUT, Entity::EXTERNAL, Entity::REVERSAL]
                ]
            ],

            Entity::BANKING_ACCOUNT_STATEMENT_DETAILS => [
                BAS\Details\Entity::MERCHANT_ID => Fetch::FIELD_MERCHANT_ID,
                BAS\Details\Entity::BALANCE_ID => Fetch::FIELD_BALANCE_ID,
                BAS\Details\Entity::ACCOUNT_NUMBER => [
                    Fetch::LABEL  => 'Account Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                BAS\Details\Entity::CHANNEL => [
                    Fetch::LABEL  => 'Channel',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => BAS\Details\Channel::getChannels(),
                ],
                BAS\Details\Entity::STATUS => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => BAS\Details\Status::getStatuses(),
                ]
            ],

            Entity::EXTERNAL => [
                External\Entity::MERCHANT_ID => Fetch::FIELD_MERCHANT_ID,
                External\Entity::TRANSACTION_ID => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                External\Entity::CHANNEL => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                    Fetch::VALUES => BAS\Channel::getAll(),
                ],
                External\Entity::BANK_REFERENCE_NUMBER => [
                    Fetch::LABEL  => 'Bank Ref Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                External\Entity::UTR => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::BANK_TRANSFER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'balance_id' => Fetch::FIELD_BALANCE_ID,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'virtual_account_id' => [
                    Fetch::LABEL  => 'Virtual Account ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'mode' => [
                    Fetch::LABEL  => 'Mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'neft',
                        'rtgs',
                        'ift',
                        'imps',
                    ],
                ],
                'payer_account' => [
                    Fetch::LABEL  => 'Payer Account',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payer_ifsc' => [
                    Fetch::LABEL  => 'Payer IFSC',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payee_account' => [
                    Fetch::LABEL  => 'Payee Account',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payee_ifsc' => [
                    Fetch::LABEL  => 'Payee IFSC',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_id' => Fetch::FIELD_REFUND_ID,
            ],

            Entity::BHARAT_QR => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'virtual_account_id' => [
                    Fetch::LABEL  => 'Virtual Account ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'method'        => Fetch::FIELD_METHOD,
                'provider_reference_id' => [
                    Fetch::LABEL  => 'Provider Reference ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_reference' => [
                    Fetch::LABEL  => 'Merchant Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::BATCH => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'failed',
                        'created',
                        'processed',
                        'partially_processed',
                    ],
                ],
                'processing' => [
                    Fetch::LABEL  => 'Processing',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'payment_link',
                        'refund',
                        'emandate',
                        'reconciliation',
                        'irctc_refund',
                        'irctc_delta_refund',
                        'irctc_settlement',
                        'linked_account',
                        'virtual_bank_account',
                        'recurring_charge',
                        'payout',
                        'sub_merchant',
                        'direct_debit',
                    ],
                ],
                'sub_type' => [
                    Fetch::LABEL  => 'Sub Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'acknowledge',
                        'debit',
                        'register',
                        'combined',
                        'payment',
                        'refund',
                    ],
                ],
                'gateway' => [
                    Fetch::LABEL  => 'Gateway',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => array_merge(
                                        array_keys(RequestProcessor\Base::GATEWAY_SENDER_MAPPING),
                                        [
                                            'enach_rbl',
                                            'hdfc'
                                        ]),
                ],
            ],

            Entity::BATCH_FUND_TRANSFER => [
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'settlement',
                        'payout',
                        'refund',
                    ],
                ],
                'date' => [
                    Fetch::LABEL  => 'Date',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'channel' => [
                       Fetch::LABEL  => 'Channel',
                       Fetch::TYPE   => Fetch::TYPE_ARRAY,
                       Fetch::VALUES => Channel::getChannels()
                ],
            ],

            Entity::BILLDESK => [
                'AuthStatus' => [
                    Fetch::LABEL  => 'AuthStatus',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        '0001',
                        '0300',
                        '0002',
                        '0399',
                        'NA',
                    ],
                ],
                'BankReferenceNo' => [
                    Fetch::LABEL  => 'Bank Reference No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'RefStatus' => [
                    Fetch::LABEL  => 'Refund Status',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'RefundId' => [
                    Fetch::LABEL  => 'Billdesk Refund Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'TxnReferenceNo' => [
                    Fetch::LABEL  => 'Txn Reference No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MOZART => [
                'gateway'    => Fetch::FIELD_GATEWAY,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'refund_id'  => Fetch::FIELD_REFUND_ID,
            ],

            Entity::CARD => [
                'global_card_id' => [
                    Fetch::LABEL  => 'Global Card Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'iin' => [
                    Fetch::LABEL  => 'IIN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'last4' => [
                    Fetch::LABEL  => 'Last4',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'Visa',
                        'MasterCard',
                        'Maestro',
                        'Diners Club',
                        'American Express',
                        'RuPay',
                        'Unknown',
                        'Discover',
                    ],
                ],
                'status' => Fetch::FIELD_PAYMENT_STATUS,
                'vault' => [
                    Fetch::LABEL  => 'Vault',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'vault_token' => [
                    Fetch::LABEL  => 'Vault Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::WORKFLOW_ACTION => [
                'entity_id' => [
                    Fetch::LABEL => 'Entity Id',
                ],
            ],

            Entity::CONTACT => [
                'email'           => [],
                'name'            => [],
                'contact'         => [],
                'reference_id'    => [],
                'fund_account_id' => [],
                'account_number'  => [],
                'active'          => [
                    Fetch::TYPE => Fetch::TYPE_BOOLEAN
                ],
                'type'            => [],
            ],

            Entity::CREDITS => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'fee',
                        'amount',
                    ],
                ],
            ],

            Entity::CUSTOMER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'active' => [
                    Fetch::LABEL  => 'Active',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'contact' => [
                    Fetch::LABEL  => 'Contact',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CUSTOMER_BALANCE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CUSTOMER_TRANSACTION => [
                'entity_id' => [
                    Fetch::LABEL  => 'Payment/Refund Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'transfer',
                        'refund',
                    ],
                ],
            ],

            Entity::CYBERSOURCE => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'ref' => [
                    Fetch::LABEL  => 'Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'capture_ref' => [
                    Fetch::LABEL  => 'Capture Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::EBS => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::FEE_BREAKUP => [
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'pricing_rule_id' => [
                    Fetch::LABEL  => 'Pricing Rule Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FIRST_DATA => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'action' => [
                    Fetch::LABEL  => 'Action',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_id' => [
                    Fetch::LABEL  => 'Refund ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_payment_id' => [
                    Fetch::LABEL  => 'Gateway Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'tdate' => [
                    Fetch::LABEL  => 'Tdate',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'caps_payment_id' => [
                    Fetch::LABEL  => 'Caps Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_transaction_id' => [
                    Fetch::LABEL  => 'Gateway Transaction ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::DISPUTE => [
                Dispute\Entity::MERCHANT_ID     => Fetch::FIELD_MERCHANT_ID,
                Dispute\Entity::PAYMENT_ID      => Fetch::FIELD_PAYMENT_ID,
                Dispute\Entity::STATUS          => [
                    Fetch::LABEL        => 'Status',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        Dispute\Status::OPEN,
                        Dispute\Status::UNDER_REVIEW,
                        Dispute\Status::WON,
                        Dispute\Status::LOST,
                        Dispute\Status::CLOSED,
                    ],
                ],
                Dispute\Entity::INTERNAL_STATUS => [
                    Fetch::LABEL        => 'Internal Status',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => Dispute\InternalStatus::getInternalStatuses(),
                ],
                Dispute\Entity::PHASE           => [
                    Fetch::LABEL        => 'Phase',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        Dispute\Phase::CHARGEBACK,
                        Dispute\Phase::PRE_ARBITRATION,
                        Dispute\Phase::ARBITRATION,
                        Dispute\Phase::RETRIEVAL,
                        Dispute\Phase::FRAUD,
                    ],
                ],
                Dispute\Entity::AMOUNT          => [
                    Fetch::LABEL        => 'Amount',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                Dispute\Entity::INTERNAL_RESPOND_BY_TO          => [
                    Fetch::LABEL        => 'Internal Respond By To',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                Dispute\Entity::INTERNAL_RESPOND_BY_FROM         => [
                    Fetch::LABEL        => 'Internal Respond By From',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
                Dispute\Entity::ORDER_BY_INTERNAL_RESPOND         => [
                    Fetch::LABEL        => 'Prioritize on Internal Respond By Disputes',
                    Fetch::TYPE         => Fetch::TYPE_BOOLEAN,
                ],
                Dispute\Entity::GATEWAY_DISPUTE_SOURCE             => [
                    Fetch::LABEL        => 'Gateway Dispute Source',
                    Fetch::TYPE         => Fetch::TYPE_ARRAY,
                    Fetch::VALUES       => [
                        Dispute\Constants::GATEWAY_DISPUTE_SOURCE_CUSTOMER,
                        Dispute\Constants::GATEWAY_DISPUTE_SOURCE_NETWORK,
                    ],
                ],

            ],

            Entity::DISPUTE_EVIDENCE => [
                Dispute\Evidence\Entity::DISPUTE_ID =>  [
                        Fetch::LABEL        => 'Dispute ID',
                        Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],
            Entity::DISPUTE_EVIDENCE_DOCUMENT => [
                Dispute\Evidence\Document\Entity::DISPUTE_ID =>  [
                    Fetch::LABEL        => 'Dispute ID',
                    Fetch::TYPE         => Fetch::TYPE_STRING,
                ],
            ],


            Entity::DISPUTE_REASON => [
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Dispute\Reason\Network::list(),
                ],
                'code' => [
                    Fetch::LABEL  => 'Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'description' => [
                    Fetch::LABEL  => 'Description',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_code' => [
                    Fetch::LABEL  => 'Gateway Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_description' => [
                    Fetch::LABEL  => 'Gateway Description',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ]
            ],

            Entity::D2C_BUREAU_REPORT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::SETTLEMENT_ONDEMAND => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'initiated',
                        'processed',
                        'partially_processed',
                        'reversed',
                    ],
                ],
            ],
            Entity::SETTLEMENT_ONDEMAND_PAYOUT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'settlement_ondemand_id' => [
                    Fetch::LABEL  => 'Settlement Ondemand ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'initiated',
                        'processed',
                        'reversed',
                    ],
                ],
                'payout_id' => [
                    Fetch::LABEL => 'Payout ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ]
            ],

            Entity::SETTLEMENT_ONDEMAND_FUND_ACCOUNT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::SETTLEMENT_ONDEMAND_TRANSFER => [
                'payout_id' => [
                    Fetch::LABEL => 'Payout ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'processing',
                        'processed',
                        'reversed',
                    ],
                ],
                'mode'   => [
                    Fetch::LABEL  => 'Mode',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'IMPS',
                        'NEFT',
                    ],
                ],
            ],

            Entity::SETTLEMENT_ONDEMAND_ATTEMPT => [
                'settlement_ondemand_transfer_id' => [
                    Fetch::LABEL => 'Settlement Ondemand Transfer ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'processing',
                        'processed',
                        'reversed',
                    ],
                ],
            ],

            Entity::SETTLEMENT_ONDEMAND_BULK => [
                'settlement_ondemand_id' => [
                    Fetch::LABEL => 'Settlement Ondemand ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'settlement_ondemand_transfer_id' => [
                    Fetch::LABEL => 'Settlement Ondemand Transfer ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::EMI_PLAN => [
                'bank' => [
                    Fetch::LABEL  => 'Bank',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Type::CREDIT,
                        Type::DEBIT,
                    ],
                ],
            ],

            Entity::FEATURE => [
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'entity_type' => [
                    Fetch::LABEL  => 'Entity Type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'name' => [
                    Fetch::LABEL  => 'Name',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FILE_STORE => [
                'entity_id' => [
                    Fetch::LABEL  => 'Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::FUND_ACCOUNT => [
                'source_id'    => [],
                'account_type' => [],
            ],

            Entity::FUND_ACCOUNT_VALIDATION => [
                'fund_account_id' => [
                    Fetch::LABEL  => 'Fund Account Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'completed',
                        'failed',
                    ],
                ],
            ],

            Entity::SETTLEMENT_DESTINATION => [
                'settlement_id' => [
                    Fetch::LABEL => 'Settlement ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'destination_type' => [
                    Fetch::LABEL => 'destination type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FUND_TRANSFER_ATTEMPT => [
                'batch_fund_transfer_id' => [
                    Fetch::LABEL  => 'Batch Fund Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'source_type' => [
                    Fetch::LABEL  => 'Source Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'settlement',
                        'payout',
                        'refund',
                        'fund_account_validation'
                    ],
                ],
                'source_id' => [
                    Fetch::LABEL  => 'Source Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'initiated',
                        'failed',
                        'processed',
                    ],
                ],
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'channel' => [
                    Fetch::LABEL  => 'Channel',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Channel::getChannels()
                ],
                'gateway_ref_no' => [
                    Fetch::LABEL  => 'Gateway Ref No',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::GATEWAY_DOWNTIME => [
                'method' => Fetch::FIELD_METHOD,
                'gateway' => Fetch::FIELD_GATEWAY,
                'issuer' => [
                    Fetch::LABEL  => 'Issuer',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::GATEWAY_FILE => [
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'emi',
                        'refund',
                        'combined',
                        'emandate_debit',
                        'emandate_register',
                    ],
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'file_generated',
                        'file_sent',
                        'failed',
                        'acknowledged'
                    ],
                ],
                'target' => [
                    Fetch::LABEL  => 'Target',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'rbl',
                        'hdfc',
                        'axis',
                        'icici',
                        'kotak',
                        'federal',
                    ],
                ],
            ],

            Entity::HDFC => [
                'auth' => [
                    Fetch::LABEL  => 'Auth Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_transaction_id' => [
                    Fetch::LABEL  => 'Gateway Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'refund_id' => [
                    Fetch::LABEL  => 'Refund Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'ref' => [
                    Fetch::LABEL  => 'Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::HITACHI => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'refund_id'  => Fetch::FIELD_REFUND_ID,
                'action'     => [
                    Fetch::LABEL => 'Action',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'pRequestId' => [
                    Fetch::LABEL => 'Request ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'pRRN'       => [
                    Fetch::LABEL => 'RRN',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::IIN => [
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'otp_read' => [
                    Fetch::LABEL  => 'Otp Read',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'iin' => [
                    Fetch::LABEL  => 'Iin',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'issuer' => [
                    Fetch::LABEL  => 'Issuer',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'network' => [
                    Fetch::LABEL  => 'Network',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'credit',
                        'debit',
                        'unknown',
                    ],
                ],
            ],

            Entity::INVOICE => [
                'batch_id' => [
                    Fetch::LABEL  => 'Batch Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'receipt' => [
                    Fetch::LABEL  => 'Receipt',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'user_id' => [
                    Fetch::LABEL  => 'User Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'draft',
                        'issued',
                        'partially_paid',
                        'paid',
                        'cancelled',
                        'expired',
                    ],
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'ecod',
                        'link',
                        'invoice',
                    ],
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'notes' => Fetch::FIELD_NOTES,
                'subscription_id' => Fetch::FIELD_SUBSCRIPTION_ID,
                'customer_name' => [
                    Fetch::LABEL  => 'Customer Name',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_email' => [
                    Fetch::LABEL  => 'Customer Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_contact' => [
                    Fetch::LABEL  => 'Customer Contact',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::ITEM => [
                'active' => [
                    Fetch::LABEL  => 'Active',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::LEGAL_ENTITY => [
                'external_id' => [
                    Fetch::LABEL  => 'External ID',
                ],
            ],

            Entity::KEY => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::MERCHANT_DOCUMENT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::STAKEHOLDER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::MERCHANT => [
                'activated' => [
                    Fetch::LABEL  => 'Activated',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.amex' => [
                    Fetch::LABEL  => 'Amex',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.card' => [
                    Fetch::LABEL  => 'Card',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'category' => [
                    Fetch::LABEL  => 'MCC Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'category2' => [
                    Fetch::LABEL  => 'Category 2',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'hold_funds' => [
                    Fetch::LABEL  => 'Hold Funds',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'live' => [
                    Fetch::LABEL  => 'Live',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.itzcash' => [
                    Fetch::LABEL  => 'Itzcash',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.oxigen' => [
                    Fetch::LABEL  => 'Oxigen',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.amexeasyclick' => [
                    Fetch::LABEL  => 'Amex Easy Click',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.paycash' => [
                    Fetch::LABEL  => 'Paycash',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.citibankrewards' => [
                    Fetch::LABEL  => 'Citibank Reward Points',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.mobikwik' => [
                    Fetch::LABEL  => 'Mobikwik',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.paytm' => [
                    Fetch::LABEL  => 'Paytm',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.payumoney' => [
                    Fetch::LABEL  => 'Payumoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.payzapp' => [
                    Fetch::LABEL  => 'Payzapp',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.olamoney' => [
                    Fetch::LABEL  => 'Olamoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.mpesa' => [
                    Fetch::LABEL  => 'Mpesa',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.upi' => [
                    Fetch::LABEL  => 'Upi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.airtelmoney' => [
                    Fetch::LABEL  => 'Airtelmoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.freecharge' => [
                    Fetch::LABEL  => 'Freecharge',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.jiomoney' => [
                    Fetch::LABEL  => 'Jiomoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'methods.sbibuddy' => [
                    Fetch::LABEL  => 'Sbibuddy',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'pricing_plan_id' => [
                    Fetch::LABEL  => 'Pricing Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'account_code' => [
                    Fetch::LABEL    => 'Account Code',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'parent_id' => [
                    Fetch::LABEL  => 'Marketplace Parent Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'receipt_email_enabled' => [
                    Fetch::LABEL  => 'Receipt Email_enabled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'legal_entity_id' => [
                    Fetch::LABEL  => 'Legal Entity Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'fee_bearer' => [
                    Fetch::LABEL  => 'Fee Bearer',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'platform',
                        'customer',
                    ],
                ],
                'fee_model' => [
                    Fetch::LABEL  => 'Fee Model',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'prepaid',
                        'postpaid',
                    ],
                ],
                'risk_rating' => [
                    Fetch::LABEL  => 'Risk Rating',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        '1',
                        '2',
                        '3',
                        '4',
                        '5',
                    ],
                ],
                'external_id' => [
                    Fetch::LABEL  => 'External ID',
                ],
            ],

            ENTITY::MERCHANT_REWARD => [
                'merchant_id' => [
                    Fetch::LABEL  => 'Merchant Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'reward_id' => [
                    Fetch::LABEL  => 'Reward Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::METHODS => [
                'amex' => [
                    Fetch::LABEL  => 'Amex',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'card' => [
                    Fetch::LABEL  => 'Card',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'itzcash' => [
                    Fetch::LABEL  => 'Itzcash',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'oxigen' => [
                    Fetch::LABEL  => 'Oxigen',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'amexeasyclick' => [
                    Fetch::LABEL  => 'Amex Easy Click',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'paycash' => [
                    Fetch::LABEL  => 'Paycash',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'citibankrewards' => [
                    Fetch::LABEL  => 'Citibank Reward Points',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'mobikwik' => [
                    Fetch::LABEL  => 'Mobikwik',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'paytm' => [
                    Fetch::LABEL  => 'Paytm',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'payumoney' => [
                    Fetch::LABEL  => 'Payumoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'payzapp' => [
                    Fetch::LABEL  => 'Payzapp',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'mpesa' => [
                    Fetch::LABEL  => 'Mpesa',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'olamoney' => [
                    Fetch::LABEL  => 'Olamoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'upi' => [
                    Fetch::LABEL  => 'Upi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'airtelmoney' => [
                    Fetch::LABEL  => 'Airtelmoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'freecharge' => [
                    Fetch::LABEL  => 'Freecharge',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'jiomoney' => [
                    Fetch::LABEL  => 'Jiomoney',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'sbibuddy' => [
                    Fetch::LABEL  => 'Sbibuddy',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::MERCHANT_INVOICE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'invoice_number' => [
                    Fetch::LABEL  => 'Invoice No.',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gstin' => [
                    Fetch::LABEL  => 'GSTIN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'month' => [
                    Fetch::LABEL  => 'Month',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'year' => [
                    Fetch::LABEL  => 'Year',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MOBIKWIK => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::ENACH => [
                'umrn' => [
                    Fetch::LABEL  => 'UMRN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::NETBANKING => [
                'bank_payment_id' => [
                    Fetch::LABEL  => 'Bank Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'caps_payment_id' => [
                    Fetch::LABEL  => 'Caps Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'int_payment_id' => [
                    Fetch::LABEL  => 'Int Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::NODAL_STATEMENT => [
                'q'  => [
                    Fetch::LABEL  => 'Search Query',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::OFFER => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::ORDER => [
                'account_number' => [
                    Fetch::LABEL  => 'Account Number',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'authorized' => [
                    Fetch::LABEL  => 'Authorized',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'notes' => Fetch::FIELD_NOTES,
                'receipt' => [
                    Fetch::LABEL  => 'Receipt',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'attempted',
                        'paid',
                    ],
                ],
            ],

            Entity::PAYMENT_ANALYTICS => [
                'checkout_id' => [
                    Fetch::LABEL  => 'Checkout Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::PAYMENT => [
                'app_token' => [
                    Fetch::LABEL  => 'App Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'acquirer_data' => [
                    Fetch::LABEL  => 'Bank Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'bank' => [
                    Fetch::LABEL  => 'Bank Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'card_id' => [
                    Fetch::LABEL  => 'Card Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'global_customer_id' => [
                    Fetch::LABEL  => 'Global Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'email' => [
                    Fetch::LABEL  => 'Contact Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway' => Fetch::FIELD_GATEWAY,
                'gateway_terminal_id' => [
                    Fetch::LABEL  => 'Gateway Terminal Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'global_token_id' => [
                    Fetch::LABEL  => 'Global Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'iin' => [
                    Fetch::LABEL  => 'Card IIN',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'international' => [
                    Fetch::LABEL  => 'International',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'invoice_id' => [
                    Fetch::LABEL  => 'Invoice Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'last4' => [
                    Fetch::LABEL  => 'Card Last 4',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'method' => Fetch::FIELD_METHOD,
                'notes' => Fetch::FIELD_NOTES,
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'payment_link_id' => [
                    Fetch::LABEL => 'Payment Link Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'refund_status' => [
                    Fetch::LABEL  => 'Refund Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'null',
                        'partial',
                        'full',
                    ],
                ],
                'save' => [
                    Fetch::LABEL  => 'Save',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'status' => Fetch::FIELD_PAYMENT_STATUS,
                'subscription_id' => Fetch::FIELD_SUBSCRIPTION_ID,
                'terminal_id' => [
                    Fetch::LABEL  => 'Terminal ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'token_id' => [
                    Fetch::LABEL  => 'Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'recurring_status' => [
                    Fetch::LABEL  => 'Token Recurring Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'null',
                        'initiated',
                        'confirmed',
                        'rejected',
                    ],
                ],
                'transfer_id' => [
                    Fetch::LABEL  => 'Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'verified' => [
                    Fetch::LABEL  => 'Verified',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'null',
                        '0',
                        '1',
                        '2',
                    ],
                ],
                'wallet' => Fetch::FIELD_WALLET,
                'vpa' => [
                    Fetch::LABEL  => 'VPA',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::PAYMENT_LINK => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'active',
                        'inactive',
                    ],
                ],
                'status_reason' => [
                    Fetch::LABEL  => 'Status Reason',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'expired',
                        'deactivated',
                        'completed',
                    ],
                ],
                'user_id' => [
                    Fetch::LABEL => 'User Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'receipt' => [
                    Fetch::LABEL => 'Receipt',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'title' => [
                    Fetch::LABEL => 'Title',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::PAPER_MANDATE_UPLOAD => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'pending',
                        'failed',
                        'accepted',
                        'rejected',
                    ],
                ],
                'paper_mandate_id' => [
                    Fetch::LABEL => 'paper_mandate_id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::PAYOUT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'balance_id' => Fetch::FIELD_BALANCE_ID,
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'destination' => [
                    Fetch::LABEL  => 'Bank Account Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'method' => [
                    Fetch::LABEL  => 'Method',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Payout\Method::getAll(),
                ],
                'payout_mode' => [
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => FundTransfer\Mode::getAll(),
                ],
                'transaction_id'  => [],
                'utr'             => [],
                'contact_name'    => [],
                'contact_phone'   => [],
                'contact_id'      => [],
                'contact_email'   => [],
                'contact_type'    => [],
                'fund_account_id' => [],
                'status'          => [
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => array_keys(Payout\Status::$internalToPublicStatusMap),
                ],
                'reference_id'  => [],
                'channel'       => [
                    Fetch::LABEL    => 'Channel',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => Channel::getChannels(),
                ],
            ],

            Entity::PAYOUT_LINK => [
                'merchant_id' => [
                    Fetch::LABEL  => 'Merchant Id (*)',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::PAYTM => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'received' => [
                    Fetch::LABEL  => 'Received',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
            ],

            Entity::PRICING => [
                'plan_id' => [
                    Fetch::LABEL  => 'Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::REFUND => [
                'amount' => [
                    Fetch::LABEL  => 'Amount',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'batch_id' => [
                    Fetch::LABEL  => 'Batch Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway' => Fetch::FIELD_GATEWAY,
                'payment_gateway' => [
                    Fetch::LABEL  => 'Payment Gateway',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => config('gateway.available')
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'method' => Fetch::FIELD_METHOD,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'failed',
                        'processed',
                    ],
                ],
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'notes' => Fetch::FIELD_NOTES,
            ],

            Entity::REWARD => [
                'advertiser_id' => [
                    Fetch::LABEL  => 'Advertiser Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'brand_name' => [
                    Fetch::LABEL  => 'Brand',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::REWARD_COUPON => [
                'reward_id' => [
                    Fetch::LABEL  => 'Reward Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'coupon_code' => [
                    Fetch::LABEL  => 'Coupon Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ]
            ],

            Entity::REPORT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'merchant',
                        'order',
                        'payment',
                        'refund',
                        'reversal',
                        'settlement',
                        'transaction'
                    ],
                ],
            ],

            Entity::REVERSAL => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'entity_id' => [
                    Fetch::LABEL  => 'Transfer Id/ Payout Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::RISK => [
                'fraud_type' => [
                    Fetch::LABEL  => 'Fraud Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'suspected',
                        'confirmed',
                    ],
                ],
                'source' => [
                    Fetch::LABEL  => 'Source',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'bank',
                        'gateway',
                        'maxmind',
                        'manual',
                        'internal',
                    ],
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
            ],

            Entity::SETTLEMENT => [
                'batch_fund_transfer_id' => [
                    Fetch::LABEL  => 'Batch Fund Transfer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'created',
                        'initiated',
                        'failed',
                        'processed',
                    ],
                ],
                'transaction_id' => [
                    Fetch::LABEL  => 'Transaction Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'utr' => [
                    Fetch::LABEL  => 'UTR',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'channel' => [
                    Fetch::LABEL  => 'Channel',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Channel::getChannels()
                ],
                'balance_id' => Fetch::FIELD_BALANCE_ID,
            ],

            Entity::SETTLEMENT_DETAILS => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'settlement_id' => [
                    Fetch::LABEL  => 'Settlement Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::STATEMENT => [
                'balance_id'      => [],
                'contact_id'      => [],
                'payout_id'       => [],
                'contact_name'    => [],
                'contact_phone'   => [],
                'contact_email'   => [],
                'contact_type'    => [],
                'fund_account_id' => [],
                'utr'             => [],
                'mode'            => [
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => FundTransfer\Mode::getAll(),
                ],
            ],

            Entity::TERMINAL => [
                'enabled' => [
                    Fetch::LABEL  => 'Enabled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'gateway' => Fetch::FIELD_GATEWAY,
                'category' => [
                    Fetch::LABEL  => 'Category',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'shared' => [
                    Fetch::LABEL  => 'Shared',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'gateway_merchant_id' => [
                    Fetch::LABEL  => 'Gateway Merchant Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_terminal_id' => [
                    Fetch::LABEL  => 'Gateway Terminal Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'network_category' => [
                    Fetch::LABEL  => 'Network Category',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'gateway_acquirer' => [
                    Fetch::LABEL  => 'Gateway Acquirer',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'axis',
                        'hdfc',
                        'icic',
                    ],
                ],
                'emi' => [
                    Fetch::LABEL  => 'Emi',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'plan_id' => [
                    Fetch::LABEL  => 'Plan Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::TRANSACTION => [
                'entity_id' => [
                    Fetch::LABEL  => 'Payment/Refund/Settlement Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'balance_id' => Fetch::FIELD_BALANCE_ID,
                'reconciled' => [
                    Fetch::LABEL  => 'Reconciled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'settled' => [
                    Fetch::LABEL  => 'Settled',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'on_hold' => [
                    Fetch::LABEL  => 'On Hold',
                    Fetch::TYPE   => Fetch::TYPE_BOOLEAN
                ],
                'settlement_id' => [
                    Fetch::LABEL  => 'Settlement Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'payment',
                        'refund',
                        'settlement',
                        'adjustment',
                        'transfer',
                        'reversal',
                        'payout',
                        'commission',
                        'repayment_breakup',
                        'credit_repayment',
                        'installment',
                        'charge',
                    ],
                ],
            ],

            Entity::TRANSFER => [
                'source' => [
                    Fetch::LABEL  => 'Source Payment/Order/Merchant ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'recipient' => [
                    Fetch::LABEL  => 'Recipient Merchant/Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'account_code' => [
                    Fetch::LABEL    => 'Account Code',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'account_code_used' => [
                    Fetch::LABEL    => 'Account Code Used',
                    Fetch::TYPE     => Fetch::TYPE_BOOLEAN,
                ],
            ],

            Entity::TOKEN => [
                'bank' => [
                    Fetch::LABEL  => 'Bank Code',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'card_id' => [
                    Fetch::LABEL  => 'Card Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'merchant_id'   => Fetch::FIELD_MERCHANT_ID,
                'method'        => Fetch::FIELD_METHOD,
                'terminal_id' => [
                    Fetch::LABEL  => 'Terminal Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'token' => [
                    Fetch::LABEL  => 'Token',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'recurring_status' => [
                    Fetch::LABEL    => 'Recurring Status',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'wallet' => Fetch::FIELD_WALLET
            ],

            Entity::UPI => [
                'gateway'   => Fetch::FIELD_GATEWAY,
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'bank' => Fetch::FIELD_UPI,
                'gateway_payment_id' => [
                    Fetch::LABEL  => 'Gateway Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'npci_reference_id' => [
                    Fetch::LABEL  => 'NPCI Reference Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'refund_id' => Fetch::FIELD_REFUND_ID,
                'merchant_reference' => [
                    Fetch::LABEL  => 'Merchant Reference',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::USER => [
                'email' => [
                    Fetch::LABEL  => 'Email',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MERCHANT_REQUEST => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::VIRTUAL_ACCOUNT => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'balance_id' => Fetch::FIELD_BALANCE_ID,
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'active',
                        'closed',
                        'paid',
                    ],
                ],
                'customer_id' => [
                    Fetch::LABEL  => 'Customer ID',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'receiver_type' => [
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'bank_account',
                        'qr_code',
                        'vpa',
                    ],
                ],
            ],

            Entity::WALLET => [
                'payment_id' => Fetch::FIELD_PAYMENT_ID,
                'gateway_payment_id' => [
                    Fetch::LABEL  => 'Gateway Payment Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'wallet' => Fetch::FIELD_WALLET,
            ],

            Entity::SCHEDULE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::NODAL_BENEFICIARY => [
                'merchant_id'     => Fetch::FIELD_MERCHANT_ID,
                'bank_account_id' => [
                    Fetch::LABEL => 'Bank Account Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING
                ],
                'channel' => [
                    Fetch::LABEL  => 'Channel',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => Channel::getChannels(),
                ],
                'registration_status' => [
                    Fetch::LABEL  => 'Registration Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => NodalBeneficiary\Status::getAllowedBeneficiaryStatus(),
                ],
            ],

            Entity::ENTITY_ORIGIN => [
                'origin_id'   => [
                    Fetch::LABEL => 'Origin Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'entity_id'   => [
                    Fetch::LABEL => 'Entity Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MERCHANT_ACCESS_MAP => [
                'merchant_id'     => FETCH::FIELD_MERCHANT_ID,
                'entity_owner_id' => [
                    Fetch::LABEL => 'Entity Owner Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MERCHANT_APPLICATION => [
                'merchant_id'     => FETCH::FIELD_MERCHANT_ID,
                'application_id'  => [
                    Fetch::LABEL  => 'Application Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING
                ],
            ],

            Entity::MERCHANT_INHERITANCE_MAP => [
                'merchant_id' => [
                    Fetch::LABEL  => 'Merchant Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING
                ],
                'parent_merchant_id' => [
                    Fetch::LABEL => 'Parent Merchant Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'created_at' =>  [
                    Fetch::LABEL  => 'Created At',
                    Fetch::TYPE   => Fetch::TYPE_STRING
                ]
            ],

            Entity::PARTNER_CONFIG => [
                Config\Entity::ENTITY_TYPE => [
                    Fetch::LABEL  => 'Entity Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'application',
                        'merchant',
                    ],
                ],
                Config\Entity::ENTITY_ID => [
                    Fetch::LABEL => 'Entity Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Config\Entity::ORIGIN_ID => [
                    Fetch::LABEL => 'Origin Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Config\Entity::DEFAULT_PLAN_ID  => [
                    Fetch::LABEL => 'Default Plan',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Config\Entity::IMPLICIT_PLAN_ID => [
                    Fetch::LABEL => 'Implicit Plan',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Config\Entity::EXPLICIT_PLAN_ID => [
                    Fetch::LABEL => 'Explicit Plan',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Config\Entity::COMMISSION_MODEL => [
                    Fetch::LABEL  => 'Commission Model',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Config\CommissionModel::COMMISSION,
                        Config\CommissionModel::SUBVENTION,
                    ],
                ],
            ],

            Entity::COMMISSION     => [
                Commission\Entity::TYPE => [
                    Fetch::LABEL  => 'Commission Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Commission\Type::IMPLICIT,
                        Commission\Type::EXPLICIT,
                    ],
                ],
                Commission\Entity::SOURCE_TYPE => [
                    Fetch::LABEL  => 'Source Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'payment',
                        'refund',
                    ],
                ],
                Commission\Entity::SOURCE_ID  => [
                    Fetch::LABEL => 'Source Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Commission\Entity::PARTNER_ID => [
                    Fetch::LABEL => 'Partner Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Commission\Entity::PARTNER_CONFIG_ID => [
                    Fetch::LABEL => 'Partner Config Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Commission\Entity::TRANSACTION_ID => [
                    Fetch::LABEL => 'Transaction Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                Commission\Entity::STATUS => [
                    Fetch::LABEL  => 'Commission Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Commission\Status::CREATED,
                        Commission\Status::CAPTURED,
                        Commission\Status::REFUNDED,
                    ],
                ],
                Commission\Entity::MODEL => [
                    Fetch::LABEL  => 'Model',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Config\CommissionModel::COMMISSION,
                        Config\CommissionModel::SUBVENTION,
                    ],
                ],
            ],

            Entity::COMMISSION_INVOICE => [
                Invoice\Entity::MERCHANT_ID => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::COMMISSION_COMPONENT => [
                Component\Entity::COMMISSION_ID => [
                    Fetch::LABEL => 'Commission Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING
                ],
                Component\Entity::PRICING_TYPE => [
                    Fetch::LABEL => 'Commission pricing type',
                    Fetch::TYPE  => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Commission\Constants::VARIABLE,
                        Commission\Constants::FIXED
                    ]
                ],
                Component\Entity::PRICING_FEATURE => [
                    Fetch::LABEL => 'Commission pricing feature',
                    Fetch::TYPE  => Fetch::TYPE_STRING
                ]
            ],

            Entity::PARTNER_ACTIVATION => [
                Activation\Entity::MERCHANT_ID => [
                    Fetch::LABEL => 'Merchant id',
                    Fetch::TYPE  => Fetch::TYPE_STRING
                ],
            ],

            Entity::MERCHANT_PRODUCT => [
                Product\Entity::MERCHANT_ID => [
                    Fetch::LABEL => 'Merchant id',
                    Fetch::TYPE  => Fetch::TYPE_STRING
                ],
                Product\Entity::PRODUCT_NAME => [
                    Fetch::LABEL => 'Product name',
                    Fetch::TYPE  => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        Product\Name::PAYMENT_GATEWAY,
                        Product\Name::PAYMENT_LINKS,
                    ]
                ]
            ],

            Entity::MERCHANT_USER => [
                MerchantUser\Entity::MERCHANT_ID => [
                    Fetch::LABEL  => 'Merchant Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                MerchantUser\Entity::USER_ID => [
                    Fetch::LABEL  => 'User Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::MERCHANT_PROMOTION => [
                'promotion_id'  => [
                    Fetch::LABEL => 'Promotion Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                MerchantUser\Entity::MERCHANT_ID => [
                    Fetch::LABEL  => 'Merchant Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::UPI_TRANSFER => [
                'payment_id'            => Fetch::FIELD_PAYMENT_ID,
                'virtual_account_id'    => [
                    Fetch::LABEL => 'Virtual Account ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'payer_vpa'             => [
                    Fetch::LABEL => 'Payer VPA',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'payee_vpa'             => [
                    Fetch::LABEL => 'Payee VPA',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'npci_reference_id'     => [
                    Fetch::LABEL => 'NPCI Reference Id',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::FEE_RECOVERY => [
                'entity_id' => [
                    Fetch::LABEL    => 'Source Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING
                ],
                'type'      => [
                    Fetch::LABEL    => 'Type',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FeeRecovery\Type::getAll(),
                ],
                'status'    => [
                    Fetch::LABEL    => 'Status',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => FeeRecovery\Status::getAll(),
                ],
                'recovery_payout_id'    => [],
                'attempt_number'        => [],
                'reference_number'      => [],
            ],

            Entity::BANK_TRANSFER_HISTORY => [
                'bank_transfer_id' => [
                    Fetch::LABEL => 'Bank Transfer ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],

            Entity::VIRTUAL_VPA_PREFIX => [
                'merchant_id' => [
                    Fetch::LABEL => 'Merchant ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'prefix' => [
                    Fetch::LABEL => 'Prefix',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'terminal_id' => [
                    Fetch::LABEL => 'Terminal ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],

            Entity::VIRTUAL_VPA_PREFIX_HISTORY => [
                'virtual_vpa_prefix_id' => [
                    Fetch::LABEL => 'Virtual VPA Prefix ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'merchant_id' => [
                    Fetch::LABEL => 'Merchant ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'is_active' => [
                    Fetch::LABEL => 'Active',
                    Fetch::TYPE => Fetch::TYPE_BOOLEAN,
                ],
            ],

            Entity::BANK_TRANSFER_REQUEST => [
                'is_created' => [
                    Fetch::LABEL => 'Bank Transfer Created',
                    Fetch::TYPE => Fetch::TYPE_BOOLEAN,
                ],
                'utr' => [
                    Fetch::LABEL => 'UTR',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'payee_account' => [
                    Fetch::LABEL => 'Payee Account',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'gateway' => [
                    Fetch::LABEL => 'Gateway',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'kotak',
                        'yesbank',
                        'rbl',
                        'icici',
                        'dashboard',
                    ],
                ],
            ],

            Entity::UPI_TRANSFER_REQUEST => [
                'is_created' => [
                    Fetch::LABEL => 'UPI Transfer Created',
                    Fetch::TYPE => Fetch::TYPE_BOOLEAN,
                ],
                'npci_reference_id' => [
                    Fetch::LABEL => 'NPCI Reference ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'payee_vpa' => [
                    Fetch::LABEL => 'Payee VPA',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'gateway' => [
                    Fetch::LABEL => 'Gateway',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'upi_mindgate',
                        'upi_icici',
                    ],
                ],
            ],

            Entity::VIRTUAL_ACCOUNT_TPV => [
                'virtual_account_id' => [
                    Fetch::LABEL => 'Virtual Account ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
                'entity_type' => [
                    Fetch::LABEL => 'Entity Type',
                    Fetch::TYPE => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'bank_account'
                    ],
                ],
                'entity_id' => [
                    Fetch::LABEL => 'Entity ID',
                    Fetch::TYPE => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_DEVICE          => [
                'contact'         => [
                    Fetch::LABEL    => 'Contact',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'customer_id'     => [
                    Fetch::LABEL    => 'Customer ID',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_DEVICE_TOKEN    => [
                'device_id'       => [
                   Fetch::LABEL     => 'Device Id',
                   Fetch::TYPE      => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_REGISTER_TOKEN  => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_BANK_ACCOUNT    => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'account_number'  => [
                    Fetch::LABEL    => 'Account Number',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_VPA             => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'username'        => [
                    Fetch::LABEL    => 'Username',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'bank_account_id' => [
                    Fetch::LABEL    => 'Bank Account Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING
                ],
            ],

            Entity::P2P_BENEFICIARY     => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_TRANSACTION     => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'customer_id'     => [
                    Fetch::LABEL    => 'Customer ID',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'status'          => [
                    Fetch::LABEL    => 'Status',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => [
                        TransactionStatus::CREATED,
                        TransactionStatus::COMPLETED,
                        TransactionStatus::FAILED,
                    ],
                ],
            ],

            Entity::P2P_UPI_TRANSACTION => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'rrn'             => [
                    Fetch::LABEL    => 'RRN',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
            ],

            Entity::P2P_CONCERN         => [
                'device_id'       => [
                    Fetch::LABEL    => 'Device Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'transaction_id'  => [
                    Fetch::LABEL    => 'Transaction Id',
                    Fetch::TYPE     => Fetch::TYPE_STRING,
                ],
                'status'          => [
                    Fetch::LABEL    => 'Status',
                    Fetch::TYPE     => Fetch::TYPE_ARRAY,
                    Fetch::VALUES   => [
                        ConcernStatus::CREATED,
                        ConcernStatus::INITIATED,
                        ConcernStatus::PENDING,
                        ConcernStatus::CLOSED,
                    ],
                ],
            ],

            Entity::UPI_MANDATE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'order_id' => [
                    Fetch::LABEL  => 'Order Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'token_id' => [
                    Fetch::LABEL  => 'Token Id',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
            ],

            Entity::REQUEST_LOG => [
                'entity_id'   => [
                    Fetch::LABEL => 'Entity ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'entity_type' => [
                    Fetch::LABEL => 'Entity Type',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'route_name'  => [
                    Fetch::LABEL => 'Route Name',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
            ],

            Entity::MERCHANT_E_INVOICE => [
                'merchant_id' => Fetch::FIELD_MERCHANT_ID,
                'month' => [
                    Fetch::LABEL  => 'Month',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'year' => [
                    Fetch::LABEL  => 'Year',
                    Fetch::TYPE   => Fetch::TYPE_STRING,
                ],
                'type' => [
                    Fetch::LABEL  => 'Type',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        EInvoice\Types::BANKING,
                        EInvoice\Types::PG,
                    ],
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        EInvoice\Status::STATUS_CREATED,
                        EInvoice\Status::STATUS_FAILED,
                        EInvoice\Status::STATUS_GENERATED,
                        EInvoice\Status::STATUS_INITIATED,
                    ]
                ]
            ],

            Entity::CARE_CALLBACK => [
                'merchant_id'  => [
                    Fetch::LABEL => 'Merchant ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'admin_id'  => [
                    Fetch::LABEL => 'Admin ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
                'status' => [
                    Fetch::LABEL  => 'Status',
                    Fetch::TYPE   => Fetch::TYPE_ARRAY,
                    Fetch::VALUES => [
                        'requested',
                        'connected',
                        'failed_to_connect',
                        'spillover',
                        'cancelled',
                        'in_queue',
                    ],
                ],
            ],

            Entity::CARE_CALLBACK_LOG => [
                'callback_id'  => [
                    Fetch::LABEL => 'Callback ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::CARE_CALLBACK_OPERATOR => [

            ],

            Entity::INTERNATIONAL_ENABLEMENT_DETAIL => [
                'merchant_id'  => [
                    Fetch::LABEL => 'Merchant ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],

            Entity::INTERNATIONAL_ENABLEMENT_DOCUMENT => [
                'international_enablement_detail_id'  => [
                    Fetch::LABEL => 'International Enablement Detail ID',
                    Fetch::TYPE  => Fetch::TYPE_STRING,
                ],
            ],
        ];

        //
        // Ensures default type and label against each attribute's config exists.
        //
        // Todos:
        // - Remove at least hundred unnecessary lines from this file
        // - Move this logic to dashbaord to reduce payload size of this request
        //
        foreach ($entities as $entity => & $attributes)
        {
            foreach ($attributes as $attribute => & $config)
            {
                if (is_array($config) === true)
                {
                    $config += [
                        Fetch::LABEL => ucwords(str_replace('_', ' ', $attribute)),
                        Fetch::TYPE  => Fetch::TYPE_STRING,
                    ];
                }
            }
        }

        return $entities;
    }

    protected static function filterSearchFiltersForExternalAdmin($entityType, $searchFilters)
    {
        $entityTypeStudlyCase = studly_case(title_case($entityType));

        $allowedFilters = Validator::getValidationRules("externalAdminFetchMultiple{$entityTypeStudlyCase}Rules");

        $result = [];

        foreach ($searchFilters as $filter => $description)
        {
            if (array_has($allowedFilters, $filter, true) === false)
            {
                continue;
            }

            $result[$filter] = $description;
        }

        return $result;
    }

}
