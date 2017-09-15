<?php

namespace RZP\Tests\Unit\Models\Base;

use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant;

class RepositoryTest extends TestCase
{
    public function testVerifyIdGenerationWhenTestAndLiveEntityAreInSync()
    {
        $repo = (new Merchant\Repository);

        $entity = (new Merchant\Entity);

        $this->expectException(
            'RZP\Exception\LogicException',
            'Unique id not generated for the entity');

        $repo->saveOrFail($entity);
    }
}
