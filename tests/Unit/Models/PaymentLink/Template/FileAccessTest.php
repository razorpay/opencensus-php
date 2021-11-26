<?php

namespace RZP\Tests\Unit\Models\PaymentLink\Template;

use Illuminate\Support\Facades\File;
use RZP\Models\PaymentLink\Template\Hosted;
use RZP\Models\PaymentLink\Template\FileAccess;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;

class FileAccessTest extends BaseTest
{
    const FILE_ID       = "randomid";
    const FILE_NAME     = "randomname";
    const EXTENTION     = "blade.php";
    const FILE_CONTENT  = "SOME CONTENT";

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $extention;

    /**
     * @var string
     */
    private $path;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->path         = resource_path(Hosted::BASE_PATH);
        $this->filePath     = $this->path . '/'. self::FILE_ID.'-'.self::FILE_NAME.'.'.self::EXTENTION;

        File::put($this->filePath, self::FILE_CONTENT);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        if (empty($this->filePath) !== true)
        {
            File::delete($this->filePath);
        }

        parent::tearDown();
    }

    /**
     * @group nocode_pp_file_access
     */
    public function testGetForInvalidFile()
    {
        $fileAccess = new FileAccess($this->path, self::EXTENTION, "SOMEID");

        $this->assertNull($fileAccess->get());
    }

    /**
     * @group nocode_pp_file_access
     */
    public function testGetForValidFile()
    {
        $fileAccess = new FileAccess($this->path, self::EXTENTION, self::FILE_ID, self::FILE_NAME);

        $this->assertEquals($fileAccess->get(), self::FILE_CONTENT);
    }
}
