<?php

namespace RZP\Models\Payout\Batch;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base\Service as BaseService;
use RZP\Models\Base\Traits\ServiceHasCrudMethods;

class Service extends BaseService
{
    /**
     * @var Repository
     */
    protected $entityRepo;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->entityRepo = $this->repo->payouts_batch;

        $this->core = (new Core());
    }

    use ServiceHasCrudMethods;

    /**
     * Periodically create test payouts on X Demo Account
     *
     * @return array
     */
    public function createXDemoPayoutCron() : array
    {
        $merchant_id = \RZP\Models\Merchant\Account::X_DEMO_PROD_ACCOUNT;

        $x_demo_bank_account = \RZP\Constants\BankingDemo::BANK_ACCOUNT;

        $timestamp  = Carbon::now(Timezone::IST)->getTimestamp();

        $input = array(
            'reference_id' => 'PB-' . $timestamp,
            'payouts'      => [
                [
                    'account_number'       => $x_demo_bank_account,
                    'amount'               => 100000,
                    'currency'             => 'INR',
                    'mode'                 => 'UPI',
                    'purpose'              => 'payout',
                    'reference_id'         => 'P1-' . $timestamp,
                    'queue_if_low_balance' => false,
                    'fund_account'         => [
                        'account_type' => 'vpa',
                        'vpa'          => [
                            'address' => 'gaurav' . $timestamp . '@exampleupi',
                        ],
                        'contact'      => [
                            'name'         => 'Gaurav Kumar',
                            'email'        => 'gaurav.kumar@example.com',
                            'contact'      => '9876543210',
                            'type'         => 'vendor',
                            'reference_id' => 'C1-' . $timestamp,
                            'notes'        => [
                                'notes_key_1' => 'Tea, Earl Grey, Hot',
                                'notes_key_2' => 'Tea, Earl Grey... decaf.',
                            ],
                        ],
                    ],
                    'narration'            => 'Acme Corp Fund Transfer',
                ],
                [
                    'account_number'       => $x_demo_bank_account,
                    'amount'               => 100000,
                    'currency'             => 'INR',
                    'mode'                 => 'UPI',
                    'purpose'              => 'payout',
                    'reference_id'         => 'P2-' . $timestamp,
                    'queue_if_low_balance' => false,
                    'fund_account'         => [
                        'account_type' => 'vpa',
                        'vpa'          => [
                            'address' => 'rajesh' . $timestamp . '@exampleupi',
                        ],
                        'contact'      => [
                            'name'         => 'Rajesh Kumar',
                            'email'        => 'rajesh.kumar@example.com',
                            'contact'      => '9999999999',
                            'type'         => 'vendor',
                            'reference_id' => 'C2-' . $timestamp,
                        ],
                    ],
                    'narration'            => 'Acme Corp Fund Transfer',
                ],
                [
                    'account_number'       => $x_demo_bank_account,
                    'amount'               => 100000,
                    'currency'             => 'INR',
                    'mode'                 => 'UPI',
                    'purpose'              => 'refund',
                    'fund_account'         => [
                        'account_type' => 'vpa',
                        'vpa'          => [
                            'address' => 'sean' . $timestamp . '@exampleupi',
                        ],
                        'contact'      => [
                            'name'         => 'Sean Monterio',
                            'email'        => 'sean.monterio@example.com',
                            'contact'      => '9876543210',
                            'type'         => 'employee',
                            'reference_id' => 'C3-' . $timestamp,
                        ],
                    ],
                    'queue_if_low_balance' => true,
                    'reference_id'         => 'P3-' . $timestamp,
                    'narration'            => 'Acme Corp Fund Transfer',
                    'notes'                => [
                        'notes_key_1' => 'Beam me up Scotty',
                        'notes_key_2' => 'Engage',
                    ],
                ],
                [
                    'account_number'       => $x_demo_bank_account,
                    'amount'               => 100000,
                    'currency'             => 'INR',
                    'mode'                 => 'UPI',
                    'purpose'              => 'refund',
                    'fund_account'         => [
                        'account_type' => 'vpa',
                        'vpa'          => [
                            'address' => 'tanya' . $timestamp . '@exampleupi',
                        ],
                        'contact'      => [
                            'name'         => 'Tanya Singh',
                            'email'        => 'tanya.singh@example.com',
                            'contact'      => '9876543210',
                            'type'         => 'self',
                            'reference_id' => 'C4-' . $timestamp,
                        ],
                    ],
                    'queue_if_low_balance' => true,
                    'reference_id'         => 'P4-' . $timestamp,
                    'narration'            => 'Acme Corp Fund Transfer',
                ],
            ],
        );

        return $this->create($input);
    }
}
