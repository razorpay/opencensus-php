<?php

namespace RZP\Tests\Functional\Payout;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class CompositePayoutTest extends TestCase
{
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

        // Assert that none of contact, fund account or payout are created.
        $this->assertEquals(count($payouts), 0);
        $this->assertEquals(count($fundAccounts), 0);
        $this->assertEquals(count($contacts), 0);
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
}
