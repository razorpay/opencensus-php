<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
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
        $this->fixtures->create('org:razorpay_organization');
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

    public function createRazorpayOrganization()
    {
        $now = Carbon::now()->timestamp;

        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'    => self::RZP_ORG,
            'email' => 'admin@razorpay.com',

            'cross_org_access' => true,
        ]);

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
            'name'   => 'SuperAdmin',
        ]);

        $this->fixtures->create('role', [
            'id'     => self::MANAGER_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => 'Admin',
        ]);

        $permissions = $this->fixtures->create('permission:default_permissions');

        foreach ($permissions as $permission)
        {
            DB::table(Table::PERMISSION_MAP)->insert([
                [
                    'permission_id' => $permission->getId(),
                    'entity_id'     => self::RZP_ORG,
                    'entity_type'   => 'org',
                ]
            ]);
        }

        $liveAdminRole = clone $adminRole;
        $testAdminRole = clone $adminRole;

        $testAdminRole->permissions()->attach($permissions);

        $this->onLive();

        $liveAdminRole->permissions()->attach($permissions);

        $this->fixtures->setDefaultConn();

        $admin = $this->fixtures->create('admin', [
            'id'     => self::SUPER_ADMIN,
            'org_id' => self::RZP_ORG,
            'email'  => 'superadmin@razorpay.com'
        ]);

        $liveAdmin = clone $admin;
        $testAdmin = clone $admin;

        $testAdmin->roles()->attach($testAdminRole);

        $this->onLive();

        $liveAdmin->roles()->attach($liveAdminRole);

        $this->fixtures->setDefaultConn();

        $this->fixtures->create('admin_token', [
            'admin_id'   => self::SUPER_ADMIN,
            'token'      => self::DEFAULT_TOKEN,
            'created_at' => $now,
            'expires_at' => Carbon::now()->addYear()->timestamp,
        ]);

        return $org;
    }
}
