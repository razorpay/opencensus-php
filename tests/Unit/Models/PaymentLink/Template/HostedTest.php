<?php

namespace RZP\Tests\Unit\Models\PaymentLink\Template;

use Illuminate\Support\Facades\File;
use RZP\Models\PaymentLink\Template\Hosted;
use RZP\Tests\Unit\Models\PaymentLink\BaseTest;

class HostedTest extends BaseTest
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var Hosted
     */
    private $hosted;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filePath = $this->getPath() . "/randomid-randomname.blade.php";

        File::put($this->filePath, "");

        $this->hosted = new Hosted("randomid", "randomname");
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
     * @group nocode_pp_hosted
     */
    public function testExists()
    {
        $this->assertTrue($this->hosted->exists());
    }

    /**
     * @group nocode_pp_hosted
     */
    public function testGetViewName()
    {
        $this->assertEquals($this->hosted->getViewName(), "randomid-randomname");
    }

    private function getPath(): string
    {
        return resource_path(Hosted::BASE_PATH);
    }
}
