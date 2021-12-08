<?php

namespace RZP\Tests\Functional\CorporateCards;

use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CorporateCardsTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/CorporateCardsDataTest.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testCreateCorporateCard()
    {
        $tokenResponse = $this->testMerchantTokenCreate();

        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner');

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/corporate_cards/token/' . $tokenResponse['token'];

        $this->startTest($data);
    }

    public function testCreateCorporateCardFailInvalidToken()
    {
        // Authorization
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner');
        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());

        $data = $this->testData[__FUNCTION__];

        $data['request']['url'] = '/corporate_cards/token/995d8c51833bc0959068d302fe0f29a71ebf6720';

        $this->startTest($data);
    }

    public function testMerchantTokenCreate() : array
    {
        // Authorization
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner');
        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());

        return $this->startTest();
    }
}
