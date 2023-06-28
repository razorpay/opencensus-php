<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Crypt;

use RZP\Models\Terminal\Mode;
use RZP\Models\Terminal\Type;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Shared;
use RZP\Models\Merchant\Account;
use RZP\Models\Terminal\TpvType;
use RZP\Tests\TestDummy\Factory;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Terminal\BankingType;
use RZP\Models\Payment\Processor\Upi;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\Netbanking;
use RZP\Models\Payment\Processor\CardlessEmi;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class Terminal extends Base
{

    use DbEntityFetchTrait;

    public function create(array $attributes = [])
    {
        $this->addEnabledBanksIfApplicable($attributes);

        return parent::create($attributes);
    }

    public function createEntityInTestAndLive($entity, $attributes = [])
    {
        $this->addEnabledBanksIfApplicable($attributes);

        return parent::createEntityInTestAndLive($entity, $attributes);
    }

    protected function addEnabledBanksIfApplicable(array & $attributes)
    {
        $netbanking = intval($attributes['netbanking'] ?? 0);
        $paylater   = intval($attributes['paylater'] ?? 0);

        $gatewayAquirer = $attributes['gateway_acquirer'] ?? '';

        $gateway = $attributes['gateway'] ?? null;

        if ((($netbanking !== 1) and ($paylater !== 1)) or
            ((in_array($gateway, Gateway::$methodMap[Method::NETBANKING], true) === false) and
             (PayLater::isMultilenderProvider($gatewayAquirer) === false)) or
            (isset($attributes['enabled_banks']) === true))
        {
            return;
        }

        if ($netbanking === 1)
        {
            $corporate = $attributes['corporate'] ?? BankingType::RETAIL_ONLY;

            $tpv = $attributes['tpv'] ?? TpvType::NON_TPV_ONLY;

            $supportedBanks = Netbanking::getSupportedBanksForGateway($gateway, $corporate, $tpv);

            $disabledBanks = Netbanking::getDefaultDisabledBanksForGateway($gateway, $corporate, $tpv);

            $enabledBanks = array_diff($supportedBanks, $disabledBanks);
        }
        elseif ($paylater === 1)
        {
            $supportedBanks = Paylater::getSupportedBanksForMultilenderProvider($gatewayAquirer);

            $disabledBanks = Paylater::getDefaultDisabledBanksForMultilenderProvider($gatewayAquirer);

            $enabledBanks = array_diff($supportedBanks, $disabledBanks);

            $enabledBanks = array_values($enabledBanks);
        }

        $attributes['enabled_banks'] = $enabledBanks;
    }

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
        $this->createSharedNetbankingCanaraTerminal();
        $this->createSharedNetbankingKotakTerminal();
        $this->createSharedNetbankingIciciTerminal();
        $this->createSharedNetbankingAirtelTerminal();
        $this->createSharedNetbankingObcTerminal();
        $this->createSharedNetbankingAxisTerminal();
        $this->createSharedNetbankingFederalTerminal();
        $this->createSharedNetbankingBobTerminal();
        $this->createSharedNetbankingIdfcTerminal();
        $this->createSharedNetbankingRblTerminal();
        $this->createSharedNetbankingAllahabadTerminal();
        $this->createSharedNetbankingIndusindTerminal();
        $this->createSharedNetbankingPnbTerminal();
        $this->createSharedNetbankingEquitasTerminal();
        $this->createSharedNetbankingYesbTerminal();
        $this->createSharedNetbankingCubTerminal();
        $this->createSharedNetbankingIbkTerminal();
        $this->createSharedNetbankingIdbiTerminal();
        $this->createSharedNetbankingUbiTerminal();
        $this->createSharedNetbankingScbTerminal();
        $this->createSharedNetbankingJkbTerminal();
        $this->createSharedNetbankingSbiTerminal();
        $this->createSharedCybersourceHdfcTerminal();
        $this->createSharedCybersourceHdfcRecurringTerminals();
        $this->createSharedCybersourceAxisTerminal();
        $this->createSharedHitachiTerminal();
        $this->createSharedFirstDataTerminal();
        $this->createSharedEbsTerminal();
        $this->createSharedBladeTerminal();
        $this->createSharedFssTerminal();
        $this->createSharedEmandateAxisTerminal();
        $this->createSharedCardlessEmiTerminal();
        $this->createSharedCardlessEmiWalnut369Terminal();
        $this->createSharedCardlessEmiSezzleTerminal();
        $this->createSharedNetbankingKvbTerminal();
        $this->createSharedNetbankingKvbTpvTerminal();
        $this->createSharedNetbankingSvcTerminal();
        $this->createSharedNetbankingJsbTerminal();
        $this->createSharedNetbankingIobTerminal();
        $this->createSharedNetbankingFsbTerminal();
        $this->createSharedNetbankingAusfTerminal();
        $this->createSharedNetbankingDlbTerminal();
        $this->createSharedNetbankingNsdlTerminal();
        $this->createSharedNetbankingBdblTerminal();
        $this->createSharedNetbankingSaraswatTerminal();
        $this->createSharedNetbankingUcoTerminal();
        $this->createSharedNetbankingUjjivanTerminal();
        $this->createSharedNetbankingUjjivanTpvTerminal();
        $this->createSharedNetbankingTmbTerminal();
        $this->createSharedNetbankingKarnatakaTerminal();
        $this->createsharednetbankingDbsTerminal();
    }

    public function createBharatQrIsgTerminal()
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'isg',
            'gateway_merchant_id'       => '12345',
            'gateway_terminal_id'       => '40120840',
            'card'                      => 1,
            'mc_mpan'                   => base64_encode('5220240401208405'),
            'visa_mpan'                 => base64_encode('4403844012084006'),
            'rupay_mpan'                => base64_encode('6100030401208403'),
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::BHARAT_QR => '1',
            ],
        ];

        return $this->create($attributes);
    }

    public function createBharatQrWorldlineTerminal()
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'worldline',
            'gateway_merchant_id'       => '037122003842039',
            'gateway_terminal_id'       => '70374018',
            'gateway_acquirer'          => 'axis',
            'card'                      => 1,
            'gateway_terminal_password' => '9900991100',
            'mc_mpan'                   => base64_encode('5122600004774122'),
            'visa_mpan'                 => base64_encode('4604901004774122'),
            'rupay_mpan'                => base64_encode('6100020004774141'),
            'vpa'                       => 'MAB.037122003842039@AXISBANK',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::BHARAT_QR     => '1',
            ],
        ];

        return parent::create($attributes);
    }

    public function createBharatQrTerminal()
    {
        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'hitachi',
            'gateway_merchant_id'       => 'abcd_hitachi_bharat',
            'gateway_terminal_id'       => 'abcde',
            'gateway_acquirer'          => 'ratn',
            'gateway_terminal_password' => 'abcdef',
            'card'                      => 1,
            'mc_mpan'                   => base64_encode('4287346823986423'),
            'visa_mpan'                 => base64_encode('5287346823986423'),
            'rupay_mpan'                => base64_encode('6287346823986423'),
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::BHARAT_QR => '1',
            ],
        ];

        return $this->create($attributes);

    }

    public function createBharatQrTerminalUpi(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'abcd_bharat_qr',
            'gateway_terminal_id'       => 'abcde',
            'gateway_acquirer'          => 'ratn',
            'gateway_terminal_password' => 'abcdef',
            'upi'                       => true,
            'vpa'                       => 'random@icici',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::BHARAT_QR => '1'
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createBharatQrUpiMindgateTerminal()
    {
        $termId = Shared::UPI_MINDGATE_BQR_TERMINAL;

        $attributes = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'abcd_bharat_qr',
            'gateway_terminal_id'       => 'abcde',
            'gateway_acquirer'          => 'ratn',
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => true,
            'gateway_acquirer'          => 'hdfc',
            'vpa'                       => 'rndm.razorpay@hdfcbank',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::BHARAT_QR => '1'
            ],
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
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
            'network_category'          => 'ecommerce',
        ];

        return $this->create($attributes);
    }

    public function createDisableDefaultHdfcTerminal()
    {
        return $this->disableTerminal('1n25f6uN5S1Z5a');
    }

    public function createEnableDefaultHdfcTerminal()
    {
        return $this->enableTerminal('1n25f6uN5S1Z5a');
    }

    public function disableTerminal($id = '1RecurringTerm')
    {
        return $this->fixtures->edit('terminal', $id, ['enabled' => false]);
    }

    public function enableTerminal($id = '1RecurringTerm')
    {
        return $this->fixtures->edit('terminal', $id, ['enabled' => true]);
    }

    public function setEnabledBanks($id = '1RecurringTerm', array $enabledBanks = [])
    {
        return $this->fixtures->edit('terminal', $id, ['enabled_banks' => $enabledBanks]);
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

        return $this->create($attributes);
    }

    public function createPayuIntentTerminal()
    {
        $attributes = [
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createPayuTerminal($attributes);
    }

    public function createPayuTerminal(array $override)
    {
        $attributes = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'payu',
            'card'                  => 1,
            'netbanking'            => 1,
            'upi'                   => 1,
            'emi'                   => 1,
            'emi_subvention'        => 'customer',
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
            'enabled_wallets'       => ['jiomoney','mobikwik','paytm'],
            'mode'                  =>  '2',
        ];
        $attributes = array_merge($attributes, $override);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPayuEmandateTerminal(array $override)
    {
        $attributes = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'payu',
            'emandate'              => 1,
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
            'mode'                  =>  '2',
            'type'                  => [
                'recurring_3ds'                 => '1',
                'recurring_non_3ds'             => '1',
                'direct_settlement_with_refund' => '1',
                'optimizer'                     => '1'
            ],
        ];
        $attributes = array_merge($attributes, $override);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCashfreeIntentTerminal()
    {
        $attributes = [
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createCashfreeTerminal($attributes);
    }

    public function createCashfreeTerminal(array $override)
    {
        $attributes = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'cashfree',
            'card'                  => 1,
            'upi'                   => 1,
            'netbanking'            => 1,
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
        ];
        $attributes = array_merge($attributes, $override);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCcavenueTerminal(array $attributes = [])
    {
        $default  = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'ccavenue',
            'card'                  => 1,
            'netbanking'            => 1,
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
            'gateway_access_code'   => 'qwerty890',
            'enabled_wallets'       => ['paytm'],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    /**
     * Create the terminal on ccavenue gateway with UPI collect enabled.
     *
     * @return array
     */
    public function createCcavenueUpiTerminal()
    {
        $attributes = [
            Method::UPI                   => '1',
            'type'                        => [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '0',
            ],
        ];

        return $this->createCcavenueTerminal($attributes);
    }

    /**
     * Create the terminal on ccavenue gateway with UPI intent enabled.
     *
     * @return array
     */
    public function createCcavenueIntentTerminal()
    {
        $attributes = [
            Method::UPI                   => '1',
            'type'                        => [
                Type::PAY => '1',
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '0',
            ],
        ];

        return $this->createCcavenueTerminal($attributes);;

    }

    public function createPinelabsTerminal()
    {
        $attributes = [
            'merchant_id'            => '10000000000000',
            'gateway'                => 'pinelabs',
            'card'                   => 1,
            'netbanking'             => 0,
            'gateway_merchant_id'    => 'abcd',
            'gateway_access_code'    => 'test_access_code',
            'gateway_secure_secret'  => 'secret',
            'gateway_access_code'    => 'dummy',
            'upi'                    => 1,
            'mode'                   => 2,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createBilldeskOptimizerIntentTerminal()
    {
        $attributes = [
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createBilldeskOptimizerTerminal($attributes);
    }

    public function createBilldeskOptimizerTerminal(array $override)
    {
        $attributes = [
            'merchant_id'            => '10000000000000',
            'gateway'                => 'billdesk_optimizer',
            'card'                   => 1,
            'upi'                    => 1,
            'netbanking'             => 0,
            'gateway_merchant_id'    => 'abcd',
            'gateway_secure_secret2' => 'secret',
            'mode'                   => 2,
        ];
        $attributes = array_merge($attributes, $override);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createOptimizerRazorpayTerminal(array $override)
    {
        $attributes = [
            'merchant_id'            => '10000000000000',
            'gateway'                => 'optimizer_razorpay',
            'card'                   => 1,
            'upi'                    => 1,
            'netbanking'             => 0,
            'gateway_merchant_id'    => 'abcd',
            'gateway_secure_secret'  => 'secret',
            'mode'                   => 2,
            'type'                      => [
                Type::OPTIMIZER => '1',
                TYPE::NON_RECURRING => '1'
            ],
        ];
        $attributes = array_merge($attributes, $override);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createOptimizerRazorpayIntentTerminal(array $override)
    {
        $attributes = [
            'type'                      => [
                Type::OPTIMIZER => '1',
                TYPE::PAY   => '1',
                TYPE::NON_RECURRING => '1'
            ]
        ];

        return $this->createOptimizerRazorpayTerminal($attributes);
    }

    public function createIngenicoTerminal()
    {
        $attributes = [
            'merchant_id'            => '10000000000000',
            'gateway'                => 'ingenico',
            'card'                   => 1,
            'netbanking'             => 0,
            'gateway_merchant_id'    => 'abcd',
            'gateway_access_code'    => 'test_access_code',
            'gateway_secure_secret'  => 'secret',
            'gateway_access_code'    => 'dummy'
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCheckoutDotComTerminal(array $override)
    {
        $attributes = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'checkout_dot_com',
            'card'                  => 1,
            'upi'                   => 0,
            'netbanking'            => 0,
            'gateway_merchant_id'   => 'abcd',
            'category'              => '1240',
        ];
        $attributes = array_merge($attributes, $override);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCheckoutDotComRecurringTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'checkout_dot_com',
            'card'                  => 1,
            'upi'                   => 0,
            'netbanking'            => 0,
            'gateway_merchant_id'   => 'abcd1',
            'category'              => '1240',
            'international'         =>  1,
            'type'                      => [
                Type::RECURRING_NON_3DS => '1',
                Type::RECURRING_3DS => '1'
            ],
        ];
        $attributes = array_merge($defaultValues, $attributes);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCheckoutDotComNonRecurringTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'checkout_dot_com',
            'card'                  => 1,
            'upi'                   => 0,
            'netbanking'            => 0,
            'gateway_merchant_id'   => 'abcd1',
            'category'              => '1240',
            'international'         =>  1,
            'type'                      => [
                Type::NON_RECURRING => '1',
            ],
        ];
        $attributes = array_merge($defaultValues, $attributes);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCheckoutDotComTerminalAllTypes(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'checkout_dot_com',
            'card'                  => 1,
            'upi'                   => 0,
            'netbanking'            => 0,
            'gateway_merchant_id'   => 'abcd1',
            'category'              => '1240',
            'international'         =>  1,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::RECURRING_NON_3DS => '1',
                Type::RECURRING_3DS => '1'
            ],
        ];
        $attributes = array_merge($defaultValues, $attributes);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createZaakpayTerminal()
    {
        $attributes = [
            'merchant_id'            => '10000000000000',
            'gateway'                => 'zaakpay',
            'card'                   => 1,
            'netbanking'             => 0,
            'gateway_merchant_id'    => 'abcd',
            'network_category'       => 'ecommerce',
            'gateway_secure_secret'  => 'secret',
            'gateway_secure_secret2' => 'secret2',
            'gateway_access_code'    => 'dummy'
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createBilldeskTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'billdesk',
            'gateway_merchant_id'   => 'abcd',
            'card'                  => 0,
            'netbanking'            => 1,
            'type'                  => [
                'non_recurring' => '1'
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'atom',
            'card'                      => 1,
            'netbanking'                => 1,
            'gateway_merchant_id'       => 'razorpay',
            'gateway_terminal_password' => 'razorpay_password',
            'network_category'          => 'ecommerce',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomTpvTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::ATOM_RAZORPAY_TPV_TERMINAL;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'atom',
            'card'                  => 0,
            'netbanking'            => 1,
            'tpv'                   => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_access_code'   => 'random_code',
            'gateway_secure_secret' => 'random_secret',
            'network_category'      => 'ecommerce',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedAtomNetbankingTerminal()
    {
        $termId = \RZP\Models\Terminal\Shared::ATOM_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'atom',
            'netbanking'            => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_access_code'   => 'random_code',
            'gateway_secure_secret' => 'random_secret',
            'network_category'      => 'ecommerce',
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
            'gateway_merchant_id'       => '3387026421',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedFssTerminal(array $attributes = [], $acquirer = 'barb')
    {
        $termId = \RZP\Models\Terminal\Shared::FSS_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'card_fss',
            'card'                      => 1,
            'gateway_merchant_id'       => 'random',
            'gateway_terminal_id'       => 'FssBobDebit123',
            'gateway_terminal_password' => 'password',
            'gateway_secure_secret'     => '12345678',
            'gateway_acquirer'          => $acquirer ?? 'barb',
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
            'gateway'                   => 'mpi_blade',
            'card'                      => 1,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedDigioTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::DIGIO_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'esigner_digio',
            'card'                      => 0,
            'emandate'                  => 1,
            'type'                      => [
                Type::RECURRING_3DS => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
            'shared'                    => 1,
            'gateway_merchant_id'       => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedLegaldeskTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::LEGALDESK_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'esigner_legaldesk',
            'card'                      => 0,
            'emandate'                  => 1,
            'type'                      => [
                Type::RECURRING_3DS     => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
            'shared'                    => 1,
            'gateway_merchant_id'       => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedEnachRblTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::ENACH_RBL_RAZORPAY_TERMINAL;

        $defaultValues = [
            TerminalEntity::ID                  => $termId,
            TerminalEntity::MERCHANT_ID         => '100000Razorpay',
            TerminalEntity::GATEWAY             => 'enach_rbl',
            TerminalEntity::GATEWAY_ACQUIRER    => 'ratn',
            TerminalEntity::CARD                => 0,
            TerminalEntity::EMANDATE            => 1,
            TerminalEntity::TYPE                => [
                Type::RECURRING_3DS => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
            TerminalEntity::SHARED              => 1,
            TerminalEntity::CAPABILITY          => 2,
            TerminalEntity::GATEWAY_MERCHANT_ID => 'random',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSbiTpvTerminal(array $attributes = [])
    {
        $id = Shared::NETBANKING_SBI_TPV_TERMINAL;

        $merchantId = Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => $id,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_SBI,
            'card'                  => 0,
            'netbanking'            => 1,
            'tpv'                   => 1,
            'gateway_merchant_id'   => 'netbanking_sbi_merchant_id',
            'gateway_secure_secret' => 'random_secret',
            'network_category'      => 'ecommerce',
            'type'                  => [
                'non_recurring'     => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedEnachNpciNetbankingTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::ENACH_NPCI_NETBANKING_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'enach_npci_netbanking',
            'gateway_acquirer'          => 'citi',
            'card'                      => 0,
            'emandate'                  => 1,
            'type'                      => [
                Type::RECURRING_3DS => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
            'shared'                    => 1,
            'gateway_merchant_id'       => 'dummy_gateway_mid',
            'gateway_merchant_id2'      => 'shared_utility_code',
            'gateway_access_code'       => 'CITI000PIGW',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        //$this->create($attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedEnachNpciNetbankingYesbTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::ENACH_NPCI_NETBANKING_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'enach_npci_netbanking',
            'gateway_acquirer'          => 'yesb',
            'card'                      => 0,
            'emandate'                  => 1,
            'type'                      => [
                Type::RECURRING_3DS => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
            'shared'                    => 1,
            'gateway_merchant_id'       => 'shared_utility_code',
            'gateway_merchant_id2'      => 'shared_utility_code',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        //$this->create($attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectEnachNpciNetbankingTerminal(array $attributes = [])
    {
        $default = [
            'id'                        => 'EnachNbNpciTnl',
            'merchant_id'               => '10000000000000',
            'gateway_merchant_id'       => 'direct_utility_code',
            'gateway_merchant_id2'      => 'direct_utility_code'
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createSharedEnachNpciNetbankingTerminal($attributes);
    }

    public function createDirectEnachRblTerminal(array $attributes = [])
    {
        $attributes = [
            'id'                        => '1EnachRblTrmnl',
            'merchant_id'               => '10000000000000',
        ];

        return $this->createSharedEnachRblTerminal($attributes);
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

        $default = [
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

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedMindgateRecurringTerminal(array $attributes = [])
    {
        $termId = Shared::UPI_MINDGATE_RECURRING_TERMINAL;

        $default = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_mindgate',
            'card'                      => 0,
            'netbanking'                => 0,
            'upi'                       => 1,
            'gateway_merchant_id'       => 'abcd',
            'gateway_merchant_id2'      => 'auth_code',
            'gateway_terminal_id'       => 'mindgate_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => '293823',
            'gateway_secure_secret'     => 'secret',
        ];

        $attributes['type'] = [
            Type::RECURRING_3DS     => '1',
            Type::RECURRING_NON_3DS => '1',
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDedicatedMindgateRecurringTerminal(array $attributes = [])
    {
        $termId = Shared::UPI_MINDGATE_RECURRING_TERMINAL_DEDICATED;

        $default = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_mindgate',
            'card'                      => 0,
            'netbanking'                => 0,
            'upi'                       => 1,
            'gateway_merchant_id'       => 'abcd',
            'gateway_merchant_id2'      => 'auth_code',
            'gateway_terminal_id'       => 'mindgate_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => '293823',
            'gateway_secure_secret'     => 'secret',
        ];

        $attributes['type'] = [
            Type::RECURRING_3DS     => '1',
            Type::RECURRING_NON_3DS => '1',
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedIciciRecurringTerminal(array $attributes = [])
    {
        $termId = Shared::UPI_ICICI_RECURRING_TERMINAL;

        $default = [
            'id'   => $termId,
            'type' => [
                Type::RECURRING_3DS     => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
            'tpv' => 2
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createDedicatedUpiIciciRecurringTerminal(array $attributes)
    {
        $termId = Shared::UPI_ICICI_RECURRING_TERMINAL_DEDICATED;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => true,
            'tpv'                       => 2,
            'type'                      => [
                Type::COLLECT               => '1',
                Type::RECURRING_3DS         => '1',
                Type::RECURRING_NON_3DS     => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDedicatedUpiIciciTerminal($attributes)
    {
        $termId = Shared::UPI_ICICI_TERMINAL_DEDICATED;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => 'LiveAccountMer',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => true,
            'tpv'                       => 2,
            'type'                      => [
                    Type::PAY               => '1',
                    Type::NON_RECURRING     => '1',
                    Type::ONLINE            => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedIciciRecurringIntentTerminal(array $attributes = [])
    {
        $termId = Shared::UPI_ICICI_RECURRING_INTENT_TERMINAL;

        $default = [
            'id'   => $termId,
            'type' => [
                Type::RECURRING_3DS     => '1',
                Type::RECURRING_NON_3DS => '1',
                Type::PAY               => '1'
            ],
            'tpv' => 2
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createDedicatedUpiIciciIntentRecurringTerminal(array $attributes = [])
    {
        $termId = Shared::UPI_ICICI_RECURRING_INTENT_TERMINAL_DEDICATED;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => true,
            'tpv'                       => 2,
            'type'                      => [
                Type::PAY               => '1',
                Type::RECURRING_3DS         => '1',
                Type::RECURRING_NON_3DS     => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDedicatedUpiIciciTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::UPI_ICICI_RECURRING_TPV_TERMINAL,
            'tpv'              => 1,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => true,
            'type'                      => [
                Type::PAY               => '1',
                Type::RECURRING_3DS         => '1',
                Type::RECURRING_NON_3DS     => '1',
            ]
        ];

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createSharedOlamoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::OLAMONEY_RAZORPAY_TERMINAL;

        $default = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'wallet_olamoney',
            'card'                      => 0,
            'netbanking'                => 0,
            'gateway_merchant_id'       => 'olamoney_merchant',
            'gateway_merchant_id2'      => 'olamoney_auth_code',
            'gateway_terminal_id'       => 'olamoney_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_terminal_password2'=> null,
            'gateway_access_code'       => 'random_access_code',
            'gateway_secure_secret'     => 'secret',
            'gateway_secure_secret2'    => null,

        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCardlessEmiTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CARDLESS_EMI_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                   => $termId,
            'merchant_id'          => '10000000000000',
            'gateway'              => 'cardless_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 1,
            'gateway_merchant_id'  => 'cardless_emi_merchant',
            'gateway_merchant_id2' => 'cardless_emi_merchant2',
            'gateway_acquirer'     => 'earlysalary',
            'mode'                 => 1,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCardlessEmiWalnut369Terminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CARDLESS_EMI_WALNUT369_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'cardless_emi',
            'card'                      => 0,
            'netbanking'                => 0,
            'cardless_emi'              => 1,
            'gateway_acquirer'          => 'walnut369',
            'gateway_merchant_id'       => 'cardless_emi_merchant',
            'mode'                      => 3,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCardlessEmiSezzleTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CARDLESS_EMI_SEZZLE_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'cardless_emi',
            'card'                      => 0,
            'netbanking'                => 0,
            'cardless_emi'              => 1,
            'gateway_acquirer'          => 'sezzle',
            'gateway_merchant_id'       => 'cardless_emi_merchant',
            'mode'                      => 2,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectCredTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CRED_TERMINAL;

        $attributes = [
            'id'                    => $termId,
            'merchant_id'           => '10000000000000',
            'gateway'               => 'cred',
            'card'                  => 0,
            'netbanking'            => 0,
            'cred'                  => 1,
            'gateway_merchant_id'   => 'cred_merchant',
            'gateway_secure_secret' => 'cred_merchant',
            'mode'                  => 2,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createTwidTerminal(array $attributes = [])
    {
        $termId = Shared::TWID_TERMINAL;

        $attributes = [
            'id'                     => $termId,
            'merchant_id'            => '10000000000000',
            'gateway'                => 'twid',
            'card'                   => 0,
            'netbanking'             => 0,
            'app'                    => 1,
            'gateway_merchant_id'    => 'twid_merchant',
            'gateway_secure_secret'  => 'twid_secret',
            'gateway_secure_secret2' => 'twid_secret2',
            'mode'                   => 3,
            'enabled_apps'           => ['twid'],
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardlessEmiFlexMoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CARDLESS_EMI_FLEXMONEY_TERMINAL;

        $attributes = [
            'id'                   => $termId,
            'merchant_id'          => '10000000000000',
            'gateway'              => 'cardless_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 1,
            'gateway_merchant_id'  => 'cardless_emi_merchant',
            'gateway_merchant_id2' => 'cardless_emi_merchant2',
            'gateway_acquirer'     => 'flexmoney',
            'mode'                 => 1,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardlessEmiFlexMoneySubproviderTerminal(array $attributes = [])
    {
        $termId = Shared::CARDLESS_EMI_FLEXMONEY_MULTILENDER_TERMINAL;

        $attributes = [
            'id'                   => $termId,
            'merchant_id'          => '10000000000000',
            'gateway'              => 'cardless_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 1,
            'gateway_merchant_id'  => 'cardless_emi_merchant',
            'gateway_merchant_id2' => 'cardless_emi_merchant2',
            'gateway_acquirer'     => 'flexmoney',
            'mode'                 => 1,
            'enabled_banks'        => CardlessEmi::getSupportedBanksForMultilenderProvider('flexmoney'),
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardlessEmiFlexMoneyEmptyEnabledBanks(array $attributes = [])
    {
        $termId = Shared::CARDLESS_EMI_FLEXMONEY_EMPTY_ENABLED_BANKS;

        $attributes = [
            'id'                   => $termId,
            'merchant_id'          => '10000000000000',
            'gateway'              => 'cardless_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 1,
            'gateway_merchant_id'  => 'cardless_emi_merchant',
            'gateway_merchant_id2' => 'cardless_emi_merchant2',
            'gateway_acquirer'     => 'flexmoney',
            'mode'                 => 1,
            'enabled_banks'        => [],
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardlessEmiZestMoneyTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CARDLESS_EMI_ZESTMONEY_TERMINAL;

        $attributes = [
            'id'                   => $termId,
            'merchant_id'          => '10000000000000',
            'gateway'              => 'cardless_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 1,
            'gateway_merchant_id'  => 'cardless_emi_merchant',
            'gateway_merchant_id2' => 'cardless_emi_merchant2',
            'gateway_acquirer'     => 'zestmoney',
            'mode'                 => 1,
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPaylaterEpaylaterTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::PAYLATER_EPAYLATER_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paylater',
            'card'                      => 0,
            'netbanking'                => 0,
            'paylater'                  => 1,
            'gateway_merchant_id'       => 'abcd',
            'gateway_merchant_id2'      => 'ABCD',
            'gateway_acquirer'          => 'epaylater',
            'mode'                      => 1,
            'gateway_terminal_password' => Crypt::encrypt('random_secret'),
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createGetsimplTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                        =>  '1ShrdSimplTrml',
            'merchant_id'               =>  $sharedMerchantAccount,
            'gateway'                   =>  'paylater',
            'gateway_acquirer'          =>  'getsimpl',
            'shared'                    =>  0,
            'paylater'                  =>  1,
            'gateway_merchant_id'       =>  'RazorpayGetsimpl',
            'gateway_terminal_password' =>  'terminal_password',
            'mode'                      =>  '2',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPaylaterIciciTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::TEST_ACCOUNT;
        $termId = \RZP\Models\Terminal\Shared::PAYLATER_ICICI_TERMINAL;

        $defaultValues = [
            'id'                        =>  $termId,
            'merchant_id'               =>  $sharedMerchantAccount,
            'gateway'                   =>  'paylater',
            'gateway_acquirer'          =>  'icic',
            'shared'                    =>  0,
            'paylater'                  =>  1,
            'gateway_merchant_id'       =>  'DUMMY_MERCHANT_ID',
            'gateway_terminal_password' =>  'terminal_password',
            'mode'                      =>  '2',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPaylaterFlexmoneyTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = Account::TEST_ACCOUNT;
        $termId                = Shared::PAYLATER_FLEXMONEY_TERMINAL;

        $defaultValues = [
            'id'                        =>  $termId,
            'merchant_id'               =>  $sharedMerchantAccount,
            'gateway'                   =>  'paylater',
            'gateway_acquirer'          =>  'flexmoney',
            'shared'                    =>  0,
            'paylater'                  =>  1,
            'gateway_merchant_id'       =>  'DUMMY_MERCHANT_ID',
            'gateway_terminal_password' =>  'terminal_password',
            'mode'                      =>  '1',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPaylaterLazypayTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = Account::TEST_ACCOUNT;
        $termId                = Shared::PAYLATER_LAZYPAY_TERMINAL;

        $defaultValues = [
            'id'                        =>  $termId,
            'merchant_id'               =>  $sharedMerchantAccount,
            'gateway'                   =>  'paylater',
            'gateway_acquirer'          =>  'lazypay',
            'shared'                    =>  0,
            'paylater'                  =>  1,
            'gateway_merchant_id'       =>  'DUMMY_MERCHANT_ID',
            'gateway_terminal_password' =>  'terminal_password',
            'gateway_secure_secret'     =>  'test_secret',
            'mode'                      =>  '3',
        ];

        $attributes = array_merge($defaultValues, $attributes);

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

        return $this->create($attributes);
    }

    public function createSharedAmazonpayTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::AMAZONPAY_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                        => $termId,
            'merchant_id'               => Account::TEST_ACCOUNT,
            'gateway'                   => Gateway::WALLET_AMAZONPAY,
            'gateway_access_code'       => 'gateway_access_key',
            'gateway_merchant_id'       => 'amazonpay_merchant',
            'gateway_terminal_password' => 'amazonpay_secure_secret',
        ];

        return $this->create($attributes);
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

        return $this->create($attributes);
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

        return $this->create($attributes);
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
            'gateway_secure_secret2'     => 'secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedCybersourceHdfcTerminalWithoutSecret2(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CYBERSOURCE_HDFC_TERMINAL_WITHOUT_SECRET2;

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
            'gateway_secure_secret2'     => 'secret',
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
            Type::RECURRING_3DS     => '1',
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
            'gateway_secure_secret2'    => 'secret',
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
            'gateway_merchant_id'       => '3ds_gateway_merchant_id',
        ];

        $terminal1 = $this->createEntityInTestAndLive('terminal', $attributes);

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
            'gateway_merchant_id'       => 'non_3ds_gateway_merchant_id',
            'gateway_merchant_id2'      => '3ds_gateway_merchant_id',
        ];

        $terminal2 = $this->createEntityInTestAndLive('terminal', $attributes);

        return [$terminal1, $terminal2];
    }

    public function createDirectFirstDataRecurringTerminals($inputAttrs)
    {
        $attributes = [
            'id'                        => 'FDRcrDTrmnl3DS',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'first_data',
            'gateway_acquirer'          => 'icic',
            'card'                      => 1,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::RECURRING_3DS => '1'
            ],
            'gateway_merchant_id'       => '3ds_gateway_merchant_id',
        ];

        $attributes = array_merge($attributes, $inputAttrs);

        $terminal1 = $this->createEntityInTestAndLive('terminal', $attributes);

        $attributes = [
            'id'                        => 'FDRcrDTrmlN3DS',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'first_data',
            'gateway_acquirer'          => 'icic',
            'card'                      => 1,
            'type'                      => [
                Type::RECURRING_NON_3DS => '1'
            ],
            'mode'                      => Mode::PURCHASE,
            'gateway_merchant_id'       => 'non_3ds_gateway_merchant_id',
            'gateway_merchant_id2'      => '3ds_gateway_merchant_id',
        ];

        $attributes = array_merge($attributes, $inputAttrs);

        $terminal2 = $this->createEntityInTestAndLive('terminal', $attributes);

        return [$terminal1, $terminal2];
    }

    public function createDirectFirstDataRecurringTerminal($inputAttrs)
    {
        $attributes = [
            'id'                        => 'FDRcrDTrmnl3DS',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'first_data',
            'gateway_acquirer'          => 'icic',
            'card'                      => 1,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::RECURRING_3DS => '1'
            ],
            'gateway_merchant_id'       => '3ds_gateway_merchant_id',
        ];

        $attributes = array_merge($attributes, $inputAttrs);

        return $this->createEntityInTestAndLive('terminal', $attributes);
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

    public function createMigsRecurringTerminalWithBothRecurringTypes(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'MiGSRcg3DSN3DS',
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'axis_migs',
            'gateway_acquirer'          => 'axis',
            'card'                      => 1,
            'type'                      => [
                Type::RECURRING_NON_3DS => '1',
                Type::RECURRING_3DS => '1'
            ],
            'gateway_merchant_id'       => 'random',
            'gateway_terminal_id'       => 'recurring_random',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createHitachiRecurringTerminalWithBothRecurringTypes(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'HitcRcg3DSN3DS',
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'hitachi',
            'gateway_acquirer'          => 'ratn',
            'card'                      => 1,
            'type'                      => [
                Type::RECURRING_NON_3DS => '1',
                Type::RECURRING_3DS     => '1',
                Type::DEBIT_RECURRING   => '1',
            ],
            'gateway_merchant_id'       => 'random',
            'gateway_terminal_id'       => 'recurring_random',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectHitachiRecurringTerminalWithBothRecurringTypes(array $attributes = [])
    {
        $terminalId = \RZP\Models\Terminal\Shared::HITACHI_DIRECT_TERMINAL;

        $defaultValues = [
            'id'                        => $terminalId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'hitachi',
            'gateway_acquirer'          => 'ratn',
            'card'                      => 1,
            'type'                      => [
                Type::RECURRING_NON_3DS => '1',
                Type::RECURRING_3DS     => '1',
                Type::DEBIT_RECURRING   => '1',
            ],
            'gateway_merchant_id'       => 'random',
            'gateway_terminal_id'       => 'recurring_random',
            'gateway_terminal_password' => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createBankAccountTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'BANKACC3DSN3DS',
            'merchant_id'               => '10000000000000',
            'gateway'                   => Gateway::BT_YESBANK,
            'gateway_merchant_id'       => '222333',
            'gateway_merchant_id2'      => '00',
            'card'                      => 0,
            'recurring'                 => 0,
            'gateway_acquirer'          => null,
            'type'                      => [
                Type::NON_RECURRING        => '1',
                Type::NUMERIC_ACCOUNT      => '1',
            ],
            'bank_transfer'             => '1',
        ];

        $attributes = array_merge($defaultValues, $attributes);


        $terminalLiveMode = $this->getDbEntity('terminal',
                                           [
                                               'id' => $attributes['id'],
                                           ], 'live');

        $terminalTestMode = $this->getDbEntity('terminal',
                                           [
                                               'id' => $attributes['id'],
                                           ], 'test');

        //create only if terminal does not exists in both test and live modes
        if (empty($terminalLiveMode) === true and
            empty($terminalTestMode) === true)
        {
            return $this->createEntityInTestAndLive('terminal', $attributes);
        }

        $entity = E::getEntityClass('terminal');

        return Factory::build($entity, $attributes);
    }

    public function createBankAccountTerminalForBusinessBanking(array $attributes = [])
    {
        $defaultValues = [
            'id'                  => 'BANKACC3DSN3DT',
            'gateway_merchant_id' => '222444',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],
        ];

        $defaultValues1 = [
            'id'                  => 'BANKACC3DSN3DZ',
            'gateway_merchant_id' => '232323',
            'type'                => [
                Type::NON_RECURRING    => '1',
                Type::NUMERIC_ACCOUNT  => '1',
                Type::BUSINESS_BANKING => '1',
            ],
        ];

        $this->createBankAccountTerminal(array_merge($defaultValues, $attributes));

        $this->createBankAccountTerminal(array_merge($defaultValues1, $attributes));
    }

    public function createSharedBankAccountTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'SHRDBANKACC3DS',
            'merchant_id'               => \RZP\Models\Merchant\Account::SHARED_ACCOUNT,
            'gateway'                   => Gateway::BT_DASHBOARD,
            'gateway_merchant_id'       => '111222',
            'gateway_merchant_id2'      => '00',
            'card'                      => 0,
            'recurring'                 => 0,
            'gateway_acquirer'          => null,
            'type'                      => [
                Type::NON_RECURRING        => '1',
                Type::NUMERIC_ACCOUNT      => '1',
            ],
            'bank_transfer'             => '1',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createHdfcEcmsBankAccountTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'ECMSBANKACCTER',
            'merchant_id'               => Account::SHARED_ACCOUNT,
            'gateway'                   => Gateway::BT_HDFC_ECMS,
            'gateway_merchant_id'       => 'HB4589',
            'gateway_merchant_id2'      => '00',
            'card'                      => 0,
            'recurring'                 => 0,
            'bank_transfer'             => '1',
            'gateway_acquirer'          => null,
            'type'                      => [
                Type::NON_RECURRING                    => '1',
                Type::NUMERIC_ACCOUNT                  => '1',
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1'
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createHdfcEcmsBankAccountDedicatedTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'ECMSBNKACCTERD',
            'merchant_id'               => '10000000000035',
            'gateway'                   => Gateway::BT_HDFC_ECMS,
            'gateway_merchant_id'       => 'HB4589',
            'gateway_merchant_id2'      => '01',
            'card'                      => 0,
            'recurring'                 => 0,
            'bank_transfer'             => '1',
            'gateway_acquirer'          => null,
            'type'                      => [
                Type::NON_RECURRING                    => '1',
                Type::NUMERIC_ACCOUNT                  => '1',
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1'
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedBankAccountTerminalAlphaNum(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'SHRDBANKACCALN',
            'merchant_id'               => \RZP\Models\Merchant\Account::SHARED_ACCOUNT,
            'gateway'                   => Gateway::BT_DASHBOARD,
            'gateway_merchant_id'       => 'RZR',
            'gateway_merchant_id2'      => 'PAY',
            'card'                      => 0,
            'recurring'                 => 0,
            'gateway_acquirer'          => null,
            'type'                      => [
                Type::NON_RECURRING             => '1',
                Type::ALPHA_NUMERIC_ACCOUNT     => '1',
            ],
            'bank_transfer'             => '1',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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

    public function createCybersourceAxisMotoTerminal(array $attributes = [])
    {
        $termId = '10CybAxMtTrmnl';
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
            'type'                      => [
                Type::MOTO              => '1',
                Type::NON_RECURRING     => '1',
            ],
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedHitachiTerminal(array $attributes = [])
    {
        $terminalId = \RZP\Models\Terminal\Shared::HITACHI_TERMINAL;

        $defaultValues = [
            TerminalEntity::ID                        => $terminalId,
            TerminalEntity::MERCHANT_ID               => '100000Razorpay',
            TerminalEntity::GATEWAY                   => 'hitachi',
            TerminalEntity::CARD                      => 1,
            TerminalEntity::NETBANKING                => 0,
            TerminalEntity::SHARED                    => 1,
            TerminalEntity::GATEWAY_ACQUIRER          => 'rbl',
            TerminalEntity::GATEWAY_MERCHANT_ID       => 'hitachi',
            TerminalEntity::GATEWAY_TERMINAL_PASSWORD => 'hitachi',
            TerminalEntity::GATEWAY_SECURE_SECRET     => 'secret',
            TerminalEntity::CAPABILITY                => '2',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectHitachiTerminal(array $attributes = [])
    {
        $terminalId = \RZP\Models\Terminal\Shared::HITACHI_DIRECT_TERMINAL;

        $defaultValues = [
            TerminalEntity::ID                        => $terminalId,
            TerminalEntity::MERCHANT_ID               => '10000000000000',
            TerminalEntity::GATEWAY                   => 'hitachi',
            TerminalEntity::CARD                      => 1,
            TerminalEntity::NETBANKING                => 0,
            TerminalEntity::SHARED                    => 0,
            TerminalEntity::GATEWAY_ACQUIRER          => 'ratn',
            TerminalEntity::GATEWAY_MERCHANT_ID       => 'hitachiDirectMerchantId',
            TerminalEntity::GATEWAY_TERMINAL_ID       => 'hitachiDirectTerminalId',
            TerminalEntity::GATEWAY_TERMINAL_PASSWORD => 'hitachi',
            TerminalEntity::GATEWAY_SECURE_SECRET     => 'secret',
            TerminalEntity::CATEGORY                  => '1240',
            TerminalEntity::CAPABILITY                => '2',
            TerminalEntity::TYPE                      => [
                                                            Type::NON_RECURRING         => '1',
                                                            Type::RECURRING_3DS         => '1',
                                                            Type::RECURRING_NON_3DS     => '1',
                                                            Type::DEBIT_RECURRING       => '1',
                                                        ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectFulcrumTerminal(array $attributes = [])
    {
        $terminalId = \RZP\Models\Terminal\Shared::FULCRUM_DIRECT_TERMINAL;

        $defaultValues = [
            TerminalEntity::ID                        => $terminalId,
            TerminalEntity::MERCHANT_ID               => '10000000000000',
            TerminalEntity::GATEWAY                   => 'fulcrum',
            TerminalEntity::CARD                      => 1,
            TerminalEntity::NETBANKING                => 0,
            TerminalEntity::SHARED                    => 0,
            TerminalEntity::GATEWAY_ACQUIRER          => 'ratn',
            TerminalEntity::GATEWAY_MERCHANT_ID       => 'fulcrumDirectMerchantId',
            TerminalEntity::GATEWAY_TERMINAL_ID       => 'fulcrumDirectTerminalId',
            TerminalEntity::GATEWAY_TERMINAL_PASSWORD => 'fulcrum',
            TerminalEntity::GATEWAY_SECURE_SECRET     => 'secret',
            TerminalEntity::CATEGORY                  => '1240',
            TerminalEntity::CAPABILITY                => '2',
            TerminalEntity::TYPE                      => [
                Type::NON_RECURRING         => '1',
                Type::RECURRING_3DS         => '1',
                Type::RECURRING_NON_3DS     => '1',
                Type::DEBIT_RECURRING       => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

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

        return $this->create($attributes);
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

        return $this->create($attributes);
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

        return $this->create($attributes);
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

    public function createSharedBilldeskSihubTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::BILLDESK_SIHUB_RAZORPAY_TERMINAL;

        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => $sharedMerchantAccount,
            'gateway'               => 'billdesk_sihub',
            'gateway_merchant_id'   => 'rand_bd_sihub',
            'card'                  => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedMandateHqTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::MANDATE_HQ_RAZORPAY_TERMINAL;

        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => $sharedMerchantAccount,
            'gateway'               => 'mandate_hq',
            'gateway_merchant_id'   => 'rand_mandate_hq',
            'card'                  => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedRupaySihubTerminal(array $attributes = [])
    {
        $termId = Shared::RUPAY_SIHUB_RAZORPAY_TERMINAL;

        $sharedMerchantAccount = Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => $sharedMerchantAccount,
            'gateway'               => 'rupay_sihub',
            'gateway_merchant_id'   => 'rand_rupay_sihub',
            'card'                  => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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
            'gateway_merchant_id'       => 'razorpaypaytm',
            'gateway_secure_secret'     => 'randomsecret',
            'gateway_terminal_id'       => 'nodalaccountpaytm',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_access_code'       => 'www.merchant.com'
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

        return $this->create($attributes);
    }

    public function createSharedNetbankingHdfcTerminal(array $attributes = [])
    {
        $defaultAttributes = [
            'id'                        => Shared::NETBANKING_HDFC_TERMINAL,
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef'
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return $this->create($attributes);
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

        return $this->create($attributes);
    }

    public function createSharedNetbankingCanaraTerminal(array $attributes = [])
    {
        $attributes = [
            'id'                        => Shared::NETBANKING_CANARA_TERMINAL,
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_canara',
            'gateway_merchant_id'       => 'abcd',
            'gateway_secure_secret'     => 'secure_secret'
        ];

        return $this->create($attributes);
    }

    public function createSharedNetbankingCorporationTerminal(array $attributes = [])
    {
        $attributes = [
            'id'                        => Shared::NETBANKING_CORPORATION_TERMINAL,
            'card'                      => 0,
            'netbanking'                => 1,
            'tpv'                       => 2,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_corporation',
            'gateway_merchant_id'       => 'abcd',
            'gateway_secure_secret'     => 'secure_secret'
        ];

        return $this->create($attributes);
    }

    public function createRXTerminal()
    {
        $termId = Shared::SHARP_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                         => $termId,
            'merchant_id'                => '100000Razorpay',
            'gateway'                    => Gateway::BT_YESBANK,
            'gateway_merchant_id'        => '456456',
            'gateway_terminal_id'        => 'abcde',
            'gateway_terminal_password'  => 'abcdef',
            'gateway_terminal_password2' => 'abcdef',
            'gateway_secure_secret2'     => 'abcdef',
            'card'                       => 1,
            'emi'                        => 0,
            'mc_mpan'                    => base64_encode('1234560000000000'),
            'visa_mpan'                  => base64_encode('1234560000000001'),
            'rupay_mpan'                 => base64_encode('1234560000000002'),
            'vpa'                        => 'random@razorpay',
            'type'                       => [
                'non_recurring' => '1',
            ]
        ];

        $terminal = $this->createEntityInTestAndLive('terminal', $attributes);

        $this->fixtures->edit('terminal', $terminal['id'], ['gateway_merchant_id' => '232323']);
    }

    public function createSharedSharpTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::SHARP_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                         => $termId,
            'merchant_id'                => '100000Razorpay',
            'gateway'                    => 'sharp',
            'gateway_merchant_id'        => 'test_merchant_sharp',
            'gateway_terminal_id'        => 'abcde',
            'gateway_terminal_password'  => 'abcdef',
            'gateway_terminal_password2' => 'abcdef',
            'gateway_secure_secret2'     => 'abcdef',
            'card'                       => 1,
            'emi'                        => 0,
            'mc_mpan'                    => base64_encode('1234560000000000'),
            'visa_mpan'                  => base64_encode('1234560000000001'),
            'rupay_mpan'                 => base64_encode('1234560000000002'),
            'vpa'                        => 'random@razorpay',
            'type'                       => [
                'non_recurring' => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedHitachiMotoTerminal()
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;
        $attributes = [
            'id'                        => 'ShrdHitaMotTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'hitachi',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_acquirer'          => 'rbl',
            'gateway_merchant_id'       => 'hitachi',
            'gateway_terminal_password' => 'hitachi',
            'gateway_secure_secret'     => 'secret',
            'type'                      => [
                Type::MOTO              => '1',
                Type::NON_RECURRING     => '1',
            ],
        ];

        return $this->create($attributes);
    }

    public function createSharedHdfcMotoTerminal()
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;
        $attributes = [
            'id'                        => 'ShrdHdfcMotTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'hitachi',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_acquirer'          => 'rbl',
            'gateway_merchant_id'       => 'hdfc',
            'gateway_terminal_password' => 'hdfc',
            'gateway_secure_secret'     => 'secret',
            'type'                      => [
                Type::MOTO              => '1',
                Type::NON_RECURRING     => '1',
            ],
        ];

        return $this->create($attributes);
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

        return $this->create($attributes);
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

        return $this->create($attributes);
    }

    public function createSharedHitachiEmiTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => 'ShrdHtchEmiTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'hitachi',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_acquirer'          => 'rbl',
            'gateway_merchant_id'       => 'hitachi',
            'gateway_terminal_password' => 'hitachi',
            'gateway_secure_secret'     => 'secret',
            'emi'                       => 1,
            'emi_duration'              => 9,
            'emi_subvention'            => 'customer',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
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
            'id'                         => $termId,
            'merchant_id'                => '100000Razorpay',
            'gateway'                    => 'hdfc',
            'gateway_acquirer'           => 'hdfc',
            'card'                       => 1,
            'gateway_merchant_id'        => 'razorpay hdfc',
            'gateway_terminal_id'        => 'account hdfc',
            'gateway_terminal_password'  => 'razorpay_password',
            'gateway_terminal_password2' => 'razorpay_password',
            'gateway_secure_secret2'     => 'razorpay_password',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedMpgsTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'                => '10000000000000',
            'gateway'                    => 'mpgs',
            'gateway_acquirer'           => 'ocbc',
            'card'                       => 1,
            'shared'                     => 1,
            'netbanking'                 => 0,
            'mode'                       => 3,
            'capability'                 => 0,
            'gateway_merchant_id'        => 'razorpay ocbc',
            'gateway_terminal_id'        => 'account ocbc',
            'gateway_terminal_password'  => 'razorpay_password',
            'gateway_terminal_password2' => 'razorpay_password',
            'gateway_secure_secret2'     => 'razorpay_password',
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

        return $this->create($attributes);
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
            'gateway_merchant_id2'      => 'RAZORPAY',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createDirectSettlementRefundIciciTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10iciciDrtseTl',
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_icici',
            'gateway_merchant_id'       => 'test',
            'gateway_merchant_id2'      => 'test2',
            'gateway_secure_secret'     => 'razorpay_password',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::NON_RECURRING                 => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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

        return $this->create($attributes);
    }

    public function createSharedNetbankingIciciCorpTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                => Shared::NETBANKING_ICICI_CRP_TERMINAL,
            'corporate'         => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedNetbankingIciciTerminal($attributes);
    }

    public function createSharedNetbankingHdfcCorpTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                => Shared::NETBANKING_HDFC_CRP_TERMINAL,
            'corporate'         => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedNetbankingHdfcTerminal($attributes);
    }

    public function createSharedEmandateIciciTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_icici',
            'netbanking'                => 0,
            'emandate'                  => 1,
            'shared'                    => 1,
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'razorpay_submerchant',
            'gateway_merchant_id2'      => 'razorpay_icici',
            'gateway_secure_secret'     => 'razorpay_password',
        ];

        // Recurring supports both 3ds and non3ds terminal;
        $defaultValues['id'] = 'NIcRecurringTl';

        $defaultValues['type'] = [
            Type::RECURRING_NON_3DS => '1',
            Type::RECURRING_3DS     => '1'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createEmandateIciciTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_icici',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'netbanking'                => 0,
            'emandate'                  => 1,
        ];

        $defaultValues['type'] = [
            Type::RECURRING_NON_3DS => '1',
            Type::RECURRING_3DS     => '1'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return parent::create($attributes);
    }

    public function createSharedEmandateHdfcTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_hdfc',
            'netbanking'                => 0,
            'emandate'                  => 1,
            'shared'                    => 1,
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'razorpay_submerchant',
            'gateway_secure_secret'     => 'razorpay_password',
        ];

        // Recurring supports both 3ds and non3ds terminal;
        $defaultValues['id'] = 'NHdRecurringTl';

        $defaultValues['type'] = [
            Type::RECURRING_NON_3DS => '1',
            Type::RECURRING_3DS     => '1'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
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

        return $this->create($attributes);
    }

    public function createSharedNetbankingObcTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_OBC_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => Gateway::NETBANKING_OBC,
            'gateway_merchant_id'       => 'test_merchant_id',
            'netbanking'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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

        return $this->create($attributes);
    }

    public function createSharedEmandateAxisTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'netbanking_axis',
            'netbanking'                => 0,
            'emandate'                  => 1,
            'shared'                    => 1,
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'razorpay_submerchant',
            'gateway_merchant_id2'      => 'razorpay_axis',
            'gateway_secure_secret'     => 'razorpay_password',
        ];

        // Add fss recurring supports both 3ds and non3ds terminal;
        $defaultValues['id'] = 'NAxRecurringTl';
        $defaultValues['type'] = [
            Type::RECURRING_3DS     => '1',
            Type::RECURRING_NON_3DS => '1',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
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

    public function createSharedNetbankingAxisCorpTerminal(array $attributes = [])
    {
        $defaults = [
            'id'                => Shared::NETBANKING_AXIS_CRP_TERMINAL,
            'corporate'         => 1,
        ];

        $attributes = array_merge($defaults, $attributes);

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

        return $this->create($attributes);
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

        return $this->create($attributes);
    }

    public function createSharedNetbankingPnbCorpTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                => Shared::NETBANKING_PNB_CRP_TERMINAL,
            'corporate'         => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedNetbankingPnbTerminal($attributes);
    }

    public function createSharedNetbankingPnbTpvTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'               => Shared::NETBANKING_PNB_TPV_TERMINAL,
            'network_category' => 'securities',
            'tpv'              => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedNetbankingPnbTerminal($attributes);
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
            'tpv'                       => 2,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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

    public function createSharedNetbankingIdfcTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_IDFC_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => Gateway::NETBANKING_IDFC,
            'gateway_merchant_id'       => 'test_merchant_id',
            'netbanking'                => 1,
            'gateway_secure_secret'     => 'random_idfc_code',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createDirectSettlementIdfcTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_IDFC_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => Gateway::NETBANKING_IDFC,
            'gateway_merchant_id'       => 'test_merchant_id',
            'netbanking'                => 1,
            'gateway_secure_secret'     => 'random_idfc_code',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::NON_RECURRING                 => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingBobTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_BOB_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_bob',
            'gateway_merchant_id'       => 'netbanking_bob_merchant_id',
            'netbanking'                => 1,
            'shared'                    => 1,
            'corporate'                 => 2,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingVijayaTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => Shared::NETBANKING_VIJAYA_TERMINAL,
            'merchant_id'               => $merchantId,
            'gateway'                   => 'netbanking_vijaya',
            'gateway_merchant_id'       => 'netbanking_vijaya_merchant_id',
            'gateway_merchant_id2'      => 'netbanking_vijaya_merchant_id2',
            'netbanking'                => 1,
            'shared'                    => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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

        return $this->create($attributes);
    }

    public function createSharedNetbankingCsbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_CSB_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_CSB,
            'gateway_merchant_id'   => 'netbanking_csb_merchant_id',
            'gateway_merchant_id2'  => 'netbanking_csb_merchant_id2',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingAllahabadTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_ALLAHABAD_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_ALLAHABAD,
            'gateway_merchant_id'   => 'netbanking_alla_merchant_id',
            'gateway_merchant_id2'  => 'netbanking_alla_merchant_id2',
            'gateway_secure_secret' => 'razorpay_password',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }


    public function createIdfcTpvTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::IDFB_TPV_TERMINAL;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'netbanking_idfc',
            'card'                  => 0,
            'netbanking'            => 1,
            'tpv'                   => 1,
            'gateway_merchant_id'   => 'netbanking_idfb_merchant_id',
            'gateway_secure_secret' => 'random_secret',
            'network_category'      => 'ecommerce',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createAllahabadTpvTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::ALLA_TPV_TERMINAL;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'netbanking_allahabad',
            'card'                  => 0,
            'netbanking'            => 1,
            'tpv'                   => 1,
            'gateway_merchant_id'   => 'netbanking_alla_merchant_id',
            'gateway_access_code'   => 'netbanking_alla_merchant_id2',
            'gateway_secure_secret' => 'random_secret',
            'network_category'      => 'ecommerce',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
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

    public function createSharedNetbankingEquitasTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_ESFB_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_EQUITAS,
            'gateway_merchant_id'   => 'netbanking_equitas_merchant_id',
            'gateway_merchant_id2'  => 'netbanking_equitas_merchant_id2',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingEquitasTpvTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_ESFB_TPV_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_EQUITAS,
            'gateway_merchant_id'   => 'netbanking_equitas_merchant_id',
            'tpv'                   => 1,
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingYesbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_YESB_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_YESB,
            'gateway_merchant_id'   => 'netbanking_yesb_merchant_id',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingYesbTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::NETBANKING_YESB_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingYesbTerminal($attributes);
    }

    public function createSharedNetbankingCubTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_CUB_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_CUB,
            'gateway_merchant_id'   => 'netbanking_cub_merchant_id',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingIbkTerminal(array $attributes = []){

        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
        'id'                    => Shared::NETBANKING_IBK_TERMINAL,
        'merchant_id'           => $merchantId,
        'gateway'               => Gateway::NETBANKING_IBK,
        'gateway_merchant_id'   => 'netbanking_ibk_merchant_id',
        'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues,$attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingIdbiTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                   => Shared::NETBANKING_IDBI_TERMINAL,
            'merchant_id'          => $merchantId,
            'gateway'              => Gateway::NETBANKING_IDBI,
            'gateway_merchant_id'  => 'netbanking_idbi_merchant_id',
            'netbanking'           => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingCubTpvTerminal(array $attributes = [])
    {
        $attributes = [
            // not adding in shared as cub terminals will anyway be direct
            'id'               => '1000NbCubTpvTl',
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingCubTerminal($attributes);
    }

    public function createSharedNetbankingIbkTpvTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $attributes = [
            'id'               => '1000NbIbkTpvTl',
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_IBK,
            'gateway_merchant_id'   => 'netbanking_ibk_merchant_id',
            'netbanking'            => 1,
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingIbkTerminal($attributes);
    }

    public function createSharedNetbankingSbiTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_SBI_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_SBI,
            'gateway_merchant_id'   => 'netbanking_sbi_merchant_id',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedEmandateSbiTerminal(array $attributes = [])
    {
        $defaultValues = [
            'merchant_id'               => '100000Razorpay',
            'netbanking'                => 0,
            'emandate'                  => 1,
            'shared'                    => 1,
        ];

        // Recurring supports both 3ds and non3ds terminal;
        $defaultValues['id'] = 'NSbRecurringTl';

        $defaultValues['type'] = [
            Type::RECURRING_NON_3DS => '1',
            Type::RECURRING_3DS     => '1'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedNetbankingSbiTerminal($attributes);
    }

    public function createSharedNetbankingSibTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_SIB_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_SIB,
            'gateway_merchant_id'   => 'netbanking_sib_merchant_id',
            'netbanking'            => 1,
            'gateway_secure_secret' => 'random_secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingUbiTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_UBI_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_UBI,
            'gateway_merchant_id'   => 'netbanking_ubi_merchant_id',
            'netbanking'            => 1,
            'gateway_secure_secret' => 'random_secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingScbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                            => Shared::NETBANKING_SCB_TERMINAL,
            'merchant_id'                   => $merchantId,
            'gateway'                       => Gateway::NETBANKING_SCB,
            'gateway_merchant_id'           => 'netbanking_scb_merchant_id',
            'gateway_secure_secret2'        => 'netbanking_scb_decryption_key',
            'netbanking'                    => 1,
            'gateway_secure_secret'         => 'netbanking_scb_encryption_key',
            'gateway_terminal_password'     => 'netbanking_scb_hash_salt',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingJkbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                            => Shared::NETBANKING_JKB_TERMINAL,
            'merchant_id'                   => $merchantId,
            'gateway'                       => Gateway::NETBANKING_JKB,
            'gateway_merchant_id'           => 'netbanking_jkb_merchant_id',
            'netbanking'                    => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingCbiTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_CBI_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_CBI,
            'gateway_merchant_id'   => 'netbanking_cbi_merchant_id',
            'netbanking'            => 1,
            'gateway_secure_secret' => 'random_secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingSibTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::NETBANKING_SIB_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedNetbankingSibTerminal($attributes);
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
            'enabled'                   => true,
            'upi'                       => true,
            'type'                      => [
                'non_recurring' => '1',
                'collect'       => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiAxisTerminal(array $attributes)
    {
        $termId = Shared::UPI_AXIS_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_axis',
            'gateway_merchant_id'       => 'TSTMERCHI',
            'gateway_merchant_id2'      => 'TSTMERCHIAPP',
            'vpa'                       => 'a@axis',
            'upi'                       => true,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiRblCollectTerminal(array $attributes)
    {
        $termId = Shared::UPI_RBL_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_rbl',
            'gateway_merchant_id'       => 'test_merchant',
            'gateway_merchant_id2'      => 'test@rbl',
            'upi'                       => true,
            'type'                      => [
                'non_recurring'         => '1',
                'pay'                   => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiRblIntentTerminal(array $attributes)
    {
        $termId = Shared::UPI_RBL_RAZORPAY_INTENT_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_rbl',
            'gateway_merchant_id'       => 'test_merchant',
            'vpa'                       => 'test@rbl',
            'upi'                       => true,
            'type'                      => [
                'non_recurring' => 1,
                'pay'           => 1,
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiAxisTpvTerminal(array $attributes)
    {
        $attributes = [
            'id'               => Shared::UPI_AXIS_TPV_RAZORPAY_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedUpiAxisTerminal($attributes);
    }

    public function createSharedUpiIciciIntentTerminal(array $attributes)
    {
        $attributes = [
            'id'                        => Shared::UPI_ICICI_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createSharedUpiHulkTerminal(array $override)
    {
        $termId = Shared::UPI_HULK_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_hulk',
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'vpa_merchantsVpaId',
            'gateway_secure_secret'     => 'razorpay_password',
            'gateway_terminal_password' => 'hulk_api_password',
            'upi'                       => true,
        ];

        $attributes = array_merge($defaultValues, $override);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiHulkIntentTerminal(array $override = [])
    {
        $attributes = [
            'id'                        => Shared::UPI_HULK_RAZORPAY_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring'         => '1',
                'pay'                   => '1',
            ],
            'gateway_merchant_id2'      => 'testmerchant@razor',
        ];

        return $this->createSharedUpiHulkTerminal(array_merge($attributes, $override));
    }

    public function createSharedUpiHulkTpvTerminal(array $override = [])
    {
        $attributes = [
            'id'               => Shared::UPI_HULK_RAZORPAY_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedUpiHulkIntentTerminal(array_merge($attributes, $override));
    }

    public function createUpiYesbankIntentTerminal()
    {
        $attributes = [
            'id'                        => Shared::UPI_YESBANK_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createSharedUpiYesbankTerminal($attributes);
    }

    public function createSharedUpiYesbankTerminal(array $override)
    {
        $termId = Shared::UPI_YESBANK_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_yesbank',
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'vpa_merchantsVpaId',
            'gateway_secure_secret'     => 'razorpay_password',
            'gateway_terminal_password' => 'hulk_api_password',
            'upi'                       => true,
            'vpa'                       => 'testvpa@yesb',
        ];

        $attributes = array_merge($defaultValues, $override);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDedicatedUpiYesbankTerminal($attributes)
    {
        $termId = Shared::UPI_YESBANK_DEDICATED_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_yesbank',
            'gateway_merchant_id'       => 'razorpayupi',
            'gateway_terminal_id'       => 'nodal account upi yesbank',
            'gateway_terminal_password' => 'razorpay_password',
            'vpa'                       => 'testvpa@yesb',
            'upi'                       => true,
            'tpv'                       => 2,
            'type'                      => [
                Type::PAY               => '1',
                Type::NON_RECURRING     => '1',
                Type::ONLINE            => '1',
                Type::COLLECT           => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDedicatedSharpTerminal($attributes)
    {
        $termId = Shared::SHARP_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'sharp',
            'gateway_merchant_id'       => 'test_merchant_sharp',
            'gateway_terminal_id'       => 'nodal account',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_terminal_password2' => 'abcdef',
            'gateway_secure_secret2'     => 'abcdef',
            'vpa'                       => 'testvpa@yesb',
            'upi'                       => true,
            'tpv'                       => 2,
            'type'                      => [
                Type::PAY               => '1',
                Type::NON_RECURRING     => '1',
                Type::ONLINE            => '1',
                Type::COLLECT           => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createLiveDedicatedUpiYesbankTerminal($attributes)
    {
        $termId = Shared::UPI_LIVE_YESBANK_DEDICATED_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => 'LiveAccountMer',
            'gateway'                   => 'upi_yesbank',
            'gateway_merchant_id'       => 'razorpayliveupi',
            'gateway_terminal_id'       => 'nodal account upi yesbank',
            'gateway_terminal_password' => 'razorpay_password',
            'vpa'                       => 'randomvpa@yesb',
            'upi'                       => true,
            'tpv'                       => 2,
            'type'                      => [
                Type::PAY               => '1',
                Type::NON_RECURRING     => '1',
                Type::ONLINE            => '1',
                Type::COLLECT           => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUpiAxisOliveTerminal(array $attributes)
    {
        $termId = Shared::UPI_AXISOLIVE_TPV_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_axisolive',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi axis olive',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'vpa'                       => 'test@vpa',
            'enabled'                   => true,
            'upi'                       => true,
            'type'                      => [
                'non_recurring' => '1',
                'collect'       => '0',
                'in_app'        => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiAxisOlivetpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::UPI_AXISOLIVE_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createUpiAxisOliveTerminal($attributes);
    }

    public function createSharedUpiAxisIntentTpvTerminal(array $override = [])
    {
        $attributes = [
            'id'               => Shared::UPI_AXIS_TPV_RAZORPAY_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedUpiAxisIntentTerminal(array_merge($attributes, $override));
    }

    public function createSharedAepsIciciTerminal(array $attributes)
    {
        $termId = Shared::AEPS_ICICI_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'aeps_icici',
            'gateway_terminal_id'       => 'aeps_terminal_id',
            'aeps'                      => true,
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
            // Sample hex for as encryption, not in used
            'gateway_terminal_password' => '93158d5892188161a259db660ddb1d0b',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiAirtelTerminal(array $attributes)
    {
        $termId = Shared::UPI_AIRTEL_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_airtel',
            'gateway_merchant_id'       => 'razorpay upi airtel',
            'gateway_merchant_id2'      => 'razorpay@mairtel',
            'gateway_terminal_password' => 'upipassword',
            'upi'                       => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiKotakTerminal(array $attributes)
    {
        $defaultValues = [
            'id'                        => Shared::UPI_KOTAK_RAZORPAY_TERMINAL,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_kotak',
            'gateway_merchant_id'       => 'Razorpay01',
            'gateway_merchant_id2'      => '919000000000',
            'upi'                       => 1,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
//                'collect'       => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUpiRzprblTerminal(array $attributes)
    {
        $defaultValues = [
            'id'                        => Shared::UPI_RZPRBL_TERMINAL,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_rzprbl',
            'gateway_merchant_id'       => 'RazorpayVpaId1',
            'gateway_merchant_id2'      => 'RzpDeviceId123',
            'upi'                       => 1,
            'shared'                    => 0,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiCitiTerminal(array $attributes)
    {
        $defaultValues = [
            TerminalEntity::ID                          => Shared::UPI_AIRTEL_RAZORPAY_TERMINAL,
            TerminalEntity::MERCHANT_ID                 => '100000Razorpay',
            TerminalEntity::GATEWAY                     => 'upi_citi',
            TerminalEntity::GATEWAY_MERCHANT_ID         => 'citi-client-id',
            TerminalEntity::GATEWAY_MERCHANT_ID2        => 'razorpay@citi',
            TerminalEntity::GATEWAY_TERMINAL_PASSWORD   => 'citi-secret-key',
            TerminalEntity::UPI                         => 1,
            TerminalEntity::VPA                         => 'razorpay@citi',
            TerminalEntity::ACCOUNT_NUMBER              => '9876543210',

        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiMindgateIntentTerminal(array $override)
    {
        $attributes = [
            'id'                        => Shared::UPI_MINDGATE_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createSharedUpiMindgateTerminal(array_merge($attributes, $override));
    }

    //UPI_AIRTEL_INTENT_TERMINAL
    public function createSharedUpiAirtelIntentTerminal(array $override)
    {
        $attributes = [
            'id'                        => Shared::UPI_AIRTEL_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createSharedUpiAirtelTerminal(array_merge($attributes, $override));
    }

    public function createSharedUpiAxisIntentTerminal(array $override)
    {
        $attributes = [
            'id'                        => Shared::UPI_AXIS_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ],
            'vpa'                       => 'test@vpa'
        ];

        return $this->createSharedUpiAxisTerminal(array_merge($attributes, $override));
    }

    public function createDirectSettlementUpiAxisTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DiSeUpAxTmnl',
            'merchant_id'               => '10000000000000',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Type::NON_RECURRING                    => '1',
                Type::PAY                              => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedUpiAxisTerminal($attributes);
    }

    public function createDirectSettlementUpiIciciTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DiSeUpIcTmnl',
            'merchant_id'               => '10000000000000',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Type::NON_RECURRING                    => '1',
                Type::PAY                              => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createDirectSettlementUpiMindgateTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DiSeUpMnTmnl',
            'merchant_id'               => '10000000000000',
            'gateway_merchant_id'       => 'direct settlement mindgate',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Type::NON_RECURRING                    => '1',
                Type::PAY                              => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedUpiMindgateTerminal($attributes);
    }

    public function createSharedUpiMindgateSignedIntentTerminal(array $override)
    {
        $privateKey = 'MHQCAQEEIPk6R12xwmvV/JJDehGHSrQpNZxE3jmNXHcmgNUY2858oAcGBSuBBAAK' .
                      'oUQDQgAES9US3XYL8yPYqqnScq2+hTmuKBnl70RMeSDEmFN/euNHoQs+7ouwI/OH' .
                      '9sivFz/5a5n9ZEvc7aakvVauZi/rAA==';

        $publicKey = 'MFYwEAYHKoZIzj0CAQYFK4EEAAoDQgAES9US3XYL8yPYqqnScq2+hTmuKBnl70RM' .
                     'eSDEmFN/euNHoQs+7ouwI/OH9sivFz/5a5n9ZEvc7aakvVauZi/rAA==';

        $attributes = [
            'gateway_terminal_password2'    => $privateKey,
            'gateway_access_code'           => $publicKey,
        ];

        return $this->createSharedUpiMindgateIntentTerminal(array_merge($attributes, $override));
    }

    public function createSharedUpiMindgateTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::UPI_MINDGATE_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedUpiMindgateTerminal($attributes);
    }

    public function createSharedUpiICICITpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::UPI_ICICI_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createSharedUpiMindgateIntentTpvTerminal(array $attributes = [])
    {
        $attributes = [
            'id'               => Shared::UPI_MINDGATE_INTENT_TPV_TERMINAL,
            'tpv'              => 1,
        ];

        return $this->createSharedUpiMindgateIntentTerminal($attributes);
    }

    public function createSharedUpiMindgateSbiTerminal(array $attributes)
    {
        $termId = Shared::UPI_MINDGATE_SBI_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => Account::SHARED_ACCOUNT,
            'gateway'                   => Gateway::UPI_SBI,
            'gateway_merchant_id'       => 'SBI0000000000119',
            'gateway_merchant_id2'      => 'razorpay@sbibank',
            'upi'                       => 1,
            'gateway_acquirer'          => Upi::SBIN,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedUpiMindgateSbiIntentTerminal(array $override)
    {
        $attributes = [
            'id'                        => Shared::UPI_MINDGATE_SBI_INTENT_TERMINAL,
            'type'                      => [
                'non_recurring' => '1',
                'pay'           => '1',
            ]
        ];

        return $this->createSharedUpiMindgateSbiTerminal(array_merge($attributes, $override));
    }

    public function createSharedPaysecureTerminal(array $attributes)
    {
        $termId = Shared::PAYSECURE_RAZORPAY_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => Account::SHARED_ACCOUNT,
            'gateway'                   => Gateway::PAYSECURE,
            'card'                      => 1,
            'shared'                    => 1,
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

        return $this->create($attributes);
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
            'gateway_merchant_id'       => 'random_dealer_id',
            'gateway_merchant_id2'      => 'random_id',
            'gateway_terminal_id'       => 'freecharge_terminal',
            'gateway_terminal_password' => 'razorpay_password',
            'gateway_secure_secret'     => 'secret',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedEnstageTerminal(array $attributes)
    {
        $termId = \RZP\Models\Terminal\Shared::ENSTAGE_TERMINAL;
        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'mpi_enstage',
            'card'                      => 1,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'random',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::IVR => '1',
            ],
        ];
        $attributes = array_merge($defaultValues, $attributes);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCsbTpvTerminal(array $attributes = [])
    {
        $termId = \RZP\Models\Terminal\Shared::CSB_TPV_TERMINAL;

        $defaultValues = [
            'id'                    => $termId,
            'merchant_id'           => '100000Razorpay',
            'gateway'               => 'netbanking_csb',
            'card'                  => 0,
            'netbanking'            => 1,
            'tpv'                   => 1,
            'gateway_merchant_id'   => 'razorpay',
            'gateway_access_code'   => 'random_code',
            'gateway_secure_secret' => 'random_secret',
            'network_category'      => 'ecommerce',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createDirectSettlementHdfcTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DirectseTmnl',
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Type::NON_RECURRING                    => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createDirectSettlementAxisMigsTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DirectseTmnl',
            'card'                      => 1,
            'netbanking'                => 0,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'axis_migs',
            'gateway_merchant_id'       => '12345678',
            'gateway_terminal_id'       => 'abcde1',
            'gateway_terminal_password' => 'abcdef',
            'gateway_secure_secret'     => 'supersecret',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Type::NON_RECURRING                    => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createDirectSettlementCybersourceTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DirectseTmnl',
            'card'                      => 1,
            'netbanking'                => 0,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'cybersource',
            'gateway_acquirer'          => 'hdfc',
            'gateway_merchant_id'       => 'merchant_id',
            'gateway_terminal_id'       => 'cybersource',
            'gateway_terminal_password' => 'cybersource',
            'gateway_access_code'       => '111111',
            'gateway_secure_secret'     => 'secret',
            'gateway_secure_secret2'     => 'secret',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITHOUT_REFUND => '1',
                Type::NON_RECURRING                    => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createDirectSettlementRefundHdfcTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => '10DirectseTmnl',
            'card'                      => 0,
            'netbanking'                => 1,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'netbanking_hdfc',
            'gateway_merchant_id'       => 'abcd',
            'gateway_terminal_id'       => 'abcde',
            'gateway_terminal_password' => 'abcdef',
            'type'                      => [
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::NON_RECURRING                 => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createBajajFinservTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                        => 'BajajFinservTrm',
            'gateway'                   => 'bajajfinserv',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 0,
            'gateway_merchant_id'       => 'BajajFinserv',
            'gateway_secure_secret'     => 'BajajFinserv',
            'gateway_secure_secret2'    => 'BajajFinservKey',
            'gateway_access_code'       => 'BajajFinservIv',
            'emi'                       => 1,
            'emi_duration'              => 9,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedBajajFinservTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;
        $defaultValues = [
            'id'                        => 'ShrdBajajFinservTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'bajajfinserv',
            'card'                      => 1,
            'netbanking'                => 0,
            'shared'                    => 1,
            'gateway_merchant_id'       => 'BajajFinserv',
            'gateway_secure_secret'     => 'BajajFinserv',
            'gateway_secure_secret2'    => 'BajajFinservKey',
            'gateway_access_code'       => 'BajajFinservIv',
            'emi'                       => 1,
            'emi_duration'              => 9,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPhonepeTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;
        $defaultValues = [
            'id'                        => '1ShrdPhnepeTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'wallet_phonepe',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 0,
            'gateway_merchant_id'       => 'RazorpayPhonepe',
            'gateway_secure_secret'     => 'secure_secret',
            'gateway_access_code'       => 'access_code',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPhonepeswitchTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => '1ShrdPhnpeSTrm',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'wallet_phonepeswitch',
            'card'                      => 0,
            'netbanking'                => 0,
            'shared'                    => 0,
            'gateway_merchant_id'       => 'RazorpayPhonepe',
            'gateway_secure_secret'     => 'secure_secret',
            'gateway_secure_secret2'    => 'access_code',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedPaypalTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => '1ShrdPaypalTml',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => 'wallet_paypal',
            'shared'                    => 0,
            'gateway_merchant_id'       => 'RazorpayPaypal',
            'mode'                      => '2',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPaypalUsdTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                         => '1PaypalUSDTmnl',
            'merchant_id'                => $sharedMerchantAccount,
            'gateway'                    => 'wallet_paypal',
            'shared'                     => 1,
            'gateway_merchant_id'        => 'RazorpayPaypal2',
            'mode'                       => '2',
            'currency'                   => 'USD'
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNach(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                        => '1citinachDTmnl',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => Gateway::NACH_CITI,
            'nach'                      => 1,
            'gateway_merchant_id'       => 'NACH00000000013149',
            'gateway_merchant_id2'      => 'NACH00000000013149',
            'gateway_access_code'       => 'CITI000PIGW',
            'gateway_acquirer'          => 'citi',
            'recurring'                 => 1,
            'created_at'                => time(),
            'updated_at'                => time(),
            'type'                      => [
                Type::RECURRING_3DS     => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createNachSharedTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                        => 'citinachShrdtl',
            'merchant_id'               => $sharedMerchantAccount,
            'gateway'                   => Gateway::NACH_CITI,
            'nach'                      => 1,
            'gateway_merchant_id'       => 'NACH00000000013150',
            'gateway_merchant_id2'      => 'NACH00000000013150',
            'gateway_access_code'       => 'CITI000PIGW',
            'gateway_acquirer'          => 'citi',
            'recurring'                 => 1,
            'created_at'                => time(),
            'updated_at'                => time(),
            'type'                      => [
                Type::RECURRING_3DS     => '1',
                Type::RECURRING_NON_3DS => '1',
            ],
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedNetbankingKvbTerminal(array $attributes = []){

        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_KVB_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_KVB,
            'gateway_merchant_id'   => 'netbanking_kvb_merchant_id',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingKvbTpvTerminal(array $attributes = []){

        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_KVB_TPV_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_KVB,
            'gateway_merchant_id'   => 'netbanking_kvb_merchant_id',
            'netbanking'            => 1,
            'tpv'                   => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createVpaSharedTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                          => 'VirtVpaShrdTrm',
            'gateway_merchant_id'         => 'HDFCVPATEST',
            'type'                        => [
                Type::NON_RECURRING => '1',
                Type::UPI_TRANSFER  => '1',
            ],
            'virtual_upi_root'            => 'rzpy.',
            'virtual_upi_merchant_prefix' => 'test000000',
            'virtual_upi_handle'          => 'hdfcbank',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createSharedUpiMindgateTerminal($attributes);
    }

    public function createVpaSharedTerminalIcici(array $attributes = [])
    {
        $defaultValues = [
            'id'                          => 'VaVpaShrdicici',
            'gateway_merchant_id'         => '403343',
            'gateway_merchant_id2'        => 'rzr.payto00000@icici',
            'type'                        => [
                Type::NON_RECURRING => '1',
                Type::UPI_TRANSFER  => '1'
            ],
            'virtual_upi_root'            => 'rzr.',
            'virtual_upi_merchant_prefix' => 'payto00000',
            'virtual_upi_handle'          => 'icici',
        ];
        $attributes    = array_merge($defaultValues, $attributes);

        return $this->createSharedUpiIciciTerminal($attributes);
    }

    public function createUpiJuspayTerminal(array $attributes = [])
    {
        $defaultValues = [
            'id'                         => Shared::UPI_JUSPAY_TERMINAL,
            'merchant_id'                => '10000000000000',
            'gateway'                    => Gateway::UPI_JUSPAY,
            'gateway_merchant_id'        => 'MERCHANTid',
            'gateway_merchant_id2'       => 'merchantid2',
            'gateway_secure_secret'      => 'NotUsedAsOfNow',
            'vpa'                        => 'some@abfspay',
            'upi'                        =>  1
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingSvcTerminal(array $attributes = [])
    {

        $merchantId = Account::TEST_ACCOUNT;

        $defaultValues = [
            TerminalEntity::ID                  => Shared::NETBANKING_SVC_TERMINAL,
            TerminalEntity::MERCHANT_ID         => $merchantId,
            TerminalEntity::GATEWAY             => Gateway::NETBANKING_SVC,
            TerminalEntity::GATEWAY_MERCHANT_ID => 'netbanking_svc_merchant_id',
            TerminalEntity::NETBANKING          => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingDcbTerminal(array $attributes = [])
    {
        $merchantId = Account::TEST_ACCOUNT;

        $defaultValues = [
            TerminalEntity::ID                  => Shared::NETBANKING_DCB_TERMINAL,
            TerminalEntity::MERCHANT_ID         => $merchantId,
            TerminalEntity::GATEWAY             => Gateway::NETBANKING_DCB,
            TerminalEntity::GATEWAY_MERCHANT_ID => 'netbanking_dcb_merchant_id',
            TerminalEntity::NETBANKING          => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createUpiJuspayIntentTerminal(array $attributes = [])
    {
        $defaultValues  = [
            'id'                            => Shared::UPI_JUSPAY_TERMINAL,
            'gateway'                       => Gateway::UPI_JUSPAY,
            'merchant_id'                   => '10000000000000',
            'gateway_acquirer'              => 'axis',
            'category'                      => '1234',
            'gateway_merchant_id'           => 'MER0000000000111',
            'gateway_merchant_id2'          => 'MERCHANNEL0000000000111',
            'gateway_secure_secret'         => 'NotUsedAsOfNow',
            'upi'                           => 1,
            'vpa'                           => 'abcd@some',
            'type'                          => [
                Type::NON_RECURRING    => '1',
                Type::PAY              => '1'
            ]
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createHdfcDebitEmi(array $attributes = [])
    {
        $defaultValues = [
            'id'                   => 'HdfcDebitEmiTl',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'hdfc_debit_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 0,
            'emi'                  => 1,
            'emi_duration'         => 3,
            'gateway_merchant_id'  => 'debit_emi_merchant',
            'gateway_merchant_id2' => 'debit_emi_merchant2',
            'mode'                 => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createKotakDebitEmi(array $attributes = [])
    {
        $defaultValues = [
            'id'                   => 'KotakDebitEmi1',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'kotak_debit_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 0,
            'emi'                  => 1,
            'emi_duration'         => 3,
            'gateway_merchant_id'  => 'debit_emi_merchant',
            'gateway_merchant_id2' => 'debit_emi_merchant2',
            'mode'                 => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }
    public function createIndusindDebitEmi(array $attributes = [])
    {
        $defaultValues = [
            'id'                   => 'Indusinddcemi1',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'indusind_debit_emi',
            'card'                 => 0,
            'netbanking'           => 0,
            'cardless_emi'         => 0,
            'emi'                  => 1,
            'emi_duration'         => 3,
            'gateway_merchant_id'  => 'debit_emi_merchant',
            'gateway_merchant_id2' => 'debit_emi_merchant2',
            'mode'                 => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedNetbankingJsbTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                   => '1ShrdNBJSBFTml',
            'merchant_id'          => $sharedMerchantAccount,
            'gateway'              => Gateway::NETBANKING_JSB,
            'gateway_merchant_id'  => 'netbanking_jsb',
            'netbanking'           => 1,
            'shared'               => 1,
        ];

        $attributes = array_merge($defaultValues,$attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingFsbTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                   => '1ShrdNBFSBFTml',
            'merchant_id'          => $sharedMerchantAccount,
            'gateway'              => Gateway::NETBANKING_FSB,
            'gateway_merchant_id'  => 'netbanking_fsb',
            'netbanking'           => 1,
            'shared'               => 1,
        ];

        $attributes = array_merge($defaultValues,$attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingSaraswatTerminal(array $attributes = [])
    {
        $sharedMerchantAccount = \RZP\Models\Merchant\Account::SHARED_ACCOUNT;

        $defaultValues = [
            'id'                   => "1ShrdNBSrtFTml",
            'merchant_id'          => $sharedMerchantAccount,
            'gateway'              => Gateway::NETBANKING_SARASWAT,
            'gateway_merchant_id'  => 'netbanking_saraswat',
            'netbanking'           => 1,
            'shared'               => 1,
        ];

        $attributes = array_merge($defaultValues,$attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingIobTerminal(array $attributes = [])
    {

        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id' => '1ShrdNBIOBATml',
            'merchant_id' => $merchantId,
            'gateway' => Gateway::NETBANKING_IOB,
            'gateway_merchant_id' => 'netbanking_iob_merchant_id',
            'gateway_merchant_id2' => 'netbanking_iob_merchant_id2',
            'netbanking' => 1,
            'shared' => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingAusfTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id' => '1ShrdNBAUSFTml',
            'merchant_id' => $merchantId,
            'gateway' => Gateway::NETBANKING_AUSF,
            'gateway_merchant_id' => 'netbanking_ausf_merchant_id',
            'netbanking' => 1,
            'shared' => 1,
            'gateway_terminal_password'  => Crypt::encrypt('test_terminal_password'),
            'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingNsdlTerminal(array $attributes = [])
    {

        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => '1ShrdNBNSDLTml',
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_NSDL,
            'gateway_merchant_id'   => 'netbanking_nsdl_merchant_id',
            'netbanking'            => 1,
            'shared'                => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingBdblTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => '1ShrdNBBDBLTml',
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_BDBL,
            'gateway_merchant_id'   => 'netbanking_bdbl_merchant_id',
            'netbanking'            => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingDlbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id' => '1ShrdNBDLXBTml',
            'merchant_id' => $merchantId,
            'gateway' => Gateway::NETBANKING_DLB,
            'gateway_merchant_id' => 'netbanking_dlb_merchant_id',
            'netbanking' => 1,
            'shared' => 1,
            'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);

    }

    public function createSharedFPXBankingTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'merchant_id' => $merchantId,
            'gateway' => Gateway::FPX,
            'gateway_merchant_id' => 'netbanking_dlb_merchant_id',
            'netbanking' => 1,
            'shared' => 0,
            'card' => 0,
            'gateway_secure_secret'      => Crypt::encrypt('test_secure_secret'),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);

    }

    public function createSharedNetbankingUcoTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                  => '1ShrdNBUBCATml',
            'merchant_id'         => $merchantId,
            'gateway'             => Gateway::NETBANKING_UCO,
            'gateway_merchant_id' => 'netbanking_uco_merchant_id',
            'netbanking'          => 1,
         ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }
    public function createSharedNetbankingUjjivanTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id' => Shared::NETBANKING_UJVN_TERMINAL,
            'merchant_id' => $merchantId,
            'gateway' => Gateway::NETBANKING_UJJIVAN,
            'gateway_merchant_id' => 'netbanking_ujjivan_merchant_id',
            'netbanking' => 1,
            'shared' => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingUjjivanTpvTerminal(array $attributes = []){

        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => Shared::NETBANKING_UJVN_TPV_TERMINAL,
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_UJJIVAN,
            'gateway_merchant_id'   => 'netbanking_ujjivan_merchant_id',
            'netbanking'            => 1,
            'tpv'                   => 1,
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);
    }

    public function createSharedNetbankingTmbTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => '1ShrdNBTMBLTml',
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_TMB,
            'gateway_merchant_id'   => 'netbanking_tmb_merchant_id',
            'netbanking'            => 1,
            'shared'                => 1,
            'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);

    }

    public function createSharedNetbankingKarnatakaTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => '1ShrdNBKARBTml',
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_KARNATAKA,
            'gateway_merchant_id'   => 'netbanking_karnataka_merchant_id',
            'netbanking'            => 1,
            'shared'                => 1,
            'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);

    }

    public function createSharedNetbankingDbsTerminal(array $attributes = [])
    {
        $merchantId = \RZP\Models\Merchant\Account::TEST_ACCOUNT;

        $defaultValues = [
            'id'                    => '1ShrdNBDBSSTml',
            'merchant_id'           => $merchantId,
            'gateway'               => Gateway::NETBANKING_DBS,
            'gateway_merchant_id'   => 'netbanking_dbs_merchant_id',
            'netbanking'            => 1,
            'shared'                => 1,
            'gateway_secure_secret' => Crypt::encrypt('test_secure_secret'),
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->create($attributes);

    }

    public function createUpiPaytmTerminal(array $attributes = [])
    {

        $default = [
            'id'                        => '1000PaytmTrmnl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paytm',
            'upi'                       => 1,
            'gateway_merchant_id'       => 'test_merchant_id',
            'gateway_secure_secret'     => 'test_secure_secret',
            'gateway_terminal_id'       => 'test_terminal_id',
            'gateway_access_code'       => 'test_access_code',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardPaytmTerminal(array $attributes = [])
    {

        $default = [
            'id'                        => '100CPaytmTrmnl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'paytm',
            'card'                      => 1,
            'gateway_merchant_id'       => 'test_merchant_id',
            'gateway_secure_secret'     => 'test_secure_secret',
            'gateway_terminal_id'       => 'test_terminal_id',
            'gateway_access_code'       => 'test_access_code',
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::OPTIMIZER => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardPayuTerminal(array $attributes = [])
    {

        $default = [
            'id'                    => '1000CPayuTrmnl',
            'merchant_id'           => '10000000000000',
            'gateway'               => 'payu',
            'card'                  => 1,
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
            'enabled_wallets'       => ['jiomoney','mobikwik','paytm'],
            'mode'                  =>  '2',
            'type'                  => [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::OPTIMIZER => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardBilldeskOptimiserTerminal(array $attributes = [])
    {

        $default = [
            'id'                        => '100BDOptiTrmnl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'billdesk_optimizer',
            'card'                      => 1,
            'gateway_merchant_id'       => 'abcd',
            'gateway_secure_secret2'    => 'secret',
            'mode'                      => 2,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::OPTIMIZER => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createCardCashfreeTerminal(array $attributes = [])
    {

        $default = [
            'id'                    => '100CashfreeTml',
            'merchant_id'           => '10000000000000',
            'gateway'               => 'cashfree',
            'card'                  => 1,
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
            'type'                  => [
                Type::NON_RECURRING => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '1',
                Type::OPTIMIZER => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPaytmIntentTerminal()
    {
        $attributes = [
            'id'                        => '1UPIPaytmTrmnl',
            'type'                      => [
                Type::NON_RECURRING     => '1',
                Type::DIRECT_SETTLEMENT_WITH_REFUND => '0',
                Type::PAY                => '1',
            ],
        ];

        return $this->createUpiPaytmTerminal($attributes);

    }

    public function createEmerchantpayTerminal(array $override)
    {
        $attributes = [
            'merchant_id'            => '10000000000000',
            'gateway'                => 'emerchantpay',
            'card'                   => 0,
            'netbanking'             => 0,
            'app'                    => 1,
            'gateway_merchant_id'    => 'emerchantpay_merchant',
            'gateway_secure_secret'  => 'emerchantpay_secret',
            'gateway_secure_secret2' => 'emerchantpay_secret2',
            'gateway_terminal_id'    => 'emtrustly',
            'mode'                   => 3,
            'enabled_apps'           => ['trustly','poli','sofort','giropay'],
        ];

        $attributes = array_merge($attributes, $override);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUpiIciciDedicatedTerminal(array $attributes = [])
    {
        $termId = Shared::UPI_ICICI_DEDICATED_TERMINAL;

        $defaultValues = [
            'id'                        => $termId,
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_icici',
            'gateway_merchant_id'       => 'razorpay upi',
            'gateway_terminal_id'       => 'nodal account upi icici',
            'gateway_merchant_id2'      => 'razorpay@eazypay',
            'gateway_terminal_password' => 'razorpay_password',
            'upi'                       => true,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::PAY           => '1',
                Type::COLLECT       => '1',
            ],
        ];
        $attributes = array_merge($defaultValues, $attributes);
        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUPIInAppTerminal(array $attributes = [])
    {
        $default = [
            'id'                        => '1000UpiInAppTl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_axisolive',
            'gateway_merchant_id'       => 'razorpay axis upi',
            'gateway_terminal_id'       => 'nodal account upi axis',
            'gateway_merchant_id2'      => 'razorpayaxis@axis',
            'gateway_terminal_password' => 'razorpay_password',
            'vpa'                       => 'some@axis',
            'upi'                       => true,
            'type'                      => [
                Type::NON_RECURRING => '1',
                Type::IN_APP        => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUPIInAppIOSTerminal(array $attributes = [])
    {
        $default = [
            'id'                        => '1000UpiInAppTl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_axisolive',
            'gateway_merchant_id'       => 'razorpay axis upi',
            'gateway_terminal_id'       => 'nodal account upi axis',
            'gateway_merchant_id2'      => 'razorpayaxis@axis',
            'gateway_terminal_password' => 'razorpay_password',
            'vpa'                       => 'some@axis',
            'upi'                       => true,
            'type'                      => [
                Type::IN_APP        => '1',
                Type::IOS           => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUPIInAppAndroidTerminal(array $attributes = [])
    {
        $default = [
            'id'                        => '1000UpiInAppTl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_axisolive',
            'gateway_merchant_id'       => 'razorpay axis upi',
            'gateway_terminal_id'       => 'nodal account upi axis',
            'gateway_merchant_id2'      => 'razorpayaxis@axis',
            'gateway_terminal_password' => 'razorpay_password',
            'vpa'                       => 'some@axis',
            'upi'                       => true,
            'type'                      => [
                Type::IN_APP        => '1',
                Type::ANDROID       => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createUPIInAppIOSAndAndroidTerminal(array $attributes = [])
    {
        $default = [
            'id'                        => '1000UpiInAppTl',
            'merchant_id'               => '10000000000000',
            'gateway'                   => 'upi_axisolive',
            'gateway_merchant_id'       => 'razorpay axis upi',
            'gateway_terminal_id'       => 'nodal account upi axis',
            'gateway_merchant_id2'      => 'razorpayaxis@axis',
            'gateway_terminal_password' => 'razorpay_password',
            'vpa'                       => 'some@axis',
            'upi'                       => true,
            'type'                      => [
                Type::IN_APP        => '1',
                Type::IOS           => '1',
                Type::ANDROID       => '1',
            ],
        ];

        $attributes = array_merge($default, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedBajajTerminal(array $attributes = [])
    {
        $termId = Shared::BAJAJ_RAZORPAY_TERMINAL;

        $attributes = [
            'id'                     => $termId,
            'merchant_id'            => '100000Razorpay',
            'gateway'                => 'wallet_bajaj',
            'gateway_merchant_id'    => 'dummy_merchant_id',
            'gateway_secure_secret'  => 'dummy_secure_secret',
            'gateway_secure_secret2' => 'dummy_secure_secret2',
            'gateway_access_code'    => 'dummy_access_code',
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createSharedEghlTerminal(array $attributes = [])
    {
        $termId = Shared::EGHL_SHARED_TERMINAL;

        $attributes = [
            'id'                     => $termId,
            'merchant_id'            => '10000000000000',
            'gateway'                => 'eghl',
            'gateway_merchant_id'    => 'dummy_merchant_id',
            'gateway_secure_secret'  => 'dummy_secure_secret',
            'gateway_secure_secret2' => 'dummy_secure_secret2',
            'gateway_access_code'    => 'dummy_access_code',
            'enabled_wallets'        => ['boost', "grabpay", "touchngo", "mcash"]
        ];

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createPayuSodexoTerminal()
    {
        $attributes = [
            'merchant_id'           => '10000000000000',
            'gateway'               => 'payu',
            'card'                  => 1,
            'netbanking'            => 1,
            'upi'                   => 1,
            'emi'                   => 1,
            'emi_subvention'        => 'customer',
            'gateway_merchant_id'   => 'abcd',
            'network_category'      => 'ecommerce',
            'gateway_secure_secret' => 'secret',
            'enabled_wallets'       => ['jiomoney','mobikwik','paytm'],
            'mode'                  =>  '2',
            'type'                  => [
                'recurring_3ds'                 => '1',
                'recurring_non_3ds'             => '1',
                'direct_settlement_with_refund' => '1',
                'optimizer'                     => '1',
                'sodexo'                        => '1'
            ],
        ];
        $attributes = array_merge($attributes, $override);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }

    public function createIcici(array $attributes = [])
    {
        $defaultValues = [
            'id'                   => '100icicidedtml',
            'merchant_id'          => '10000000000000',
            'gateway'              => 'icici',
            'card'                 => 1,
            'gateway_merchant_id'       => 'icici_merchant',
            'gateway_terminal_id'       => 'iciciDebit123',
            'gateway_terminal_password' => 'password',
            'gateway_secure_secret'     => '12345678',
        ];

        $attributes = array_merge($defaultValues, $attributes);

        return $this->createEntityInTestAndLive('terminal', $attributes);
    }
}
