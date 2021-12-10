<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;

class AmountCreditsTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CreditsData.php';

        parent::setUp();

        $this->ba->getAdmin()->merchants()->attach('10000000000000');

        // All API calls to Credits have to be through admin account.
        $this->ba->adminAuth();
    }

    public function testCreateCreditsLog()
    {
        $this->startTest();
    }

    /*public function testCreditsLogAlreadyExists()
    {
        $this->fixtures->create('credits');
        $this->startTest();
    }*/

    public function testGetCreditsLog()
    {
        $creditsLog = $this->fixtures->create('credits');

        $this->testData[__FUNCTION__]['request']['url'] .= $creditsLog->getPublicId();
        $this->testData[__FUNCTION__]['response']['content']['id'] = $creditsLog->getPublicId();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPositiveUpdateCredits()
    {
        $creditsLog = $this->addAmountCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 190);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['credits'], 190);
    }

    public function testNegativeUpdateCredits()
    {
        $creditsLog = $this->addAmountCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 100);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['credits'], 100);
    }

    public function testNegativeAmountCredits()
    {
        $this->fixtures->merchant->editCredits('1000000', Account::TEST_ACCOUNT);
        $this->fixtures->merchant->editCreditsforNodalAccount('1000000');

        $this->startTest();

        $balance = $this->getEntityById('balance', Account::TEST_ACCOUNT, true);

        $merchantCredits = $balance['credits'];

        $this->assertEquals($merchantCredits, 999850);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -150);
    }


    public function testFailNegativeUpdateCredits()
    {
        $creditsLog = $this->fixtures->create('credits', ['value' => 150]);

        $merchant = $creditsLog->merchant;

        $balance = (new Merchant\Balance\Repository)->editMerchantAmountCredits($merchant, 10);

        $data = [
            'request' => [
                'url' => '/merchants/10000000000000/credits/' . $creditsLog->getId(),
            ]
        ];

        $this->startTest($data);
    }

    public function testFailDeductCreditsCampaign()
    {
        $creditslog = $this->fixtures->create('credits', ['value' => 90]);

        $data = [
            'request' => [
                'url' => '/merchants/10000000000000/credits/' . $creditslog->getId(),
            ]
        ];

        $this->startTest($data);
    }

    public function testAmountCreditsGrantedInCampaign()
    {
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'campaign' => 'noisy-ads']);
        $this->fixtures->create('credits', ['value' => 90]);
        $this->fixtures->create('credits', ['value' => 90]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreditsGrantedToMerchant()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('credits', ['value' => 90, 'merchant_id' => $merchant->getId()]);
        $this->fixtures->create('credits', ['value' => 90]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function test3MonthExpiryForAllAmountCredits()
    {
        $creditsLog = $this->addAmountCredits(['value' => 1000, 'campaign' => 'silent-ads']);

        $created = Carbon::parse($creditsLog['created_at']);
        $expiry = Carbon::parse($creditsLog['expired_at']);

        $this->assertNotNull($creditsLog['expired_at']);

        $diffInDays = $expiry->diffInDays($created);

        $this->assertEquals(92, $diffInDays);
    }

    public function test50DaysExpiryForAmountCredits()
    {
        $expiryDate = Carbon::now()->addDays(50)->getTimestamp();
        $creditsLog = $this->addAmountCredits(['value' => 1000, 'campaign' => 'silent-ads', 'expired_at' => $expiryDate]);

        $this->assertNotNull($creditsLog['expired_at']);

        $this->assertEquals($expiryDate, $creditsLog['expired_at']);
    }
}
