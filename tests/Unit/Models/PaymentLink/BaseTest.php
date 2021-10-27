<?php

namespace RZP\Tests\Unit\Models\PaymentLink;

use RZP\Tests\Functional\TestCase;

abstract class BaseTest extends TestCase
{
    protected $data = [];

    protected $datahelperPath;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function getData()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
        $name = $trace[4]['args'][1];
        if (empty($this->data)) {
            $this->data = require(__DIR__ . $this->datahelperPath);
        }
        return $this->data[$name];
    }
}
