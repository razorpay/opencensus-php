<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Address\Entity;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;

class Address extends Base implements ParityInterface
{

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->entityClass = Entity::class;
        $this->entityRepoClass = \RZP\Models\Address\Repository::class;
        $this->testData = new TestData\Address();
    }

    public function checkReadParity()
    {
        // TODO: Implement checkReadParity() method.
    }
}
