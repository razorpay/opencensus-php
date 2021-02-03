<?php

namespace RZP\Tests\Functional\Helpers\FundAccount;

use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;
use RZP\Services\RazorXClient;

trait FundAccountValidationTrait
{
    protected function createValidationWithFundAccountEntity(): array
    {
        $this->enableRazorXTreatmentForRazorX();

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);

        // Queue will be processed by now.
        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals(1, $fav['attempts']);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $this->assertEquals(null, $bankAccount['entity_id']);
        $this->assertEquals(null, $bankAccount['type']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        // Fee Bearer is always Platform for Fund Account Validation
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        return $response;
    }


    protected function createValidationWithFundAccountEntityFromAdmin(): array
    {
        $this->enableRazorXTreatmentForRazorX();

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $balanceEntity = $this->getLastEntity('balance', true);

        // Queue will be processed by now.
        $fav = $this->getLastEntity('fund_account_validation', true);

        //asserting on primary balance
        $this->assertEquals("primary", $balanceEntity['type']);

        $this->assertEquals(1, $fav['attempts']);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);
        $this->assertEquals('active', $fav['results']['account_status']);
        // Fee and tax will be calculated at the time fund account validation is created.
        $this->assertEquals(354, $fav['fees']);
        $this->assertEquals(54, $fav['tax']);

        $this->assertEquals(null, $bankAccount['entity_id']);
        $this->assertEquals(null, $bankAccount['type']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);
        $this->assertNotNull($fta['narration']);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals($fav['id'], $txn['entity_id']);
        $this->assertEquals('fund_account_validation', $txn['type']);
        // Fee Bearer is always Platform for Fund Account Validation
        $this->assertEquals('platform', $txn['fee_bearer']);
        $this->assertEquals(false, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        return $response;
    }

    protected function getDefaultFAVFundAccountArray(string $fundAccountId)
    {
        return [
            FundAccount::ACCOUNT_NUMBER => '2224440041626905',
            Validation::FUND_ACCOUNT => [
                FundAccount::ID => $fundAccountId,
            ],
            Validation::AMOUNT       => 100,
            Validation::CURRENCY     => 'INR',
            Validation::NOTES        => [],
        ];
    }

    protected function buildFAVForFundAccountRequest(string $fundAccountId)
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/fund_accounts/validations',
            'content' => $this->getDefaultFAVFundAccountArray($fundAccountId)
        ];

        return $request;
    }

    protected function createFAVBankAccount()
    {
        $this->createFAVBankingPricingPlan();

        $fundAccount = $this->createFundAccountBankAccount();

        $request = $this->buildFAVForFundAccountRequest($fundAccount['id']);

        $this->ba->privateAuth();

        $this->makeRequestAndGetContent($request);
    }

    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    protected function enableRazorXTreatmentForStork()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment', 'getCachedTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

        $this->app->razorx->method('getCachedTreatment')
                          ->willReturn('on');
    }

    protected function updateFtaAndSource($payoutId, $status, $utr = '928337183',$bankStatusCode,$internalError)
    {
        $this->ba->appAuth();

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => [
                'bank_processed_time' => '2019-12-04 15:51:21',
                'bank_status_code'    => $bankStatusCode,
                'extra_info'          => [
                    'beneficiary_name' => null,
                    'cms_ref_no'       => 'd10ce8e4167f11eab1750a0047330000',
                    'internal_error'   => $internalError
                ],
                'failure_reason'      => '',
                'fund_transfer_id'    => 1236890,
                'mode'                => 'IMPS',
                'narration'           => 'Kissht FastCash Disbursal',
                'remarks'             => 'Check the status by calling getStatus API.',
                'source_id'           => $payoutId,
                'source_type'         => 'fund_account_validation',
                'status'              => $status,
                'utr'                 => $utr,
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }
}
