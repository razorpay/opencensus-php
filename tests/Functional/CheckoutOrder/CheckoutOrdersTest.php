<?php

namespace RZP\Tests\Functional\CheckoutOrder;

use RZP\Tests\Functional\Helpers\MocksRedisTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CheckoutOrdersTest extends TestCase
{
    use RequestResponseFlowTrait;
    use MocksRedisTrait;

    protected $testDataFilePath = __DIR__ . '/helpers/CheckoutOrdersTestData.php';
}
