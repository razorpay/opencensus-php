<?php

namespace RZP\Tests\Unit\Models\Key;

use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Key\Entity as KeyEntity;

class EntityTest extends TestCase
{
    private ?KeyEntity $key = null;
    private ?MerchantEntity $merchantEntity = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('key.keys_with_country_codes_whitelist', "SG");
        $this->setUpEntities();
    }

    protected function setUpEntities(){
        $this->key = new KeyEntity();
        $this->key->setAttribute('id','1X4hRFHFx4UiXt');
        $this->merchantEntity = new MerchantEntity();
        $this->key->merchant()->associate($this->merchantEntity);
        $this->key->build();
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->onlyMethods(['getMode'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }

    public function testStripSign(){
        $ba = $this->mockBasicAuth();
        $ba->expects($this->once())->method('getMode')->willReturn('test');
        $id = 'rzp_test_1X4hRFHFx4UiXt';
        KeyEntity::stripSign($id);
        $this->assertEquals('1X4hRFHFx4UiXt', $id);
    }

    public function testStripSignWithCountry(){
        $ba = $this->mockBasicAuth();
        $ba->expects($this->once())->method('getMode')->willReturn('test');
        $id = 'rzp_test_sg_1X4hRFHFx4UiXt';
        KeyEntity::stripSign($id);
        $this->assertEquals('1X4hRFHFx4UiXt', $id);
    }

    public function testGetPublicId(){
        $ba = $this->mockBasicAuth();
        $ba->expects($this->once())->method('getMode')->willReturn('test');
        $publicId =$this->key->getPublicId();
        $this->assertEquals('rzp_test_1X4hRFHFx4UiXt', $publicId);
    }

    public function testGetPublicIdWithCountry(){
        $ba = $this->mockBasicAuth();
        $ba->expects($this->once())->method('getMode')->willReturn('test');
        $this->merchantEntity->setRawAttributes([
            'country_code' => 'SG'
        ]);
        $publicId =$this->key->getPublicId();
        $this->assertEquals('rzp_test_sg_1X4hRFHFx4UiXt', $publicId);
    }

    public function testGetPublicKey(){
        $this->merchantEntity->setRawAttributes([
            'country_code' => 'IN'
        ]);
        $publicId =$this->key->getPublicKey('test');
        $this->assertEquals('rzp_test_1X4hRFHFx4UiXt', $publicId);
    }

    public function testGetPublicKeyWithCountry(){
        $this->merchantEntity->setRawAttributes([
            'country_code' => 'SG'
        ]);
        $publicId =$this->key->getPublicKey('test');
        $this->assertEquals('rzp_test_sg_1X4hRFHFx4UiXt', $publicId);
    }

}
