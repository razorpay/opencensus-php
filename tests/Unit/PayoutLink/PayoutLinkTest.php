<?php

namespace RZP\Tests\Unit\PayoutLink;

use RZP\Tests\Functional\TestCase;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class PayoutLinkTest extends TestCase
{
    use TestsBusinessBanking;

    protected function setUp(): void
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
            'payout_link_id'    =>      $payoutLink->getId(),
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
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

    public function testPayoutLinkTrimmedDescription()
    {
        $description = "__@#ABC@123@@testing_qwer__tyuiopasdfghjkl___";

        $trimmedDescriptionExpected = "ABC 123 testing qwer tyuiopasd";

        $payoutLink = new PayoutLinkEntity();

        $payoutLink->setDescription($description);

        $trimmedDescriptionActual = $payoutLink->getTrimmedDescription();

        $this->assertEquals($trimmedDescriptionExpected, $trimmedDescriptionActual);
    }
}
