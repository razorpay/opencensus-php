<?php

namespace RZP\Tests\Functional\PayoutLink;

use Mockery;
use Exception;
use RZP\Error\ErrorCode;
use RZP\Models\P2p\Entity;
use RZP\Models\Currency\Currency;
use RZP\Models\PayoutLink\Status;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Services\Elfin\Service as ElfinService;
use RZP\Models\PayoutLink\Entity as PayoutLink;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\PayoutLink\Clients\Contact as ContactClient;

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

        $urlWithId = $url . '?contact_id=' . $this->contact->getPublicId();

        $this->testData['testListPayoutLinkWithSearchParameter']['request']['url'] = $urlWithId;

        $response = $this->startTest();

        $fetchedPayoutLinkId = $response['items'][0]['id'];

        $this->assertEquals($fetchedPayoutLinkId , $payoutLink->getPublicId());
    }

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

    public function testShortUrlGenerationSuccessful()
    {
        $this->ba->privateAuth();

        $testHardCodedShortUrl = 'www.this_is_when_elfin_works.com';

        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('shorten')
              ->willReturn($testHardCodedShortUrl);

        $this->app->instance('elfin', $elfin);

        $response = $this->startTest();

        $this->assertEquals($response['short_url'], $testHardCodedShortUrl);
    }

    public function testShortUrlGenerationExceptionThrown()
    {
        $urlFormat = '%s/payout-links/%s/view';

        $this->ba->privateAuth();

        // mock elfin and make it throw an error, check the right exception is thrown on our end
        $elfin = $this->createMock(ElfinService::class);

        $elfin->method('shorten')
              ->willThrowException(new Exception('Elfin failed because I want it to'));

        $this->app->instance('elfin', $elfin);

        $response = $this->startTest();

        $payoutLinkId = $response['id'];

        $expectedTargetUrl = sprintf($urlFormat,
                             $this->app['config']['url.api.production'],
                             $payoutLinkId);

        // asserting that the short_url is same as the full target url, because elfin failed
        $this->assertEquals($response['short_url'], $expectedTargetUrl);

    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPayoutLinkFailedDueToContactCreationFailure()
    {
        $mockedContactCore = Mockery::mock('overload:RZP\Models\Contact\Core');

        $mockedContactCore->shouldReceive('create')
                          ->once()
                          ->andThrow(new Exception('I failed for the sake of testing'));

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testContactAddFailsWhenEmailAndPhoneNumberBothMissing()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'name'    => 'Test Contact',
                                               'email'   => '',
                                               'contact' => ''
                                           ]);

        $testData = $this->testData['testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone'];

        $testData['request']['content']['contact']['id'] = $contact->getId();

        $this->testData['testPayoutLinkCreationFailsWhenContactIdIsMissingBothEmailAndPhone'] = $testData;

        $this->ba->privateAuth();

        $this->startTest();
    }
}
