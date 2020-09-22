<?php

namespace RZP\Tests\Functional\Payout;

use RZP\Models\Payout;
use RZP\Models\Feature;
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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CompositePayoutTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->setUpMerchantForBusinessBanking(false, 10000000);
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
        $this->startTest();

        $payouts = $this->getDbEntities('payout');

        $fundAccounts = $this->getDbEntities('fund_account');

        $contacts = $this->getDbEntities('contact');

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
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
}
