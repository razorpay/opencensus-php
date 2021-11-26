<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\Item;
use RZP\Models\Base\PublicEntity;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Tests\Traits\PaymentLinkTestTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EntityTest extends BaseTest
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';
    const TEST_PL_ID_2  = '100000000001pl';
    const TEST_PPI_ID   = '10000000000ppi';
    const TEST_PPI_ID_2 = '10000000001ppi';
    const TEST_ORDER_ID = '10000000000ord';

    protected $datahelperPath   = '/Helpers/EntityTestData.php';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . $this->datahelperPath;

        parent::setUp();
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testGetCurrency($currency, $expected)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::CURRENCY    => $currency
        ]);
        $this->assertTrue($expected === $pl->getCurrency());
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testGetDescriptionAndMetaDescription($description, $expected, $meta)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::DESCRIPTION    => $description
        ]);
        $this->assertTrue($expected === $pl->getDescription());
        $this->assertTrue($meta === $pl->getMetaDescription());
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testGetTitle($text, $expected)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::TITLE   => $text
        ]);
        $this->assertTrue($expected === $pl->getTitle());
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testGetTerms($text, $expected)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::TERMS   => $text
        ]);
        $this->assertTrue($expected === $pl->getTerms());
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testIsExpired($status, $reason, $assertBool)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::STATUS          => $status,
            Entity::STATUS_REASON   => $reason
        ]);
        $this->assertTrue($pl->isExpired() === $assertBool);
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testIsCompleted($status, $reason, $assertBool)
    {
        $pl = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::STATUS          => $status,
            Entity::STATUS_REASON   => $reason
        ]);
        $this->assertTrue($pl->isCompleted() === $assertBool);
    }

    /**
     * @dataProvider getData
     * @group nocode_pp_entity
     */
    public function testGetSelectedInputField($udfField)
    {
        $settings = [
            Entity::SELECTED_INPUT_FIELD    => $udfField
        ];

        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);

        $paymentLink->getSettingsAccessor()->upsert($settings)->save();
        $this->assertTrue($udfField === $paymentLink->getSelectedInputField());
    }

    /**
     * @group nocode_pp_entity
     */
    public function testGetAmountToSendSmsOrEmailSingleItem()
    {
        $amount = 5000;
        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID);
        $this->createPaymentPageItems(self::TEST_PL_ID, [[
            PaymentPageItem\Entity::ID   => PublicEntity::generateUniqueId(),
            PaymentPageItem\Entity::ITEM => [
                Item\Entity::AMOUNT => $amount,
            ]
        ]]);
        $this->assertTrue($amount === $paymentLink->getAmountToSendSmsOrEmail());
    }

    /**
     * @group nocode_pp_entity
     */
    public function testGetAmountToSendSmsOrEmailMultipleItem()
    {
        $paymentLink = $this->createPaymentLinkWithMultipleItem();
        $this->assertNull($paymentLink->getAmountToSendSmsOrEmail());
    }

    /**
     * @group nocode_pp_entity
     */
    public function testIncrementTimesPaidBy()
    {
        $paymentLink = $this->createPaymentLink(self::TEST_PL_ID, [
            Entity::TIMES_PAID  => 9
        ]);

        $paymentLink->incrementTimesPaidBy(1);
        $this->assertEquals(10, $paymentLink->getTimesPaid());

        $paymentLink->incrementTimesPaidBy(-1);
        $this->assertEquals(9, $paymentLink->getTimesPaid());
    }

    /**
     * @group nocode_pp_entity
     */
    public function testSetJsonAttributeId()
    {
        $paymentLink = $this->createPaymentLink();

        $paymentLink->setUdfJsonschemaId("someID");
        $this->assertEquals("someID", $paymentLink->getUdfJsonschemaId());
    }

    /**
     * @group nocode_pp_entity
     */
    public function testGetCapturedPaymentsCount()
    {
        $this->ba->proxyAuth();

        $data       = $this->createPaymentLinkAndOrderForThat();
        $pl         = $data['payment_link'];
        $order      = $data['payment_link_order']['order'];

        $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $order);

        $count  = 1;
        while ($count < 6)
        {
            $this->assertEquals($count, $pl->getCapturedPaymentsCount());

            $orderRes   = $this->startTest();
            $orderId    = $order->stripDefaultSign($orderRes['order']['id']);
            $this->makePaymentForPaymentLinkWithOrderAndAssert($pl, $this->getDbEntityById('order', $orderId));

            $count++;
        }

        $this->assertEquals($count, $pl->getCapturedPaymentsCount());
    }
}
