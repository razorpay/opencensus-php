<?php
namespace RZP\Tests\Unit\Models\FileStore;

use Config;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity;
use RZP\Models\FileStore;
use RZP\Encryption\Type;

class FileStoreTest extends TestCase
{
    protected function setUp(): void
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
        $this->markTestSkipped("BVT Golden Hour: test case failing in public runner");

        $encryptionType = Type::PGP_ENCRYPTION;

        $extension = FileStore\Format::XLSX;

        $publicKey          = file_get_contents(__DIR__ . '/../../Encryption/pgp_public_test_key.asc');

        $encryptionData = [
            'public_key'  => $publicKey,
        ];

        $content = [['heading1'], ['test content']];

        $file = $this->creator->extension($extension)
                     ->content($content)
                     ->name($this->fileName)
                     ->store($this->store)
                     ->type($this->type)
                     ->encrypt($encryptionType, $encryptionData)
                     ->save();

        $this->assertEquals('application/pgp-encrypted', $file->getFileInstance()->getMime());
    }

    function testEncryptionFailure()
    {
        $encryptionType = Type::PGP_ENCRYPTION;

        $extension = FileStore\Format::XLSX;

        $publicKey          = 'somerandomkey';

        $encryptionData = [
            'public_key'  => $publicKey,
        ];

        $this->expectException('RZP\Exception\LogicException', 'PGP Encryption Failed');

        $content = [['heading1'], ['test content']];

        $file = $this->creator->extension($extension)
                     ->content($content)
                     ->name($this->fileName)
                     ->store($this->store)
                     ->type($this->type)
                     ->encrypt($encryptionType, $encryptionData)
                     ->save();
    }

    function testEncoding()
    {
        $extension = FileStore\Format::TXT;

        $dataToEncode = $this->content;

        $encodedData = base64_encode($dataToEncode);

        $file = $this->creator->extension($extension)
            ->content($this->content)
            ->name($this->fileName)
            ->store($this->store)
            ->type($this->type)
            ->encode()
            ->save();

        $this->assertEquals($file->getFileInstance()->getMime(), 'text/plain');
        $this->assertEquals(file_get_contents($file->get()['local_file_path']), $encodedData);
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
            'commission_invoice_ap_south_bucket_config' => [
                'name'   => 'invoice_bucket',
                'region' => 'region2'
            ],
            'ap_south_activation_bucket_config' => [
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
            'h2h_default_bucket_config' => [
                'name'   => 'h2h_bucket',
                'region' => 'region4'
            ],
            'recon_sftp_input_bucket' => [
                'name'   => 'recon_sftp_bucket',
                'region' => 'region5'
            ],
            'fund_transfer_sftp_bucket_config' => [
                'name'   => 'fund_transfer_sftp_bucket',
                'region' => 'region5'
            ],
            'payouts_bucket_config' => [
                'name'   => 'payouts_bucket',
                'region' => 'region6',
            ]
        ];

        Config::set('filestore.aws', $bucketConfig);

        $this->checkBucketAndRegion(
            $this->type,
            'recon_sftp_input_bucket',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'invoice_pdf',
            'invoice_bucket_config',
            $bucketConfig,
            $this->merchant);

        $this->checkBucketAndRegion(
            'commission_invoice',
            'commission_invoice_ap_south_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'business_proof_url',
            'ap_south_activation_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'fund_transfer_default',
            'h2h_default_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'fund_transfer_h2h',
            'fund_transfer_sftp_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'beneficiary_file',
            'fund_transfer_sftp_bucket_config',
            $bucketConfig);

        $this->checkBucketAndRegion(
            'payout_sample',
            'payouts_bucket_config',
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
