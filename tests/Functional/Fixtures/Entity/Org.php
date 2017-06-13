<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use Config;
use DB;

use RZP\Constants\Table;

class Org extends Base
{
    const HDFC_ORG     = 'HDFCbankOrgnId';
    const RZP_ORG      = '100000razorpay';
    const DEFAULT_GRP  = '1RazorpayGrpId';
    const ADMIN_ROLE   = 'RzpAdminRoleId';
    const MANAGER_ROLE = 'RzpMngerRoleId';
    const SUPER_ADMIN  = 'RzrpySprAdmnId';

    const DEFAULT_TOKEN = 'SecretTokenForRazorpayAdminAuthentication';

    public function setUp()
    {
        $this->fixtures->create('org:razorpay_org');
    }

    public function createDefaultTestOrganization()
    {
        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'            => self::HDFC_ORG,
            'email'         => 'test@hdfcbank.com',
            'email_domains' => 'hdfcbank.com'
        ]);

        $orgHost = $this->fixtures->create('org_hostname', [
            'org_id'    => self::HDFC_ORG,
            'hostname'  => 'hdfcbank.com',
        ]);

        return $org;
    }

    public function createRazorpayOrg()
    {
        $now = Carbon::now()->timestamp;

        $permissions = $this->fixtures->create(
            'permission:default_permissions');

        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'               => self::RZP_ORG,
            'email'            => 'admin@razorpay.com',
            'cross_org_access' => true,
        ]);

        $org->permissions()->attach($permissions);

        $this->fixtures->create('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.dev'
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.com'
        ]);

        $this->fixtures->create('group', [
            'id'     => self::DEFAULT_GRP,
            'name'   => 'razorpay_group',
            'org_id' => self::RZP_ORG,
        ]);

        $adminRole = $this->fixtures->create('role', [
            'id'     => self::ADMIN_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => Config::get('heimdall.default_role_name'),
        ]);

        $this->fixtures->create('role', [
            'id'     => self::MANAGER_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => 'Admin',
        ]);

        $adminRole->permissions()->attach($permissions);

        $admin = $this->fixtures->create('admin', [
            'id'     => self::SUPER_ADMIN,
            'org_id' => self::RZP_ORG,
            'email'  => 'superadmin@razorpay.com'
        ]);

        $admin->roles()->attach($adminRole);

        $this->fixtures->create('admin_token', [
            'admin_id'   => self::SUPER_ADMIN,
            'token'      => self::DEFAULT_TOKEN,
            'created_at' => $now,
            'expires_at' => Carbon::now()->addYear()->timestamp,
        ]);

        return $org;
    }
}
