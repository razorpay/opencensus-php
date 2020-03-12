<?php

namespace RZP\Tests\Unit\PayoutLink;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkTest extends TestCase
{
    use TestsBusinessBanking;

    public function setUp()
    {
        parent::setUp();

        $this->setUpMerchantForBusinessBanking(true, 10000000);
    }

    public function testPayoutFunction()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email' => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link' ,
            [
                'contact_id' => $contact->getId(),
                'balance_id' => $this->bankingBalance->getId()
            ]);

        $payout = $this->fixtures->create('payout' , [
            'payout_link_id' => $payoutLink->getId()
        ]);

        $this->assertEquals($payoutLink->payout()->getId(), $payout->getId());
    }

    public function testPayoutFunctionReturnsNull()
    {
        $contact = $this->fixtures->create('contact',
                                           [
                                               'id'      => '1000011contact',
                                               'contact' => '8888888888',
                                               'email' => '',
                                               'name'    => 'test user'
                                           ]);

        $payoutLink = $this->fixtures->create('payout_link' ,
                                              [
                                                  'contact_id' => $contact->getId(),
                                                  'balance_id' => $this->bankingBalance->getId()
                                              ]);

        $this->assertNull($payoutLink->payout());
    }
}
