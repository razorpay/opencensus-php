<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Models\Terminal\Shared;
use RZP\Models\Terminal\Mode;
use RZP\Models\Terminal\Type;
use RZP\Models\Base\UniqueIdEntity;

class Terminal extends Base
{
    public function createAllSharedTerminals()
    {
        $this->createSharedHdfcTerminal();
        $this->createSharedAtomTerminal();
        $this->createSharedAxisTerminal();
        $this->createSharedBilldeskTerminal();
        $this->createSharedAxisGeniusTerminal();
        $this->createSharedPaytmTerminal();
        $this->createSharedMobikwikTerminal();
        $this->createSharedNetbankingHdfcTerminal();
        $this->createSharedNetbankingKotakTerminal();
        $this->createSharedNetbankingIciciTerminal();
        $this->createSharedNetbankingAirtelTerminal();
        $this->createSharedNetbankingAxisTerminal();
        $this->createSharedNetbankingFederalTerminal();
        $this->createSharedNetbankingRblTerminal();
        $this->createSharedNetbankingIndusindTerminal();
        $this->createSharedNetbankingPnbTerminal();
        $this->createSharedCybersourceHdfcTerminal();
        $this->createSharedCybersourceHdfcRecurringTerminals();
        $this->createSharedCybersourceAxisTerminal();
        $this->createSharedFirstDataTerminal();
        $this->createSharedEbsTerminal();
        $this->createSharedBladeTerminal();
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

        $this->createSharedHdfcTerminal([
            'id' => 'SharedTrmnl123',
            'merchant_id' => $sharedMerchantAccount,
            'category' => 123,
        ]);

        $this->createSharedHdfcTerminal([
            'id' => 'SharedTrmnl124',
            'merchant_id' => $sharedMerchantAccount,
            'category' => 124,
        ]);

        $this->createSharedHdfcTerminal([
            'id' => 'SharedTrmnl125',
            'merchant_id' => $sharedMerchantAccount,
            'category' => 125,
        ]);
    }

    public function createAtomTerminal(array $attributes = [])
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'atom',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'netbanking'                => 1,
        ];

        return parent::create($attributes);
    }

    public function createDisableDefaultHdfcTerminal()
    {
        $term = \RZP\Models\Terminal\Entity::findOrFail('1n25f6uN5S1Z5a');
        $term->forceDelete();

        return $term;
    }

    public function disableTerminal($id = '1RecurringTerm')
    {
        return $this->fixtures->edit('terminal', $id, ['enabled' => false]);
    }

    public function enableTerminal($id = '1RecurringTerm')
    {
        return $this->fixtures->edit('terminal', $id, ['enabled' => true]);
    }

    public function createEbsTerminal(array $attributes = [])
    {
        $attributes = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'ebs',
            'gateway_merchant_id'   => 'abcd',
            'gateway_secure_secret' => 'secret',
            'card'                  => 0,
            'netbanking'            => 1,
        ];

        return parent::create($attributes);
    }

    public function createBilldeskTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createAxisGeniusTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'razorpay_axis_genius_terminal',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'atom',
            'card'                  => 1,
            'netbanking'            => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomNetbankingTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'atom',
            // 'card'                  => 1,
            'netbanking'            => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_terminal_id'   => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::AXIS_MIGS_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'axis_migs',
            'gateway_acquirer'          => 'axis',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay axis_migs',
            'gateway_terminal_id'       => 'nodal account axis_migs',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedFirstDataTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::FIRST_DATA_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'first_data',
            'gateway_acquirer'          => 'icic',
            'card'                      => 1,
            'gateway_merchant_id'       => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedBladeTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::BLADE_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'blade',
            'card'                      => 1,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPayzappTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::PAYZAPP_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_payzapp',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => '298374982374928374928',
            'gateway_merchant_id2'      => '3456',
            'gateway_terminal_id'       => '293847923847293874983',
            'gateway_terminal_password' => 'S9DFIU9S8DFU98SD',
            'gateway_access_code'       => '2938',
            'gateway_secure_secret'     => '102983092182309128123',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPayumoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::PAYUMONEY_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_payumoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'payumoney_merchant',
            'gateway_merchant_id2'      => 'payumoney_auth_code',
            'gateway_terminal_id'       => 'payumoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => '293823',
            'gateway_secure_secret'     => 'secret',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedOlamoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::OLAMONEY_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_olamoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'olamoney_merchant',
            'gateway_merchant_id2'      => 'olamoney_auth_code',
            'gateway_terminal_id'       => 'olamoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'random_access_code',
            'gateway_secure_secret'     => 'secret',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedMpesaTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::MPESA_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_mpesa',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'mpesa_merchant',
            'gateway_merchant_id2'      => 'mpesa_merchant_2',
            'gateway_secure_secret'     => 'secret',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedFreechargeTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::FREECHARGE_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_freecharge',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'random_id',
            'gateway_terminal_id'       => 'freecharge_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_secure_secret'     => 'secret',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAirtelmoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::AIRTELMONEY_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_airtelmoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'airtelmoney_merchant',
            'gateway_merchant_id2'      => 'airtelmoney_auth_code',
            'gateway_terminal_id'       => 'airtelmoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'random_access_code',
            'gateway_secure_secret'     => 'secret',
        ];

        return parent::create($attributes);
    }

    public function createSharedJiomoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::JIOMONEY_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_jiomoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'jiomoney_merchant',
            'gateway_access_code'       => 'random_access_code',
            'gateway_secure_secret'     => 'secret',
        ];

        return parent::create($attributes);
    }

    public function createSharedSbibuddyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::SBIBUDDY_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_sbibuddy',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'sbibuddy_merchant',
            'gateway_secure_secret'     => 'secret',
        ];

        return parent::create($attributes);
    }

    public function createSharedCybersourceHdfcTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CYBERSOURCE_HDFC_TERMINAL;
        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::RECURRING_3DS => '1'
            ],
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'merchant_id',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDynamicSharedCybersourceHdfcTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'merchant_id',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedHdfcRecurringTerminals(array $attributes = [])
    {
        $attributes = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'hdfc',
            'card'                      => 1,
            'netbanking'                => 0,
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'shared_merchant_hdfc',
            'gateway_terminal_id'       => 'shared_terminal_hdfc',
            'gateway_terminal_password' => 'shared_account_hdfc_terminal_pass',
        ];

        // Add fss recurring supports both 3ds and non3ds terminal;
        $attributes['id'] = 'FssRecurringTl';

        $attributes['type'] = [
            Type::RECURRING_3DS => '1',
            Type::RECURRING_NON_3DS => '1',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCybersourceHdfcRecurringTerminals(array $attributes = [])
    {
        $attributes = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'merchant_id',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        ];

        // Add recurring 3ds terminal;
        $attributes['id'] = '1RecurringTerm';
        $attributes['type'] = [
            Type::NON_RECURRING => '1',
            Type::RECURRING_3DS => '1'
        ];

        $this->createEntityInTestAndLive('terminal', $attributes);

        $attributes['id'] = '3RecurringTerm';
        $attributes['type'] = [
            Type::NON_RECURRING => '1',
            Type::RECURRING_3DS => '1'
        ];

        $this->createEntityInTestAndLive('terminal', $attributes);

        // Add recurring 3ds
        $attributes['id'] = '2RecurringTerm';
        $attributes['type'] = [
            Type::RECURRING_NON_3DS => '1',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedFirstDataRecurringTerminals()
    {
        $attributes = [
            'id'                        => 'FDRcrgTrmnl3DS',
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'first_data',
            'gateway_acquirer'          => 'icic',
            'card'                      => 1,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::RECURRING_3DS => '1'
            ],
            'gateway_merchant_id'       => 'random',
        ];

        $this->createEntityInTestAndLive('terminal', $attributes);

        $attributes = [
            'id'                        => 'FDRcrgTrmlN3DS',
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'first_data',
            'gateway_acquirer'          => 'icic',
            'card'                      => 1,
            'type'                      => [
                Type::RECURRING_NON_3DS => '1'
            ],
            'mode'                      => Mode::PURCHASE,
            'gateway_merchant_id'       => 'random',
        ];

        $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedMigsRecurringTerminals()
    {
        $attributes = [
            'id'                        => 'MiGSRcgTmnl3DS',
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'axis_migs',
            'gateway_acquirer'          => 'axis',
            'card'                      => 1,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::RECURRING_3DS => '1'
            ],
            'gateway_merchant_id'       => 'random',
            'gateway_terminal_id'       => 'recurring_random',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $this->createEntityInTestAndLive('terminal', $attributes);

        $attributes['id']   = 'MiGSRcgTmlN3DS';
        $attributes['type'] = [
            Type::RECURRING_NON_3DS => '1'
        ];

        $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCybersourceAxisTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CYBERSOURCE_AXIS_TERMINAL;
        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'cybersource',
            'card'                      => 1,
            'netbanking'                => 0,
            'gateway_acquirer'          => 'axis',
            'gateway_merchant_id'       => 'cybersource',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedEbsTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::EBS_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'ebs',
            'gateway_merchant_id'   => 'abcd',
            'gateway_secure_secret' => 'secret',
            'card'                  => 0,
            'netbanking'            => 1,
        ];

        return parent::create($attributes);
    }

    public function createDirectBilldeskTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                    => '10BillDirTrmnl',
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedBilldeskTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL;

        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => $sharedMerchantAccount,
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedBilldeskTpvTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::BILLDESK_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'category'              => 0,
            'tpv'                   => 1,
            'network_category'      => 'securities',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAxisGeniusTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::AXIS_GENIUS_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'axis_genius',
            'card'                      => 1,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'razorpay axis_genius',
            'gateway_terminal_id'       => 'nodal account axis_genius',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPaytmTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::PAYTM_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpay paytm',
            'gateway_terminal_id'       => 'nodal account paytm',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNetbankingHdfcTerminal(array $attributes = [])
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'card'                      => 0,
            'netbanking'                => 1,
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1
        ];

        return parent::create($attributes);
    }

    public function createSharedNetbankingHdfcTerminal(array $attributes = [])
    {
        $attributes = [
            'id'                        => Shared::NETBANKING_HDFC_TERMINAL,
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef'
        ];

        return parent::create($attributes);
    }

    public function createNetbankingCorporationTerminal(array $attributes = [])
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'card'                      => 0,
            'netbanking'                => 1,
            'gateway'                   => 'netbanking_corporation',
            'gateway_merchant_id'       => 'abcd',
            'gateway_secure_secret'     => 'secure_secret'
        ];

        return parent::create($attributes);
    }

    public function createSharedNetbankingCorporationTerminal(array $attributes = [])
    {
        $attributes = [
            'id'                        => Shared::NETBANKING_CORPORATION_TERMINAL,
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_corporation',
            'gateway_merchant_id'       => 'abcd',
            'gateway_secure_secret'     => 'secure_secret'
        ];

        return parent::create($attributes);
    }

    public function createSharedSharpTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::SHARP_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'sharp',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'emi'                       => 0,
        ];

        return parent::create($attributes);
    }

    public function createSharedHdfcEmiTerminal()
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $attributes = [
            'id'                        => 'ShrdHdfcEmiTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'hdfc',
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'emi'                       => 1,
            'emi_duration'              => 9,
            'emi_subvention'            => 'customer',
        ];

        return parent::create($attributes);
    }

    public function createSharedHdfcEmiMerchantSubventionTerminal()
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $attributes = [
            'id'                        => 'ShrdEmiMrSubTr',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'hdfc',
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'emi'                       => 1,
            'emi_duration'              => 9,
            'emi_subvention'            => 'merchant',
        ];

        return parent::create($attributes);
    }

    public function createSharedMobikwikTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::MOBIKWIK_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'mobikwik',
            'card'                      => 0,
            'gateway_merchant_id'       => 'razorpay paytm',
            'gateway_terminal_id'       => 'nodal account paytm',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedHdfcTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::HDFC_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'hdfc',
            'gateway_acquirer'          => 'hdfc',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay hdfc',
            'gateway_terminal_id'       => 'account hdfc',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDynamicSharedHdfcTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'hdfc',
            'gateway_acquirer'          => 'hdfc',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay hdfc',
            'gateway_terminal_id'       => 'account hdfc',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNetbankingKotakTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_kotak',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 0,
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingKotakTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_KOTAK_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_kotak',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingIciciTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_ICICI_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_icici',
            'gateway_merchant_id'       => 'razorpay_submerchant',
            'gateway_merchant_id2'      => 'razorpay_icici',
            'gateway_secure_secret'     => 'razorpay_password',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingIciciTpvTerminal()
    {
        $attributes = [
            'id'                => Shared::NETBANKING_ICICI_TPV_TERMINAL,
            'network_category'  => 'securities',
            'tpv'               => 1,
        ];

        return $this->createSharedNetbankingIciciTerminal($attributes);
    }

    public function createSharedNetbankingAirtelTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_AIRTEL_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_airtel',
            'gateway_merchant_id'       => 'test_merchant_id',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingAxisTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_AXIS_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_axis',
            'gateway_merchant_id'       => 'test_pid',
            'gateway_secure_secret'     => 'test_masterkey',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingAxisTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'                => Shared::NETBANKING_AXIS_TPV_TERMINAL,
            'network_category'  => 'securities',
            'tpv'               => 1,
        ];

        return $this->createSharedNetbankingAxisTerminal($attributes);
    }


    public function createSharedNetbankingIndusindTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_INDUSIND_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_indusind',
            'gateway_merchant_id'       => 'test_pid',
            'gateway_secure_secret'     => 'test_masterkey',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingIndusindTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::NETBANKING_INDUSIND_TPV_TERMINAL,
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingIndusindTerminal($attributes);
    }

    public function createSharedNetbankingPnbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'          => Shared::NETBANKING_PNB_TERMINAL,
            'merchant_id' => $merchantId,
            'gateway'     => 'netbanking_pnb',
            'netbanking'  => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingFederalTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_FEDERAL_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_federal',
            'gateway_merchant_id'       => 'netbanking_federal_merchant_id',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingFederalTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::NETBANKING_FEDERAL_TPV_TERMINAL,
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingFederalTerminal($attributes);
    }

    public function createSharedNetbankingRblTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_RBL_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_rbl',
            'gateway_merchant_id'       => 'netbanking_rbl_merchant_id',
            'gateway_merchant_id2'      => 'netbanking_rbl_merchant_id2',
            'gateway_access_code'       => 'random_rbl_code',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedNetbankingRblTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::NETBANKING_RBL_TPV_TERMINAL,
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingRblTerminal($attributes);
    }

    public function createSharedAmexTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $termId = \RZP\Models\Terminal\Shared::AMEX_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'amex',
            'card'                      => 1,
            'gateway_merchant_id'       => 'razorpay amex',
            'gateway_terminal_id'       => 'nodal account amex',
            'gateway_terminal_password' => 'razorpay_password',
        ];

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

    public function createSharedUpiIciciTerminal(array $attributes)
    {
        $termId = Shared::UPI_ICICI_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => true,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedOpenwalletTerminal(array $attributes = [])
    {
        $terminalId = Shared::OPENWALLET_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $terminalId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_openwallet',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiMindgateTerminal(array $attributes)
    {
        $termId = Shared::UPI_MINDGATE_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectTerminalForNonTestMerchant(array $attributes)
    {
        $defaultValues = [
            'id'                    => '10BillDirTrmn2',
            'merchant_id'           => '1MercShareTerm',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createDirectFreechargeTerminal(array $attributes)
    {
        //
        // adding a new direct terminal for freecharge to test
        // dealer integration
        //
        $defaultValues = [
            'id'                        => '101FrchrgeTmnl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'wallet_freecharge',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'random_id',
            'gateway_merchant_id2'      => 'freecharge_dealer',
            'gateway_terminal_id'       => 'freecharge_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_secure_secret'     => 'secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }
}
