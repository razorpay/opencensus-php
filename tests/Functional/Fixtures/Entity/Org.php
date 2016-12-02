<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class Org extends Base
{
    const HDFC_ORG     = 'HDFCbankOrgnId';
    const RZP_ORG      = 'RazorpayOrgnId';
    const DEFAULT_GRP  = '1RazorpayGrpId';
    const ADMIN_ROLE   = 'RzpAdminRoleId';
    const MANAGER_ROLE = 'RzpMngerRoleId';
    const RZP_USER     = 'RazorpayUserId';

    const DEFAULT_TOKEN = 'SecretTokenForRazorpayAdminAuthentication';

    public function setUp()
    {
        $this->fixtures->on('test')->create('org:razorpay_organization');
        $this->fixtures->on('live')->create('org:razorpay_organization');
    }

    public function createDefaultTestOrganization()
    {
        // Default organisation to be used for tests
        $this->fixtures->create('org', [
            'id'            => self::HDFC_ORG,
            'hostname'      => 'hdfcbank.com',
            'email'         => 'test@hdfcbank.com',
            'email_domains' => 'hdfcbank.com'
        ]);
    }

    public function createRazorpayOrganization()
    {
        $now = Carbon::now()->timestamp;

        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', ['id' => self::RZP_ORG]);

        $this->fixtures->create('group', [
            'id'     => self::DEFAULT_GRP,
            'name'   => 'razorpay_group',
            'org_id' => self::RZP_ORG,
        ]);

        $adminRole = $this->fixtures->create('role', [
            'id'     => self::ADMIN_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => 'admin'
        ]);

        $this->fixtures->create('role', [
            'id'     => self::MANAGER_ROLE,
            'org_id' => self::RZP_ORG,
        ]);

        $permissions = $this->fixtures->create('permission:default_permissions');

        $adminRole->permissions()->attach($permissions);

        $admin = $this->fixtures->create('admin', [
            'id'     => self::RZP_USER,
            'org_id' => self::RZP_ORG,
            'email'  => 'admin@rzp.io'
        ]);

        $admin->roles()->attach($adminRole);

        $this->fixtures->create('admin_token', [
            'admin_id'   => self::RZP_USER,
            'token'      => self::DEFAULT_TOKEN,
            'created_at' => $now,
            'expires_at' => Carbon::now()->addYear()->timestamp,
        ]);

        return $org;
    }
}
