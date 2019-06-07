<?php

namespace RZP\Tests\Functional\Helpers\FundAccount;

use Closure;
use Mockery;

use RZP\Models\Merchant\Webhook;

trait FundAccountValidationTrait
{
    protected function createValidationWithFundAccountEntity(): array
    {
        $response = $this->startTest();

        $bankAccount = $this->getLastEntity('bank_account', true);
        $fundAccount = $this->getLastEntity('fund_account', true);

        // Queue will be processed by now.
        $fav = $this->getLastEntity('fund_account_validation', true);
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
        $this->assertEquals(true, $txn['settled']);
        $this->assertEquals(354, $txn['fee']);
        $this->assertEquals(354, $txn['mdr']);
        $this->assertEquals(54, $txn['tax']);
        $this->assertEquals($fav['amount'], $txn['amount']);

        return $response;
    }

    protected function mockInfernoFire(Closure $closure)
    {
        $inferno = Mockery::mock(Webhook\Inferno::class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->once()
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }
}
