<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Terminal\Shared;

class Terminal extends Base
{
    public function createAllSharedTerminals()
    {
        $this->createSharedHdfcTerminal();
        $this->createSharedAtomTerminal();
        $this->createSharedAxisTerminal();
        $this->createSharedBilldeskTerminal();
        $this->createSharedAxisGeniusTerminal();
        $this->createSharedKotakTerminal();
        $this->createSharedPaytmTerminal();
        $this->createSharedMobikwikTerminal();
        $this->createSharedNetbankingHdfcTerminal();
        $this->createSharedNetbankingKotakTerminal();
        $this->createSharedCybersourceHdfcTerminal();
        $this->createSharedCybersourceHdfcRecurringTerminals();
        $this->createSharedCybersourceAxisTerminal();
        $this->createSharedFirstDataTerminal();
        $this->createSharedEbsTerminal();
    }

    public function createMultipleNetbankingTerminals()
    {
        $this->createSharedAtomNetbankingTerminal();
        $this->createSharedBilldeskTerminal();
        $this->createSharedNetbankingKotakTerminal();
        $this->createSharedEbsTerminal();
    }

    public function createMultipleCategoryTerminals()
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $this->createSharedHdfcTerminal(['id' => 'SharedTrmnl123',
                                         'merchant_id' => $sharedMerchantAccount,
                                         'category' => 123,
                                         'shared' => 1]);

        $this->createSharedHdfcTerminal(['id' => 'SharedTrmnl124',
                                         'merchant_id' => $sharedMerchantAccount,
                                         'category' => 124,
                                         'shared' => 1]);

        $this->createSharedHdfcTerminal(['id' => 'SharedTrmnl125',
                                         'merchant_id' => $sharedMerchantAccount,
                                         'category' => 125,
                                         'shared' => 1]);
    }

    public function createAtomTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'atom',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'netbanking'                => 1,
        );

        return parent::create($attributes);
    }

    public function createDisableDefaultHdfcTerminal()
    {
        $term = \RZP\Models\Terminal\Entity::findOrFail('1n25f6uN5S1Z5a');
        $term->forceDelete();

        return $term;
    }

    public function createEbsTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'ebs',
            'gateway_merchant_id'   => 'abcd',
            'gateway_secure_secret' => 'secret',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 0);

        return parent::create($attributes);
    }

    public function createBilldeskTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 0);

        return parent::create($attributes);
    }

    public function createAxisGeniusTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'razorpay_axis_genius_terminal',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'atom',
            'card'                  => 1,
            'netbanking'            => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomNetbankingTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'atom',
            // 'card'                  => 1,
            'netbanking'            => 1,
            'shared'                => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'axis_migs',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_migs',
            'gateway_terminal_id'       => 'nodal account axis_migs',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedFirstDataTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::FIRST_DATA_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'first_data',
            'card'                      => 1,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'random',
            'gateway_secure_secret'     => 'secret',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPayzappTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::PAYZAPP_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'wallet_payzapp',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => '298374982374928374928',
            'gateway_merchant_id2'      => '3456',
            'gateway_terminal_id'       => '293847923847293874983',
            'gateway_terminal_password' => 'S9DFIU9S8DFU98SD',
            'gateway_access_code'       => '2938',
            'gateway_secure_secret'     => '102983092182309128123',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPayumoneyTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::PAYUMONEY_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'wallet_payumoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'payumoney_merchant',
            'gateway_merchant_id2'      => 'payumoney_auth_code',
            'gateway_terminal_id'       => 'payumoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => '293823',
            'gateway_secure_secret'     => 'secret',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedOlamoneyTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::OLAMONEY_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'wallet_olamoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'olamoney_merchant',
            'gateway_merchant_id2'      => 'olamoney_auth_code',
            'gateway_terminal_id'       => 'olamoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'random_access_code',
            'gateway_secure_secret'     => 'secret',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedFreechargeTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::FREECHARGE_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'wallet_freecharge',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'freecharge_merchant',
            'gateway_terminal_id'       => 'freecharge_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_secure_secret'     => 'secret',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAirtelmoneyTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::AIRTELMONEY_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'wallet_airtelmoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'airtelmoney_merchant',
            'gateway_merchant_id2'      => 'airtelmoney_auth_code',
            'gateway_terminal_id'       => 'airtelmoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'random_access_code',
            'gateway_secure_secret'     => 'secret',
        );

        return parent::create($attributes);
    }

    public function createSharedCybersourceHdfcTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::CYBERSOURCE_HDFC_TERMINAL;
        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'merchant_id',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCybersourceHdfcRecurringTerminals(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'merchant_id',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        );

        // Add recurring 3ds terminal;
        $attributes['id'] = '1RecurringTerm';
        $attributes['recurring'] = 1;

        $this->createEntityInTestAndLive('terminal', $attributes);

        // Add recurring 3ds
        $attributes['id'] = '2RecurringTerm';
        $attributes['recurring'] = 2;

        $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCybersourceAxisTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::CYBERSOURCE_AXIS_TERMINAL;
        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'cybersource',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedEbsTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::EBS_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'ebs',
            'gateway_merchant_id'   => 'abcd',
            'gateway_secure_secret' => 'secret',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 1,
        );
        return parent::create($attributes);
    }


    public function createDirectBilldeskTerminal(array $attributes = array())
    {
        $defaultValues = array(
            'id'                    => '10BillDirTrmnl',
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 0,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }
    public function createSharedBilldeskTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 1,
        );

        return parent::create($attributes);
    }

    public function createSharedBilldeskTpvTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'shared'                => 1,
            'category'              => 9999,
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisGeniusTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedKotakTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::KOTAK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'kotak',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay kotak',
            'gateway_terminal_id'       => 'nodal account kotak',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPaytmTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::PAYTM_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpay paytm',
            'gateway_terminal_id'       => 'nodal account paytm',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNetbankingHdfcTerminal(array $attributes = array())
    {
        $attributes = array(
            'merchant_id'               => '10000000000000',
            'card'                      => 0,
            'netbanking'                => 1,
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1);

        return parent::create($attributes);
    }

    public function createSharedNetbankingHdfcTerminal(array $attributes = array())
    {
        $attributes = array(
            'id'                        => Shared::NETBANKING_HDFC_TERMINAL,
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef');

        return parent::create($attributes);
    }

    public function createSharedSharpTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::SHARP_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'sharp',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'emi'                       => 1);

        return parent::create($attributes);
    }

    public function createSharedHDFCEmiTerminal()
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $attributes = array(
            'id'                        => 'ShrdHdfcEmiTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'emi'                       => 1,
            'emi_duration'              => 9,
            'shared'                    => 1);

        return parent::create($attributes);
    }

    public function createSharedMobikwikTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::MOBIKWIK_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'mobikwik',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay paytm',
            'gateway_terminal_id'       => 'nodal account paytm',
            'gateway_terminal_password' => 'razorpay_password',
        );

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedSbiepayTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::SBIEPAY_RAZORPAY_TERMINAL;

        $attributes = array(
            'id'                    => $termId,
            'merchant_id'           => '10000000000000',
            'gateway'               => 'sbiepay',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0);

        return parent::create($attributes);
    }

    public function createSharedHdfcTerminal(array $attributes = array())
    {
        $termId = \RZP\Models\Terminal\Shared::HDFC_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'hdfc',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay hdfc',
            'gateway_terminal_id'       => 'account hdfc',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNetbankingKotakTerminal(array $attributes = array())
    {
        $defaultValues = array(
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_kotak',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 0,
            'netbanking'                => 1,);

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingKotakTerminal(array $attributes = array())
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = array(
            'id'                        => Shared::NETBANKING_KOTAK_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_kotak',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'netbanking'                => 1,
            'shared'                    => 1);

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedAmexTerminal(array $attributes = array())
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $termId = \RZP\Models\Terminal\Shared::AMEX_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'amex',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay amex',
            'gateway_terminal_id'       => 'nodal account amex',
            'gateway_terminal_password' => 'razorpay_password',
            'shared'                    => 1,
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAmexCategoryTerminals()
    {
        // Create education terminal
        $attributes = ['id' => 'ShAmexEduTrmnl', 'network_category' => 'education'];

        $this->createSharedAmexTerminal($attributes);

        // Create education services terminal
        $attributes = ['id' => 'ShAmexUtilTmnl', 'network_category' => 'utilities'];

        $this->createSharedAmexTerminal($attributes);

        // Create retail services terminal
        $attributes = ['id' => 'ShRetailSvcsTl', 'network_category' => 'retail_services'];

        $this->createSharedAmexTerminal($attributes);
    }

    public function createSharedUPITerminal(array $attributes)
    {
        $termId = Shared::UPI_ICICI_RAZORPAY_TERMINAL;

        $defaultValues = array(
            'id'                        => $termId,
            'merchant_id'               => '1MercShareTerm',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_terminal_password' => 'razorpay_password',
        );

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }
}
