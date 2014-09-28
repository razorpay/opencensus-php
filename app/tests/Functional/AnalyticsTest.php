<?php
namespace Tests\Functional;

use Models;
use URL;

class AnalyticsTest extends TestCase
{   
    protected $merchant;

    protected $merchant_details;

    public function setUp()
    {   
        parent::setUp();

        $this->merchant = $this->createEntity('merchant');

        $this->merchant_details = $this->createEntity('merchant_details', array('merchant_id'=>$this->merchant->id));
    }

    /**
     * Tests payment post
     */
    public function testPostPayment()
    {
        $resources = ['payment', 'refund', 'settlement'];

        foreach($resources as $resource)
        {
            for($i=0; $i<5; $i++)
            {
                $data = array(
                    'amount'        => 500,
                    'created_at'    => time(),
                    'updated_at'    => time(),
                    'merchant_id'   => $this->merchant->id
                );

                $response = $this->call('POST', '/test/transactions/'.$resource, $data);  

                $content = $response->getContent();

                $this->assertJson($content);
                
                $obj = json_decode($content);

                $this->assertTrue($obj->status);        
            }
        }
    }
}