<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use RZP\Constants\Entity as E;

class Stakeholder extends Base
{
    public function create(array $attributes = array())
    {
        return $this->createEntityInTestAndLive(E::STAKEHOLDER, $attributes);
    }
}
