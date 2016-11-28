<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Org extends Base
{
    const HDFC_ORG     = 'HDFCbankOrgnId';
    const RZP_ORG      = 'RazorpayOrgnId';
    const DEFAULT_GRP  = '1RazorpayGrpId';
    const ADMIN_ROLE   = 'RzpAdminRoleId';
    const MANAGER_ROLE = 'RzpMngerRoleId';

    public function setUp()
    {
        $this->fixtures->create('merchant:razorpay_organisation');
    }

    public function createDefaultTestOrganisation()
    {
        // Default organisation to be used for tests
        $this->fixtures->create('org', [
            'id'            => self::HDFC_ORG,
            'hostname'      => 'hdfcbank.com',
            'email'         => 'test@hdfcbank.com',
            'email_domains' => 'hdfcbank.com'
        ]);
    }

    public function createRazorpayOrganisation()
    {
        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', ['id' => self::RZP_ORG]);

        $this->fixtures->create('group', [
            'id'     => self::DEFAULT_GRP,
            'name'   => 'razorpay_group',
            'org_id' => self::RZP_ORG,
        ]);

        $this->fixtures->create('roles', [
            'id'     => self::ADMIN_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => 'admin'
        ]);

        $this->fixtures->create('roles', [
            'id'     => self::MANAGER_ROLE,
            'org_id' => self::RZP_ORG,
        ]);

        return $org;
    }
}
