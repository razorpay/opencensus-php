<?php

namespace RZP\Tests\Functional\Fixtures\Entity;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Customer\Token;

class Customer extends Base
{
    protected $expiredAtTime;

    public function setUp()
    {
        $this->expiredAtTime = Carbon::now(Timezone::IST)
                                ->addYears(Token\Entity::DEFAULT_EXPIRY_YEARS)
                                ->getTimestamp();

        $this->fixtures->create('customer:customers');
        $this->fixtures->create('customer:app_tokens');
        $this->fixtures->create('customer:tokens');
        $this->fixtures->create('customer:bank_accounts');
        $this->fixtures->on('live')->create('customer:customers');
        $this->fixtures->on('test');
    }


    protected $customers = [
        [
            'id'            => '100000customer',
            'name'          => 'test',
            'email'         => 'test@razorpay.com',
            'contact'       => '1234567890',
            'merchant_id'   => '10000000000000'
        ],

        [
            'id'            => '10000gcustomer',
            'name'          => 'test',
            'email'         => 'test@razorpay.com',
            'contact'       => '+919988776655',
            'merchant_id'   => '100000Razorpay'
        ],
        [
            'id'                 => '100011customer',
            'global_customer_id' => '10000gcustomer',
            'name'               => 'test',
            'email'              => 'test@razorpay.com',
            'contact'            => '1234567890',
            'merchant_id'        => '10000000000000'
        ],
    ];

    protected $customerApps = array(
        array(
            'customer_id'   => '10000gcustomer',
            'id'            => '1000000custapp',
            'device_token'  => '1000custdevice',
            'merchant_id'   => '100000Razorpay',
        ),
    );

    protected $customerTokens = array(
        [
            'id'            => '1000custwallet',
            'token'         => '100wallettoken',
            'customer_id'   => '100000customer',
            'method'        => 'wallet',
            'wallet'        => 'paytm',
            'bank'          => null,
            'card_id'       => null,
            'used_at'       => 10,
            'created_at'    => 1500000000,
            'status'        => 'activated',
        ],
        [
            'id'            => '100000custbank',
            'token'         => '10000banktoken',
            'customer_id'   => '100000customer',
            'method'        => 'netbanking',
            'bank'          => 'HDFC',
            'wallet'        => null,
            'card_id'       => null,
            'used_at'       => 10,
            'created_at'    => 1500000001,
            'status'        => 'activated',
        ],
        [
            'id'            => '100000custcard',
            'token'         => '10000cardtoken',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'bank'          => null,
            'wallet'        => null,
            'recurring'     => false,
            'card_id'       => '100000000lcard',
            'used_at'       => 10,
            'created_at'    => 1500000002,
            'status'        => 'activated',
        ],
        [
            'id'            => '100000rpaycard',
            'token'         => '10002cardtoken',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'bank'          => null,
            'wallet'        => null,
            'recurring'     => false,
            'card_id'       => '10000000rucard',
            'used_at'       => 10,
            'created_at'    => 1500000002,
            'status'        => 'activated',
        ],
        [
            'id'            => '100001custcard',
            'token'         => '10001cardtoken',
            'customer_id'   => '100000customer',
            'method'        => 'card',
            'bank'          => null,
            'wallet'        => null,
            'card_id'       => '100000001lcard',
            'used_at'       => 10,
            'created_at'    => 1500000003,
            'status'        => 'activated',
        ],
        [
            'id'            => '10000custgcard',
            'token'         => '1000gcardtoken',
            'customer_id'   => '10000gcustomer',
            'merchant_id'   => '100000Razorpay',
            'method'        => 'card',
            'card_id'       => '100000000gcard',
            'bank'          => null,
            'wallet'        => null,
            'used_at'       => 10,
            'created_at'    => 1500000004,
            'status'        => 'activated',
        ],
        [
            'id'                => '100000emandate',
            'token'             => '10001emantoken',
            'customer_id'       => '100000customer',
            'method'            => 'emandate',
            'bank'              => 'HDFC',
            'beneficiary_name'  => 'BeneficiaryName',
            'account_number'    => '10000',
            'IFSC'              => 'ifsc',
            'account_type'      => 'account_type',
            'used_at'           => 10,
            'created_at'        => 1500000005,
            'max_amount'        => 105,
            'status'            => 'activated',
        ],
    );

    protected $bankAccounts = array(
        array(
            'id'            => '1000000lcustba',
            'merchant_id'   => '10000000000000',
            'entity_id'     => '100000customer',
            'type'          => 'customer'
        ),
    );

    public function createCustomers()
    {
        $customers = array();

        foreach ($this->customers as $customer)
        {
            $customers[] = $this->fixtures->create('customer', $customer);
        }

        return $customers;
    }

    public function createAppTokens()
    {
        $apps = array();

        foreach ($this->customerApps as $attributes)
        {
            $apps[] = $this->fixtures->create('app_token', $attributes);
        }

        return $apps;
    }

    public function createTokens()
    {
        $tokens = array();

        foreach ($this->customerTokens as $attributes)
        {
            if (($attributes['method'] === 'card') or
                ($attributes['method'] === 'emandate'))
            {
                $attributes['expired_at'] = $this->expiredAtTime;
            }
            $tokens[] = $this->fixtures->create('token', $attributes);
        }

        return $tokens;
    }

    public function createBankAccounts()
    {
        $bankAccounts = array();

        foreach ($this->bankAccounts as $attributes)
        {
            $bankAccounts[] = $this->fixtures->create('bank_account', $attributes);
        }

        return $bankAccounts;
    }

    public function createCustomerBalance($attributes = [])
    {
        $merchantId = '10000000000000';
        $customerValues = [
            'id'            => '200000customer',
            'name'          => 'Balancetest',
            'email'         => 'balancetest@razorpay.com',
            'contact'       => '1234567890',
            'merchant_id'   => $merchantId
        ];

        $customer = $this->fixtures->create('customer', $customerValues);

        $customerBalanceValues = [
            'customer_id'   => $customer->getId(),
            'merchant_id'   => $merchantId,
            'balance'       => 0,
            'daily_usage'   => 0,
            'weekly_usage'  => 0,
            'monthly_usage' => 0,
            'max_balance'   => 2000000,
        ];

        $customerBalanceValues = array_merge($customerBalanceValues, $attributes);

        return $this->fixtures->create('customer_balance', $customerBalanceValues);
    }

    public function createEmandateToken($attributes = [])
    {
        $defaultValues = [
            'merchant_id'      => '10000000000000',
            'customer_id'      => '100000customer',
            'method'           => 'emandate',
            'bank'             => 'UTIB',
            'recurring'        => '1',
            'max_amount'       => '9999900',
            'auth_type'        => 'netbanking',
            'account_number'   => '914010009305862',
            'ifsc'             => 'UTIB0000123',
            'beneficiary_name' => 'Test account',
            'recurring_status' => 'initiated',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->fixtures->create('token', $attributes);
    }

    public function createUpiPaymentsLocalCustomerToken($attributes = [])
    {
        $upiLocalTokenDefaultValues = [
            'id'            => '1000000custupi',
            'token'         => '10000upitoken',
            'customer_id'   => '100000customer',
            'method'        => 'upi',
            'bank'          => null,
            'wallet'        => null,
            'recurring'     => false,
            'vpa_id'        => '10000000000vpa',
            'used_at'       => 10,
            'created_at'    => 1500000002,
        ];

        $attributes = array_merge($upiLocalTokenDefaultValues, $attributes);

        return $this->fixtures->create('token', $attributes);
    }

    public function createUpiPaymentsGlobalCustomerToken($attributes = [])
    {
        $upiGlobalTokenAttributes = [
                'id'            => '100000custgupi',
                'token'         => '10000gupitoken',
                'customer_id'   => '10000gcustomer',
                'merchant_id'   => '100000Razorpay',
                'method'        => 'upi',
                'vpa_id'        => '1000000000gupi',
                'bank'          => null,
                'wallet'        => null,
                'used_at'       => 10,
                'created_at'    => 1500000004,
        ];

        $attributes = array_merge($upiGlobalTokenAttributes, $attributes);

        return $this->fixtures->create('token', $attributes);
    }
}
