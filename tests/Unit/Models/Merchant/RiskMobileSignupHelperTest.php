<?php

namespace Unit\Models\Merchant;

use Mockery;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\RiskMobileSignupHelper;

class RiskMobileSignupHelperTest extends TestCase
{
    public function testCreateFdTicket()
    {
        $merchant = $this->fixtures->create('merchant', [
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => $merchant->getId(),
            'contact_mobile'    => '1234567124',
        ]);

        $this->app['config']->set('applications.freshdesk.mock', true);

        $postTicketMock = Mockery::mock('RZP\Services\Mock\FreshdeskTicketClient', [$this->app])->makePartial();

        $postTicketMock->shouldReceive('postTicket')
                       ->with($this->callback(
                            function ($input, $urlKey) {
                                return ($input['description'] === '<p>Random html paragraph tag</p><h1>Random html h1 tag</h1>');
                            })
                        );

        (new RiskMobileSignupHelper())->createFdTicket(
            $merchant,
            'emails.merchant.activation',
            'Subject',
            [
                'merchant'  => [
                    'name'  => 'TName',
                    'org'   => [
                        'business_name'  =>  'BName',
                    ],
                ]
            ],
            [],
            '<p>Random html paragraph tag</p><h1>Random html h1 tag</h1>'
        );
    }
}
