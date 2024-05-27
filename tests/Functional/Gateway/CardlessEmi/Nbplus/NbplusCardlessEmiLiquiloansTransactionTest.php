<?php

namespace RZP\Tests\Functional\Gateway\CardlessEmi\Nbplus;

use RZP\Constants\Entity;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Payment\Entity as Payment;
use RZP\Services\NbPlus as NbPlusPaymentService;
use RZP\Tests\Functional\Payment\NbplusPaymentServiceCardlessEmiTest;

/* Temporarily Disabling Liquiloans Tests
class NbplusCardlessEmiLiquiloansTransactionTest extends NbplusPaymentServiceCardlessEmiTest
{
   protected function setUp(): void
   {
       parent::setUp();

       $this->provider = 'liquiloans';

       $this->payment = $this->getDefaultCardlessEmiPaymentArray($this->provider);

       $this->terminal = $this->fixtures->create('terminal:shared_cardless_emi_liquiloans_terminal');

   }


   public function testLiquiloansTransaction()
   {
       $this->doAuthAndCapturePayment($this->payment);

       $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

       $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
       $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

       $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

       $this->assertEquals(1180, $transactionEntity['mdr']);
       $this->assertEquals(1180, $transactionEntity['fee']);
       $this->assertEquals(180, $transactionEntity['tax']);
       $this->assertEquals(48820, $transactionEntity['credit']);

   }

   public function testLiquiloansSourcedByMerchantTransaction()
   {
       $this->fixtures->merchant->addFeatures(['liquiloans_direct_fee']);

       $this->doAuthAndCapturePayment($this->payment);

       $payment = $this->getDbLastEntityToArray(Entity::PAYMENT);

       $this->assertEquals($payment[Payment::CPS_ROUTE], Payment::NB_PLUS_SERVICE);
       $this->assertEquals($payment[Payment::STATUS], Payment::CAPTURED);

       $transactionEntity = $this->getDbLastEntity(Entity::TRANSACTION);

       $this->assertEquals(1180, $transactionEntity['mdr']);
       $this->assertEquals(1180, $transactionEntity['fee']);
       $this->assertEquals(180, $transactionEntity['tax']);

       $this->assertEquals(43820, $transactionEntity['credit']);
   }


}
*/
