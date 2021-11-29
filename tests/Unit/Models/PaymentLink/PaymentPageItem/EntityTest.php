<?php

namespace RZP\Tests\Unit\Models\PaymentLink\PaymentPageItem;

use RZP\Models\PaymentLink\PaymentPageItem;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;

class EntityTest extends BaseTest
{
    protected $datahelperPath   = '/PaymentPageItem/Helpers/EntityTestData.php';

    /**
     * @group nocode_ppi_entity
     */
    public function testSetMinPurchase()
    {
        $entity = new PaymentPageItem\Entity();
        $entity->setMinPurchase(10);

        $this->assertEquals($entity->getMinPurchase(), 10);
    }

    /**
     * @group nocode_ppi_entity
     */
    public function testSetMinAmount()
    {
        $entity = new PaymentPageItem\Entity();
        $entity->setMinAmount(10);

        $this->assertEquals($entity->getMinAmount(), 10);
    }

    /**
     * @group nocode_ppi_entity
     */
    public function testSetPublicPlanIdAttribute()
    {
        $entity = new PaymentPageItem\Entity();

        $entity->setAttribute(PaymentPageItem\Entity::PLAN_ID, "123");

        $attr = [];

        $entity->setPublicPlanIdAttribute($attr);

        $this->assertEquals($attr[PaymentPageItem\Entity::PLAN_ID], "plan_123");
    }

    /**
     * @group nocode_ppi_entity
     */
    public function testSetPublicProductConfigAttribute()
    {
        $entity = new PaymentPageItem\Entity();

        $config = '{"a": "b"}';

        $entity->setProductConfig($config);

        $attr = [];

        $entity->setPublicProductConfigAttribute($attr);

        $this->assertEquals($attr[PaymentPageItem\Entity::PRODUCT_CONFIG], json_decode($config));
    }

    /**
     * @dataProvider getData
     * @group        nocode_ppi_entity
     * @param string $config
     * @param null   $key
     * @param null   $value
     */
    public function testGetProductConfig(string $config, $key=null, $value=null)
    {
        $entity = new PaymentPageItem\Entity();

        $entity->setProductConfig($config);

        $this->assertEquals($entity->getProductConfig($key), $value);
    }

    /**
     * @group nocode_ppi_entity
     */
    public function testIsSlotLeft()
    {
        $entity = new PaymentPageItem\Entity();

        $this->assertTrue($entity->isSlotLeft(10));
    }

    /**
     * @group nocode_ppi_entity
     */
    public function testDoesPlanExists()
    {
        $entity = new PaymentPageItem\Entity();

        $entity->setAttribute(PaymentPageItem\Entity::PLAN_ID, "plan_213");

        $this->assertTrue($entity->doesPlanExists());
    }
}
