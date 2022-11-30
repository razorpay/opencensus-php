<?php

namespace RZP\Tests\Functional\Helpers;

use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Models\Payout;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Merchant\Account;
use RZP\Models\Settlement\Channel;
use RZP\Models\Merchant\Balance\FreePayout;
use RZP\Models\Merchant\Balance\AccountType;

/**
 * Consists reusable methods to help with business banking related tests.
 */
trait TestsBusinessBanking
{
    /**
     * @var \RZP\Models\Merchant\Balance\Entity|null
     */
    protected $bankingBalance;

    /**
     * @var \RZP\Models\VirtualAccount\Entity|null
     */
    protected $virtualAccount;

    /**
     * @var \RZP\Models\BankAccount\Entity|null
     */
    protected $bankAccount;

    /**
     * @var \RZP\Models\Contact\Entity|null
     */
    protected $contact;

    /**
     * @var \RZP\Models\Transaction\Entity|null
     */
    protected $transaction;

    /**
     * @var \RZP\Models\Payout\Entity|null
     */
    protected $payout;

    /**
     * Setup merchant for business banking.
     *
     * @param bool $skipFeatureAddition
     * @param int $balance
     * @param string $balanceType
     * @param string $channel
     */
    protected function setUpMerchantForBusinessBanking(
        bool $skipFeatureAddition = false,
        int $balance = 0,
        string $balanceType = AccountType::SHARED,
        $channel = null)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->merchant->activate();

        // Creates banking balance

        $bankingBalance = $this->fixtures->merchant->createBalanceOfBankingType(
            $balance, '10000000000000',$balanceType, $channel);

        // Need to create a Banking Account since we send this data to ledger in ledger calls
        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCde',
            'account_number'        =>  $bankingBalance['account_number'],
            'balance_id'            =>  $bankingBalance['id'],
            'account_type'          =>  'current',
            'channel'               =>  $bankingBalance['channel'],
        ];

        $this->createBankingAccount($bankingAccountAttributes);

        // Creates virtual account, its bank account receiver on new banking balance.
        $virtualAccount = $this->fixtures->create('virtual_account');
        $bankAccount    = $this->fixtures->create(
            'bank_account',
            [
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626905',
                'ifsc_code'      => 'RAZRB000000',
            ]);
        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        // Updates banking balance's account number after bank account creation.
        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        $defaultFreePayoutsCount = $this->getDefaultFreePayoutsCount($bankingBalance);

        $this->fixtures->create('counter', [
            'account_type'          => $balanceType,
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        // Enables required features on merchant
        if ($skipFeatureAddition === false)
        {
            $this->fixtures->merchant->addFeatures(['virtual_accounts', 'payout']);
        }

        $this->setupRedisConfigKeysForTerminalSelection();

        // Additionally, creates a terminal for bank transfer on banking balance.
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Sets instance member variable to be re-usable in other test methods for assertions.
        $this->bankingBalance = $bankingBalance;
        $this->virtualAccount = $virtualAccount;
        $this->bankAccount    = $bankAccount;
    }

    protected function setUpMerchantForBusinessBankingLive(
        bool $skipFeatureAddition = false,
        int $balance = 0,
        string $balanceType = AccountType::SHARED,
        $channel = Channel::YESBANK)
    {
        // Activate merchant with business_banking flag set to true.
        $this->fixtures->on('live')->merchant->edit('10000000000000', ['business_banking' => 1]);
        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);
        $this->fixtures->on('live')->merchant->activate();

        // Creates banking balance
        $bankingBalance = $this->fixtures->on('live')->merchant->createBalanceOfBankingType(
            $balance, '10000000000000',$balanceType, $channel);

        // Creates virtual account, its bank account receiver on new banking balance.
        $virtualAccount = $this->fixtures->on('live')->create('virtual_account');
        $bankAccount    = $this->fixtures->on('live')->create(
            'bank_account',
            [
                'id'             => '1000000lcustba',
                'type'           => 'virtual_account',
                'entity_id'      => $virtualAccount->getId(),
                'account_number' => '2224440041626905',
                'ifsc_code'      => 'RAZRB000000',
            ]);

        $virtualAccount->bankAccount()->associate($bankAccount);
        $virtualAccount->balance()->associate($bankingBalance);
        $virtualAccount->save();

        $bankingAccount    = $this->fixtures->on('live')->create(
            'banking_account',
            [
                'id'             => '1000000lcustba',
                'account_type'   => $balanceType,
                'merchant_id'    => '10000000000000',
                'account_number' => '2224440041626905',
                'account_ifsc'   => 'RAZRB000000',
                'status'         => 'activated'
            ]);

        $bankingAccount->balance()->associate($bankingBalance);
        $bankingAccount->save();

        $defaultFreePayoutsCount = $this->getDefaultFreePayoutsCount($bankingBalance);

        $this->fixtures->on('live')->create('counter', [
            'account_type'          => $balanceType,
            'balance_id'            => $bankingBalance->getId(),
            'free_payouts_consumed' => $defaultFreePayoutsCount,
        ]);

        // Updates banking balance's account number after bank account creation.
        $bankingBalance->setAccountNumber($virtualAccount->bankAccount->getAccountNumber());
        $bankingBalance->save();

        // Enables required features on merchant
        if ($skipFeatureAddition === false)
        {
            $this->fixtures->on('live')->merchant->addFeatures(['virtual_accounts', 'payout']);
        }

        $this->setupRedisConfigKeysForTerminalSelection();

        // Sets instance member variable to be re-usable in other test methods for assertions.
        $this->bankingBalance = $bankingBalance;
        $this->virtualAccount = $virtualAccount;
        $this->bankAccount    = $bankAccount;
    }

    protected function setupRedisConfigKeysForTerminalSelection()
    {
        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_SHARED_ACCOUNT_ALLOWED_CHANNELS => [
                    Channel::YESBANK,
                    Channel::ICICI
                ]
            ]);

        (new Admin\Service)->setConfigKeys(
            [
                Admin\ConfigKey::RX_ACCOUNT_NUMBER_SERIES_PREFIX => [
                    Account::SHARED_ACCOUNT => '222444',
                ]
            ]);
    }

    protected function createPayout(array $extraPayoutParams = [], array $contact = [], bool $createContact = true)
    {
        if ($createContact === true)
        {
            $this->createContact($contact);
        }

        $this->createFundAccount();

        $payoutParams = [
            'purpose'           => 'refund',
            'fund_account_id'   => $this->fundAccount['id'],
            'notes'             => [
                'abc' => 'xyz',
            ],
            'amount'            => 1000,
            'currency'          => 'INR',
            'balance_id'        => $this->bankingBalance->getId(),
            'pricing_rule_id'   => '1nvp2XPMmaRLxb',
        ];

        $payoutParams = array_merge($payoutParams, $extraPayoutParams);

        $this->payout = $this->fixtures->create('payout', $payoutParams);

        $this->transaction = $this->getDbLastEntity('transaction');
    }

    protected function reversePayout(Payout\Entity $payout)
    {
        // TODO: Fix this shit with proper fixtures
        (new Payout\Core)->updateStatusAfterFtaRecon($payout, [
            'fta_status'     => 'failed',
            'failure_reason' => '',
        ]);
    }

    public function createContact(array $contact = [])
    {
        if (empty($contact)) {
            $input  = [
                'id' => '1000010contact',
                'email' => 'contact@razorpay.com',
                'contact' => '8888888888',
                'name' => 'test user'
            ];

            $this->contact = $this->fixtures->on('test')->create('contact', $input);

        } else{
            $this->contact = $this->fixtures->on('test')->create('contact', $contact);
        }
    }

    protected function createFundAccount()
    {
        $this->fundAccount = $this->fixtures->on('test')->fund_account->createBankAccount(
            [
                'source_type' => 'contact',
                'source_id'   => $this->contact->getId(),
            ],
            [
                'name'           => 'test',
                'ifsc'           => 'SBIN0007105',
                'account_number' => '111000',
            ]);

    }

    protected function createVpaFundAccount(array $attributes = [])
    {
        $this->contact === null ? $this->createContact() : $this->contact ;

        $defaultAttributes = [
            'source_id'   => $this->contact->getId(),
            'source_type' => 'contact',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->fund_account->createVpa($attributes);
    }

    protected function createFAVBankingPricingPlan($mode = 'test')
    {
        $pricingPlan = [
            'plan_name'           => 'FAV Plan',
            'percent_rate'        => 290,
            'fixed_rate'          => 0,
            'org_id'              => '100000razorpay',
            'type'                => 'pricing',
            'plan_id'             => '1hDYlICobzOCYt',
            'product'             => 'banking',
            'feature'             => 'fund_account_validation',
            'payment_method'      => 'bank_account',
            'account_type'        => 'shared'
        ];

        $this->fixtures->on($mode)->create('pricing', $pricingPlan);
    }

    // TODO: Remove all the params and take an array of key value pair of features and their expected values instead
    // Check function setMockRazorxTreatment.
    protected function mockRazorxTreatment(string $channel = 'yesbank',
                                           string $ftsEnabled = 'off',
                                           string $webhookViaStork = 'off',
                                           string $webhookArrayPublicPayload = 'off',
                                           string $defaultBehaviour = 'off',
                                           string $payoutToAmexCards = 'on',
                                           string $payoutToPrepaidCards = 'on',
                                           string $createPayoutWithoutTxn = 'off',
                                           string $razorpayXAclDenyUnauthorised = 'on',
                                           string $payoutToCardsViaRbl = 'on',
                                           string $useWorkflowMicroService = 'off',
                                           string $bulkPayoutsImprovementsRollout = 'on',
                                           string $oldToNewIfscForMergedBank = 'on',
                                           string $rejectCommentInWebhook = 'off',
                                           string $allowVAToVAPayouts = 'control',
                                           string $allowWalletAccountAmazonPay = 'on',
                                           string $rblBASFetchV2 = 'off',
                                           string $enableQueuedPayoutsViaPayoutsService = 'control',
                                           string $payoutsToFtsSync = 'off',
                                           string $enableOnHoldPayoutsViaPayoutsService = 'control',
                                           string $cacEnabled = 'off',
                                           string $disableCACForAuthentication = 'off',
                                           string $cacDisabled = 'on')

    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                function ($mid, $feature, $mode) use (
                    $channel,
                    $ftsEnabled,
                    $webhookArrayPublicPayload,
                    $defaultBehaviour,
                    $payoutToAmexCards,
                    $payoutToPrepaidCards,
                    $payoutToCardsViaRbl,
                    $createPayoutWithoutTxn,
                    $razorpayXAclDenyUnauthorised,
                    $useWorkflowMicroService,
                    $bulkPayoutsImprovementsRollout,
                    $oldToNewIfscForMergedBank,
                    $rejectCommentInWebhook,
                    $allowVAToVAPayouts,
                    $allowWalletAccountAmazonPay,
                    $rblBASFetchV2,
                    $enableQueuedPayoutsViaPayoutsService,
                    $payoutsToFtsSync,
                    $enableOnHoldPayoutsViaPayoutsService,
                    $cacEnabled,
                    $disableCACForAuthentication,
                    $cacDisabled
                )
                {
                    if (ends_with($feature, 'mode_payout_filter'))
                    {
                        return strtolower($channel);
                    }

                    if (starts_with($feature, 'fts_'))
                    {
                        return strtolower($ftsEnabled);
                    }

                    if ($feature === 'payout_to_prepaid_cards')
                    {
                        return strtolower($payoutToPrepaidCards);
                    }

                    if ($feature === 'payout_to_cards_via_rbl')
                    {
                        return strtolower($payoutToCardsViaRbl);
                    }

                    if ($feature === 'forward_to_new_workflows_service')
                    {
                        return strtolower($useWorkflowMicroService);
                    }

                    if ($feature === 'razorpay_x_acl_deny_unauthorised')
                    {
                        return strtolower($razorpayXAclDenyUnauthorised);
                    }

                    if ($feature === 'bulk_payouts_improvements_rollout')
                    {
                        return strtolower($bulkPayoutsImprovementsRollout);
                    }

                    if ($feature === 'old_to_new_ifsc_for_merged_bank')
                    {
                        return strtolower($oldToNewIfscForMergedBank);
                    }

                    if ($feature === 'payouts_reject_comment_in_webhook_filter')
                    {
                        return strtolower($rejectCommentInWebhook);
                    }

                    if ($feature === 'rx_allow_va_to_va_payouts')
                    {
                        return strtolower($allowVAToVAPayouts);
                    }

                    if ($feature === 'rx_enable_amazonpay_wallet_payout')
                    {
                        return strtolower($allowWalletAccountAmazonPay);
                    }

                    if ($feature === 'rbl_v2_bas_api_integration')
                    {
                        return strtolower($rblBASFetchV2);
                    }

                    if ($feature === 'enable_queued_payouts_via_payouts_service')
                    {
                        return strtolower($enableQueuedPayoutsViaPayoutsService);
                    }

                    if ($feature === 'enable_on_hold_payouts_via_payouts_service')
                    {
                        return strtolower($enableOnHoldPayoutsViaPayoutsService);
                    }

                    if ($feature === 'payout_to_fts_sync_mode')
                    {
                        return strtolower($payoutsToFtsSync);
                    }

                    if ($feature === 'rx_custom_access_control_enabled')
                    {
                        return strtolower($cacEnabled);
                    }

                    if ($feature === 'disable_cac_for_github_test_suites')
                    {
                        return strtolower($disableCACForAuthentication);
                    }

                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return strtolower($cacDisabled);
                    }

                    return strtolower($defaultBehaviour);
                }));

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn(strtolower($webhookViaStork));
    }

    // sets razorx mock based on input array of key value pair of features and their expected values
    protected function setMockRazorxTreatment(array $razorxTreatment, string $defaultBehaviour = 'off')
    {
        // Mock Razorx
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode) use ($razorxTreatment, $defaultBehaviour)
                              {
                                  if (array_key_exists($feature, $razorxTreatment) === true)
                                  {
                                      return $razorxTreatment[$feature];
                                  }

                                  return strtolower($defaultBehaviour);
                              }));
    }

    protected function createWorkflowFeature(array $attributes = [], $mode = 'test')
    {
        $defaultAttributes = [
            'name'        => 'payout_workflows',
            'entity_id'   => '10000000000000',
            'entity_type' => 'merchant',
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        return $this->fixtures->on($mode)->create('feature', $attributes);
    }

    protected function createBankingAccount(array $attributes = [], string $mode = 'test')
    {
        $bankingAccount = $this->fixtures->on($mode)->create('banking_account', [
            'id'                    => $attributes["id"] ?? 'ABCde1234ABCde',
            'account_number'        => $attributes["account_number"] ?? '2224440041626905',
            'account_ifsc'          => $attributes["account_ifsc"] ?? 'RATN0000088',
            'account_type'          => $attributes["account_type"] ?? 'current',
            'merchant_id'           => $attributes["merchant_id"] ?? '10000000000000',
            'channel'               => $attributes["channel"] ?? 'rbl',
            'pincode'               => $attributes["pincode"] ?? '1',
            'bank_reference_number' => $attributes["bank_reference_number"] ?? '',
            'balance_id'            => $attributes["balance_id"] ?? '',
            'status'                => 'activated',
        ]);

        return $bankingAccount;
    }

    protected function getDefaultFreePayoutsCount($balance)
    {
        $balanceAccountType = $balance->getAccountType();

        $channel = $balance->getChannel();

        $defaultFreePayoutsCountConstantName = 'DEFAULT_FREE_' . strtoupper($balanceAccountType) . '_ACCOUNT_PAYOUTS_COUNT';

        if (($balanceAccountType === AccountType::DIRECT) and
            (empty($channel) === false))
        {
            $defaultFreePayoutsCountConstantName = $defaultFreePayoutsCountConstantName . '_' . strtoupper($channel);
        }

        $defaultFreePayoutsCountConstantName = $defaultFreePayoutsCountConstantName . '_SLAB1';

        $defaultFreePayoutsCount = constant(FreePayout::class . '::' . $defaultFreePayoutsCountConstantName);

        return $defaultFreePayoutsCount;
    }

    protected function setUpCounterAndFreePayoutsCount($accountType = AccountType::SHARED,
                                                       $balanceId =  '10000000000000',
                                                       $channel = null,
                                                       $mode = 'test')
    {
        $this->setFreePayoutsCountInAdminKey($accountType, $channel);

        $this->setUpCounterForFreePayout($accountType, $balanceId, $mode);
    }

    protected function setFreePayoutsCountInAdminKey($accountType = AccountType::SHARED, $channel = null)
    {
        $freePayoutsCountConstantName = 'FREE_' . strtoupper($accountType) . '_ACCOUNT_PAYOUTS_COUNT';

        if (($accountType === AccountType::DIRECT) and
            (empty($channel) === false))
        {
            $freePayoutsCountConstantName = $freePayoutsCountConstantName . '_' . strtoupper($channel);
        }

        (new Admin\Service)->setConfigKeys(
            [
                constant(Admin\ConfigKey::class . '::' . $freePayoutsCountConstantName . '_SLAB1') => 300,
                constant(Admin\ConfigKey::class . '::' . $freePayoutsCountConstantName . '_SLAB2')  => 300,
            ]);
    }

    protected function setUpCounterForFreePayout($accountType = AccountType::SHARED,
                                                 $balanceId =  '10000000000000',
                                                 $mode = 'test')
    {
        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => $accountType,
                                            'balance_id'   => $balanceId,
                                        ],
                                        $mode)->first();

        $this->fixtures->on($mode)->edit(
            'counter',
            $counter->getId(),
            [
                'free_payouts_consumed' => 0,
            ]);
    }

    protected function setUpCounterToNotAffectPayoutFeesAndTaxInManualTimeChangeTests($balance)
    {

        $balanceAccountType = $balance->getAccountType();

        $channel = $balance->getChannel();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => $balanceAccountType,
                                            'balance_id'   => $balance->getId(),
                                        ])->first();

        $defaultFreePayoutsCountConstantName = 'DEFAULT_FREE_' . strtoupper($balanceAccountType) . '_ACCOUNT_PAYOUTS_COUNT';

        if (($balanceAccountType === AccountType::DIRECT) and
            (empty($channel) === false))
        {
            $defaultFreePayoutsCountConstantName = $defaultFreePayoutsCountConstantName . '_' . strtoupper($channel);
        }

        $defaultFreePayoutsCountConstantName = $defaultFreePayoutsCountConstantName . '_SLAB1';

        $defaultFreePayoutsCount = constant(FreePayout::class . '::' . $defaultFreePayoutsCountConstantName);

        $this->fixtures->edit(
            'counter',
            $counter->getId(),
            [
                'free_payouts_consumed'               => $defaultFreePayoutsCount,
                'free_payouts_consumed_last_reset_at' => Carbon::now(Timezone::IST)->firstOfMonth()->getTimestamp(),
            ]
        );
    }
}
