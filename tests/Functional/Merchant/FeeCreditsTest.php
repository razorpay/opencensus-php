<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\Credits;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class FeeCreditsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CreditsData.php';

        parent::setUp();

        // All API calls to Credits have to be through admin account.
        $this->ba->appAuth();
    }

    public function testCreateCreditsLog()
    {
        $this->startTest();
    }

    /*public function testCreditsLogAlreadyExists()
    {
        $this->fixtures->create('credits', [Credits\Entity::TYPE => Credits\Type::FEE]);

        $this->testData[__FUNCTION__]['request']['content']['type'] = Credits\Type::FEE;

        $this->startTest();
    }*/

    public function testGetCreditsLog()
    {
        $creditsLog = $this->fixtures->create('credits', [Credits\Entity::TYPE => Credits\Type::FEE]);

        $this->testData[__FUNCTION__]['request']['url'] .= $creditsLog->getId();
        $this->testData[__FUNCTION__]['response']['content']['id'] = $creditsLog->getPublicId();

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testPositiveUpdateCredits()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 190);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['fee_credits'], 190);
    }

    public function testNegativeUpdateCredits()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);
        $id = $creditsLog['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $id;

        $this->startTest();

        $creditsLog = $this->getEntityById('credits', $id, true);
        $this->assertEquals($creditsLog['value'], 100);

        $balance = $this->fetchBalance();
        $this->assertEquals($balance['fee_credits'], 100);
    }

    public function testFailNegativeUpdateCredits()
    {
        $creditsLog = $this->fixtures->create('credits', ['value' => 150, 'type' => Credits\Type::FEE]);
        $merchant = $creditsLog->merchant;

        $balance = (new Merchant\Balance\Repository)->editMerchantAmountCredits($merchant, 10);

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/10000000000000/credits/' . $creditsLog->getId();

        $this->startTest();
    }

    public function testFailDeductCreditsCampaign()
    {
        $creditslog = $this->fixtures->create('credits', ['value' => 90, 'type' => Credits\Type::FEE]);

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/10000000000000/credits/' . $creditslog->getId();
        $this->startTest();
    }

    public function testNegativeFeeCredits()
    {
        $this->fixtures->merchant->editFeeCredits('30000000', Account::TEST_ACCOUNT);
        $this->fixtures->merchant->editCreditsforNodalAccount('30000000', 'fee');

        $this->startTest();

        $balance = $this->getEntityById('balance', Account::TEST_ACCOUNT, true);

        $merchantCredits = $balance['fee_credits'];

        $this->assertEquals($merchantCredits, 27660840);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -2339160);
    }

    public function testFeeCreditsGrantedInCampaign()
    {
        $this->fixtures->create(
            'credits',
            [
                'value' => 90, 'campaign' => 'noisy-ads',
                'type' => Credits\Type::FEE
            ]);
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'type' => Credits\Type::FEE]);
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'type' => Credits\Type::AMOUNT]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreditsGrantedToMerchant()
    {
        $this->testData[__FUNCTION__]['request']['url'] .= '?type=fee';

        $this->fixtures->create('merchant');
        $this->fixtures->create(
            'credits',
            [
                'value' => 90,
                'type' => Credits\Type::AMOUNT
            ]);
        $this->fixtures->create(
            'credits',
            ['value' => 90, 'type' => Credits\Type::FEE]);

        $this->ba->proxyAuth();
        $this->startTest();
    }

    public function testCreditsTypeCollision()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);

        $this->startTest();
    }

    public function testDeleteCreditsLog()
    {
        $creditsLog = $this->addFeeCredits(['value' => 150, 'campaign' => 'silent-ads']);

        $creditsLog = $this->fixtures->create('credits', ['type' => 'fee']);

        $this->testData[__FUNCTION__]['request']['url'] .= $creditsLog->getId();

        $this->startTest();
    }
}
