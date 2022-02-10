<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class InternationalEnablementDetail extends Base
{
    public function create(array $attributes = array())
    {
        $internationalEnablementDetail = $this->createEntityInTestAndLive('international_enablement_detail',$attributes);

        return $internationalEnablementDetail;
    }
}
