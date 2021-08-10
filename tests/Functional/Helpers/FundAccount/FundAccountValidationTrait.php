<?php

namespace RZP\Tests\Functional\Helpers\FundAccount;

use RZP\Jobs\FavQueueForFTS;
use RZP\Services\RazorXClient;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;
use RZP\Models\FundAccount\Validation\Service as FavService;

trait FundAccountValidationTrait
{
    protected function createValidationWithFundAccountEntity(): array
    {
        $this->enableRazorXTreatmentForRazorX();

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

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

    /**
     * It simulates the webhook that updates the FAV to a new state, and triggers the relevant flow.
     *
     * Pass $status as 'COMPLETED'/'INITIATED' as required, if you want the fav status to not be 'FAILED' by default.
     *
     * Any key-values of the webhook body that you want to be customised are to be passed in the $attributes array.
     * Receipts are passed in $attributes array as well. E.g. ['receipt' => 'SAMPLE_RECEIPT']. Note that receipts will NOT
     * be handled if bank_status_code is passed in $attributes array.
     *
     * @param string $favId
     * @param string $status
     * @param array  $attributes
     * @param string $mode
     */
    protected function triggerFlowToUpdateFavWithNewState(string $favId,
                                                          $status = 'FAILED',
                                                          $attributes = [],
                                                          $mode = 'test')
    {
        // Remove sign from the entity ID
        if (strpos($favId, 'fav_') !== false)
        {
            $favId = substr($favId, 4);
        }

        // Handle Receipts.
        if ((array_key_exists('receipt', $attributes) === true) and
            (array_key_exists('bank_status_code', $attributes) === false))
        {
            $this->processReceipt($attributes);
        }

        // Create test input for webhook based update of FAV, simulates FTS webhook
        $input = [
            'bank_processed_time' => '',
            'bank_status_code'    => ($status === 'COMPLETED') ? 'SUCCESS' : 'FAILED',
            'channel'             => 'ICICI',
            'extra_info'          => [
                'beneficiary_name' => ($status === 'COMPLETED') ? 'Razorpay Test' : '',
                'cms_ref_no'       => '',
                'ponum'            => '',
                'internal_error'   => false,
            ],
            'failure_reason'      => '',
            'fund_transfer_id'    => rand(),
            'gateway_error_code'  => ($status === 'COMPLETED') ? '' : '36',
            'gateway_ref_no'      => str_shuffle('H4nn3D3B12test'),
            'mode'                => 'IMPS',
            'source_type'         => 'fund_account_validation',
            'source_id'           => $favId,
            'status'              => ($status === 'COMPLETED') ? 'PROCESSED' : $status,
            'remarks'             => ($status === 'COMPLETED') ? 'Transaction Successful' : 'Invalid Bene/Mobile number',
            'utr'                 => str_shuffle('111917301337'),
            'source_account_id'   => 1111111,
            'bank_account_type'   => 'current',
        ];

        // Merge extra_info from attributes to input first
        if (array_key_exists('extra_info', $attributes))
        {
            $input['extra_info'] = array_merge($input['extra_info'], $attributes['extra_info']);
            unset($attributes['extra_info']);
        }

        // Handle INITIATED status separately
        if ($status === 'INITIATED')
        {
            $attributes = [
                'bank_status_code'   => '',
                'gateway_error_code' => '',
                'status'             => 'INITIATED',
                'remarks'            => '',
                'utr'                => '',
            ];
        }

        // Create the webhook payload as per defaults and the custom attributes array passed to the function
        $input = array_merge($input, $attributes);

        // Make the webhook calls
        $this->ba->ftsAuth($mode);

        $request = [
            'method'  => 'POST',
            'url'     =>  '/update_fts_fund_transfer',
            'content' => $input,
        ];

        $this->makeRequestAndGetContent($request);
    }

    /**
     * This function is used to resolve any receipt into the appropriate bank_status_code.
     * Check out app/Models/FundTransfer/Yesbank/Request/Status.php::mockGenerateFailedResponse() for reference
     *
     * @param array $attributes
     */
    private function processReceipt(array &$attributes)
    {
        $bankStatusCode = null;

        if ($attributes['receipt'] === 'failed_resp_beneficiary_details_invalid')
        {
            $bankStatusCode = 'INVALID_BENEFICIARY_DETAILS';
        }
        else
        {
            $bankStatusCode = 'FAILED';
        }

        if(is_null($bankStatusCode) === false)
        {
            $attributes['bank_status_code'] = $bankStatusCode;
        }

        unset($attributes['receipt']);
    }


    protected function createValidationWithFundAccountEntityFromAdmin(): array
    {
        $this->enableRazorXTreatmentForRazorX();

        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);
        $balanceEntity = $this->getLastEntity('balance', true);

        $this->triggerFlowToUpdateFavWithNewState($response['id'], 'COMPLETED');

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

    /**
     * @deprecated Deprecated since FTA was deprecated from FAV flow.
     *
     * @see triggerFlowToUpdateFavWithNewState() instead.
     *
     * @param        $payoutId
     * @param        $status
     * @param string $utr
     * @param        $bankStatusCode
     * @param        $internalError
     */
    protected function updateFtaAndSource($payoutId, $status, $utr = '928337183',$bankStatusCode,$internalError)
    {
        $this->ba->ftsAuth();

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
