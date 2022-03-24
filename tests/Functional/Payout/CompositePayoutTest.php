<?php

namespace RZP\Tests\Functional\Payout;

use RZP\Error\Error;
use RZP\Models\Payout;
use RZP\Models\Feature;
use RZP\Models\Card\Type;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Payout\WorkflowFeature;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class CompositePayoutTest extends TestCase
{
    use PayoutTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CompositePayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testCreateCompositePayout()
    {
        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contact = $this->getDbLastEntity('contact');

        // Assert that the last entities in db are created by the composite payout request
        $this->assertEquals('pout_' . $payout['id'], $response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account_id']);
        $this->assertEquals('cont_' . $contact['id'], $response['fund_account']['contact_id']);

        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    public function testCreateCompositePayoutWithOldNewIfsc()
    {
        $this->markTestSkipped('the IFSC being used is invalid, skipping the test till its replaced with valid value');

        $this->mockRazorxTreatment();

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contact = $this->getDbLastEntity('contact');

        $bankAccounts = $this->getDbEntities('bank_account');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $this->assertEquals($bankAccounts[4]['id'], $fta['bank_account_id']);
        $this->assertEquals('PUNB0168510',$bankAccounts[4]['ifsc']);
        $this->assertEquals('contact', $bankAccounts[4]['type']);

        // Assert that the last entities in db are created by the composite payout request
        $this->assertEquals('pout_' . $payout['id'], $response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account_id']);
        $this->assertEquals('cont_' . $contact['id'], $response['fund_account']['contact_id']);

        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    public function testCreateCompositePayoutWithOldNewIfscWithExistingAccount()
    {
        $this->markTestSkipped('the IFSC being used is invalid, skipping the test till its replaced with valid value');

        $this->testCreateCompositePayoutWithOldNewIfsc();

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $bankAccounts = $this->getDbEntities('bank_account');

        $this->assertEquals($bankAccounts[4]['id'], $fta['bank_account_id']);

        $this->assertEquals('PUNB0168510',$bankAccounts[4]['ifsc']);

        $this->mockRazorxTreatment();

        $response = $this->startTest();

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $bankAccounts2 = $this->getDbEntities('bank_account');

        $this->assertEquals($bankAccounts2[4]['id'], $fta['bank_account_id']);
        $this->assertEquals('PUNB0168510',$bankAccounts2[4]['ifsc']);

        $this->assertEquals($bankAccounts[4]['id'], $bankAccounts2[4]['id']);
    }

    public function testCreateCompositePayoutWithNewCreditsFlowMerchantWithNoCredits()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => -2000000 , 'campaign' => 'test rewards 1', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'used' => 2000000, 'campaign' => 'test rewards 2', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'used' => 1000000, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 1000000 , 'used' => 1000000, 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contact = $this->getDbLastEntity('contact');

        // Assert that the last entities in db are created by the composite payout request
        $this->assertEquals('pout_' . $payout['id'], $response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account_id']);
        $this->assertEquals('cont_' . $contact['id'], $response['fund_account']['contact_id']);

        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    public function testCreateCompositePayoutWithNewCreditsFlowMerchantWithCredits()
    {
        $this->fixtures->create('credits', ['merchant_id' => '10000000000000', 'value' => 2000000 , 'campaign' => 'test rewards', 'type' => 'reward_fee', 'product' => 'banking']);

        $this->fixtures->create('credit_balance', ['merchant_id' => '10000000000000', 'balance' => 2000000 ]);

        $creditBalanceEntity = $this->getDbLastEntity('credit_balance');

        $creditEntity = $this->getDbLastEntity('credits');

        $this->fixtures->edit('credits', $creditEntity['id'], ['balance_id' => $creditBalanceEntity['id']]);

        $balance = $this->getLastEntity('balance', true);

        $balanceBefore = $balance['balance'];

        $this->ba->privateAuth();
        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contact = $this->getDbLastEntity('contact');

        $balance = $this->getLastEntity('balance', true);

        $this->assertEquals('shared', $balance['account_type']);
        $this->assertEquals($balanceBefore - 2000000, $balance['balance']);

        $creditBalanceEntity = $this->getLastEntity('credit_balance', true);
        $this->assertEquals(2000000, $creditBalanceEntity['balance']);

        $creditEntity = $this->getLastEntity('credits', true);
        $this->assertEquals(900, $creditEntity['used']);

        $creditTxnEntity = $this->getLastEntity('credit_transaction', true);
        $this->assertEquals('payout', $creditTxnEntity['entity_type']);
        $this->assertEquals($payout['id'], $creditTxnEntity['entity_id']);
        $this->assertEquals(900, $creditTxnEntity['credits_used']);

        // Assert that the last entities in db are created by the composite payout request
        $this->assertEquals('pout_' . $payout['id'], $response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account_id']);
        $this->assertEquals('cont_' . $contact['id'], $response['fund_account']['contact_id']);

        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    public function testCreateCompositePayoutWithDuplicateContactAndFundAccount()
    {
        $this->testCreateCompositePayout();

        $this->testCreateCompositePayout();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that 2 payouts are created and only one fund account and one contact are created.
        $this->assertEquals(count($payouts), 2);
        $this->assertEquals(count($fundAccounts), 1);
        $this->assertEquals(count($contacts), 1);

        // Assert that 2 payouts have different ids
        $this->assertNotEquals($payouts[0]['id'], $payouts[1]['id']);
    }

    public function testCreateCompositePayoutWithDuplicateContactDifferentFundAccount()
    {
        $this->testCreateCompositePayout();

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that 2 payouts are created and only one fund account and one contact are created.
        $this->assertEquals(count($payouts), 2);
        $this->assertEquals(count($fundAccounts), 2);
        $this->assertEquals(count($contacts), 1);

        // Assert that 2 payouts and 2 fund accounts have different ids
        $this->assertNotEquals($payouts[0]['id'], $payouts[1]['id']);
        $this->assertNotEquals($fundAccounts[0]['id'], $fundAccounts[1]['id']);
    }

    public function testCreateCompositePayoutWithFundAccountId()
    {
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
    }

    public function testCreateCompositePayoutWithContactId()
    {
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
    }

    public function testCreateCompositePayoutWithContactValidationFailure()
    {
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
    }

    public function testCreateCompositePayoutWithFundAccountValidationFailure()
    {
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);

        // Assert that contact is created even though the request failed.
        $this->assertEquals(count($contacts), 1);
    }

    public function testCreateCompositePayoutWithPayoutValidationFailure()
    {
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
    }

    public function testCreateCompositePayoutWithoutFundAccountIdAndFundAccount()
    {
        $response = $this->startTest();

        $this->assertArrayHasKey(Error::STEP, $response['error']);

        $this->assertArrayHasKey(Error::METADATA, $response['error']);

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
    }

    public function testCreateCompositePayoutForNonSavedCardFlow()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_NON_SAVED_CARDS,
                                                Feature\Constants::ALLOW_CARD_NAME_CHANGES,
                                                Feature\Constants::S2S,
                                                Feature\Constants::PAYOUT_TO_CARDS]);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $card = $this->getDbEntity('card');

        $contact = $this->getDbEntity('contact');

        $this->assertEquals($card['name'], $contact['name']);

        $this->assertArrayNotHasKey('tokenised', $response['fund_account']['card']);

        $this->assertArrayNotHasKey('name', $response['fund_account']['card']);
    }

    public function testCreateCompositePayoutWithTokenisedCard()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_NON_SAVED_CARDS,
                                                Feature\Constants::ALLOW_CARD_NAME_CHANGES,
                                                Feature\Constants::S2S,
                                                Feature\Constants::PAYOUT_TO_CARDS]);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateCompositePayoutForNonSavedCardFlowWithoutNameChanges()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::ALLOW_NON_SAVED_CARDS,
                                                Feature\Constants::S2S,
                                                Feature\Constants::PAYOUT_TO_CARDS]);

        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::CREDIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->ba->privateAuth();

        $response = $this->startTest();

        $card = $this->getDbEntity('card');

        $contact = $this->getDbEntity('contact');

        $this->assertNotEquals($card['name'], $contact['name']);

        $this->assertArrayNotHasKey('tokenised', $response['fund_account']['card']);
    }

    public function testCreateCompositePayoutForCred()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_TO_CARDS, Feature\Constants::S2S]);

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contact = $this->getDbLastEntity('contact');

        // Assert that the last entities in db are created by the composite payout request
        $this->assertEquals('pout_' . $payout['id'], $response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account_id']);
        $this->assertEquals('cont_' . $contact['id'], $response['fund_account']['contact_id']);

        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    public function testFreeCompositePayoutCreation()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->setUpCounterAndFreePayoutsCount('shared', $balanceId);

        $testData = $this->testData['testCreateCompositePayout'];

        $testData['response']['content']['fees'] = 0;
        $testData['response']['content']['tax']  = 0;

        $this->testData['testCreateCompositePayout'] = $testData;

        $this->testCreateCompositePayout();

        $counter = $this->getDbEntities('counter',
                                        [
                                            'account_type' => 'shared',
                                            'balance_id'   => $balanceId,
                                        ])->first();

        // Assert that the free payout was consumed.
        $this->assertEquals(1, $counter->getFreePayoutsConsumed());

        $payout = $this->getDbLastEntity('payout');

        // Assert that free_payout is assigned as fee_type
        $this->assertEquals(Payout\Entity::FREE_PAYOUT, $payout->getFeeType());
    }

    public function testCreateCompositePayoutWithSkipWfAtPayoutAndSkipWorkflowTrue()
    {
        $this->createSkipWorkflowForPayoutFeature();

        $response = $this->startTest();

        $payout = $this->getDbEntityById('payout', $response['id']);

        $fundAccount = $this->getDbEntityById('fund_account', $response['fund_account_id']);

        $contact = $this->getDbEntityById('contact', $response['fund_account']['contact_id']);


        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);
        $this->assertEquals(WorkflowFeature::WORKFLOW_FEATURES[Feature\Constants::SKIP_WF_AT_PAYOUTS],
            $payout[Payout\Entity::WORKFLOW_FEATURE]);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    public function testCreateCompositePayoutWithSkipWfAtPayoutAndSkipWorkflowFalse()
    {
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that payout is not created.
        $this->assertEquals(count($payouts), 0);

        // Assert that contact and fund account are created even though the request failed. This is to verify that if
        // validation in the start doesn't fail the request, contact and fund account will be created even if payout
        // creation fails at later stage.
        $this->assertEquals(count($fundAccounts), 1);
        $this->assertEquals(count($contacts), 1);
    }

    public function testCreateCompositePayoutWithInsufficientBalanceAndQueueFlagUnset()
    {
        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000,
            ]);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that payout is not created.
        $this->assertEquals(count($payouts), 0);

        // Assert that contact and fund account are created even though the request failed. This is to verify that if
        // validation in the start doesn't fail the request, contact and fund account will be created even if payout
        // creation fails at later stage.
        $this->assertEquals(count($fundAccounts), 1);
        $this->assertEquals(count($contacts), 1);
    }

    public function testRetryCompositePayoutCreate()
    {
        $this->testCreateCompositePayoutWithInsufficientBalanceAndQueueFlagUnset();

        $balanceId = $this->bankingBalance->getId();

        $this->fixtures->edit(
            'balance',
            $balanceId,
            [
                'balance' => 2000000000,
            ]);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that only one of each of payout, fund account and contact are created.
        $this->assertEquals(count($payouts), 1);
        $this->assertEquals(count($fundAccounts), 1);
        $this->assertEquals(count($contacts), 1);
    }

    public function testCreateCompositePayoutWithOriginField()
    {
        $this->startTest();
    }

    public function testCreateCompositePayoutWithSourceDetailsField()
    {
        $this->startTest();
    }

    public function testCreateCompositePayoutWithoutFundAccountIdAndFundAccountNewApiError()
    {
        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
    }

    public function testCreateCompositePayoutWithoutFundAccountIdAndFundAccountNewApiErrorOnLiveMode()
    {
        $this->liveSetUp();

        $payoutsBefore = $this->getDbEntities('payout', [], 'live');

        $fundAccountsBefore = $this->getDbEntities('fund_account',  [], 'live');

        $contactsBefore = $this->getDbEntities('contact', [], 'live');

        $this->ba->privateAuth('rzp_live_TheLiveAuthKey');

        $this->fixtures->merchant->addFeatures([Feature\Constants::NEW_BANKING_ERROR]);

        $this->startTest();

        $payouts = $this->getDbEntities('payout', [], 'live');

        $fundAccounts = $this->getDbEntities('fund_account',  [], 'live');

        $contacts = $this->getDbEntities('contact', [], 'live');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), count($payoutsBefore));
        $this->assertEquals(count($fundAccounts), count($fundAccountsBefore));
        $this->assertEquals(count($contacts), count($contactsBefore));
    }

    public function testCreateCompositeM2PPayoutForDebitCardWithUpperCaseCardMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($payout['mode'],'card');
        $this->assertEquals($payout['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['mode'],'CT');
    }

    public function testCreateCompositeM2PPayoutForDebitCardWithLowerCaseCardMode()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth();

        $this->startTest();

        $payout = $this->getLastEntity('payout', true);

        $payoutAttempt = $this->getLastEntity('fund_transfer_attempt', true);

        $this->assertEquals($payout['mode'],'card');
        $this->assertEquals($payout['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['channel'], 'm2p');
        $this->assertEquals($payoutAttempt['mode'],'CT');
    }

    public function testCreateCompositeM2PPayoutForDebitCardWithoutSupportedModes()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => "default_issuer"
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->ba->privateAuth();

        $this->startTest();
    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateCompositeM2PPayoutForDebitCardMerchantBlockedByProduct()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => 10000000000000,
                'key'         => 'settlement',
                'value'       => 'true',
            ]
        );

        $this->startTest();
    }

    // Following test depends on configs. Adding/removing configs defined in Models/FundTransfer/M2P/M2PConfigs file can fail these.
    // We need to make changes to the test sample data to pass them
    public function testCreateCompositeM2PPayoutForDebitCardMerchantBlockedByNetwork()
    {
        $this->fixtures->create('iin', [
            'iin'     => 340169,
            'network' => Network::$fullName[Network::MC],
            'type'    => Type::DEBIT,
            'issuer'  => Issuer::YESB
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::S2S,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create('feature', [
            'name'        => Feature\Constants::PAYOUT_TO_CARDS,
            'entity_id'   => 10000000000000,
            'entity_type' => 'merchant',
        ]);

        $this->fixtures->create(
            'settings',
            [
                'module'      => 'm2p_transfer',
                'entity_type' => 'merchant',
                'entity_id'   => 10000000000000,
                'key'         => Network::MC,
                'value'       => 'true',
            ]
        );

        $this->startTest();
    }

    public function testCreateCompositePayoutForWalletAccountTypeAmazonpay()
    {
        $this->mockRazorxTreatment();

        $response = $this->startTest();

        $payout = $this->getDbLastEntity('payout');

        $fundAccount = $this->getDbLastEntity('fund_account');

        $contact = $this->getDbLastEntity('contact');

        // Assert that the last entities in db are created by the composite payout request
        $this->assertEquals('pout_' . $payout['id'], $response['id']);
        $this->assertEquals('fa_' . $fundAccount['id'], $response['fund_account_id']);
        $this->assertEquals('cont_' . $contact['id'], $response['fund_account']['contact_id']);

        // Assert that contact, fund_account and payout in db are related to each other
        $this->assertEquals($payout['fund_account_id'], $fundAccount['id']);
        $this->assertEquals($fundAccount['source_id'], $contact['id']);

        // Assert that the response payout, fund_account and contact are also related to each other
        $this->assertEquals($response['fund_account_id'], $response['fund_account']['id']);
        $this->assertEquals($response['fund_account']['contact_id'], $response['fund_account']['contact']['id']);
    }

    // Fund accounts received in composite wallet payout response shouldn't have
    // merchant_disabled field
    public function testCreateCompositePayoutForWalletApiRequestNoMerchantDisabledField()
    {
        $this->mockRazorxTreatment();

        $this->ba->privateAuth();

        $response = $this->startTest();

        // Assert that the response doesn't have 'merchant_disabled' field
        $this->assertArrayNotHasKey('merchant_disabled', $response['fund_account']);
    }

    // Fund accounts received in composite wallet payout response shouldn't have
    // merchant_disabled field
    public function testCreateCompositePayoutForBankAccountApiRequestNoMerchantDisabledField()
    {
        $this->mockRazorxTreatment();

        $this->ba->privateAuth();

        $response = $this->startTest();

        // Assert that the response doesn't have 'merchant_disabled' field
        $this->assertArrayNotHasKey('merchant_disabled', $response['fund_account']);
    }

    /**
     * Given duplicate fund accounts with the same unique_hash exists,
     * When a composite payout creation request with same fund account details is received
     * Then the existing oldest active Fund account should be associated with the payout
     **/
    public function testCreateCompositePayoutSelectsOldestActiveFundAccountWhenDuplicateFundAccountIsFound()
    {
        $this->fixtures->create('contact', [
            "id" => "J7iImMrzcOhfSi",
            "merchant_id" => "10000000000000",
            "name" => "Prashanth YV",
            "contact" => "9999999999",
            "email" => "prashanth@razorpay.com",
            "type" => "employee",
            "reference_id" => null,
            "notes" => [
                "note_key" => "note_value"
            ],
            "active" => true,
            "created_at" => 1647423442,
            "updated_at" => 1647423442,
        ]);

        $this->fixtures->create('bank_account', [
            "id" => "J7iQ0CTMCm9xdY",
            "merchant_id" => "10000000000000",
            "entity_id" => "J7iQ02v8z258fx",
            "type" => "contact",
            "ifsc_code" => "SBIN0007105",
            "account_number" => "111000",
            "beneficiary_name" => "Prashanth YV",
            "beneficiary_country" => "IN",
            "notes" => [],
            "created_at" => 1647423853,
            "name" => "Prashanth YV",
            "ifsc" => "SBIN0007105",
        ]);

        // Create duplicate Fund Accounts with same unique_hash
        $oldestFundAccountId = "J7iImZSVfq0Ydc";
        $oldestFundAccountCreationTime = 1647423443;

        $oldestActiveFundAccountId = "J7iImZSVfq0Ydd";
        $oldestActiveFundAccountCreationTime = 1647423453;

        $newestActiveFundAccountId = "J7iImZSVfq0Yde";
        $newestActiveFundAccountCreationTime = 1647423463;

        $this->fixtures->create('fund_account', [
            "id" => $oldestFundAccountId,
            "merchant_id" => "10000000000000",
            "source_type" => "contact",
            "source_id" => "J7iImMrzcOhfSi",
            "account_type" => "bank_account",
            "account_id" => "J7iQ0CTMCm9xdY",
            "active" => false,
            "created_at" => $oldestFundAccountCreationTime,
            "updated_at" => $oldestFundAccountCreationTime,
            "unique_hash" => "8f6ad5450d7466e718fa383ed6cff11dea8ef26d624dbf154891b9d69061f6b4",
        ]);

        $this->fixtures->create('fund_account', [
            "id" => $oldestActiveFundAccountId,
            "merchant_id" => "10000000000000",
            "source_type" => "contact",
            "source_id" => "J7iImMrzcOhfSi",
            "account_type" => "bank_account",
            "account_id" => "J7iQ0CTMCm9xdY",
            "active" => true,
            "created_at" => $oldestActiveFundAccountCreationTime,
            "updated_at" => $oldestActiveFundAccountCreationTime,
            "unique_hash" => "8f6ad5450d7466e718fa383ed6cff11dea8ef26d624dbf154891b9d69061f6b4",
        ]);

        $this->fixtures->create('fund_account', [
            "id" => $newestActiveFundAccountId,
            "merchant_id" => "10000000000000",
            "source_type" => "contact",
            "source_id" => "J7iImMrzcOhfSi",
            "account_type" => "bank_account",
            "account_id" => "J7iQ0CTMCm9xdY",
            "active" => true,
            "created_at" => $newestActiveFundAccountCreationTime,
            "updated_at" => $newestActiveFundAccountCreationTime,
            "unique_hash" => "8f6ad5450d7466e718fa383ed6cff11dea8ef26d624dbf154891b9d69061f6b4",
        ]);

        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        // Assert that the created payout picks up the oldest active Fund Account ID
        $this->assertEquals($oldestActiveFundAccountId, $payouts[0]['fund_account_id']);
    }
}
