<?php

namespace Functional\Pagination;

use Carbon\Carbon;

use RZP\Constants\Timezone;
use RZP\Models\Admin\ConfigKey;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class PaginationTest extends TestCase
{
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/PaginationTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    public function testSetPaginationParameters()
    {
        $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $startTimestamp = Carbon::now();

        $endTimestamp = Carbon::now()->addDay(1)->getTimestamp();

        $this->ba->adminAuth();

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content'][ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE]['start_time'] = $startTimestamp->getTimestamp();

        $request['content'][ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE]['end_time'] = $endTimestamp;

        $content = $this->startTest();

        $response = $content[0]['new_value'];

        $this->assertEquals(array_keys($request['content'][ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE]), array_keys($response));
    }

    public function testTrimMerchantData()
    {
        $this->testSetPaginationParameters();

        $razorx = \Mockery::mock(RazorXClient::class)->makePartial();

        $this->app->instance('razorx', $razorx);

        $razorx->shouldReceive('getTreatment')
            ->andReturnUsing(function (string $id, string $featureFlag, string $mode)
            {
                if($featureFlag === RazorxTreatment::BLOCKED_MERCHANT_FOR_TRIM_SPACE ||
                    $featureFlag === RazorxTreatment::TRIM_MIGRATION_IN_PROGRESS)
                {
                    return 'on';
                }
                return 'control';
            });

        $createdMerchant = $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $fund_account = $this->fixtures->create('fund_account:bank_account', ['id' => '100000000000fa']);

        $this->fixtures->edit('bank_account',
            $fund_account['account_id'],
            [
                'merchant_id'      => $createdMerchant['id'],
                'beneficiary_name' => 'name   ',
                'account_number'   => "11111111\n "
            ]
        );

        $this->fixtures->edit('fund_account',
            $fund_account['id'],
            [
                'merchant_id' => $createdMerchant['id']
            ]
        );

        $payout = $this->fixtures->create('payout');

        $customPurpose = [
            'purpose'      => 'custom_purpose  ',
            'purpose_type' => 'refund'
        ];

        $requestPurpose =  [
            'content' => $customPurpose,
            'url'     => '/payouts/purposes',
            'method'  => 'POST'
        ];

        $this->ba->privateAuth();

        $payoutPurposeBeforeUpdate = $this->makeRequestAndGetContent($requestPurpose);

        $customType = [
            'type' => 'custom_type  '
        ];

        $requestContactType =  [
            'content' => $customType,
            'url'     => '/contacts/types',
            'method'  => 'POST'
        ];

        $contactTypeBeforeUpdate = $this->makeRequestAndGetContent($requestContactType);

        $this->fixtures->edit('payout',
            $payout['id'],
            [
                'merchant_id' => $createdMerchant['id'],
                'purpose'     => $customPurpose['purpose']
            ]
        );

        $this->fixtures->create('contact',
            [
                'id'          => '1000011contact',
                'contact'     => '8888888888',
                'email'       => '',
                'name'        => 'test user   ',
                'type'        => $customType['type'],
                'merchant_id' => $createdMerchant['id']
            ]);

        $bank_account = $this->getLastEntity('bank_account', true);

        $payout = $this->getLastEntity('payout', true);

        $contact = $this->getLastEntity('contact', true);

        $this->fixtures->create('contact',
            [
                'id'          => '1000012contact',
                'contact'     => '8888888888',
                'email'       => '',
                'name'        => 'test user   ',
                'type'        => null,
                'merchant_id' => $createdMerchant['id']
            ]);

        $contact2 = $this->getLastEntity('contact', true);

        $this->testData[__FUNCTION__]['request']['content']['merchant_ids'] = [$createdMerchant['id']];

        $this->ba->cronAuth();

        $this->startTest();

        $bankAccountUpdated = $this->getLastEntity('bank_account', true);

        $payoutUpdated = $this->getLastEntity('payout', true);

        $contactUpdated = $this->getDbEntityById('contact', $contact['id']);

        $contactUpdated2 = $this->getDbEntityById('contact', $contact2['id']);

        $this->ba->privateAuth();

        $requestPurpose['method'] = 'GET';

        $updatedPurpose = $this->makeRequestAndGetContent($requestPurpose);

        $requestContactType['method'] = 'GET';

        $updateTypes = $this->makeRequestAndGetContent($requestContactType);

        $this->assertFalse(in_array($customPurpose, $updatedPurpose['items'], false));

        $customPurpose['purpose'] = trim($customPurpose['purpose']);

        $this->assertTrue(in_array($customPurpose, $updatedPurpose['items'], true));

        $this->assertFalse(in_array($customType, $updateTypes['items'], false));

        $customType['type'] = trim($customType['type']);

        $this->assertTrue(in_array($customType, $updateTypes['items'], true));

        $this->assertNotEquals($payout['purpose'], $payoutUpdated['purpose']);
        $this->assertEquals(trim($payout['purpose']), $payoutUpdated['purpose']);

        $this->assertNotEquals($bank_account['beneficiary_name'], $bankAccountUpdated['beneficiary_name']);
        $this->assertEquals(trim($bank_account['beneficiary_name']), $bankAccountUpdated['beneficiary_name']);

        $this->assertNotEquals($bank_account['account_number'], $bankAccountUpdated['account_number']);
        $this->assertEquals(trim($bank_account['account_number']), $bankAccountUpdated['account_number']);

        $this->assertNotEquals($contact['name'], $contactUpdated['name']);
        $this->assertEquals(trim($contact['name']), $contactUpdated['name']);

        $this->assertNotEquals($contact2['name'], $contactUpdated2['name']);
        $this->assertEquals(trim($contact2['name']), $contactUpdated2['name']);

        $this->assertNotEquals($contact['type'], $contactUpdated['type']);
        $this->assertEquals(trim($contact['type']), $contactUpdated['type']);
    }

    public function testTrimMerchantDataAfterStartTimeReachEndTime()
    {
        $this->testTrimMerchantData();

        $this->ba->cronAuth();

        $this->startTest();
    }

    public function testSetPaginationParametersStartTimeGreaterThanEndTime()
    {
        $this->getDbEntity('merchant', ['id' => '10000000000000']);

        $startTimestamp = Carbon::now();

        $endTimestamp = Carbon::now()->addDay(-1)->getTimestamp();

        $this->ba->adminAuth();

        $request = $this->testData['testSetPaginationParameters']['request'];

        $request['content'][ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE]['start_time'] = $startTimestamp->getTimestamp();

        $request['content'][ConfigKey::PAGINATION_ATTRIBUTES_FOR_TRIM_SPACE]['end_time'] = $endTimestamp;

        $this->makeRequestAndGetContent($request);

        $this->ba->cronAuth();

        $this->startTest();
    }
}
