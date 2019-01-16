<?php

namespace RZP\Tests\Functional\Instrument;

use RZP\Models\FundAccount;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;

class FundAccountValidationTest extends TestCase
{
    use FundAccountTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FundAccountValidationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['fund_account_validations']);
    }

    public function testCreateValidationWithFundAccountId()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $testDataToReplace = [
            'request' => [
                'content' => [
                    FundAccountValidation::FUND_ACCOUNT => [
                        FundAccount\Entity::ID   => $fundAccountResponse[FundAccountValidation::ID],
                    ],
                ]
            ]
        ];

        $this->ba->privateAuth();

        $this->startTest($testDataToReplace);
    }

    public function testCreateValidationWithWrongFundAccountId()
    {
        $testDataToReplace = [
            'request' => [
                'content' => [
                    FundAccountValidation::FUND_ACCOUNT => [
                        FundAccount\Entity::ID   => (new UniqueIdEntity())->generateId()->getId(),
                    ],
                ]
            ]
        ];

        $this->ba->privateAuth();

        $this->startTest($testDataToReplace);
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    /*public function testGetValidation()
    {
        $instrument = $this->testCreateValidationWithFundAccountId();

        $instrument = $this->getEntityById('instrument', $instrument['id']);
    }*/

    /*public function testGetMultipleValidations()
    {
        $createdInstruments = $this->fixtures->times(2)->create('instrument');
        $createdInstruments = array_reverse($createdInstruments);
        $collection = new \RZP\Models\Base\PublicCollection($createdInstruments);
        $array = $collection->toArrayPublic();

        $this->testData[__FUNCTION__]['response']['content'] = $array;

        $this->ba->privateAuth();

        $this->startTest();
    }*/
}
