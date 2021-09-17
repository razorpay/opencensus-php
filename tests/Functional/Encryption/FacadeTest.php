<?php

namespace RZP\Tests\Functional\Encryption;

use Crypt;
use RZP\Models\Terminal;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use Illuminate\Encryption\Encrypter;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Entity as MerchantEntity;

class FacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testEncryptionDecryptionRzpDefaultOrg()
    {
        $terminal = $this->fixtures->create('terminal', []);

        $dataToEncrypt = 'somerandomdata';

        $encryptedData = Crypt::encrypt($dataToEncrypt, true,  $terminal);

        $decryptedData = Crypt::decrypt($encryptedData, true, $terminal);

        $this->assertEquals($dataToEncrypt, $decryptedData);
    }

    public function testEncryptionDecryptionAxisOrg()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
        ->setConstructorArgs([$this->app])
        ->setMethods(['getTreatment'])
        ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
        ->will($this->returnCallback(
            function ($actionId, $feature, $mode)
            {
                return 'on';
            }) );
        
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = MerchantEntity::AXIS_ORG_ID; // axis orgId

        $this->fixtures->create('org', ['id' => $orgId]);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
        ]);

        $terminal = $this->fixtures->create('terminal', ['merchant_id' => $merchantId, 'org_id' => $orgId]);

        $dataToEncrypt = 'somerandomdata';

        $encryptedData = Crypt::encrypt($dataToEncrypt, true, $terminal);

        $decryptedData = Crypt::decrypt($encryptedData, true, $terminal);

        $this->assertEquals($dataToEncrypt, $decryptedData);

        // To reassure, that actually axis key got used
        $orgKey = '5dlTd5lQhN56CkSrnyrRBtRMsXS9exWS'; // ENCRYPTION_KEY_AXIS
        $newEncrypter = new Encrypter($orgKey, 'AES-256-CBC');
        $decryptedData2 = $newEncrypter->decrypt($encryptedData, true);
        $this->assertEquals($decryptedData, $decryptedData2);
    }

    // Test decryption if an entity's data is already encrpyted by default key
    // To replicate the case when we try to decrypt an entity's data which was created before this code was deployed (therefore encryption done using default key)
    public function testDecryptionAfterEncryptedWithDefaultKey()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
        ->setConstructorArgs([$this->app])
        ->setMethods(['getTreatment'])
        ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
        ->will($this->returnCallback(
            function ($actionId, $feature, $mode)
            {
                return 'on';
            }) );
        
        $terminal = $this->fixtures->create('terminal', []);

        $dataToEncrypt = 'somerandomdata';

        $encryptedData = Crypt::encrypt($dataToEncrypt, true, $terminal);

        // Now, we use terminal having org_id as axis for decryption
        $merchantId = '1cXSLlUU8V9sXl';
        $orgId      = MerchantEntity::AXIS_ORG_ID; // axis orgId

        $this->fixtures->create('org', ['id' => $orgId]);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id' => $orgId,
        ]);

        $terminal2 = $this->fixtures->create('terminal', ['merchant_id' => $merchantId, 'org_id' => $orgId]);

        $decryptedData = Crypt::decrypt($encryptedData, true, $terminal2);

        // asserts that decryption is working successfullly even though it was encrypted using default key
        $this->assertEquals($dataToEncrypt, $decryptedData);
    }
}