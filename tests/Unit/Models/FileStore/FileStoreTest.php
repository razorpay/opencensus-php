<?php
namespace RZP\Tests\Unit\Models\FileStore;

use RZP\Tests\Functional\TestCase;
use RZP\Models\FileStore;

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
    }

    function testInvalidStore()
    {
        $store = 'invalid';

        $this->setExpectedException('RZP\Exception\LogicException', 'Not a valid Store:');

        $this->creator->extension($this->extension)
                ->content($this->content)
                ->name($this->fileName)
                ->store($store)
                ->type($this->type)
                ->save();
    }

    function testInvalidType()
    {
        $type = 'invalid';

        $this->setExpectedException('RZP\Exception\LogicException', 'Not a valid Type:');

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

        $this->setExpectedException('RZP\Exception\BadRequestValidationFailureException', 'Invalid Extension');

        $this->creator->extension($extension)
                ->content($this->content)
                ->name($this->fileName)
                ->store($this->store)
                ->type($this->type)
                ->save();
    }
}
