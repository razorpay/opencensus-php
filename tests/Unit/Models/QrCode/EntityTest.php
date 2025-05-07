<?php
namespace RZP\Tests\Unit\Models\QrCode;

use Mockery;
use RZP\Models\Customer\Entity;
use RZP\Models\QrCode;
use RZP\Tests\Functional\TestCase;

class EntityTest extends TestCase
{
    protected $originalQrString = '000201010211021652873468239864230415428734682398642061662873468239864230827YESB0CMSNOC222333004882700126300010A0000005240112random@icici27350010A0000005240117RZPFcpxLdbmuCnhkL5204539953033565802IN5903aut6009BANGALORE610656003062270514FcpxLdbmuCnhkL0705abcde63047209';
    protected $tokenizedQrString = '0002010102110224NTI4NzM0NjgyMzk4NjQyMw==0420NDI4NzM0NjgyMzk4NjQy0624NjI4NzM0NjgyMzk4NjQyMw==0827YESB0CMSNOC222333004882700126300010A0000005240112random@icici27350010A0000005240117RZPFcpxLdbmuCnhkL5204539953033565802IN5903aut6009BANGALORE610656003062270514FcpxLdbmuCnhkL0705abcde63047209';

    function testGetQrStringWithTokenizedMpans()
    {
        $tokenizedStringFromMethod = (new QrCode\Entity())->getQrStringWithTokenizedMpans($this->originalQrString);

        $this->assertEquals($tokenizedStringFromMethod, $this->tokenizedQrString);

        $twiceTokenizedStringFromMethod = (new QrCode\Entity())->getQrStringWithTokenizedMpans($tokenizedStringFromMethod);

        $this->assertEquals($twiceTokenizedStringFromMethod, $this->tokenizedQrString);
    }

    function testGetQrStringWithDetokenizedMpans()
    {

        $detokenizedStringFromMethod = (new QrCode\Entity())::getQrStringWithDetokenizedMpans($this->tokenizedQrString);

        $this->assertEquals($detokenizedStringFromMethod, $this->originalQrString);

        $twiceDetokenizedString = (new QrCode\Entity())::getQrStringWithDetokenizedMpans($detokenizedStringFromMethod);

        // tests that detokenizing a string which already have detokenized mpans should be same as original string
        $this->assertEquals($twiceDetokenizedString, $this->originalQrString);
    }

    public function testCustomerLazyRead()
    {
        $mockCustomerRepo = Mockery::mock('\RZP\Models\Customer\Repository', [$this->app]);
        $mockCustomerEntity = new Entity();
        $mockCustomerId = '100000customer';
        $mockCustomerEntity->fill([
            'id' => $mockCustomerId,
            'name' => 'RzpCustomerName',
            'email' => 'RzpCustomer@email.com',
            'contact' => '9999999999',
            'merchant_id' => '100000Razorpay'
        ]);
        $mockCustomerRepo->shouldReceive('find')->with($mockCustomerId)->andReturn($mockCustomerEntity);

        $mockRepoManager = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);
        $this->app->instance('repo', $mockRepoManager);
        $mockRepoManager->shouldReceive('driver')->with('customer')->andReturn($mockCustomerRepo);


        $qrCode = new QrCode\Entity();
        $qrCode->setAttribute('customer_id', $mockCustomerId);
        $this->assertEquals($mockCustomerEntity, $qrCode->customer);
    }
}
