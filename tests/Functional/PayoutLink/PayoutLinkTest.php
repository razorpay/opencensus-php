<?php

use RZP\Models\P2p\Entity;
use RZP\Models\Currency\Currency;
use \RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Models\PayoutLink\Status;
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
        $this->testDataFilePath = __DIR__ . '/helpers/PayoutLinkTestData.php';

        parent::setUp();

        $this->createContact();

        $this->createFundAccount();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    /**
     * This is to Test the BUILD on Payout Link Entity
     */
//    public function testPayoutLinkCreation()
//    {
//        $input = [
//            PayoutLink::CONTACT_ID => $this->contact->getId(),
//            PayoutLink::AMOUNT     => 1000,
//            PayoutLink::CURRENCY   => Currency::INR,
//            PayoutLink::DESCRIPTION   => 'TEST DESCRIPTION',
//        ];
//
//        $payout_link = (new PayoutLink)->build($input);
//
//        $payout_link->merchant()->associate($this->contact->merchant);
//
//        $payout_link->saveOrFail();
//    }

    public function testPostRequestForCreatingPayoutLink()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

//    public function testPostRequestForCreatingPayoutLinkWithContactId()
//    {
//
//    }
//
//    public function testGetPayoutLinkById()
//    {
//
//    }
//
//    public function testListPayoutLink()
//    {
//
//    }
//
//    public function testListPayoutLinkwithSearchParameter()
//    {
//
//    }
//
//    public function testShortUrlGenerationSuccessful()
//    {
//
//    }
//
//    public function testShortUrlGenerationExceptionThrown()
//    {
//
//    }
//
//    public function testNewContactCreationPayoutLinkCreateFlow()
//    {
//
//    }
//
//    public function testPayoutLinkCreateWithContactIdParam()
//    {
//
//    }
//
//    public function testPayoutLinkFailedDueToContactCreationFailure()
//    {
//
//    }
//
//    public function testTargetUrlOfShortUrlForPayoutLink()
//    {
//
//    }

    //     #todo: pl
    //     A test case which creates a payout, links to payoutlink and then also checks for all the hasMany and belongsTo relationships

}