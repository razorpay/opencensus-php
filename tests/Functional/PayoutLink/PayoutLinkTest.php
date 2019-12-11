<?php

use RZP\Models\Currency\Currency;
use RZP\Models\PayoutLink\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkTest extends TestCase
{

    use TestsBusinessBanking;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TestData.php';

        parent::setUp();

        $this->createContact();

        $this->createFundAccount();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    /**
     * This is to Test the BUILD on Payout Link Entity
     */
    public function testPayoutLinkCreation()
    {
        $input = [
            PayoutLink::AMOUNT     => 1000,
            PayoutLink::CURRENCY   => Currency::INR,
            PayoutLink::DESCRIPTION   => 'TEST DESCRIPTION',
        ];

        $payout_link = (new PayoutLink)->build($input);

        $payout_link->merchant()->associate($this->contact->merchant);

        $payout_link->contact()->associate($this->contact);

        $payout_link->setStatus(Status::ISSUED);

        $payout_link->saveOrFail();
    }

    /**
     * This is to test the many-to-many mapping between payout links and payouts
     */
    public function testPayoutCreationAndLinkingToPayoutLinks()
    {
        #Todo: pl, figure out how to create payouts, then associate it with payout-links,
        # and test if the relationships hasMany and belongs to are fetching the right entities
    }

}
