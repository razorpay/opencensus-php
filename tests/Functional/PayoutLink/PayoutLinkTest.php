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

    const TEST_PAYOUT_LINK_PAYLOAD = [
        'id'           => 'DnhDjMDHlQEjgM',
        'contact_id'   => '1000010contact',
        'amount'       => 1000,
        'merchant_id'  => '10000000000000',
        'user_id'      => null,
        'currency'     => 'INR',
        'description'  => 'This is a test payout',
        'receipt'      => 'Test Payout Receipt',
        'notes'        => [
            'hi' => 'hello'
        ],
        'short_url'    => 'http=>//76594130.ngrok.io/i/mGs4ehe',
        'status'       => 'issued',
        'created_at'   => 1575367399,
        'cancelled_at' => null
        ];

    const FIXTURE_ENTITY = 'payout_link';

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
    public function testPayoutLinkCreation()
    {
        $input = [
            PayoutLink::CONTACT_ID => $this->contact->getId(),
            PayoutLink::AMOUNT     => 1000,
            PayoutLink::CURRENCY   => Currency::INR,
            PayoutLink::DESCRIPTION   => 'TEST DESCRIPTION',
        ];

        $payout_link = (new PayoutLink)->build($input);

        $payout_link->merchant()->associate($this->contact->merchant);

        $payout_link->saveOrFail();
    }

    public function testPostRequestForCreatingPayoutLink()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPostRequestForCreatingPayoutLinkWithContactId()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetPayoutLinkById()
    {
        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              self::TEST_PAYOUT_LINK_PAYLOAD);

        $this->ba->privateAuth();

        $url = $this->testData['testGetPayoutLinkById']['request']['url'];

        $urlWithId = $url . $payoutLink->getPublicId();

        $this->testData['testGetPayoutLinkById']['request']['url'] = $urlWithId;

        $this->startTest();

    }

    public function testListPayoutLink()
    {
        $this->ba->privateAuth();

        $i = 0;

        $payoutLinkCount = 5;

        // creating a new variable so that the original one is not altered during the test
        $testPayload = self::TEST_PAYOUT_LINK_PAYLOAD;

        $testPayoutLinkIds = [];

        while ($i < $payoutLinkCount)
        {
            $id = Entity::generateUniqueId();

            $testPayload['id'] = $id;

            $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                                   $testPayload);

            array_push($testPayoutLinkIds, $payoutLink->getPublicId());

            $i++;
        }

        $response = $this->startTest();

        $this->assertEquals($payoutLinkCount , $response['count']);

        $fetchedPayoutLinks = $response['items'];

        foreach($fetchedPayoutLinks as $pl)
        {
            $this->assertContains($pl['id'], $testPayoutLinkIds);
        }
    }

    public function testListPayoutLinkWithSearchParameter()
    {
        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY,
                                              self::TEST_PAYOUT_LINK_PAYLOAD);

        $this->ba->privateAuth();

        $url = $this->testData['testListPayoutLinkWithSearchParameter']['request']['url'];

        $urlWithId = $url . '?merchant_id=10000000000000';

        $this->testData['testListPayoutLinkWithSearchParameter']['request']['url'] = $urlWithId;

        $response = $this->startTest();

        $fetchedPayoutLinkId = $response['items'][0]['id'];

        $this->assertEquals($fetchedPayoutLinkId , 'plnk_DnhDjMDHlQEjgM');
    }
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

    public function testPayoutWithPayoutLinkRelationship()
    {
        $payout = $this->fixtures->create('payout');

        $payoutLink = $this->fixtures->create(self::FIXTURE_ENTITY);

        $payout->payoutLink()->associate($payoutLink);

        $payout->saveOrFail();

        $payoutLink = $this->getDbEntity('payout_link', ['id' => 'DnhDjMDHlQEjgM']);

        $associatedPayout = $payoutLink->payouts[0];

        $this->assertEquals($associatedPayout->getId(), $payout->getId());
    }

}