<?php

namespace RZP\Tests\Functional\FundAccount;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;

use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction\Core;
use RZP\Models\FundAccount\Validation\Repository;

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

        $this->ba->privateAuth();
    }

    public function testCreateValidationWithFundAccountId()
    {
        $fundAccountResponse = $this->createFundAccountBankAccount();

        $testDataToReplace = [
            'request' => [
                'content' => [
                    'fund_account' => [
                        'id' => $fundAccountResponse['id'],
                    ],
                ]
            ]
        ];

        $this->startTest($testDataToReplace);

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('created', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);

        $msg = \RZP\Models\FundAccount\Validation\Processor\BankAccount::PENNY_TESTING_NARRATION;
        $this->assertEquals($msg, $fta['narration']);
    }

    public function testCreateValidationWithWrongFundAccountId()
    {
        $this->startTest($testDataToReplace);
    }

    public function testCreateValidationWithFundAccountEntity()
    {
        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('created', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);

        $msg = \RZP\Models\FundAccount\Validation\Processor\BankAccount::PENNY_TESTING_NARRATION;
        $this->assertEquals($msg, $fta['narration']);
    }

    public function testCreateValidationWithWrongFundAccountEntity()
    {
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
