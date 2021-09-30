<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Models\Item;
use RZP\Models\Currency\Currency;
use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\PaymentLink\Status;
use RZP\Models\PaymentLink\StatusReason;
use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Tests\Traits\PaymentLinkTestTrait;

class EntityTest extends TestCase
{
    use PaymentLinkTestTrait;

    const TEST_PL_ID    = '100000000000pl';

    /**
     * @dataProvider getCurrencyDataProvider
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
     * @dataProvider getDescriptionAndMetaDescriptionDataProvider
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
     * @dataProvider getGeneralDataProvider
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
     * @dataProvider getGeneralDataProvider
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
     * @dataProvider isExpiredDataProvider
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
     * @dataProvider isCompletedDataProvider
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
     * @dataProvider getSelectedInputFieldDataProvider
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

    public function getSelectedInputFieldDataProvider(): array
    {
        return [
            ["phone"],
            ["email"]
        ];
    }

    public function isCompletedDataProvider(): array
    {
        return [
            [Status::INACTIVE, StatusReason::EXPIRED, false],
            [Status::INACTIVE, StatusReason::COMPLETED, true],
            [Status::ACTIVE, StatusReason::COMPLETED, false],
            [Status::ACTIVE, StatusReason::EXPIRED, false],
            ["asdad", "asdad", false],
        ];
    }

    public function isExpiredDataProvider(): array
    {
        return [
            [Status::INACTIVE, StatusReason::EXPIRED, true],
            [Status::INACTIVE, StatusReason::COMPLETED, false],
            [Status::ACTIVE, StatusReason::COMPLETED, false],
            [Status::ACTIVE, StatusReason::EXPIRED, false],
            ["asdad", "asdad", false],
        ];
    }

    public function getGeneralDataProvider(): array
    {
        return [
            ["Some Text", "Some Text"],
            [null, null],
        ];
    }

    public function getDescriptionAndMetaDescriptionDataProvider(): array
    {
        $desc = "Lorem ipsum dolor sit, amet consectetur adipisicing elit. Suscipit quibusdam laborum distinctio officiis quos dolor placeat. Reiciendis modi ut delectus.";
        $meta = "Meta Description";
        $json = '{"metaText": "'.$meta.'"}';
        return [
            [$desc, $desc, $desc],
            [null, null, null],
            [$json, $json, $meta],
            ['{"a":"b"}', '{"a":"b"}', '{"a":"b"}'],
        ];
    }

    public function getCurrencyDataProvider(): array
    {
        return [
            [Currency::INR, Currency::INR],
            [null, Currency::INR],
            [Currency::AED, Currency::AED]
        ];
    }
}
