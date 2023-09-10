<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity\TestData;

use Exception;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;

interface TestDataInterface {
    public function getTestData(): array;
}
