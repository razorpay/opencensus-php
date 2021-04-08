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

        $this->fixtures->merchant->addFeatures([Features::FAV_FTA_DPRCN_FWD]);

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
