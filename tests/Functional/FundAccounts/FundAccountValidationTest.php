<?php

namespace RZP\Tests\Functional\FundAccount;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;

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

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

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

        return $response;
    }

    public function testCreateValidationWithWrongFundAccountId()
    {
        $this->startTest();
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

        $fav = $this->getLastEntity('fund_account_validation', true);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
    }

    public function testFeeForFundAccountValidation()
    {
        $this->testCreateValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);
        // Default pricing has rule 1zE31zbybacab4 with fixed rate 300
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);
    }

    public function testTransactionForFundAccountValidation()
    {
        $this->testCreateValidationWithFundAccountEntity();

        $fav = $this->getLastEntity('fund_account_validation', true);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
    }

    public function testPostFundTransfer()
    {
        $fundTransferAttemptArray = $this->testCreateValidationWithFundAccountId();

        $input = [
            Entity::SOURCE_ID => $fundTransferAttemptArray['id'],
            \RZP\Models\BankAccount\Entity::REGISTERED_BENEFICIARY_NAME => 'This is Awesome',
        ];

        (new FundAccount\Validation\Core())->postFundTransfer($input);
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
