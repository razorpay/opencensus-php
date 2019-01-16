<?php

namespace RZP\Tests\Functional\FundAccount;

use RZP\Models\FundAccount;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction\Core;
use RZP\Models\FundAccount\Validation\Repository;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Models\FundAccount\Validation\Entity as FundAccountValidation;

class FundAccountValidationTest extends TestCase
{
    use FundAccountTrait;
    use EntityActionTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FundAccountValidationTestData.php';

        parent::setUp();

        $this->fixtures->merchant->addFeatures(['fund_account_validations']);

        $this->addFeeCredits(['value' => 10000, 'campaign' => 'silent-ads']);
    }

    public function testCreateValidationWithFundAccountId()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $testDataToReplace = [
            'request' => [
                'content' => [
                    FundAccountValidation::FUND_ACCOUNT => [
                        FundAccount\Entity::ID => $fundAccountResponse[FundAccountValidation::ID],
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
                        FundAccount\Entity::ID => (new UniqueIdEntity())->generateId()->getId(),
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

    public function testCreateValidationWithWrongFundAccountEntity()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testCreateValidationForCustomerFeeBearer()
    {
        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_bearer' => 'customer']);

        $this->testCreateValidationWithFundAccountEntity();
    }

    public function testFeeForFundAccountValidation()
    {
        $this->markTestIncomplete('transaction is not being created at the moment');

        $this->testCreateValidationWithFundAccountEntity();

        $fundAccountValidation = $this->getDbLastEntity('fund_account_validation');

        list($fees, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($fundAccountValidation);

        //assert fee and tax here.
    }

    public function testTransactionForFundAccountValidation()
    {
        $this->testCreateValidationWithFundAccountEntity();

        $fundAccountValidation = $this->getDbLastEntity('fund_account_validation');

        (new Repository())->transaction(function() use ($fundAccountValidation) {
            list($txn, $feesSplit) = (new Core())->createTransactionForSource($fundAccountValidation);
        });
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
