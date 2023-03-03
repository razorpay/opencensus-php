<?php

namespace RZP\Tests\Functional\CustomerToken;

use App;
use Mockery;
use Carbon\Carbon;
use \WpOrg\Requests\Response;
use RZP\Constants\Entity;
use RZP\Models\Card\Network;
use RZP\Models\Card\Vault;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\Merchant\Account;
use RZP\Services\CardVault;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Services\Mock\DataLakePresto;
use RZP\Services\TerminalsService;
use RZP\Tests\Functional\Helpers\TerminalTrait;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Feature;

class TokenHqPricingTest extends TestCase
{
    use PaymentTrait;
    use TestsWebhookEvents;
    use TerminalTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TokenHqPricingTestData.php';
        parent::setUp();
    }

    public function testTokenHqCron(): void
    {

        $prestoService = \Mockery::mock(DataLakePresto::class, [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('datalake.presto', $prestoService);

        $prestoService->shouldReceive('getDataFromDataLake')
            ->andReturnUsing(function (string $query)
            {
                return [
                        [
                            "aggregated_data_id" => "Qwert1234",
                            'merchant_id' => 'CnomihrtrtECsY',
                            'type' => 'request_token_create',
                            'count' => 4,
                            'fee_model'=> 'prepaid',
                            'created_date' => '2026-11-01'
                        ],
                    ];
            });


        $testData = $this->testData['testTokenHqCron'];

        $this->ba->cronAuth();

        $this->startTest($testData);

        $batch = $this->getDbLastEntity('batch');

        $this->assertEquals('processed',$batch['status']);

        $this->assertEquals('token_hq_charge',$batch['type']);

    }
}
