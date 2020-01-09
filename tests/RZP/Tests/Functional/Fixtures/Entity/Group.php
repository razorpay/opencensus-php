<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


use RZP\Models\Admin\Group\Constant;

class Group extends Base
{
    const RZP_ORG                   = '100000razorpay';

    public function createDefaultClaimedGroupSme()
    {
        $this->fixtures->create('group', [
            'id'          => Constant::SF_CLAIMED_SME_GROUP_ID,
            'name'        => Constant::SALESFORCE_CLAIMED_GROUP_ID,
            'description' => 'Salesforce Claimed Group',
            'org_id'      => self::RZP_ORG,
        ]);
    }

    public function createDefaultUnclaimedGroup()
    {
        $this->fixtures->create('group', [
            'id'          => Constant::SF_UNCLAIMED_GROUP_ID,
            'name'        => Constant::SALESFORCE_UNCLAIMED_GROUP_ID,
            'description' => 'Salesforce Unclaimed Group',
            'org_id'      => self::RZP_ORG,
        ]);
    }
}
