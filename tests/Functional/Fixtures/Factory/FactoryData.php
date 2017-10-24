<?php

namespace RZP\Tests\Functional\Fixtures\Factory;

use Config;
use Eloquent;
use RZP\Models;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Merchant;
use RZP\Tests\TestDummy\Factory;

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
            'category'                 => 1100,
            'transaction_report_email' => ['test@razorpay.com'],
            'receipt_email_enabled'    => true,
            'settlement_schedule'      => 3,
            'fee_bearer'               => \RZP\Models\Merchant\FeeBearer::PLATFORM,
            'risk_rating'              => 3,
            'invoice_code'             => '123456789011',
        ]);

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
        ]);

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
        ]);

        $factory(\RZP\Models\Merchant\Balance\Entity::class, [
            'id'                        => $faker->uniqueid,
            'balance'                   => 0,
        ]);

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
        ]);

        $factory(\RZP\Models\Card\Entity::class, [
            'id'                => $faker->uniqueid,
            'merchant_id'       => '10000000000000',
            'name'              => $faker->word,
            'network'           => 'Visa',
            'expiry_month'      => 01,
            'expiry_year'       => 2018,
            'type'              => 'debit',
            'country'           => 'IN',
            'last4'             => 1111,
            'iin'               => 411111,
            'length'            => '16',
            'issuer'            => 'hdfc',
            'emi'               => false,
            'international'     => false,
            'vault_token'       => 'NDExMTExMTExMTExMTExMQ==',
            'vault'             => 'tokenex',
            'trivia'            => '',
        ]);

        $factory(\RZP\Models\Key\Entity::class, [
            'id' => '1DP5mmOlF5G5ag',
            'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
            'secret' => 'eyJpdiI6InFjMFFDMkszYzRLeU5UZ2VnajhoMEE9PSIsInZhbHVlIjoiZzY3c0Zkd0VMQkE0cjU1T3hVQXZSSzBub1h4aHJkaThBRlwvZWJwMm5wdkE9IiwibWFjIjoiZmEyZWM5MzIyODBjMmU3N2RhMmQ2ZjA2ODA3OTk5ZjI0ZTY2ZTQ3ZGNiYzJjOTE4ODc5ZWNkYzY4MGQwYTZhZiJ9',
            'expired_at' => null,
        ]);

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
            'gateway' => 'hdfc',
            'email' => $faker->email,
            'auto_captured' => 0,
            'captured_at' => null,
            'reference1' => $faker->uniqueid,
            'transaction_id' => null,
            'on_hold' => 0,
            'created_at' => $faker->timestamp,
            'updated_at' => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Payment\Refund\Entity::class, [
            'id' => $faker->uniqueid,
            'payment_id' => 'factory:\RZP\Models\Payment\Entity',
            'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
            'amount' => 100,
            'currency' => 'INR',
            'base_amount' => 100,
            'notes' => null,
            'transaction_id' => null,
        ]);

        $factory(\RZP\Models\Pricing\Entity::class, [
            'id' => $faker->uniqueid,
            'plan_id' => '1ycviEdCgurrFI',
            'plan_name' => 'testFixturePlan',
            'feature' => 'payment',
            'payment_method' => 'card',
            'payment_method_type' => 'credit',
            'payment_network' => 'VISA',
            'payment_issuer' => 'ICIC',
            'percent_rate' => 1000,
            'fixed_rate' => 10000,
        ]);

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
            'channel' => 'kotak',
            'settled' => 0,
        ]);

        $factory(\RZP\Models\Settlement\Entity::class, [
            'id' => $faker->uniqueid,
            'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
            'amount' => $faker->randomNumber(4),
            'status' => 'created',
            // 'transaction_id' => 'factory:\RZP\Models\Transaction\Entity',
            'fees' => $faker->randomNumber(2),
            'channel' => 'kotak',
            'failure_reason' => null,
            'return_utr' => null,
        ]);

        $factory(\RZP\Models\FundTransfer\Attempt\Entity::class, [
            'id' => $faker->uniqueid,
            // 'source_id' => 'factory:\RZP\Models\Settlement\Entity',
            'source_type' => 'settlement',
            'status' => 'initiated',
            'channel' => 'kotak',
            'version' => 'V3',
        ]);

        $factory(\RZP\Models\FundTransfer\Batch\Entity::class, [
            'id' => $faker->uniqueid,
            'date' => Carbon::today(Timezone::IST)->timestamp,
            'channel' => 'kotak',
            'amount' => $faker->randomNumber(4),
            'processed_amount' => 0,
            'processed_count' => 0,
            'fees' => $faker->randomNumber(2),
            'api_fee' => $faker->randomNumber(2),
            'gateway_fee' => $faker->randomNumber(2),
            'urls' => $faker->sentence,
            'initiated_at' => Carbon::today(Timezone::IST)->timestamp + 10,
        ]);

        $factory(\RZP\Models\Adjustment\Entity::class, [
            'id' => $faker->uniqueid,
            'merchant_id' => 'factory:RZP\Models\Merchant\Entity',
            'amount' => $faker->randomNumber,
            'currency' => 'INR',
            'channel' => 'kotak',
            'description' => $faker->text,
            'transaction_id' => 'factory:RZP\Models\Transaction\Entity',
            'settlement_id' => 'factory:RZP\Models\Settlement\Entity'
        ]);

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
        ]);

        $factory(\RZP\Gateway\Atom\Entity::class, [
            'id' => $faker->randomNumber(6),
            'gateway_payment_id' => 'factory:\RZP\Models\Payment\Entity',
            'token' => $faker->token,
            'success' => $faker->boolean,
            'callback_data' => null,
            'bank_name' => '\RZP',
            'bank_transaction_id' => $faker->randomNumber(6),
        ]);

        $factory(\RZP\Models\Card\IIN\Entity::class, [
            'iin' => 411111,
            'category' => null,
            'network' => 'Visa',
            'type' => 'credit',
            'country' => 'IN',
            'issuer' => 'SBI',
            'trivia' => $faker->sentence,
        ]);

        $factory(\RZP\Models\Merchant\Methods\Entity::class, [
            'merchant_id'       => '10000000000000',
            'credit_card'       => '1',
            'debit_card'        => '1',
            'disabled_banks'    => '[]',
            'paytm'             => '0',
        ]);

        $factory(\RZP\Models\Merchant\Webhook\Entity::class, [
            'merchant_id' => '10000000000000',
            'url' => $faker->url,
            'events' => [
                'payment.authorized' => true,
            ],
            'active' => true,
        ]);

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
        ]);

        $factory(\RZP\Models\Emi\Entity::class, [
            'id' => 10101010101010,
            'duration' => 9,
            'rate' => 1200,
            'bank' => 'HDFC',
            'methods' => 'card',
            'min_amount' => 500000,
        ]);

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
        ]);

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
            'due_by'                   => $faker->timestamp,
            'scheduled_at'             => $faker->timestamp,
            'expire_by'                => $faker->timestamp('+2 day'),
            'amount'                   => 100000,
            'currency'                 => 'INR',
        ]);

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
        ]);

        $factory(\RZP\Gateway\FirstData\Entity::class, [
            'id' => '0',
            'action' => 'authorize',
            'payment_id' => null,
            'amount' => null,
            'received' => true,
        ]);

        $factory(\RZP\Gateway\Netbanking\Base\Entity::class, [
            'id'     => '0',
            'action' => 'authorize',
            'amount' => 1000,
        ]);

        $factory(\RZP\Models\Customer\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'name'        => 'name',
            'contact'     => '9988776655',
            'notes'       => null,
        ]);

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
        ]);

        $factory(\RZP\Models\Customer\AppToken\Entity::class, [
            'id' => $faker->uniqueid,
            'customer_id'  => '10000gcustomer',
            'device_token' => 'test',
            'merchant_id'  => '10000000000000'
        ]);

        $factory(\RZP\Models\Customer\GatewayToken\Entity::class, [
            'id' => '10gatewaytoken',
            'token_id' => '10000custgcard',
            'terminal_id' => '1RecurringTerm',
            'merchant_id' => '10000000000000'
        ]);

        $factory(\RZP\Models\Merchant\Credits\Entity::class, [
            'id'            => $faker->uniqueid,
            'merchant_id'   => '10000000000000',
            'value'         => 150,
            'type'          => 'amount',
            'campaign'      => 'silent-ads',
        ]);

        $factory(\RZP\Models\Transaction\FeeBreakup\Entity::class, [
            'id'            => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Batch\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id'     => '10000000000000',
            'status'          => 'created',
            'upload_file_url' => 'batch/upload/text.xlsx',
        ]);

        $factory(\RZP\Gateway\Wallet\Base\Entity::class, [
            'id'            => '12345',
            'amount'        => '0',
            'contact'       => '9918899029',
            'email'         => 'a@b.com',
        ]);

        $factory(\RZP\Models\Feature\Entity::class, [
            'id'                => $faker->uniqueid,
            'entity_type'       => 'merchant'
        ]);

        // Admin Roles related fixtures
        $factory(\RZP\Models\Admin\Org\Entity::class, [
            'id'            => $faker->uniqueid,
            'allow_sign_up' => false,
            'email_domains' => 'razorpay.com,rzp.io',
            'email'         => $faker->rzpEmail,
            'display_name'  => 'Razorpay',
            'business_name' => 'Razorpay Software Pvt Ltd',
            'auth_type'     => 'password',
            'custom_code'   => $faker->name,
        ]);

        $factory(\RZP\Models\Admin\Org\FieldMap\Entity::class, [
            'id'            => $faker->uniqueid,
        ]);

        $factory(\RZP\Models\Admin\Org\Hostname\Entity::class, [
            'id'            => $faker->uniqueid,
            'org_id'        => $faker->uniqueid,
            'hostname'      => $faker->rzpSubdomain,
        ]);

        $factory(\RZP\Models\Admin\Permission\Entity::class, [
            'id'            => $faker->uniqueid,
            'name'          => $faker->name,
            'category'      => 'test category',
            'description'   => 'test description',
        ]);

        $factory(\RZP\Models\Admin\Role\Entity::class, [
            'id' => $faker->uniqueid,
            'name' => 'manager',
            'description' => 'Manager of roles',
        ]);

        $factory(\RZP\Models\Admin\Group\Entity::class, [
            'id'          => $faker->uniqueid,
            'name'        => $faker->name,
            'description' => 'This is a test group',
        ]);

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
        ]);

        $factory(\RZP\Models\Admin\Admin\Token\Entity::class, [
            'id'            => $faker->uniqueid,
            'admin_id'      => 'RazorpayUserId',
            'created_at'    => $faker->timestamp,
            'expires_at'    => Carbon::now()->addDays(30)->getTimestamp(),
        ]);

        $factory(\RZP\Models\Merchant\Detail\Entity::class, [
            'merchant_id'   => $faker->uniqueid,
            'contact_email' => $faker->email,
        ]);

        $factory(\RZP\Models\User\Entity::class, [
            'id'         => $faker->uniqueid,
            'name'       => $faker->word,
            'email'      => $faker->email,
            'password'   => $faker->word,
            'created_at' => $faker->timestamp,
            'updated_at' => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Customer\Balance\Entity::class, [
            'customer_id'   => 'factory:RZP\Models\Customer\Entity',
            'merchant_id'   => '10000000000000',
            'balance'       => 0,
            'daily_usage'   => 0,
            'weekly_usage'  => 0,
            'monthly_usage' => 0,
            'max_balance'   => 2000000,
        ]);

        $factory(\RZP\Models\Customer\Transaction\Entity::class, [
            'id'                => $faker->uniqueid,
            'merchant_id'       => '10000000000000',
            'status'            => 'transferred',
            'amount'            => 100,
            'debit'             => 100,
            'credit'            => 10,
            'balance'           => 0,
            'description'       => 'NA',
        ]);

        $factory(\RZP\Models\Offer\Entity::class, [
            'id'        => $faker->uniqueid,
            'active'    => true,
            'terms'     => 'Terms and Condition'
        ]);

        $factory(\RZP\Models\Plan\Entity::class, [
            'id'                => '1000000000plan',
            'merchant_id'       => '10000000000000',
            'period'            => 'monthly',
            'interval'          => 2,
            'item_id'           => '1000000000item',
            // 'schedule_id'       => null,
            'notes'             => null,
        ]);

        $factory(\RZP\Models\Plan\Subscription\Entity::class, [
            'merchant_id'   => '10000000000000',
            'customer_id'   => '100000customer',
            'status'        => 'created',
            'quantity'      => 1,
            'total_count'   => 4,
            'notes'         => null,
        ]);

        $factory(\RZP\Models\Plan\Subscription\Addon\Entity::class, [
            'merchant_id'       => '10000000000000',
            'item_id'           => '1000000000item',
            'invoice_id'        => '1000000invoice',
            'subscription_id'   => '10subscription',
        ]);

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
        ]);

        $factory(\RZP\Models\Upi\Vpa\Entity::class, [
            'id'                 => $faker->uniqueid,
            'username'           => $faker->word,
            'handle'             => 'razorpay',
            'bank_account_id'    => 'factory:RZP\Models\BankAccount\Entity',
            'customer_id'        => '100000customer',
            'frequency'          => 'multiple',
            'created_at'         => $faker->timestamp,
            'updated_at'         => $faker->timestamp,
        ]);

        $factory(\RZP\Models\P2p\Entity::class, [
            'id'                 => $faker->uniqueid,
            'username'           => $faker->word,
            'handle'             => 'razorpay',
            'bank_account_id'    => 'factory:RZP\Models\BankAccount\Entity',
            'customer_id'        => '100000customer',
            'created_at'         => $faker->timestamp,
            'updated_at'         => $faker->timestamp,
        ]);

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
            'status'             => 'created',
            'channel'            => 'kotak',
            'created_at'         => $faker->timestamp,
            'updated_at'         => $faker->timestamp,
        ]);

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
        ]);

        $factory(\RZP\Models\Reversal\Entity::class, [
            'id'                 => $faker->uniqueid,
            'amount'             => 200,
            'currency'           => 'INR',
            'notes'              => null,
            'merchant_id'        => '10000000000000',
            'created_at'         => $faker->timestamp,
            'updated_at'         => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Schedule\Entity::class, [
            'id'                => $faker->uniqueid,
            'merchant_id'       => '100000Razorpay',
            'name'              => 'Basic T3',
            'period'            => 'daily',
            'interval'          => 1,
            'delay'             => 3,
            'hour'              => 5,
        ]);

        $factory(\RZP\Models\Schedule\Task\Entity::class, [
            'id'                => $faker->randomNumber(6),
            'merchant_id'       => '10000000000000',
            'entity_id'         => '10000000000000',
            'entity_type'       => 'merchant',
            'type'              => 'settlement',
            'method'            => null,
            'schedule_id'       => 'factory:RZP\Models\Schedule\Entity',
            'next_run_at'       => 1451604600,
        ]);

        $factory(\RZP\Models\Gateway\Downtime\Entity::class, [
            'id'         => $faker->uniqueid,
            'created_at' => $faker->timestamp,
            'updated_at' => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Gateway\Rule\Entity::class, [
            'id'         => $faker->uniqueid,
            'min_amount' => 0,
            'created_at' => $faker->timestamp,
            'updated_at' => $faker->timestamp
        ]);

        $factory(\RZP\Models\Tax\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'name'        => 'Sample tax',
            'rate_type'   => 'percentage',
            'rate'        => 1000,
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
            'deleted_at'  => null,
        ]);

        $factory(\RZP\Models\Tax\Group\Entity::class, [
            'id'          => $faker->uniqueid,
            'merchant_id' => '10000000000000',
            'name'        => 'Sample tax group',
            'created_at'  => $faker->timestamp,
            'updated_at'  => $faker->timestamp,
            'deleted_at'  => null,
        ]);

        $factory(\RZP\Models\Promotion\Entity::class, [
            'id'          => $faker->uniqueid
        ]);

        $factory(\RZP\Models\Coupon\Entity::class, [
            'id'          => $faker->uniqueid
        ]);

        $factory(\RZP\Models\Merchant\Promotion\Entity::class, [
            'id'          => $faker->uniqueid,
        ]);

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
        ]);

        $factory(\RZP\Models\Invitation\Entity::class, [
            'id'                       => $faker->randomNumber(6),
            'email'                    => $faker->email,
            'merchant_id'              => $faker->uniqueid,
            'role'                     => 'manager',
            'token'                    => $faker->name(30),
        ]);

        $factory(\RZP\Models\Dispute\Reason\Entity::class, [
            'id'                  => $faker->uniqueid,
            'gateway_code'        => '8393',
            'gateway_description' => 'This was always a bad idea',
            'code'                => 'BAD_IDEA',
            'description'         => 'I told you so',
        ]);

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
        ]);

        $factory(\RZP\Models\Workflow\Entity::class, [
            'id'        => $faker->uniqueid,
            'org_id'    => '100000razorpay',
            'name'      => $faker->name,
        ]);

        $factory(\RZP\Models\Workflow\Step\Entity::class,[
            'id'               => $faker->uniqueid,
            'role_id'          => 'factory:RZP\Models\Admin\Role\Entity',
            'workflow_id'      => 'factory:RZP\Models\Workflow\Entity',
            'reviewer_count'   => 1,
            'op_type'          => 'or',
            'level'            => 1,
            'created_at'       => $faker->timestamp,
            'updated_at'       => $faker->timestamp,
        ]);

        $factory(\RZP\Models\Workflow\Action\Entity::class, [
            'id'                => $faker->uniqueid,
            'entity_id'         => \RZP\Tests\Functional\Fixtures\Entity\Org::MAKER_ADMIN,
            'entity_name'       => 'admin',
            'title'             => 'a workflow action',
            'workflow_id'       => \RZP\Tests\Functional\Fixtures\Entity\Workflow::DEFAULT_WORKFLOW_ID,
            'approved'          => false,
            'current_level'     => 1,
            'state'             => \RZP\Models\Workflow\Action\State\Entity::OPEN,
            'org_id'            => \RZP\Tests\Functional\Fixtures\Entity\Org::RZP_ORG,
            'permission_id'     => 'factory:RZP\Models\Admin\Permission\Entity',
        ]);

        $factory(\RZP\Models\Workflow\Action\State\Entity::class, [
            'id'                => $faker->uniqueid,
            'name'              => \RZP\Models\Workflow\Action\State\Entity::OPEN,
        ]);

        $factory(\RZP\Models\Workflow\Action\Checker\Entity::class, [
            'id'                => $faker->uniqueid,
            'name'              => \RZP\Models\Workflow\Action\State\Entity::OPEN,
        ]);

        $factory(\RZP\Models\Gateway\File\Entity::class, [
            'id'         => $faker->uniqueid,
            'created_at' => $faker->timestamp,
            'updated_at' => $faker->timestamp,
        ]);
    }
}
