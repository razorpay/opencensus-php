<?php

namespace RZP\Tests\Functional\FundAccount;

use Queue;
use \RZP\Constants;
use RZP\Error\Error;
use RZP\Models\Feature;
use RZP\Models\Admin\Admin;
use RZP\Jobs\FavQueueForFTS;
use RZP\Models\FundAccount\Type;
use RZP\Models\FundAccount\Validation\Core as FavCore;
use RZP\Models\FundAccount\Validation\Entity as Validation;
use RZP\Tests\Functional\TestCase;

use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Models\Merchant\Balance\Channel;
use RZP\Models\Merchant\Balance\AccountType;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\Feature\Constants as Features;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Tests\Functional\FundTransfer\AttemptTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\FundTransfer\AttemptReconcileTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountTrait;
use RZP\Tests\Functional\Helpers\FundAccount\FundAccountValidationTrait;

class FavFtaDeprecationTest extends TestCase
{
    use WebhookTrait;
    use AttemptTrait;
    use FundAccountTrait;
    use TestsWebhookEvents;
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use AttemptReconcileTrait;
    use FundAccountValidationTrait;

    protected function setUp() : void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/FavFtaDeprecationTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:shared_sharp_terminal');

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'postpaid']);

        $this->ba->privateAuth();

        $this->mockRazorxTreatment();
    }

    public function testCreateFav()
    {
        Queue::fake();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] = $fundAccountResponse['id'];

        $this->startTest();

        Queue::assertPushed(FavQueueForFTS::class);
    }

    public function testFAVCreditforBankingBalance()
    {
        $this->enableRazorXTreatmentForRazorX();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $this->fixtures->merchant->addFeatures(['expose_fa_validation_utr']);

        $this->fixtures->merchant->editEntity('merchant', '10000000000000', ['fee_model' => 'na']);

        $this->testData[__FUNCTION__]['request']['content']['fund_account']['id'] =  $fundAccountResponse['id'];

        $balanceAccount = $this->getDbLastEntity('balance');

        $this->testData[__FUNCTION__]['request']['content']['account_number'] =  $balanceAccount['account_number'];

        $this->createFAVBankingPricingPlan();

        $response = $this->startTest();

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $fav         = $this->getLastEntity('fund_account_validation', true);

        // Queue will be processed by now.
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        $this->assertNotNull($fav['results']['utr']);
        $this->assertEquals($balanceAccount['id'], $fav['balance_id']);
        $this->assertEquals('INR', $fav['currency']);

        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(3, $fav['fees']);
        $this->assertEquals(0, $fav['tax']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals('na', $txn['fee_model']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(3, $txn['fee']);
        $this->assertEquals(3, $txn['mdr']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(3, $txn['debit']);
        $this->assertEquals($fav['amount'], $txn['amount']);
        $this->assertEquals(9999997, $txn['balance']);
        $this->assertEquals(0, $txn['fee_credits']);
        //credit_type is default since fee_credits are not used by razorpayX
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertNotNull($txn['posted_at']);

        // utr should be present in response['results'] array
        $this->assertArrayKeysExist($response['results'], ['utr','account_status','registered_name']);

        return $response;
    }

    public function testFavHandlerFunction()
    {
        Queue::fake();

        $fundAccountResponse = $this->createFundAccountBankAccount();

        $request = [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT => [
                    FundAccount::ID => $fundAccountResponse['id'],
                ],
                Validation::CURRENCY     => 'INR',
                Validation::NOTES        => [],
                Validation::RECEIPT      => '12345667',
            ],
        ];

        $this->makeRequestAndGetContent($request);

        $fav = $this->getDbLastEntity('fund_account_validation');

        $ftsClientResponse = (new FavCore())->sendFAVRequestToFTS($fav['id']);

        $ftsTransferId = $ftsClientResponse['body']['fund_transfer_id'];

        (new FavCore())->setTransferId($fav['id'], $ftsTransferId);

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $newFav = $this->getDbLastEntity('fund_account_validation');

        $bankAccount = $this->getDbLastEntity('bank_account');

        // assert details relevant to FAV
        $this->assertNull($fav->getFTSTransferId());
        $this->assertNotNull($newFav->getFTSTransferId());
        $this->assertEquals($ftsClientResponse['body']['fund_transfer_id'], $newFav->getFTSTransferId());

        // assert details relevant to FTA creation
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']['id']);
        $this->assertEquals($bankAccount['id'], $fta['bank_account_id']);
    }

    public function testWebhookFiringFundAccountValidationCompleted()
    {
        $this->testFavHandlerFunction();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $eventTestDataKey = 'testFiringOfWebhookOnFAVCompletionWithStork';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'utr'    => '933815233814',
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.completed', $eventTestDataKey);

        $this->updateFtaAndSource($favId, 'PROCESSED', '933815233814', 'SUCCESS', false);

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('processed', $fta->getStatus());

        $this->assertEquals('completed', $fav->getStatus());

    }

    public function testWebhookFiringFundAccountValidationFailed()
    {
        $this->testFavHandlerFunction();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $eventTestDataKey = 'testWebhookFiringFundAccountValidationFailed';

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->expectWebhookEventWithContents('fund_account.validation.failed', $eventTestDataKey);

        $this->updateFtaAndSource($favId, 'FAILED','944926344925','ACCOUNT_INVALID',true);

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('failed', $fta->getStatus());

        $this->assertEquals('failed', $fav->getStatus());

    }

    public function testFundAccValidationWhenFtaStillInitiatedDuringRecon()
    {
        $this->testFavHandlerFunction();

        $fav = $this->getDbLastEntity('fund_account_validation');

        $fta = $this->getDbLastEntity('fund_transfer_attempt');

        $favId = $fav->getId();

        $this->fixtures->edit(
            'fund_transfer_attempt',
            $fta->getId(),
            [
                'is_fts' => 1,
            ]);

        $this->updateFtaAndSource($favId, 'INITIATED','944926344925','IN_PROGRESS',false);

        $fav = $this->getDbEntityById('fund_account_validation', $favId);

        $fta = $this->getDbEntityById('fund_transfer_attempt', $fta->getId());

        $this->assertEquals('initiated', $fta->getStatus());

        $this->assertEquals('created', $fav->getStatus());

    }

    protected function createFundAccountBankAccount($key = null)
    {
        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $request = $this->buildFundAccountRequest(Type::BANK_ACCOUNT);

        $this->ba->privateAuth($key);

        $content = $this->makeRequestAndGetContent($request);

        $expectedFundAccount = $this->getDefaultFundAccountBankAccountArray();

        $this->assertArraySelectiveEquals($expectedFundAccount, $content);

        return $content;
    }
}
