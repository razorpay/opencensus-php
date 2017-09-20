<?php
namespace RZP\Tests\Unit\Models\FileStore;

use Config;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity;
use RZP\Models\FileStore;
use RZP\Encryption\Type;

class FileStoreTest extends TestCase
{
    function setUp()
    {
        parent::setUp();

        $this->creator = new \RZP\Models\FileStore\Creator;

        $this->extension = FileStore\Format::TXT;
        $this->content = 'test content';
        $this->fileName = 'test';
        $this->store = FileStore\Store::S3;
        $this->type = FileStore\Type::KOTAK_NETBANKING_REFUND;
        $this->merchant = $this->fixtures->create('merchant');
    }

    function testInvalidStore()
    {
        $store = 'invalid';

        $this->expectException('RZP\Exception\LogicException', 'Not a valid Store:');

        $this->creator->extension($this->extension)
                ->content($this->content)
                ->name($this->fileName)
                ->store($store)
                ->type($this->type)
                ->save();
    }

    function testEncryption()
    {
        $encryptionType = Type::PGP_ENCRYPTION;

        $extension = FileStore\Format::XLSX;

        $testEncryptionKey  = 'C45858B3041DA910EBFB51D16037D95C5D0C7902';
        $publicKey          = file_get_contents(__DIR__ . '/pgp_public_test_key.asc');
        $privateKey         = file_get_contents(__DIR__ . '/pgp_private_test_key.asc');

        $encryptionData = [
            'secret'      => $testEncryptionKey,
            'public_key'  => $publicKey,
            'private_key' => $privateKey,
        ];

        $file = $this->creator->extension($extension)
                     ->content($this->content)
                     ->name($this->fileName)
                     ->store($this->store)
                     ->type($this->type)
                     ->encrypt($encryptionType, $encryptionData)
                     ->save();

        $this->assertEquals($file->getFileInstance()->getMime(), 'application/pgp');
    }

    function testEncryptionFailure()
    {
        $encryptionType = Type::PGP_ENCRYPTION;

        $extension = FileStore\Format::XLSX;

        $testEncryptionKey  = 'somerandomkey';
        $publicKey          = file_get_contents(__DIR__ . '/pgp_public_test_key.asc');
        $privateKey         = file_get_contents(__DIR__ . '/pgp_private_test_key.asc');

        $encryptionData = [
            'secret'      => $testEncryptionKey,
            'public_key'  => $publicKey,
            'private_key' => $privateKey,
        ];

        $this->expectException('RZP\Exception\LogicException', 'PGP Encryption Failed');

        $file = $this->creator->extension($extension)
                     ->content($this->content)
                     ->name($this->fileName)
                     ->store($this->store)
                     ->type($this->type)
                     ->encrypt($encryptionType, $encryptionData)
                     ->save();
    }

    function testInvalidType()
    {
        $type = 'invalid';

        $this->expectException('RZP\Exception\LogicException', 'Not a valid Type:');

        $this->creator->extension($this->extension)
                ->content($this->content)
                ->name($this->fileName)
                ->store($this->store)
                ->type($type)
                ->save();
    }

    function testInvalidExtension()
    {
        $extension = 'invalid';

        $this->expectException('RZP\Exception\BadRequestValidationFailureException', 'Invalid Extension');

        $this->creator->extension($extension)
                ->content($this->content)
                ->name($this->fileName)
                ->store($this->store)
                ->type($this->type)
                ->save();
    }

    function testBucketSelectionOnType()
    {
        $bucketConfig = [
            'bucket_region' => 'region1',
            'mock'          => true,
            'settlement_bucket_config' => [
                'name'   => 'settlement_bucket',
                'region' => 'region1'
            ],
            'invoice_bucket_config' => [
                'name'   => 'invoice_bucket',
                'region' => 'region2'
            ],
            'activation_bucket_config' => [
                'name'   => 'activation_bucket',
                'region' => 'region3'
            ],
            'h2h_bucket_config' => [
                'name'   => 'h2h_bucket',
                'region' => 'region4'
            ],
            'test_bucket_config' => [
                'name'   => 'test_bucket',
                'region' => 'region5'
            ],
        ];

        Config::set('filestore.aws', $bucketConfig);

        $this->checkBucketAndRegion(
            $this->type,
            'settlement_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'invoice_pdf',
            'invoice_bucket_config',
            $bucketConfig,
            $this->merchant);

        $this->checkBucketAndRegion(
            'business_proof_url',
            'activation_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'fund_transfer_default',
            'settlement_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'fund_transfer_h2h',
            'h2h_bucket_config',
            $bucketConfig);
    }

    public function checkBucketAndRegion($type, $configName, $bucketConfig, $merchant = null)
    {

        $file = $this->creator->extension($this->extension)
                         ->content($this->content)
                         ->name($this->fileName)
                         ->store($this->store)
                         ->type($type);

        if ($merchant !== null)
        {
            $file->merchant($this->merchant);
        }

        $fileData = $file->save()->get();

        $this->assertEquals(
            $fileData['bucket'],
            $bucketConfig[$configName]['name']);

        $this->assertEquals(
            $fileData['region'],
            $bucketConfig[$configName]['region']);
    }
}
