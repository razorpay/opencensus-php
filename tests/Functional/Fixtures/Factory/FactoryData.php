<?php

namespace RZP\Tests\Functional\Fixtures\Factory;

use Config;
use Eloquent;
use Carbon\Carbon;

use RZP\Models;
use RZP\Models\Contact;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundAccount\Validation as FundAccountValidation;
use RZP\Models\Merchant\MerchantNotificationConfig\Entity as MerchantNotificationConfigEntity;

final class FactoryData
{
    public static function defineEntityFactories($factory, $faker)
    {
        $factory(\RZP\Models\Merchant\Entity::class, [
                                                       'id'                       => $faker->uniqueid,
                                                       'org_id'                   => '100000razorpay',
                                                       'parent_id'                => null,
                                                       'name'                     => $faker->word,
                                                       'email'                    => $faker->email,
                                                       'activated'                => 0,
                                                       'live'                     => 0,
                                                       'pricing_plan_id'          => null,
                                                       'international'            => 0,
                                                       'website'                  => $faker->url,
                                                       'billing_label'            => $faker->word,
                                                       'category'                 => 5399,
                                                       'transaction_report_email' => ['test@razorpay.com'],
                                                       'receipt_email_enabled'    => true,
                                                       'channel'                  => Channel::AXIS,
                                                       'fee_bearer'               => \RZP\Models\Merchant\FeeBearer::PLATFORM,
                                                       'risk_rating'              => 3,
                                                       'invoice_code'             => '123456789011',
                                                       'activated_at'             => time(),
                                                   ]
        );

        $factory(\RZP\Models\Settlement\Transfer\Entity::class, [
            'id'                            => $faker->uniqueid,
            'currency'                      => 'INR',
            'amount'                        => 1000,
            'fee'                           => 10,
            'tax'                           => 4,
            'balance_id'                    => 12324222,
            'transaction_id'                => '12345434565434',
            'merchant_id'                   => '10000000000000',
            'settlement_id'                 => '1234er345thyu6',
            'source_merchant_id'            => '19090928394023',
            'settlement_transaction_id'     => '123564789uy767',
            'updated_at'                    => time(),
            'created_at'                    => time()
        ]);

        $factory(\RZP\Models\Merchant\Document\Entity::class, [
                                                                'id'            => $faker->uniqueid,
                                                                'merchant_id'   => '10000000000000',
                                                                'file_store_id' => 'abcdef12345678',
                                                                'document_type' => 'address_proof_url',
                                                                'entity_type'   => 'merchant',
                                                            ]
        );

        $factory(\RZP\Models\Merchant\Escalations\Entity::class, [
                                                                   'id'            => $faker->uniqueid,
                                                                   'merchant_id'   => '10000000000000',
                                                                   'type'          => 'payment_breach',
                                                                   'milestone'     => 'L1',
                                                                   'amount'        => 500000,
                                                                   'threshold'     => 500000
                                                               ]
        );

        $factory(\RZP\Models\Merchant\Escalations\Actions\Entity::class, [
                                                                           'id'            => $faker->uniqueid,
                                                                           'escalation_id'  => $faker->uniqueid,
                                                                           'action_handler' => 'some handler',
                                                                           'status'         => 'pending'
                                                                       ]
        );

        $factory(\RZP\Models\Merchant\AutoKyc\Escalations\Entity::class, [
                                                                           'id'            => $faker->uniqueid,
                                                                           'merchant_id'       =>  '10000000000000',
                                                                           'escalation_level'  =>  3,
                                                                           'escalation_type'   =>  'soft_limit'
                                                                       ]
        );

        /**
         * Entity data type of merchant_email
         */
        $factory(\RZP\Models\Merchant\Email\Entity::class, [
                                                             'id'                       => $faker->uniqueid,
                                                             'type'                     => 'refund',
                                                             'email'                    => $faker->email,
                                                             'phone'                    => '9732097320',
                                                             'policy'                   => 'tech',
                                                             'url'                      => $faker->url,
                                                             'merchant_id'              => '10000000000000',
                                                             'verified'                 => 0,
                                                             'created_at'               => $faker->timestamp,
                                                             'updated_at'               => $faker->timestamp,
                                                         ]
        );

        $factory(\RZP\Models\Merchant\Product\TncMap\Entity::class, [
                                                             'id'                       => $faker->uniqueid,
                                                             'product_name'             => 'all',
                                                             'status'                   => 'active',
                                                             'created_at'               => $faker->timestamp,
                                                             'updated_at'               => $faker->timestamp,
                                                         ]
        );

        $factory(\RZP\Models\Merchant\Product\Entity::class, [
                'id'                       => $faker->uniqueid,
                'product_name'             => 'payment_gateway',
                'created_at'               => $faker->timestamp,
                'updated_at'               => $faker->timestamp,
            ]
        );

        $factory(\RZP\Models\Merchant\FreshdeskTicket\Entity::class, [
                                                                       'id'            => $faker->uniqueid,
                                                                       'merchant_id'   => '10000000000000',
                                                                       'ticket_id'     => '1',
                                                                       'ticket_details' => '{
                    \'email\': \'sujata@razorpay.com\',
                    \'subject\': \'Reserve Balance test ticket\',
                    \'description\': \'This is a testing ticket for the new ticket API, please ignore.\',
                    \'priority\': 1,
                    \'status\': 2,
                    \'custom_fields\' : {
                        \'cf_requester_category\': \'Prospect\',
                        \'cf_requestor_subcategory\': \'For Reserve balance\'
                    }
                }',
                                                                       'type'            => 'reserve_balance_activate',
                                                                   ]
        );

        $factory(\RZP\Models\Merchant\Referral\Entity::class, [
                                                                'id'                 => $faker->uniqueid,
                                                                'merchant_id'        => '10000000000000',
                                                                'ref_code'           => 'teslacomikejzc',
                                                                'product'            => 'primary',
                                                                'url'                => $faker->url,
                                                                'created_at'         => $faker->timestamp,
                                                                'updated_at'         => $faker->timestamp,
                                                            ]
        );

        $factory(\RZP\Models\Terminal\Entity::class, [
                                                       'id'                        => $faker->uniqueid,
                                                       'merchant_id'               => 'factory:RZP\Models\Merchant\Entity',
                                                       'card'                      => 1,
                                                       'netbanking'                => 0,
                                                       'shared'                    => 1,
                                                       'currency'                  => 'INR',
                                                       'gateway'                   => 'hdfc',
                                                       'gateway_acquirer'          => 'hdfc',
                                                       'gateway_merchant_id'       => $faker->word,
                                                       'gateway_terminal_id'       => $faker->word,
                                                       'gateway_terminal_password' => null,
                                                       'gateway_access_code'       => null,
                                                       'gateway_secure_secret'     => null,
                                                   ]
        );

        $factory(\RZP\Models\Merchant\Invoice\Entity::class, [
                                                               'id'                => $faker->uniqueid,
                                                               'merchant_id'       => '10000000000000',
                                                               'invoice_number'    => $faker->name,
                                                               'month'             => Carbon::today(Timezone::IST)->month,
                                                               'year'              => Carbon::today(Timezone::IST)->year,
                                                               'gstin'             => '29kjsngjk213922',
                                                               'amount'            => 50000,
                                                               'amount_due'        => 0,
                                                               'tax'               => 2200,
                                                           ]
        );

        $factory(\RZP\Models\PaymentLink\Entity::class, [
                                                          'id'          => $faker->uniqueid,
                                                          'receipt'     => '00000000000001',
                                                          'merchant_id' => '10000000000000',
                                                          'currency'    => 'INR',
                                                          'amount'      => 100000,
                                                          'status'      => \RZP\Models\PaymentLink\Status::ACTIVE,
                                                          'title'       => 'Sample title',
                                                          'description' => 'Sample description',
                                                          'notes'       => null,
                                                          'terms'       => null,
                                                      ]
        );

        $factory(\RZP\Models\PaymentLink\PaymentPageItem\Entity::class, [
                                                                          'id'                => $faker->uniqueid,
                                                                          'merchant_id'       => '10000000000000',
                                                                          'mandatory'         => true,
                                                                          'image_url'         => null,
                                                                          'stock'             => null,
                                                                          'min_purchase'      => null,
                                                                          'max_purchase'      => null,
                                                                          'min_amount'        => null,
                                                                          'max_amount'        => null,
                                                                          'quantity_sold'     => 0,
                                                                          'total_amount_paid' => 0,
                                                                      ]
        );

        $factory(\RZP\Models\Merchant\Balance\Entity::class, [
                                                               'id'                        => $faker->uniqueid,
                                                               'merchant_id'               => '10000000000000',
                                                               'type'                      => 'primary',
                                                               'balance'                   => 0,
                                                               'currency'                  => 'INR',
                                                           ]
        );

        $factory(\RZP\Models\Merchant\Balance\BalanceConfig\Entity::class, [
                                                                             'id'                                => $faker->uniqueid,
                                                                             'balance_id'                        => '10000000000000',
                                                                             'type'                              => 'primary',
                                                                             'negative_limit_auto'              => 0,
                                                                             'negative_limit_manual'            => 0,
                                                                         ]
        );

        $factory(\RZP\Models\BankAccount\Entity::class, [
                                                          'id'                        => $faker->uniqueid,
                                                          'merchant_id'               => '10000000000000',
                                                          'entity_id'                 => '10000000000000',
                                                          'type'                      => 'merchant',
                                                          'ifsc_code'                 => 'RZPB0000000',
                                                          'account_number'            => '10010101011',
                                                          'beneficiary_name'          => 'random_name',
                                                          'beneficiary_address1'      => 'address1',
                                                          'beneficiary_address2'      => 'address2',
                                                          'beneficiary_address3'      => 'address3',
                                                          'beneficiary_address4'      => 'address4',
                                                          'beneficiary_city'          => 'new delhi',
                                                          'beneficiary_state'         => 'DE',
                                                          'beneficiary_country'       => 'IN',
                                                          'beneficiary_email'         => 'random@email.com',
                                                          'beneficiary_mobile'        => '9988776655',
                                                          'beneficiary_pin'           => '100000',
                                                      ]
        );

        $factory(\RZP\Models\WalletAccount\Entity::class, [
                                                            'id'                        => $faker->uniqueid,
                                                            'merchant_id'               => '10000000000000',
                                                            'entity_id'                 => '10000000000000',
                                                            'phone'                     => '+918124632237',
                                                            'provider'                  => 'amazonpay',
                                                            'email'                     => 'test@gmail.com',
                                                            'name'                      => 'test',
                                                            'created_at'                => $faker->timestamp,
                                                            'updated_at'                => $faker->timestamp,
                                                        ]
        );

        $factory(\RZP\Models\Card\Entity::class, [
                                                   'id'                => $faker->uniqueid,
                                                   'merchant_id'       => '10000000000000',
                                                   'name'              => $faker->word,
                                                   'network'           => 'Visa',
                                                   'expiry_month'      => 01,
                                                   'expiry_year'       => 2024,
                                                   'type'              => 'debit',
                                                   'country'           => 'IN',
                                                   'last4'             => 1111,
                                                   'iin'               => 411111,
                                                   'length'            => '16',
                                                   'issuer'            => 'hdfc',
                                                   'emi'               => false,
                                                   'international'     => false,
                                                   'vault_token'       => 'NDExMTExMTExMTExMTExMQ==',
                                                   'vault'             => 'rzpvault',
                                                   'trivia'            => '',
                                               ]
        );

        $factory(\RZP\Models\Key\Entity::class, [
                                                  'id' => '1DP5mmOlF5G5ag',
                                                  'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
                                                  'secret' => 'eyJpdiI6InFjMFFDMkszYzRLeU5UZ2VnajhoMEE9PSIsInZhbHVlIjoiZzY3c0Zkd0VMQkE0cjU1T3hVQXZSSzBub1h4aHJkaThBRlwvZWJwMm5wdkE9IiwibWFjIjoiZmEyZWM5MzIyODBjMmU3N2RhMmQ2ZjA2ODA3OTk5ZjI0ZTY2ZTQ3ZGNiYzJjOTE4ODc5ZWNkYzY4MGQwYTZhZiJ9',
                                                  'expired_at' => null,
                                              ]
        );

        $factory(\RZP\Models\Payment\Entity::class, [
                                                      'id' => $faker->uniqueid,
                                                      'merchant_id' => '10000000000000',
                                                      'method' => 'card',
                                                      'card_id' => null,
                                                      'bank' => null,
                                                      'amount' => 1000000,
                                                      'base_amount' => 1000000,
                                                      'amount_authorized' => 1000000,
                                                      'amount_refunded' => 0,
                                                      'currency' => 'INR',
                                                      'status' => 'created',
                                                      'refund_status' => null,
                                                      'contact' => $faker->randomElement(['+918199078685', '+17813924010', '+33751253819', '+919416544332', '+447706696711', '67332323', '+9613688111']),
                                                      'notes' => null,
                                                      'fee' => 0,
                                                      'gateway' => 'hdfc',
                                                      'email' => $faker->email,
                                                      'auto_captured' => 0,
                                                      'captured_at' => null,
                                                      'reference1' => null,
                                                      'transaction_id' => null,
                                                      'on_hold' => 0,
                                                      'verify_at'  => $faker->timestamp,
                                                      'created_at' => $faker->timestamp,
                                                      'updated_at' => $faker->timestamp,
                                                  ]
        );

        $factory(\RZP\Models\Payment\Refund\Entity::class, [
                                                             'id' => $faker->uniqueid,
                                                             'payment_id' => 'factory:\RZP\Models\Payment\Entity',
                                                             'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
                                                             'amount' => 100,
                                                             'currency' => 'INR',
                                                             'base_amount' => 100,
                                                             'notes' => null,
                                                             'transaction_id' => null,
                                                         ]
        );

        $factory(\RZP\Models\Pricing\Entity::class, [
                                                      'id' => $faker->uniqueid,
                                                      'plan_id' => '1ycviEdCgurrFI',
                                                      'plan_name' => 'testFixturePlan',
                                                      'feature' => 'payment',
                                                      'payment_method' => 'card',
                                                      'payment_method_type' => 'debit',
                                                      'payment_network' => 'VISA',
                                                      'payment_issuer' => 'hdfc',
                                                      'percent_rate' => 1000,
                                                      'fixed_rate' => 10000,
                                                      'org_id'    => '100000razorpay',
                                                  ]
        );

        $factory(\RZP\Models\Pricing\Entity::class, [
                                                      'id'                  => $faker->uniqueid,
                                                      'plan_id'             => '1ycviEdCgurrFI',
                                                      'plan_name'           => 'testFixturePlan',
                                                      'feature'             => 'payment',
                                                      'type'                => 'pricing',
                                                      'payment_method'      => 'card',
                                                      'payment_method_type' => 'credit',
                                                      'payment_network'     => 'VISA',
                                                      'payment_issuer'      => 'ICIC',
                                                      'percent_rate'        => 1000,
                                                      'fixed_rate'          => 10000,
                                                      'org_id'              => '100000razorpay',
                                                  ]
        );

        $factory(\RZP\Models\Transaction\Entity::class, [
                                                          'id' => $faker->uniqueid,
                                                          'entity_id' => $faker->uniqueid,
                                                          'type' => 'payment',
                                                          // 'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
                                                          'amount' => $faker->randomNumber,
                                                          'fee' => $faker->randomNumber,
                                                          'pricing_rule_id' => null,
                                                          'currency' => 'INR',
                                                          'credit' => $faker->randomNumber,
                                                          'debit' => 0,
                                                          'balance' => $faker->randomNumber,
                                                          'gateway_fee' => null,
                                                          'on_hold'   => 0,
                                                          'gratis' => false,
                                                          'channel' => Channel::AXIS,
                                                          'settled' => 0,
                                                      ]
        );

        $factory(\RZP\Models\Settlement\Entity::class, [
                                                         'id' => $faker->uniqueid,
                                                         'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
                                                         'amount' => $faker->randomNumber(4),
                                                         'status' => 'created',
                                                         // 'transaction_id' => 'factory:\RZP\Models\Transaction\Entity',
                                                         'fees' => $faker->randomNumber(2),
                                                         'channel' => Channel::AXIS,
                                                         'failure_reason' => null,
                                                         'return_utr' => null,
                                                     ]
        );

        $factory(\RZP\Models\FundTransfer\Attempt\Entity::class, [
                                                                   'id' => $faker->uniqueid,
                                                                   // 'source_id' => 'factory:\RZP\Models\Settlement\Entity',
                                                                   'source_type' => 'settlement',
                                                                   'status' => 'initiated',
                                                                   'version' => 'V3',
                                                               ]
        );

        $factory(\RZP\Models\FundTransfer\Batch\Entity::class, [
                                                                 'id'               => $faker->uniqueid,
                                                                 'date'             => Carbon::today(Timezone::IST)->timestamp,
                                                                 'channel'          => Channel::AXIS,
                                                                 'amount'           => $faker->randomNumber(4),
                                                                 'processed_amount' => 0,
                                                                 'processed_count'  => 0,
                                                                 'fees'             => $faker->randomNumber(2),
                                                                 'api_fee'          => $faker->randomNumber(2),
                                                                 'gateway_fee'      => $faker->randomNumber(2),
                                                                 'urls'             => $faker->sentence,
                                                                 'initiated_at'     => Carbon::today(Timezone::IST)->timestamp + 10,
                                                             ]
        );

        $factory(\RZP\Models\Adjustment\Entity::class, [
                                                         'id' => $faker->uniqueid,
                                                         'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
                                                         'amount' => $faker->randomNumber,
                                                         'currency' => 'INR',
                                                         'channel' => Channel::AXIS,
                                                         'description' => $faker->text,
                                                         'transaction_id' => 'factory:RZP\Models\Transaction\Entity',
                                                         'settlement_id' => 'factory:RZP\Models\Settlement\Entity'
                                                     ]
        );

        $factory(\RZP\Models\CreditTransfer\Entity::class, [
                'id' => $faker->uniqueid,
                'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
                'amount' => $faker->randomNumber,
                'currency' => 'INR',
                'channel' => Channel::AXIS,
                'description' => $faker->text
            ]
        );

        $factory(\RZP\Gateway\Hdfc\Entity::class, [
                                                    'id' => $faker->randomNumber(6),
                                                    'payment_id' => null,
                                                    'refund_id' => null,
                                                    'gateway_transaction_id' => $faker->hdfcPaymentId,
                                                    'action' => 4,
                                                    'amount' => $faker->randomNumber(2),
                                                    'enroll_result' => 2,
                                                    'status' => 'authorized',
                                                    'result' => 'APPROVED',
                                                    'eci' => 6,
                                                    'auth' => 999999,
                                                    'ref' => $faker->hdfcRef,
                                                    'avr' => 'N',
                                                    'postdate' => $faker->hdfcPostDate,
                                                ]
        );

        $factory(\RZP\Gateway\Enach\Base\Entity::class, [
                                                          'id' => $faker->randomNumber(6),
                                                          'payment_id' => null,
                                                          'refund_id' => null,
                                                          'acquirer' => 'ratn',
                                                          'action' => 'authorize',
                                                          'bank' => 'UTIB',
                                                          'amount' => $faker->randomNumber(2),
                                                          'status' => 'success',
                                                          'signed_xml' => '<xml>'
                                                      ]
        );

        $factory(\RZP\Gateway\Atom\Entity::class, [
                                                    'id' => $faker->randomNumber(6),
                                                    'gateway_payment_id' => 'factory:\RZP\Models\Payment\Entity',
                                                    'token' => $faker->token,
                                                    'success' => $faker->boolean,
                                                    'callback_data' => null,
                                                    'bank_name' => '\RZP',
                                                    'bank_transaction_id' => $faker->randomNumber(6),
                                                ]
        );

        $factory(\RZP\Models\Card\IIN\Entity::class, [
                                                       'iin' => 411111,
                                                       'category' => null,
                                                       'network' => 'Visa',
                                                       'type' => 'credit',
                                                       'country' => 'IN',
                                                       'issuer' => 'SBIN',
                                                       'trivia' => $faker->sentence,
                                                   ]
        );

        $factory(\RZP\Models\Card\TokenisedIIN\Entity::class, [
                                                        'iin' => 411111,
                                                        'high_range' => '111111111',
                                                        'low_range' => '111111111',
                                                        'token_iin_length' => 9,
                                                    ]
        );

        $factory(\RZP\Models\Merchant\Methods\Entity::class, [
                                                               'merchant_id'       => '10000000000000',
                                                               'credit_card'       => '1',
                                                               'debit_card'        => '1',
                                                               'disabled_banks'    => '[]',
                                                               'paytm'             => '0',
                                                           ]
        );

        $factory(\RZP\Models\Merchant\Webhook\Entity::class, [
                                                               'merchant_id' => '10000000000000',
                                                               'url' => $faker->url,
                                                               'events' => [
                                                                   'payment.authorized' => '1',
                                                               ],
                                                               'active' => true,
                                                           ]
        );

        $factory(\RZP\Models\Address\Entity::class, [
                                                      'line1'         => 'some line one',
                                                      'line2'         => 'some line two',
                                                      'city'          => 'Bangalore',
                                                      'state'         => 'Karnataka',
                                                      'zipcode'       => '560078',
                                                      'country'       => 'in',
                                                      'type'          => 'shipping_address',
                                                      'primary'       => true,
                                                      'entity_id'     => '100000customer',
                                                      'entity_type'   => 'customer',
                                                  ]
        );

        $factory(\RZP\Models\Emi\Entity::class, [
                                                  'id'               => '10101010101010',
                                                  'merchant_id'      => '100000Razorpay',
                                                  'duration'         => 9,
                                                  'rate'             => 1200,
                                                  'bank'             => 'HDFC',
                                                  'methods'          => 'card',
                                                  'min_amount'       => 500000,
                                                  'merchant_payback' => 518
                                              ]
        );

        $factory(\RZP\Models\Merchant\EmiPlans\Entity::class, [
                                                                'id'               => $faker->uniqueid,
                                                                'merchant_id'      => '10000000000000',
                                                                'emi_plan_id'      => '10101010101010',
                                                                'created_at'       => $faker->timestamp,
                                                                'updated_at'       => $faker->timestamp,
                                                            ]
        );

        $factory(\RZP\Models\Order\Entity::class, [
                                                    'id'              => $faker->uniqueid,
                                                    'merchant_id'     => '10000000000000',
                                                    'partial_payment' => 0,
                                                    'amount'          => 1000000,
                                                    'amount_paid'     => 0,
                                                    'currency'        => 'INR',
                                                    'status'          => 'created',
                                                    'receipt'         => $faker->uniqueid,
                                                    'payment_capture' => false,
                                                    'notes'           => null,
                                                    'attempts'        => 0,
                                                    'created_at'      => $faker->timestamp,
                                                    'updated_at'      => $faker->timestamp,
                                                ]
        );

        $factory(\RZP\Models\OrderOutbox\Entity::class, [
                'id'              => $faker->uniqueid,
                'order_id'        => $faker->uniqueid,
                'merchant_id'     => '10000000000000',
                'event_name'      => 'order_paid_event',
                'payload'         => '{"amount_paid":1000,"status":"paid"}',
                'is_deleted'      => 0,
                'retry_count'     => 0,
                'created_at'      => $faker->timestamp,
                'updated_at'      => $faker->timestamp,
            ]
        );

       $factory(\RZP\Models\Order\OrderMeta\Entity::class, [
           'id'              => $faker->uniqueid,
           'type'            => 'tax_invoice',
           'value'           => [
               'business_gstin' => '123456789012345',
               'gst_amount'     =>  10000,
               'supply_type'    => 'intrastate',
               'cess_amount'    =>  12500,
               'customer_name'  => 'Test Customer',
               'number'         => '1234',
               "date"           => "1589994898",
           ],
         ]
       );

        $factory(\RZP\Models\Merchant\CheckoutDetail\Entity::class, [
            'id'              => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Item\Entity::class, [
                                                   'id'            => '1000000000item',
                                                   'merchant_id'   => '10000000000000',
                                                   'name'          => 'Some item name',
                                                   'description'   => 'Some item description',
                                                   'type'          => 'invoice',
                                                   'amount'        => 100000,
                                                   'currency'      => 'INR',
                                                   'unit'          => null,
                                                   'tax_inclusive' => false,
                                                   'tax_id'        => null,
                                                   'tax_group_id'  => null,
                                               ]
        );

        $factory(\RZP\Models\Invoice\Entity::class, [
                                                      'id'                       => '1000000invoice',
                                                      'merchant_id'              => '10000000000000',
                                                      'customer_id'              => '100000customer',
                                                      'order_id'                 => '100000000order',
                                                      'customer_email'           => 'test@razorpay.com',
                                                      'customer_contact'         => '1234567890',
                                                      'customer_name'            => 'test',
                                                      'customer_billing_addr_id' => null,
                                                      'short_url'                => 'http://bitly.dev/2eZ11Vn',
                                                      'type'                     => 'invoice',
                                                      'view_less'                => 1,
                                                      'sms_status'               => 'sent',
                                                      'email_status'             => 'sent',
                                                      'notes'                    => null,
                                                      'status'                   => 'issued',
                                                      'date'                     => null,
                                                      'issued_at'                => null,
                                                      'expired_at'               => null,
                                                      'due_by'                   => $faker->timestamp(2),
                                                      'scheduled_at'             => $faker->timestamp,
                                                      'expire_by'                => $faker->timestamp(2),
                                                      'amount'                   => 100000,
                                                      'tax_amount'               => 0,
                                                      'gross_amount'             => 100000,
                                                      'currency'                 => 'INR',
                                                  ]
        );

        $factory(\RZP\Models\LineItem\Entity::class, [
                                                       'id'           => '100000lineitem',
                                                       'merchant_id'  => '10000000000000',
                                                       'entity_id'    => '1000000invoice',
                                                       'entity_type'  => 'invoice',
                                                       'item_id'      => '1000000000item',
                                                       'name'         => 'Some item name',
                                                       'description'  => 'Some item description',
                                                       'amount'       => 100000,
                                                       'currency'     => 'INR',
                                                       'quantity'     => 1,
                                                       'gross_amount' => 100000,
                                                       'tax_amount'   => 0,
                                                       'net_amount'   => 100000,
                                                   ]
        );

        $factory(\RZP\Gateway\FirstData\Entity::class, [
                                                         'id' => '0',
                                                         'action' => 'authorize',
                                                         'payment_id' => null,
                                                         'amount' => null,
                                                         'received' => true,
                                                     ]
        );

        $factory(\RZP\Gateway\Netbanking\Base\Entity::class, [
                                                               'id'     => '0',
                                                               'action' => 'authorize',
                                                               'amount' => 1000,
                                                           ]
        );

        $factory(\RZP\Gateway\Upi\Base\Entity::class, [
                                                        'id'                 => '0',
                                                        'action'             => 'authorize',
                                                        'amount'             => 50000,
                                                        'acquirer'           => 'SBIN',
                                                        'gateway_payment_id' => 99999999999
                                                    ]
        );

        $factory(\RZP\Models\Customer\Entity::class, [
                                                       'id'          => $faker->uniqueid,
                                                       'merchant_id' => '10000000000000',
                                                       'name'        => 'name',
                                                       'contact'     => '9988776655',
                                                       'notes'       => null,
                                                   ]
        );

        $factory(\RZP\Models\Customer\Token\Entity::class, [
                                                             'id'          => $faker->uniqueid,
                                                             'merchant_id' => '10000000000000',
                                                             'customer_id' => '100000customer',
                                                             'wallet'      => 'paytm',
                                                             'method'      => 'netbanking',
                                                             'bank'        => 'ICIC',
                                                             'card_id'     => null,
                                                             'recurring'   => false,
                                                             'used_count'  => 0,
                                                             'token'       => $faker->uniqueid,
                                                             'used_at'     => null,
                                                         ]
        );

        $factory(\RZP\Models\Customer\AppToken\Entity::class, [
                                                                'id' => $faker->uniqueid,
                                                                'customer_id'  => '10000gcustomer',
                                                                'device_token' => 'test',
                                                                'merchant_id'  => '10000000000000'
                                                            ]
        );

        $factory(\RZP\Models\Customer\GatewayToken\Entity::class, [
                                                                    'id' => '10gatewaytoken',
                                                                    'token_id' => '10000custgcard',
                                                                    'terminal_id' => '1RecurringTerm',
                                                                    'merchant_id' => '10000000000000'
                                                                ]
        );

        $factory(\RZP\Models\Merchant\Credits\Entity::class, [
                                                               'id'            => $faker->uniqueid,
                                                               'merchant_id'   => '10000000000000',
                                                               'value'         => 150,
                                                               'type'          => 'amount',
                                                               'campaign'      => 'silent-ads',
                                                           ]
        );

        $factory(\RZP\Models\Merchant\Credits\Transaction\Entity::class, [
                'id'            => $faker->uniqueid,
                'entity_id'     => '12345678912345',
                'entity_type'   => 'payout',
                'credits_used'  => 50,
            ]
        );

        $factory(\RZP\Models\Transaction\FeeBreakup\Entity::class, [
                                                                     'id'            => $faker->uniqueid,
                                                                 ]
        );

        $factory(\RZP\Models\Batch\Entity::class, [
                                                    'id'              => $faker->uniqueid,
                                                    'merchant_id'     => '10000000000000',
                                                    'status'          => 'created',
                                                    'processing'      => 0,
                                                    'total_count'     => 0,
                                                    'processed_count' => 0,
                                                    'success_count'   => 0,
                                                    'failure_count'   => 0,
                                                    'attempts'        => 0,
                                                    'created_at'      => $faker->timestamp,
                                                    'updated_at'      => $faker->timestamp,
                                                ]
        );

        $factory(\RZP\Gateway\Wallet\Base\Entity::class, [
                                                           'id'            => $faker->randomNumber(4),
                                                           'amount'        => '0',
                                                           'contact'       => '9918899029',
                                                           'email'         => 'a@b.com',
                                                       ]
        );

        $factory(\RZP\Models\Feature\Entity::class, [
                                                      'id'                => $faker->uniqueid,
                                                      'entity_type'       => 'merchant'
                                                  ]
        );

        // Admin Roles related fixtures
        $factory(\RZP\Models\Admin\Org\Entity::class, [
                                                        'id'            => $faker->uniqueid,
                                                        'allow_sign_up' => false,
                                                        'email_domains' => 'razorpay.com,rzp.io',
                                                        'email'         => $faker->rzpEmail,
                                                        'from_email'    => $faker->email,
                                                        'display_name'  => 'Razorpay',
                                                        'business_name' => 'Razorpay Software Pvt Ltd',
                                                        'auth_type'     => 'password',
                                                        'custom_code'   => $faker->name,
                                                    ]
        );

        $factory(\RZP\Models\Admin\Org\FieldMap\Entity::class, [
                                                                 'id'            => $faker->uniqueid,
                                                             ]
        );

        $factory(\RZP\Models\Admin\Org\Hostname\Entity::class, [
                                                                 'id'            => $faker->uniqueid,
                                                                 'org_id'        => $faker->uniqueid,
                                                                 'hostname'      => $faker->rzpSubdomain,
                                                             ]
        );

        $factory(\RZP\Models\Admin\Permission\Entity::class, [
                                                               'id'            => $faker->uniqueid,
                                                               'name'          => $faker->name,
                                                               'category'      => 'test category',
                                                               'description'   => 'test description',
                                                           ]
        );

        $factory(\RZP\Models\Admin\Role\Entity::class, [
                                                         'id' => $faker->uniqueid,
                                                         'name' => 'manager',
                                                         'description' => 'Manager of roles',
                                                     ]
        );

        $factory(\RZP\Models\Admin\Group\Entity::class, [
                                                          'id'          => $faker->uniqueid,
                                                          'name'        => $faker->name,
                                                          'description' => 'This is a test group',
                                                      ]
        );

        $factory(\RZP\Models\Admin\Admin\Entity::class, [
                                                          'id'                 => $faker->uniqueid,
                                                          'org_id'             => 'factory:\RZP\Models\Admin\Org\Entity',
                                                          'name'               => 'test admin',
                                                          'email'              => $faker->rzpEmail,
                                                          'username'           => 'harshil',
                                                          'password'           => 'test123456',
                                                          'remember_token'     => 'yes',
                                                          'oauth_access_token' => 'oauth123',
                                                          'oauth_provider_id'  => 'google',
                                                          'employee_code'      => 'rzp_1',
                                                          'branch_code'        => 'krmgla',
                                                          'supervisor_code'    => 'shk',
                                                          'location_code'      => '560030',
                                                          'department_code'    => 'tech',
                                                          'created_at'         => $faker->timestamp,
                                                          'updated_at'         => $faker->timestamp
                                                      ]
        );

        $factory(\RZP\Models\Admin\Admin\Token\Entity::class, [
                                                                'id'            => $faker->uniqueid,
                                                                'admin_id'      => 'RazorpayUserId',
                                                                'created_at'    => $faker->timestamp,
                                                                'expires_at'    => Carbon::now()->addDays(30)->getTimestamp(),
                                                            ]
        );

        $factory(\RZP\Models\Admin\AdminLead\Entity::class, [
                                                              'id'            => $faker->uniqueid,
                                                              'admin_id'      => 'RazorpayUserId',
                                                              'org_id'        => '100000razorpay',
                                                              'token'         => $faker->name(30),
                                                              'email'         => 'admin.lead@razorpay.com',
                                                          ]
        );

        $factory(\RZP\Models\Merchant\Detail\Entity::class, [
                                                              'merchant_id'   => $faker->uniqueid,
                                                              'contact_email' => $faker->email,
                                                          ]
        );
        $factory(\RZP\Models\Merchant\BusinessDetail\Entity::class, [
                                                                     'id'            => $faker->uniqueid,
                                                                     'merchant_id' => '10000000000000'
                                                                 ]
        );
        $factory(\RZP\Models\Merchant\AvgOrderValue\Entity::class, [
                'id'            => $faker->uniqueid,
                'merchant_id' => '10000000000000'
            ]
        );
        $factory(\RZP\Models\Merchant\M2MReferral\Entity::class, [
                                                                      'id'            => $faker->uniqueid,
                                                                      'merchant_id' => '10000000000000',
                                                                      'status'      => 'signup'
                                                                  ]
        );
        $factory(\RZP\Models\AMPEmail\Entity::class, [
                                                                   'id'             => $faker->uniqueid,
                                                                   'entity_id'      => '10000000000000',
                                                                   'entity_type'    => 'merchant',
                                                                   'vendor'         => 'mailmodo',
                                                                   'template'         => 'l1',
                                                                   'status'         => 'initiated'
                                                               ]
        );
        $factory(\RZP\Models\Merchant\Stakeholder\Entity::class, [
                                                                   'id'            => $faker->uniqueid,
                                                                   'merchant_id'   => '10000000000000',
                                                               ]
        );

        $factory(\RZP\Models\Merchant\Website\Entity::class, [
                                                           'id'            => $faker->uniqueid,
                                                           'merchant_id'   => '10000000000000',
                                                           "merchant_website_details" => [
                                                               "contact_us" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/contact_us'
                                                               ],
                                                               "terms" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/terms'
                                                               ],
                                                               "refund" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/refund'
                                                               ],
                                                               "privacy" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/contact_us'
                                                               ],
                                                               "shipping" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/contact_us'
                                                               ],
                                                               "pricing" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/contact_us'
                                                               ],
                                                               "cancellation" => [
                                                                   "section_status" => 3,
                                                                   "status"         => "submitted",
                                                                   "published_url"  => 'https://sme-dashboard.dev.razorpay.in/policy/K6G5sXGcqym5OQ/contact_us'
                                                               ],
                                                           ],
                                                           'grace_period' => 1,
                                                       ]
        );

        $factory(\RZP\Models\Merchant\VerificationDetail\Entity::class, [
                                                                          'id'            => $faker->uniqueid,
                                                                          'merchant_id'   => '10000000000000',
                                                                      ]
        );

        $factory(\RZP\Models\ClarificationDetail\Entity::class, [
                                                                          'id'            => $faker->uniqueid,
                                                                          'merchant_id'   => '10000000000000',
                                                                      ]
        );

        $factory(\RZP\Models\Merchant\Consent\Entity::class, [
                                                               'id'            => 'KdSCny9TA9OrmI',
                                                               'merchant_id'   => '10000000000000',
                                                               'user_id'       => 'KbA4mhZRhV3RBq',
                                                               'metadata'      => ["ip_address" => "", "ufh_file_id" => "file_123"],
                                                               'status'        => 'failed',
                                                               'request_id'    => 'KdRvpX6ffYF7yG',
                                                               'details_id'    => 'KdwZeHbUYIqVnW',
                                                               'consent_for'   => 'L2_Terms and Conditions',
                                                               'retry_count'   => 1,
                                                               'audit_id'      => 'cnsudhbcsdk',
                                                               'created_at'    => $faker->timestamp,
                                                               'updated_at'    => $faker->timestamp,
                                                           ]
        );

        $factory(\RZP\Models\Merchant\Consent\Details\Entity::class, [
                                                                'id'          => $faker->uniqueid,
                                                                'url'           => 'https://razorpay.com/terms/',
                                                                'created_at'    => $faker->timestamp,
                                                                'updated_at'    => $faker->timestamp,
                                                           ]
        );

        $factory(\RZP\Models\Merchant\Website\Entity::class, [
                                                               'id'            => $faker->uniqueid,
                                                           ]
        );

        $factory(\RZP\Models\User\Entity::class, [
                                                   'id'         => $faker->uniqueid,
                                                   'name'       => $faker->word,
                                                   'email'      => $faker->email,
                                                   'password'  => $faker->word,
                                                   'created_at' => $faker->timestamp,
                                                   'updated_at' => $faker->timestamp,
                                               ]
        );

        $factory(\RZP\Models\DeviceDetail\Entity::class, [
                'id'            => $faker->uniqueid,
                'merchant_id'   => '10000000000000',
                'user_id'       => 'MerchantUser01',
            ]
        );

        $factory(\RZP\Models\Merchant\MerchantUser\Entity::class, [
                'merchant_id'   => '10000000000000',
                'user_id'       => 'MerchantUser01',
                'role'          => 'owner',
            ]
        );

        $factory(\RZP\Models\Customer\Balance\Entity::class, [
                                                               'customer_id'   => 'factory:RZP\Models\Customer\Entity',
                                                               'merchant_id'   => '10000000000000',
                                                               'balance'       => 0,
                                                               'daily_usage'   => 0,
                                                               'weekly_usage'  => 0,
                                                               'monthly_usage' => 0,
                                                               'max_balance'   => 2000000,
                                                           ]
        );

        $factory(\RZP\Models\Customer\Transaction\Entity::class, [
                                                                   'id'                => $faker->uniqueid,
                                                                   'merchant_id'       => '10000000000000',
                                                                   'status'            => 'transferred',
                                                                   'amount'            => 100,
                                                                   'debit'             => 100,
                                                                   'credit'            => 10,
                                                                   'balance'           => 0,
                                                                   'description'       => 'NA',
                                                               ]
        );

        $factory(\RZP\Models\Offer\Entity::class, [
                                                    'id'        => $faker->uniqueid,
                                                    'active'    => true,
                                                    'terms'     => 'Terms and Condition'
                                                ]
        );

        $factory(\RZP\Models\Offer\EntityOffer\Entity::class, [
                                                            ]
        );

        $factory(\RZP\Models\Plan\Entity::class, [
                                                   'id'                => '1000000000plan',
                                                   'merchant_id'       => '10000000000000',
                                                   'period'            => 'monthly',
                                                   'interval'          => 2,
                                                   'item_id'           => '1000000000item',
                                                   // 'schedule_id'       => null,
                                                   'notes'             => null,
                                               ]
        );

        $factory(\RZP\Models\Plan\Subscription\Entity::class, [
                                                                'merchant_id'   => '10000000000000',
                                                                'customer_id'   => '100000customer',
                                                                'status'        => 'created',
                                                                'quantity'      => 1,
                                                                'total_count'   => 4,
                                                                'notes'         => null,
                                                            ]
        );

        $factory(\RZP\Models\Plan\Subscription\Addon\Entity::class, [
                                                                      'merchant_id'       => '10000000000000',
                                                                      'item_id'           => '1000000000item',
                                                                      'invoice_id'        => '1000000invoice',
                                                                      'subscription_id'   => '10subscription',
                                                                  ]
        );

        $factory(\RZP\Models\Device\Entity::class, [
                                                     'id'                 => $faker->uniqueid,
                                                     'merchant_id'        => '10000000000000',
                                                     'type'               => 'android',
                                                     'os'                 => 'android',
                                                     'os_version'         => '5.2.3',
                                                     'imei'               => '98765432123456',
                                                     'challenge'          => 'challenge_value',
                                                     'package_name'       => 'com.razorpay.sample',
                                                     'status'             => 'created',
                                                     'verification_token' => $faker->sha256,
                                                     'upi_token'          => 'upi_auth_token',
                                                     'auth_token'         => $faker->sha256,
                                                     'verified_at'        => null,
                                                     'registered_at'      => $faker->timestamp,
                                                 ]
        );

        $factory(\RZP\Models\Vpa\Entity::class, [
                                                  'id'                 => $faker->uniqueid,
                                                  'username'           => $faker->word,
                                                  'handle'             => 'razorpay',
                                                  'merchant_id'        => '10000000000000',
                                                  'created_at'         => $faker->timestamp,
                                                  'updated_at'         => $faker->timestamp,
                                              ]
        );

        $factory(\RZP\Models\P2p\Entity::class, [
                                                  'id'                 => $faker->uniqueid,
                                                  'username'           => $faker->word,
                                                  'handle'             => 'razorpay',
                                                  'bank_account_id'    => 'factory:RZP\Models\BankAccount\Entity',
                                                  'customer_id'        => '100000customer',
                                                  'created_at'         => $faker->timestamp,
                                                  'updated_at'         => $faker->timestamp,
                                              ]
        );

        $factory(\RZP\Models\Payout\Entity::class, [
                                                     'id'                 => $faker->uniqueid,
                                                     'customer_id'        => '100000customer',
                                                     'method'             => 'fund_transfer',
                                                     'destination_id'     => '1000000lcustba',
                                                     'destination_type'   => 'dummy',
                                                     'purpose'            => 'refund',
                                                     'amount'             => 100,
                                                     'currency'           => 'INR',
                                                     'merchant_id'        => '10000000000000',
                                                     'balance_id'         => '10000000000000',
                                                     'status'             => 'created',
                                                     'channel'            => Channel::AXIS,
                                                     'created_at'         => $faker->timestamp,
                                                     'updated_at'         => $faker->timestamp,
                                                 ]
        );

        $factory(\RZP\Models\Transfer\Entity::class, [
                                                       'id'                 => $faker->uniqueid,
                                                       'source_type'        => 'payment',
                                                       'to_type'            => 'merchant',
                                                       'amount'             => 200,
                                                       'currency'           => 'INR',
                                                       'amount_reversed'    => 0,
                                                       'notes'              => null,
                                                       'on_hold'            => 0,
                                                       'on_hold_until'      => null,
                                                       'merchant_id'        => '10000000000000',
                                                       'created_at'         => $faker->timestamp,
                                                       'updated_at'         => $faker->timestamp,
                                                   ]
        );

        $factory(\RZP\Models\Reversal\Entity::class, [
                                                       'id'                 => $faker->uniqueid,
                                                       'amount'             => 200,
                                                       'currency'           => 'INR',
                                                       'notes'              => null,
                                                       'merchant_id'        => '10000000000000',
                                                       'created_at'         => $faker->timestamp,
                                                       'updated_at'         => $faker->timestamp,
                                                   ]
        );

        $factory(\RZP\Models\Schedule\Entity::class, [
                                                       'id'                => $faker->uniqueid,
                                                       'merchant_id'       => '100000Razorpay',
                                                       'name'              => 'Basic T3',
                                                       'period'            => 'daily',
                                                       'interval'          => 1,
                                                       'delay'             => 3,
                                                       'hour'              => 5,
                                                       'type'              => 'settlement',
                                                       'org_id'            => \RZP\Tests\Functional\Fixtures\Entity\Org::RZP_ORG,
                                                   ]
        );

        $factory(\RZP\Models\Schedule\Task\Entity::class, [
                                                            'id'                => $faker->randomNumber(6),
                                                            'merchant_id'       => '10000000000000',
                                                            'entity_id'         => '10000000000000',
                                                            'entity_type'       => 'merchant',
                                                            'type'              => 'settlement',
                                                            'method'            => null,
                                                            'schedule_id'       => 'factory:RZP\Models\Schedule\Entity',
                                                            'next_run_at'       => 1451604600,
                                                        ]
        );

        $factory(\RZP\Models\Gateway\Downtime\Entity::class, [
                                                               'id'         => $faker->uniqueid,
                                                               'created_at' => $faker->timestamp,
                                                               'updated_at' => $faker->timestamp,
                                                           ]
        );

        $factory(\RZP\Models\Gateway\Rule\Entity::class, [
                                                           'id'         => $faker->uniqueid,
                                                           'min_amount' => 0,
                                                           'created_at' => $faker->timestamp,
                                                           'updated_at' => $faker->timestamp
                                                       ]
        );

        $factory(\RZP\Models\Tax\Entity::class, [
                                                  'id'          => $faker->uniqueid,
                                                  'merchant_id' => '10000000000000',
                                                  'name'        => 'Sample tax',
                                                  'rate_type'   => 'percentage',
                                                  'rate'        => 1000,
                                                  'created_at'  => $faker->timestamp,
                                                  'updated_at'  => $faker->timestamp,
                                                  'deleted_at'  => null,
                                              ]
        );

        $factory(\RZP\Models\Tax\Group\Entity::class, [
                                                        'id'          => $faker->uniqueid,
                                                        'merchant_id' => '10000000000000',
                                                        'name'        => 'Sample tax group',
                                                        'created_at'  => $faker->timestamp,
                                                        'updated_at'  => $faker->timestamp,
                                                        'deleted_at'  => null,
                                                    ]
        );

        $factory(\RZP\Models\Promotion\Entity::class, [
                                                        'id'          => $faker->uniqueid
                                                    ]
        );

        $factory(\RZP\Models\Coupon\Entity::class, [
                                                     'id'          => $faker->uniqueid
                                                 ]
        );

        $factory(\RZP\Models\Merchant\Promotion\Entity::class, [
                                                                 'id'          => $faker->uniqueid,
                                                             ]
        );

        $factory(\RZP\Models\FileStore\Entity::class, [
                                                        'id'          => $faker->uniqueid,
                                                        'merchant_id' => '10000000000000',
                                                        'type'        => 'batch_input',
                                                        'entity_type' => 'batch',
                                                        'extension'   => 'xlsx',
                                                        'mime'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                        'size'        => 10000,
                                                        'name'        => 'xyz.xlsx',
                                                        'store'       => 's3',
                                                        'location'    => 'xyz',
                                                        'bucket'      => 'rzp-test-bucket',
                                                        'region'      => 'us-east-1',
                                                        'created_at'  => $faker->timestamp,
                                                        'updated_at'  => $faker->timestamp,
                                                    ]
        );

        $factory(\RZP\Models\Invitation\Entity::class, [
                                                         'id'                       => $faker->randomNumber(6),
                                                         'email'                    => $faker->email,
                                                         'merchant_id'              => $faker->uniqueid,
                                                         'role'                     => 'manager',
                                                         'token'                    => $faker->name(30),
                                                     ]
        );

        $factory(\RZP\Models\Dispute\Reason\Entity::class, [
                                                             'id'                  => $faker->uniqueid,
                                                             'gateway_code'        => '8393',
                                                             'gateway_description' => 'This was always a bad idea',
                                                             'code'                => 'BAD_IDEA',
                                                             'description'         => 'I told you so',
                                                         ]
        );

        $factory(\RZP\Models\Dispute\Entity::class, [
                                                      'id'                 => $faker->uniqueid,
                                                      'phase'              => \RZP\Models\Dispute\Phase::CHARGEBACK,
                                                      'raised_on'          => $faker->timestamp,
                                                      'expires_on'         => $faker->timestamp,
                                                      'deduct_at_onset'    => 0,
                                                      'amount_deducted'    => 0,
                                                      'amount_reversed'    => 0,
                                                      'currency'           => 'INR',
                                                      'status'             => \RZP\Models\Dispute\Status::OPEN,
                                                      'reason_code'        => 'SOMETHING_BAD',
                                                      'reason_description' => 'Something went wrong'
                                                  ]
        );

        $factory(\RZP\Models\Workflow\Entity::class, [
                                                       'id'            => $faker->uniqueid,
                                                       'merchant_id'   => '10000000000000',
                                                       'org_id'        => '100000razorpay',
                                                       'name'          => $faker->name,
                                                   ]
        );

        $factory(\RZP\Models\Workflow\Step\Entity::class, [
                                                            'id'               => $faker->uniqueid,
                                                            'role_id'          => 'factory:RZP\Models\Admin\Role\Entity',
                                                            'workflow_id'      => 'factory:RZP\Models\Workflow\Entity',
                                                            'reviewer_count'   => 1,
                                                            'op_type'          => 'or',
                                                            'level'            => 1,
                                                            'created_at'       => $faker->timestamp,
                                                            'updated_at'       => $faker->timestamp,
                                                        ]
        );

        $factory(\RZP\Models\Workflow\Action\Entity::class, [
                                                              'id'                => $faker->uniqueid,
                                                              'entity_id'         => \RZP\Tests\Functional\Fixtures\Entity\Org::MAKER_ADMIN,
                                                              'entity_name'       => 'admin',
                                                              'title'             => 'a workflow action',
                                                              'workflow_id'       => \RZP\Tests\Functional\Fixtures\Entity\Workflow::DEFAULT_WORKFLOW_ID,
                                                              'approved'          => false,
                                                              'current_level'     => 1,
                                                              'maker_id'          => $faker->uniqueid,
                                                              'maker_type'        => 'admin',
                                                              'state'             => \RZP\Models\State\Name::OPEN,
                                                              'org_id'            => \RZP\Tests\Functional\Fixtures\Entity\Org::RZP_ORG,
                                                              'permission_id'     => 'factory:RZP\Models\Admin\Permission\Entity',
                                                          ]
        );

        $factory(\RZP\Models\Workflow\Action\State\Entity::class, [
                                                              'id'                => $faker->uniqueid
                                                          ]
        );

        $factory(\RZP\Models\State\Entity::class, [
                                                    'id'                => $faker->uniqueid,
                                                    'name'              => \RZP\Models\State\Name::OPEN,
                                                ]
        );

        $factory(\RZP\Models\Workflow\Action\Checker\Entity::class, [
                                                                      'id'                => $faker->uniqueid,
                                                                      'name'              => \RZP\Models\State\Name::OPEN,
                                                                  ]
        );

        $factory(\RZP\Models\Gateway\File\Entity::class, [
                                                           'id'         => $faker->uniqueid,
                                                           'created_at' => $faker->timestamp,
                                                           'updated_at' => $faker->timestamp,
                                                       ]
        );

        $factory(\RZP\Models\GeoIP\Entity::class, [
                                                    'ip'         => $faker->ipv4
                                                ]
        );

        $factory(\RZP\Models\Merchant\AccessMap\Entity::class, [
                                                                 // TODO: Why no fake generated id here?
                                                                 'merchant_id'     => '10000000000000',
                                                                 'entity_type'     => 'application',
                                                                 'entity_id'       => '10000000000App',
                                                                 'entity_owner_id' => '10000000000000',
                                                                 'created_at'      => Carbon::now()->getTimestamp(),
                                                                 'updated_at'      => Carbon::now()->getTimestamp(),
                                                             ]
        );

        $factory(\RZP\Models\Partner\KycAccessState\Entity::class, [
                'id'         => $faker->uniqueid,
                'partner_id'      => '10000000000000',
                'entity_type'     => 'merchant',
                'entity_id'       => '10000000000009',
                'approve_token'   => 'approve_token',
                'reject_token'    => 'reject_token',
                'rejection_count' => 0,
                'state'           => 'pending_approval',
                'created_at'      => Carbon::now()->getTimestamp(),
                'updated_at'      => Carbon::now()->getTimestamp(),
            ]
        );

        $factory(\RZP\Models\Partner\Config\Entity::class, [
                                                             'id'                  => $faker->uniqueid,
                                                             'entity_type'         => 'application',
                                                             'entity_id'           => '10000000000App',
                                                             'revisit_at'          => Carbon::now()->addYear(1)->getTimestamp(),
                                                             'commissions_enabled' => 0,
                                                             'partner_metadata'    => null
                                                         ]
        );

        $factory(\RZP\Models\Merchant\Request\Entity::class, [
                                                               'id'         => $faker->uniqueid,
                                                               'created_at' => $faker->timestamp,
                                                               'updated_at' => $faker->timestamp,
                                                           ]
        );

        $factory(\RZP\Models\VirtualAccount\Entity::class, [
                                                             'id'          => $faker->uniqueid,
                                                             'merchant_id' => '10000000000000',
                                                             'status'      => 'active',
                                                             'name'        => 'Test Merchant',
                                                             'balance_id'  => '10000000000000',
                                                             'notes'       => null,
                                                             'created_at'  => $faker->timestamp,
                                                             'updated_at'  => $faker->timestamp,
                                                         ]
        );

        $factory(\RZP\Models\QrCode\Entity::class, [
                                                     'id'          => $faker->uniqueid,
                                                     'created_at'  => $faker->timestamp,
                                                     'updated_at'  => $faker->timestamp,
                                                     'merchant_id' => '10000000000000',
                                                     'provider'    => 'bharat_qr',
                                                     'entity_id'   => $faker->uniqueid,
                                                     'entity_type' => 'virtual_account',
                                                     'short_url'   => 'abc.com',
                                                     'qr_string'   => 'kdsfjsfndsmndjksnfsdnsmdns',
                                                 ]
        );

        $factory(\RZP\Models\QrCodeConfig\Entity::class, [
                                                     'id'          => $faker->uniqueid,
                                                     'created_at'  => $faker->timestamp,
                                                     'updated_at'  => $faker->timestamp,
                                                     'merchant_id' => '10000000000000',
                                                     'config_key'  => 'cut_off_time',
                                                     'config_value'=> 1500,
                                                 ]
        );

        $factory(\RZP\Models\QrCode\NonVirtualAccountQrCode\Entity::class, [
                                                                             'id'          => $faker->uniqueid,
                                                                             'created_at'  => $faker->timestamp,
                                                                             'updated_at'  => $faker->timestamp,
                                                                             'merchant_id' => '10000000000000',
                                                                             'provider'    => 'bharat_qr',
                                                                             'entity_id'   => $faker->uniqueid,
                                                                             'entity_type' => 'virtual_account',
                                                                             'short_url'   => 'abc.com',
                                                                             'qr_string'   => 'kdsfjsfndsmndjksnfsdnsmdns',
                                                                         ]
        );

        $factory(\RZP\Gateway\Mpi\Base\Entity::class, [
                                                    ]
        );

        $factory(\RZP\Models\NodalBeneficiary\Entity::class, [
                                                               'channel'             => 'yesbank',
                                                               'beneficiary_code'    => 'abc123459',
                                                               'registration_status' => 'created',
                                                               'merchant_id'         => $faker->uniqueid,
                                                               'bank_account_id'     => $faker->uniqueid,
                                                           ]
        );

        $factory(\RZP\Models\SubscriptionRegistration\Entity::class, [
                                                                       'id'          => $faker->uniqueid,
                                                                       'merchant_id' => '10000000000000',
                                                                       'customer_id' => '100000customer',
                                                                   ]
        );

        $factory(\RZP\Models\PaperMandate\Entity::class, [
                                                           'id'          => $faker->uniqueid,
                                                           'merchant_id' => '10000000000000',
                                                           'customer_id' => '100000customer',
                                                       ]
        );

        $factory(\RZP\Models\PaperMandate\PaperMandateUpload\Entity::class, [
                                                                              'id'          => $faker->uniqueid,
                                                                              'merchant_id' => '10000000000000',
                                                                          ]
        );

        $factory(\RZP\Models\Contact\Entity::class, [
                                                      'id'           => $faker->uniqueid,
                                                      'active'       => true,
                                                      'name'         => $faker->word,
                                                      'email'        => $faker->email,
                                                      'contact'      => '9123456789',
                                                      'type'         => $faker->randomElement(array_diff(Contact\Type::$defaults, [Contact\Type::VENDOR])),
                                                      'reference_id' => $faker->uniqueid,
                                                      'notes'        => null,
                                                      'merchant_id'  => '10000000000000',
                                                      'created_at'   => $faker->timestamp,
                                                      'updated_at'   => $faker->timestamp,
                                                  ]
        );

        $factory(\RZP\Models\CorporateCard\Entity::class, [
                'id'           => $faker->uniqueid,
                'name'         => $faker->name,
                'holder_name'  => $faker->name,
                'last4'        => $faker->word,
                'vault_token'  => $faker->word,
                'expiry_month' => $faker->word,
                'expiry_year'  => $faker->word,
                'merchant_id'  => '10000000000000',
                'created_at'   => $faker->timestamp,
                'updated_at'   => $faker->timestamp,
            ]
        );

        $factory(\RZP\Models\SubVirtualAccount\Entity::class, [
                                                                'id'                    => $faker->uniqueid,
                                                                'active'                => true,
                                                                'name'                  => $faker->word,
                                                                'master_merchant_id'    => '10000000000000',
                                                                'sub_merchant_id'       => '100abc000abc01',
                                                                'master_account_number' => '2224440041626905',
                                                                'sub_account_number'    => '2323230041626906',
                                                                'master_balance_id'     => 'xbalance000000',
                                                                'sub_account_type'      => 'default',
                                                                'created_at'            => $faker->timestamp,
                                                                'updated_at'            => $faker->timestamp,
                                                            ]
        );

        $factory(\RZP\Models\Payment\Fraud\Entity::class, [
                                                                'id'                    => $faker->uniqueid,
                                                                'payment_id'            => $faker->uniqueid,
                                                                'reported_by'           => 'Visa',
                                                                'amount'                => '100',
                                                                'base_amount'           => '100',
                                                                'currency'              => 'INR',
                                                                'created_at'            => $faker->timestamp,
                                                                'updated_at'            => $faker->timestamp,
                                                            ]
        );

        $factory(\RZP\Models\FundAccount\Entity::class, [
                                                          'id'          => $faker->uniqueid,
                                                          'active'      => 1,
                                                          'merchant_id' => '10000000000000',
                                                      ]
        );

        $factory(\RZP\Models\FundAccount\Validation\Entity::class, [
                                                                     'id'                => $faker->uniqueid,
                                                                     'fund_account_id'   => $faker->uniqueid,
                                                                     'fund_account_type' => Models\FundAccount\Type::BANK_ACCOUNT,
                                                                     'merchant_id'       => '10000000000000',
                                                                     'amount'            => 100,
                                                                     'currency'          => 'INR',
                                                                     'status'            => 'created',
                                                                     'notes'             => null,
                                                                     'created_at'        => $faker->timestamp,
                                                                     'updated_at'        => $faker->timestamp,
                                                                 ]
        );

        $factory(\RZP\Models\EntityOrigin\Entity::class, [
                                                           'id'                => $faker->uniqueid,
                                                           'origin_id'         => '10000000000000',
                                                           'origin_type'       => 'merchant',
                                                           'entity_id'         => 'factory:\RZP\Models\Payment\Entity',
                                                           'entity_type'       => 'payment',
                                                           'created_at'        => $faker->timestamp,
                                                           'updated_at'        => $faker->timestamp,
                                                       ]
        );

        $factory(\RZP\Models\Partner\Commission\Entity::class, [
                                                                 'id'                => $faker->uniqueid,
                                                                 'source_type'       => 'payment',
                                                                 'source_id'         => 'factory:\RZP\Models\Payment\Entity',
                                                                 'partner_id'        => 'factory:\RZP\Models\Merchant\Entity',
                                                                 'partner_config_id' => 'factory:\RZP\Models\Partner\Config\Entity',
                                                                 'status'            => 'created',
                                                                 'debit'             => 0,
                                                                 'credit'            => 1770,
                                                                 'currency'          => 'INR',
                                                                 'fee'               => 270,
                                                                 'notes'             => null,
                                                                 'created_at'        => $faker->timestamp,
                                                                 'updated_at'        => $faker->timestamp,
                                                             ]
        );

        $factory(\RZP\Models\Payment\Analytics\Entity::class, [
                                                                'payment_id'        => 'factory:RZP\Models\Payment\Entity',
                                                                'merchant_id'       => 'factory:RZP\Models\Merchant\Entity',
                                                                'ip'                => '127.0.0.1',
                                                                'created_at'        => $faker->timestamp,
                                                                'updated_at'        => $faker->timestamp,
                                                            ]
        );

        $factory(\RZP\Models\Payment\PaymentMeta\Entity::class, [
                                                                'payment_id'        => 'factory:RZP\Models\Payment\Entity',
                                                                'created_at'        => $faker->timestamp,
                                                                'updated_at'        => $faker->timestamp,
                                                            ]
        );

        $factory(\RZP\Gateway\Mozart\Entity::class, [
                                                      'id'                => '0',
                                                      'payment_id'        => 'factory:RZP\Models\Payment\Entity',
                                                      'gateway'           => 'Bajaj',
                                                      'created_at'        => $faker->timestamp,
                                                      'updated_at'        => $faker->timestamp,
                                                  ]
        );

        $factory(\RZP\Gateway\AxisMigs\Entity::class, [
                                                        'id'                => 12345,
                                                        'payment_id'        => 'factory:RZP\Models\Payment\Entity',
                                                        'created_at'        => $faker->timestamp,
                                                        'updated_at'        => $faker->timestamp,
                                                    ]
        );

        $factory(\RZP\Gateway\Isg\Entity::class, [
                                                   'id'                => 12345,
                                                   'payment_id'        => 'factory:RZP\Models\Payment\Entity',
                                                   'created_at'        => $faker->timestamp,
                                                   'updated_at'        => $faker->timestamp,
                                               ]
        );

        $factory(\RZP\Gateway\CardlessEmi\Entity::class, [
                                                           'id'                => $faker->uniqueid,
                                                           'gateway'           => 'cardless_emi',
                                                           'created_at'        => $faker->timestamp,
                                                           'updated_at'        => $faker->timestamp,
                                                       ]
        );

        $factory(\RZP\Models\BankingAccount\Entity::class, [
                                                             'id'                => '01234567890123',
                                                             'merchant_id'       => '10000000000000',
                                                             'channel'           => 'rbl',
                                                             'created_at'        => $faker->timestamp,
                                                             'updated_at'        => $faker->timestamp,
                                                         ]
        );

        $factory(\RZP\Models\BankingAccount\Detail\Entity::class, [
                                                                    'id'                 => 1,
                                                                    'banking_account_id' => '01234567890123',
                                                                    'merchant_id'        => '10000000000000',
                                                                    'gateway_key'        => 'client_id',
                                                                    'gateway_value'      => '123',
                                                                    'updated_at'         => $faker->timestamp,
                                                                ]
        );

        $factory(\RZP\Models\Mpan\Entity::class, [

                                               ]
        );

        $factory(\RZP\Models\D2cBureauDetail\Entity::class, [
                                                              'id'                        => $faker->uniqueid,
                                                              'merchant_id'               => 'factory:RZP\Models\Merchant\Entity',
                                                              'user_id'                   => 'factory:RZP\Models\User\Entity',
                                                          ]
        );

        $factory(\RZP\Models\D2cBureauReport\Entity::class, [
                                                              'id'                        => $faker->uniqueid,
                                                              'merchant_id'               => 'factory:RZP\Models\Merchant\Entity',
                                                              'user_id'                   => 'factory:RZP\Models\User\Entity',
                                                          ]
        );

        $factory(\RZP\Models\Settlement\OndemandFundAccount\Entity::class, [
                                                                             'id'                        => $faker->uniqueid,
                                                                             'merchant_id'               => '10000000000000',
                                                                             'contact_id'                => 'cont_EwjVv4aprYdlR5',
                                                                             'fund_account_id'           => 'fa_EwjVzEQdVIqqxW',
                                                                         ]
        );

        $factory(\RZP\Models\Workflow\PayoutAmountRules\Entity::class, [
                                                                         'id'                => 12345,
                                                                         'merchant_id'       => '10000000000000',
                                                                         'condition'         => null,
                                                                         'min_amount'        => 250,
                                                                         'max_amount'        => 1000000,
                                                                         'created_at'        => $faker->timestamp,
                                                                         'updated_at'        => $faker->timestamp,
                                                                     ]
        );

        $factory(\RZP\Models\Invoice\Reminder\Entity::class, [
        ]);

        $factory(\RZP\Models\Merchant\Reminders\Entity::class, [
        ]);

        $factory(\RZP\Models\PayoutLink\Entity::class, [
            'id'                   => $faker->uniqueid,
            'contact_id'           => '1000010contact',
            'contact_name'         => '1000010contact',
            'contact_phone_number' => '1231231231',
            'contact_email'        => 'test@rzp.com',
            'amount'               => 1000,
            'merchant_id'          => '10000000000000',
            'user_id'              => null,
            'currency'             => 'INR',
            'description'          => 'This is a test payout',
            'purpose'              => 'refund',
            'receipt'              => 'Test Payout Receipt',
            'notes'                => null,
            'short_url'            => 'http=>//76594130.ngrok.io/i/mGs4ehe',
            'status'               => 'issued',
            'created_at'           => 1575367399,
            'cancelled_at'         => null,
        ]);

        $factory(\RZP\Models\Options\Entity::class, [
                                                      'id'                => $faker->uniqueid,
                                                      'merchant_id'       => '10000000000000',
                                                      'namespace'         => \RZP\Models\Options\Constants::NAMESPACE_PAYMENT_LINKS,
                                                      'service_type'      => \RZP\Models\Options\Constants::SERVICE_PAYMENT_LINKS,
                                                      'scope'             => \RZP\Models\Options\Constants::SCOPE_GLOBAL,
                                                      'reference_id'      => null,
                                                      'created_at'        => $faker->timestamp,
                                                      'updated_at'        => $faker->timestamp,
                                                  ]
        );

        $factory(\RZP\Models\Invoice\Reminder\Entity::class, [
                                                           ]
        );

        $factory(\RZP\Models\Offline\Device\Entity::class, [
            'id'                 => $faker->uniqueid,
            'type'               => 'android',
            'status'             => 'created',
            'activation_token'   => $faker->sha256,
        ]);

        $factory(\RZP\Models\Payment\Config\Entity::class, [
            'id'                 => $faker->uniqueid,
            'merchant_id'        => '10000000000000',
            'name'               => 'Test Config',
            'type'               => 'checkout',
            'config'             => '{"method" : "card"}',
            'is_default'         => true,
        ]);

        $factory(\RZP\Models\BankingAccountStatement\Entity::class, [
            'id'                 => $faker->uniqueid,
            'merchant_id'       => '10000000000000',
        ]);

        $factory(\RZP\Models\VirtualVpaPrefix\Entity::class, [
            'id'                => $faker->uniqueid,
            'terminal_id'       => 'VirtVpaShrdTrm',
        ]);

        $factory(\RZP\Models\FeeRecovery\Entity::class, [
                                                          'entity_id'                => $faker->uniqueid,
                                                          'entity_type'              => 'payout',
                                                          'status'                   => 'unrecovered',
                                                          'attempt_number'           => 0,
                                                          'reference_number'         => null,
                                                          'description'              => null
                                                      ]
        );


        $factory(\RZP\Models\PayoutDowntime\Entity::class, [
            'id'               => $faker->uniqueid,
            'status'           => 'Enabled',
            'channel'          => 'RBL',
            'created_by'       => 'OPS_A',
            'downtime_message' => 'RBL bank NEFT payments are down',
        ]);

        $factory(\RZP\Models\FundLoadingDowntime\Entity::class, [
            'id'               => $faker->uniqueid,
            'type'             => 'Sudden Downtime',
            'source'           => 'Partner Bank',
            'channel'          => 'Yes Bank',
            'mode'             => 'NEFT',
            'start_time'       => Carbon::now(Timezone::IST)->getTimestamp(),
            'end_time'         => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            'downtime_message' => 'Downtime message received and initiated',
            'created_by'       => 'chirag.chiranjib@razorpay.com',
            'created_at'       => $faker->timestamp,
            'updated_at'       => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Merchant\Attribute\Entity::class, [
            'id'                 => $faker->uniqueid,
            'merchant_id'        => '10000000000000',
            'product'            => 'banking',
            'group'              => 'onboarding',
            'type'               => 'merchant_onboarding_mechanism',
            'value'              => 'normal',

        ]);

        $factory(\RZP\Models\Counter\Entity::class, [
            'id'                                  => $faker->uniqueid,
            'account_type'                        => 'shared',
            'balance_id'                          => '10000000000000',
            'free_payouts_consumed_last_reset_at' => Carbon::now(Timezone::IST)->firstOfMonth()->getTimestamp(),
            'free_payouts_consumed'               => 0,
            'created_at'                          => $faker->timestamp,
            'updated_at'                          => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Promotion\Event\Entity::class, [
            'id'                 => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Merchant\Balance\LowBalanceConfig\Entity::class, [
            'id'                  => $faker->uniqueid,
            'merchant_id'         => '10000000000000',
            'status'              => 'enabled',
            'notification_emails' => $faker->email,
            'threshold_amount'    => 1000,
            'notify_at'           => 0,
            'notify_after'        => 8,
            'type'                => 'notification',
            'autoload_amount'     => 0,
        ]);

        $factory(\RZP\Models\Merchant\Balance\SubBalanceMap\Entity::class, [
            'id'                => $faker->uniqueid,
            'merchant_id'       => '10000000000000',
            'parent_balance_id' => $faker->uniqueid,
            'child_balance_id'  => $faker->uniqueid
        ]);

        $factory(\RZP\Models\Merchant\Credits\Balance\Entity::class, [
                                                                       'id'            => $faker->uniqueid,
                                                                       'merchant_id'   => '10000000000000',
                                                                       'type'          => 'reward_fee',
                                                                       'product'       => 'banking',
                                                                   ]
        );

        $factory(\RZP\Models\Workflow\Service\Config\Entity::class, [
                                                                      'id'            => $faker->uniqueid,
                                                                      'config_id'     => 'con_qwertgfdsc',
                                                                      'config_type'   => 'payout-approval',
                                                                      'merchant_id'   => '10000000000000',
                                                                      'org_id'        => '100000razorpay',
                                                                      'enabled'       => true,
                                                                  ]
        );

        $factory(\RZP\Models\Workflow\Service\EntityMap\Entity::class, [
                                                                         'id'            => $faker->uniqueid,
                                                                         'workflow_id'   => 'con_qwertgfdsc',
                                                                         'entity_type'   => 'payout',
                                                                         'merchant_id'   => '10000000000000',
                                                                         'org_id'        => '100000razorpay',
                                                                         'config_id'     => 'con_qwertgfdsc',
                                                                     ]
        );

        $factory(\RZP\Models\Workflow\Service\StateMap\Entity::class, [
                                                                        'id'                => $faker->uniqueid,
                                                                        'workflow_id'       => 'con_qwertgfdsc',
                                                                        'merchant_id'       => '10000000000000',
                                                                        'org_id'            => '100000razorpay',
                                                                        'actor_type_key'    => 'role',
                                                                        'actor_type_value'   => 'owner',
                                                                        'state_id'          => 'sta_qwertgfdsc',
                                                                        'state_name'        => 'name',
                                                                        'status'            => 'created',
                                                                        'group_name'        => '123',
                                                                        'type'              => 'type',
                                                                    ]
        );

        $factory(\RZP\Models\Merchant\BvsValidation\Entity::class,
                 [
                     'validation_id'     => $faker->uniqueid,
                     'owner_id'          => '10000000000000',
                     'owner_type'        => 'merchant',
                     'artefact_type'     => 'personal_pan',
                     'error_code'        => null,
                     'error_description' => null,
                     'validation_status' => 'captured',
                     'created_at'        => $faker->timestamp,
                     'updated_at'        => $faker->timestamp,
                 ]
        );

        $factory(\RZP\Models\PayoutSource\Entity::class, [
            'id'          => $faker->uniqueid,
            'payout_id'   => 'factory:\RZP\Models\Payout\Entity',
            'source_id'   => '10000000000000',
            'source_type' => 'vendor_payments',
            'priority'    => 1,
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\PayoutsDetails\Entity::class, [
            'id'                        => $faker->uniqueid,
            'payout_id'                 => 'factory:\RZP\Models\Payout\Entity',
            'tax_payment_id'            => 'txpy_10000000000000',
            'tds_category_id'           => 1,
            'additional_info'           => [
                'tds_amount'      => 1000,
                'subtotal_amount' => 10000,
                'attachments'     => [
                    [
                        'file_id'   => 'file_testing',
                        'file_name' => 'not-your-attachment.pdf'
                    ]
                ]
            ],
            'queue_if_low_balance_flag' => 1,
            'created_at'                => $faker->timestamp,
            'updated_at'                => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Merchant\MerchantApplications\Entity::class, [
            'id'             => $faker->uniqueid,
            'merchant_id'    => '10000000000000',
            'type'           => 'managed',
            'application_id' => $faker->uniqueid,
            'created_at'     => $faker->timestamp,
            'updated_at'     => $faker->timestamp,
            'deleted_at'     => null,
        ]);

        $factory(\RZP\Models\Settlement\Ondemand\Bulk\Entity::class,[
            'id'                              => $faker->uniqueid,
            'amount'                          => 20000,
            'settlement_ondemand_id'          => $faker->uniqueid,
            'settlement_ondemand_transfer_id' => NULL,
        ]);

        $factory(\RZP\Models\Settlement\Ondemand\Transfer\Entity::class,[
            'id'                              => $faker->uniqueid,
            'amount'                          => 20000,
            'attempts'                        => 0,
        ]);

        $factory(\RZP\Models\Settlement\Ondemand\Attempt\Entity::class,[
            'id'                              => $faker->uniqueid,
            'settlement_ondemand_transfer_id' => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Settlement\Ondemand\FeatureConfig\Entity::class,[
            'id'                              => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Settlement\Ondemand\Entity::class,[
            'id'                              => $faker->uniqueid,
            'merchant_id'                     => '10000000000000',
            'amount'                          => 250,
            'total_amount_settled'            => 250,
            'total_fees'                      => 1,
            'total_tax'                       => 0,
            'total_amount_reversed'           => 0,
            'total_amount_pending'            => 0,
            'max_balance'                     => 0,
            'currency'                        => 'INR',
            'status'                          => 'processed',
            'transaction_id'                  => $faker->uniqueid,
            'transaction_type'                => 'transaction',
            'created_at'                      => $faker->timestamp,
            'updated_at'                      => $faker->timestamp,
            'deleted_at'                      => null,
        ]);

        $factory(\RZP\Models\Settlement\OndemandPayout\Entity::class,[
            'id'                              => $faker->uniqueid,
            'merchant_id'                     => '10000000000000',
            'created_at'                      => $faker->timestamp,
            'updated_at'                      => $faker->timestamp,
            'deleted_at'                      => null,
        ]);

        $factory(\RZP\Models\Reward\Entity::class, [
            'id'             => $faker->uniqueid,
            'advertiser_id'  => '100000Razorpay',
            'name'           => 'Test Reward 1',
            'percent_rate'   => '100',
            'max_cashback'   => '200',
            'coupon_code'    => 'random_code',
            'starts_at'      => Carbon::now()->getTimestamp(),
            'ends_at'        => Carbon::tomorrow()->getTimestamp(),
        ]);

        $factory(\RZP\Models\Reward\MerchantReward\Entity::class, [
            'merchant_id'    => '10000000000000',
            'status'         => 'available'
        ]);

        $factory(\RZP\Models\TrustedBadge\Entity::class, [
            'merchant_id'       => '10000000000000',
            'status'            => 'eligible',
            'merchant_status'   => 'optout',
        ]);

        $factory(\RZP\Models\TrustedBadge\TrustedBadgeHistory\Entity::class, [
            'id'                => $faker->uniqueid,
            'merchant_id'       => '10000000000000',
            'status'            => 'eligible',
            'merchant_status'   => 'optout',
            'created_at'        => 1632550775,
        ]);

        $factory(\RZP\Models\Survey\Entity::class, [
            'id'                  => 'GAX5zcOdI0Y664',
            'name'                => 'test survey',
            'description'         => 'test survey',
            'survey_ttl'          => 30
        ]);

        $factory(\RZP\Models\Survey\Tracker\Entity::class, [
            'id'                  => 'GAX5zcOdI0Y663',
            'survey_id'           => 'GAX5zcOdI0Y664',
            'survey_email'        => 'test@razorpay.com',
            'survey_sent_at'      => Carbon::now()->getTimestamp(),
            'attempts'            => 1
        ]);

        $factory(\RZP\Models\Survey\Response\Entity::class, [
            'id'                  => 'PAX5zcOdI0Y663',
            'tracker_id'          => 'GAX5zcOdI0Y663',
            'survey_id'           => 'GAX5zcOdI0Y664',
        ]);

        $factory(\RZP\Models\Application\Entity::class, [
            'id'                  => 'GAX5zcOdI0Y664',
            'name'                => 'Factory App',
            'title'               => 'Factory App',
            'type'                => 'app',
            'description'         => 'This is factory app',
        ]);

        $factory(\RZP\Models\Application\ApplicationTags\Entity::class, [
            'id'                  => 'GAX5zcOdI0Y664',
            'tag'                 => 'ecommerce',
            'app_id'              => 'GAX5zcOdI0Y664',
        ]);

        $factory(\RZP\Models\Merchant\MerchantNotificationConfig\Entity::class, [
            MerchantNotificationConfigEntity::ID                          => $faker->uniqueid,
            MerchantNotificationConfigEntity::MERCHANT_ID                 => '10000000000000',
            MerchantNotificationConfigEntity::CONFIG_STATUS               => 'enabled',
            MerchantNotificationConfigEntity::NOTIFICATION_TYPE           => 'bene_bank_downtime',
            MerchantNotificationConfigEntity::NOTIFICATION_EMAILS         => ['test@rzp.in', 'test2@rzp.in'],
            MerchantNotificationConfigEntity::NOTIFICATION_MOBILE_NUMBERS => ['9898989898', '8888778888'],
            MerchantNotificationConfigEntity::CREATED_AT                  => $faker->timestamp,
            MerchantNotificationConfigEntity::UPDATED_AT                  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Offer\SubscriptionOffer\Entity::class, [
            'id'    => '10000000someid',
        ]);

        $factory(\RZP\Models\BankingAccountTpv\Entity::class, [
            'id'                         => $faker->uniqueid,
            'merchant_id'                => '10000000000000',
            'balance_id'                 => '10000000000000',
            'status'                     => 'pending',
            'is_active'                  => '1',
            'payer_ifsc'                 => 'ICIC0000104',
            'payer_name'                 => 'Test payer',
            'payer_account_number'       => '9876543210123456789',
            'type'                       => 'bank_account',
            'fund_account_validation_id' => null,
            'created_by'                 => 'admin',
            'created_at'                 => $faker->timestamp,
            'updated_at'                 => $faker->timestamp,
        ]);

        $factory(\RZP\Models\BankingAccountStatement\Details\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
            'status'      => 'active'
        ]);

        $factory(\RZP\Models\BankingAccountStatement\Pool\Base\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\BankingAccountStatement\Pool\Rbl\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\BankingAccountStatement\Pool\Icici\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Settings\Entity::class, [
            'module'                 => 'm2p_transfer',
            'entity_type'            => 'merchant',
            'key'                    => 'xyz',
            'value'                  => 'xyz',
            'entity_id'              => 'GAX5zcOdI0Y664',
        ]);

        $factory(\RZP\Models\BankingAccount\Activation\Detail\Entity::class, [
            'id'                 => $faker->uniqueid,
            'banking_account_id' => '01234567890123',
            'created_at'         => $faker->timestamp,
            'updated_at'         => $faker->timestamp,
        ]);

        $factory(\RZP\Models\BankingAccount\State\Entity::class, [
            'id'                 => $faker->uniqueid,
            'banking_account_id' => '01234567890123',
            'merchant_id'        => '10000000000000',
            'created_at'         => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Partner\Activation\Entity::class, [
            'merchant_id'  => $faker->uniqueid,
            'created_at'   => $faker->timestamp,
            'updated_at'   => $faker->timestamp,
            'submitted_at' => $faker->timestamp,
        ]);

        $factory(\RZP\Gateway\Hitachi\Entity::class, [
            'id'     => $faker->randomNumber(6),
            'amount' => $faker->randomNumber(2),
        ]);

        $factory(\RZP\Gateway\Paysecure\Entity::class, [
            'id'        => $faker->randomNumber(6),
            'tran_date' => '1234',
            'tran_time' => '567',
        ]);

        $factory(\RZP\Models\BankTransfer\Entity::class, [
            'id'            => 'randombanktran',
            'payee_account' => '123',
            'payee_ifsc'    => 'RZPB0000000',
            'gateway'       => 'hdfc',
            'amount'        => $faker->randomNumber(2),
            'mode'          => 'test',
            'time'          => 123,
        ]);

        $factory(\RZP\Models\Payout\Batch\Entity::class, [
            'batch_id'     => $faker->uniqueid,
            'reference_id' => $faker->word,
            'status'       => 'accepted',
            'merchant_id'  => '10000000000000',
            'created_at'   => $faker->timestamp,
            'updated_at'   => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Merchant\Merchant1ccConfig\Entity::class, [
            'id'        => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Merchant\OneClickCheckout\AuthConfig\Entity::class, [
            'id'        => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Store\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'status'      => \RZP\Models\PaymentLink\Status::ACTIVE,
            'title'       => 'Sample title',
            'description' => 'Sample description',
            'notes'       => null,

        ]);

        $factory(\RZP\Models\Internal\Entity::class, [
            'id'                 => $faker->uniqueid,
            'merchant_id'        => '10000000000000',
            'currency'           => 'INR',
            'amount'             => '1',
            'base_amount'        => '1',
            'utr'                => '999999999',
            'transaction_date'   => $faker->timestamp,
            'created_at'         => $faker->timestamp,
            'updated_at'         => $faker->timestamp,
            'source_entity_id'   => 'sampleEntityId',
            'source_entity_type' => 'payout',
            'mode'               => 'IFSC',
            'bank_name'          => 'HDFC Bank',
        ]);

        $factory(\RZP\Models\Dispute\DebitNote\Entity::class, [
            'id'                => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Dispute\DebitNote\Detail\Entity::class, [
            'id'                => $faker->uniqueid,
            'debit_note_id'     => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\PayoutsStatusDetails\Entity::class, [
           'id'           => $faker->uniqueid,
           'payout_id'    => 'factory:\RZP\Models\Payout\Entity',
            'status'      => 'processing',
            'reason'      => 'payout_bank_processing',
            'description' => 'Payout is being processed by our partner bank. Please check '
                              .'the final status after some time',
        ]);

        $factory(\RZP\Models\Settlement\EarlySettlementFeaturePeriod\Entity::class, [
            'id'                => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Merchant\InternationalEnablement\Detail\Entity::class, [
            'id'                => $faker->uniqueid,
            'revision_id'       => $faker->uniqueid,
            ]);

        $factory(\RZP\Models\External\Entity::class, [
            'id'                            => 'randomexternal',
            'merchant_id'                   => '10000000000000',
            'transaction_id'                => 'RZPtxn00000',
            'channel'                       => 'rbl',
            'utr'                           => '99999999912',
            'currency'                      => 'INR',
            'bank_reference_number'         => 'M86858',
            'type'                          => 'debit',
            'balance_id'                    => 12324222,
            'banking_account_statement_id'  => 'J3J9XVuHZHWOoo',
        ]);


        $factory(\RZP\Models\PaymentLink\NocodeCustomUrl\Entity::class, [
            'id'            => $faker->uniqueid,
            'merchant_id'   => '10000000000000',
            'slug'          => $faker->unique()->slug(2),
            'domain'        => $faker->unique()->url,
            'product'       => 'page',
            'product_id'    => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Roles\Entity::class, [
            'id'          => $faker->uniqueid,
            'name'        => $faker->word,
            'description' => 'Test custom role',
            'merchant_id' => '100000merchant',
            'type'        => 'custom',
            'created_by'  => $faker->email,
            'updated_by'  => $faker->email,
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);


        $factory(\RZP\Models\RoleAccessPolicyMap\Entity::class, [
            'id' => $faker->uniqueid,
            'role_id' => $faker->word,
            'authz_roles'   => null,
            'access_policy_ids' => null,
            'created_at' => $faker->timestamp,
            'updated_at' => $faker->timestamp,
        ]);

        $factory(\RZP\Models\AccessControlPrivileges\Entity::class, [
            'id'          => $faker->uniqueid,
            'name'        => 'Account Setting',
            'description' => 'A/c setting test description',
            'parent_id'   => null,
            'label'       => 'account_setting',
            'visibility'  => 1,
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\AccessPolicyAuthzRolesMap\Entity::class, [
            'id'            => $faker->uniqueid,
            'privilege_id'  => '10000privilege',
            'action'        => 'read',
            'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
            'meta_data'     => [
                'tooltip'       => 'Tooltip',
                'description'   => 'Access policy test description',
                                            ],
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
        ]);

        $factory(\RZP\Models\AccessControlHistoryLogs\Entity::class, [
            'id'            => $faker->uniqueid,
            'entity_id'     => '10AccessPolicy',
            'entity_type'   => 'access_policy_authz_roles_map',
            'message'       => 'Test history log',
            'previous_value'=> null,
            'new_value'     => [
                'id'            => $faker->uniqueid,
                'privilege_id'  => '10000privilege',
                'action'        => 'read',
                'authz_roles'   => ['authz_roles_1', 'authz_roles_2', 'authz_roles_3'],
                'meta_data'     => json_encode([
                    'tooltip'       => 'Tooltip',
                    'description'   => 'Access policy test description',
                ]),
                'created_at'  => $faker->timestamp,
                'updated_at'  => $faker->timestamp,
            ],
            'owner_id'          => '100000merchant',
            'owner_type'        => 'merchant',
            'created_at'  => $faker->timestamp,
            'created_by'  => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Merchant\InternationalIntegration\Entity::class, [
            'id'                            => $faker->uniqueid,
            'merchant_id'                   => '10000000000000',
            'integration_entity'            => 'sample',
            'integration_key'               => 'test123',
            'notes'                         => [],
        ]);

        $factory(Models\PartnerBankHealth\Entity::class, [
            'id'         => $faker->uniqueid,
            'event_type' => 'fail_fast_health.shared.imps',
            'value'      => json_encode(
                [
                    'ICIC'               => [
                        'last_down_at' => 1640430729
                    ],
                    'affected_merchants' => [
                        "ALL"
                    ],
                ]
            )
        ]);

        $factory(\RZP\Models\Merchant\Slab\Entity::class, [
            'id'        => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Merchant\LinkedAccountReferenceData\Entity::class, [
                "id"               => $faker->uniqueid,
                "account_name"     => "ABC Mutual Fund - Online Collection Account",
                "account_number"   => "123000000000000",
                "account_email"    => "test+1@gmail.com",
                "beneficiary_name"  => "ABC Mutual Fund - Funds Collection Account",
                "business_name"    => "Test Asset Management Limited",
                "business_type"    => "private_limited",
                "dashboard_access" => 0,
                "ifsc_code"        => "HDFC0000060",
                "category"         => "amc_bank_account",
               "customer_refund_access"    => 0,
        ]);

        $factory(\RZP\Models\IdempotencyKey\Entity::class, [
            'id'              => $faker->uniqueid,
            'source_id'       => null,
            'source_type'     => null,
            'idempotency_key' => $faker->uniqueid,
            'merchant_id'     => '10000000000000',
            'created_at'      => $faker->timestamp,
            'updated_at'      => $faker->timestamp,
        ]);

        $factory(Models\Payment\UpiMetadata\Entity::class, [
            'payment_id' => $faker->uniqueId,
            'mode'       => null,
            'flow'       => null,
            'type'       => 'default',
        ]);
    }
}
