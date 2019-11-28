<?php

use RZP\Models\P2p\Entity;
use \RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Models\PayoutLink\Status;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkTest extends TestCase
{

    use TestsBusinessBanking;

    public function setUp()
    {
        parent::setUp();

        $this->ba->privateAuth();

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
            PayoutLink::CONTACT_ID      => $this->contact->getId(),
            PayoutLink::FUND_ACCOUNT_ID => $this->fundAccount->getId(),
            PayoutLink::SHORT_URL       => 'http://rzp.io/faking_url',
            PayoutLink::MERCHANT_ID     => '10000000000000',
            PayoutLink::STATUS          => Status::ISSUED,
            PayoutLink::AMOUNT          => 1000,
        ];

        $payout_link = (new PayoutLink)->build($input);

        $payout_link->saveOrFail();

    }

    /**
     * This is to test the many-to-many mapping between payout links and payouts
     */
    public function testPayoutCreationAndLinkingToPayoutLinks()
    {
//        $this->testPayoutLinkCreation();


    }

}