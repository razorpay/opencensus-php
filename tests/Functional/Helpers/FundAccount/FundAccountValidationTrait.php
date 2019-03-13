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

        $fav = $this->getLastEntity('fund_account_validation', true);
        $this->assertEquals('completed', $fav['status']);
        $this->assertEquals($fundAccount['id'], 'fa_'.$fav['fund_account_id']);

        $fta = $this->getLastEntity('fund_transfer_attempt', true);
        $this->assertEquals('penny_testing', $fta['purpose']);
        $this->assertEquals($fav['id'], $fta['source']);
        $this->assertEquals($bankAccount['id'], 'ba_'.$fta['bank_account_id']);

        $this->assertNotNull($fta['narration']);

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
