<?php

namespace RZP\Tests\Unit\Models\Merchant\Detail;

use RZP\Models\Merchant\Entity;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Detail;

class ValidatorTest extends TestCase
{
    protected $datahelperPath   = '/helpers/ValidatorTestData.php';

    protected ?Detail\Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new Detail\Validator();
    }

    public function getData()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);

        $name = $trace[4]['args'][1];

        if (empty($this->data))
        {
            $this->data = require(__DIR__ . $this->datahelperPath);
        }

        return $this->data[$name];
    }

    /**
     * @dataProvider getData
     *
     * @group website_update
     *
     * @return void
     */
    public function testValidateInternalWebsiteUpdateValidations($data, $exceptionClass=null, $exceptionMessage=null)
    {
        if (empty($exceptionClass) === false)
        {
            $this->expectException($exceptionClass);
        }

        if (empty($exceptionMessage) === false)
        {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertNull($this->validator->validateInput("internal_create_workflow", $data));
    }
}
