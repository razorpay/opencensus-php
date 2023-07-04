<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use DB;
use Hash;
use Config;
use Carbon\Carbon;

use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Fixtures\Entity\Permission as PermissionEntity;

class Org extends Base
{
    use DbEntityFetchTrait;

    const SBIN_ORG                  = 'SBINbankOrgnId';
    const HDFC_ORG                  = '6dLbNSpv5XbCOG';
    const AXIS_ORG                  = 'CLTnQqDj9Si8bx';
    const RZP_ORG                   = '100000razorpay';
    const RZP_ORG_SIGNED            = 'org_100000razorpay';
    const AXIS_ORG_ID               = 'CLTnQqDj9Si8bx';
    const DEFAULT_GRP               = '1RazorpayGrpId';
    const DEFAULT_GRP_SIGNED        = 'grp_1RazorpayGrpId';
    const ADMIN_ROLE                = 'RzpAdminRoleId';
    const MANAGER_ROLE              = 'RzpMngerRoleId';
    const SUPER_ADMIN               = 'RzrpySprAdmnId';
    const SUPER_ADMIN_SIGNED        = 'admin_RzrpySprAdmnId';

    // Workflow related roles
    const MAKER_ROLE                = 'RzpMakerRoleId';
    const MAKER_ROLE_SIGNED         = 'role_RzpMakerRoleId';
    const MAKER_ADMIN               = 'RzpMakerAdmnId';
    const CHECKER_ROLE              = 'RzpChekrRoleId';
    const CHECKER_ROLE_SIGNED       = 'role_RzpChekrRoleId';
    const CHECKER_ADMIN             = 'RzpChekrAdmnId';
    const CHECKER_ADMIN_SIGNED      = 'admin_RzpChekrAdmnId';
    const CURLEC_ORG                = 'KjWRtYXwpK6VfK';


    // Payout Workflow related roles
    const OWNER_ROLE                = 'RzpOwnerRoleId';
    const OWNER_ROLE_SIGNED         = 'role_RzpOwnerRoleId';
    const BANKING_ADMIN_ROLE        = 'RzpBnkAdRoleId';
    const BANKING_ADMIN_ROLE_SIGNED = 'role_RzpBnkAdRoleId';
    const FINANCE_L1_ROLE           = 'RzpFinL1RoleId';
    const FINANCE_L1_ROLE_SIGNED    = 'role_RzpFinL1RoleId';
    const FINANCE_L2_ROLE           = 'RzpFinL2RoleId';
    const FINANCE_L2_ROLE_SIGNED    = 'role_RzpFinL2RoleId';
    const FINANCE_L3_ROLE           = 'RzpFinL3RoleId';
    const FINANCE_L3_ROLE_SIGNED    = 'role_RzpFinL3RoleId';

    const DEFAULT_TOKEN             = 'SuperSecretTokenForRazorpay';
    const DEFAULT_TOKEN_PRINCIPAL   = 'SuprAdminToken';
    const DEFAULT_ADMIN_TOKEN       = self::DEFAULT_TOKEN . self::DEFAULT_TOKEN_PRINCIPAL;

    //Workflow related role tokens
    const MAKER_TOKEN               = 'MakerSecretTokenForRazorpay';
    const MAKER_TOKEN_PRINCIPAL     = 'MakrAdminToken';
    const MAKER_ADMIN_TOKEN         = self::MAKER_TOKEN . self::MAKER_TOKEN_PRINCIPAL;
    const CHECKER_TOKEN             = 'CheckerSecretTokenForRazorpay';
    const CHECKER_TOKEN_PRINCIPAL   = 'ChkrAdminToken';
    const CHECKER_ADMIN_TOKEN       = self::CHECKER_TOKEN . self::CHECKER_TOKEN_PRINCIPAL;

    public function setUp()
    {
        $this->fixtures->create('org:razorpay_org');

        // setup live later since in live we dont create org again
        $this->fixtures->create('org:razorpay_org_live');
    }

    public function createDefaultTestOrganization()
    {
        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'            => self::SBIN_ORG,
            'email'         => 'test@sbi.com',
            'email_domains' => 'sbi.com'
        ]);

        $orgHost = $this->fixtures->create('org_hostname', [
            'org_id'    => self::SBIN_ORG,
            'hostname'  => 'sbi.com',
        ]);

        return $org;
    }

    public function createHdfcOrg()
    {
        $permissions = (new PermissionEntity)->getAllPermissions();

        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'                      => self::HDFC_ORG,
            'email'                   => 'admin@hdfc.com',
            'from_email'              => 'noreplay@hdfc.com',
            'cross_org_access'        => true,
            'default_pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ]);

        $org->permissions()->attach($permissions);

        $this->fixtures->create('org_hostname', [
            'org_id'    => self::HDFC_ORG,
            'hostname'  => 'hdfcbank.com'
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'    => self::HDFC_ORG,
            'hostname'  => 'hdfcbank.in'
        ]);

        $this->fixtures->create('group', [
            'id'     => '1HdfcbankGrpId',
            'name'   => 'hdfc_group',
            'org_id' => self::HDFC_ORG,
        ]);

        $adminRole = $this->fixtures->create('role', [
            'id'     => 'HdfAdminRoleId',
            'org_id' => self::HDFC_ORG,
            'name'   => Config::get('heimdall.default_role_name'),
        ]);

        $this->fixtures->create('role', [
            'id'     => 'HdfMngerRoleId',
            'org_id' => self::HDFC_ORG,
            'name'   => 'Admin',
        ]);

        $adminRole->permissions()->attach($permissions);

        $admin = $this->fixtures->create('admin', [
            'id'     => 'HdfcbSprAdmnId',
            'org_id' => self::HDFC_ORG,
            'email'  => 'superadmin@hdfc.com'
        ]);

        $admin->roles()->attach($adminRole);

        $this->fixtures->create('admin_token', [
            // 'id'         => 'SuperSecretTokenForHdfcbank' . self::DEFAULT_TOKEN_PRINCIPAL,
            'id'         => 'SuprHdfcbToken',
            'admin_id'   => 'HdfcbSprAdmnId',
            'token'      => Hash::make(self::DEFAULT_TOKEN),
            'created_at' => Carbon::now()->getTimestamp(),
            'expires_at' => Carbon::now()->addYears(10)->timestamp,
        ]);

        return $org;
    }

    public function createAxisOrg($customInput = [])
    {
        $input = [];
        $permissions = (new PermissionEntity)->getAllPermissions();

        if (isset($customInput['org']) && is_array($customInput['org']))
        {
            $input = $customInput['org'];
        }

        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'                      => self::AXIS_ORG_ID,
            'email'                   => 'admin@axis.com',
            'from_email'              => 'noreplay@axis.com',
            'cross_org_access'        => true,
            'default_pricing_plan_id' => 'BAJq6FJDNJ4ZqD',

        ] + $input);
        $input = [];

        $org->permissions()->attach($permissions);

        if (isset($customInput['org_hostname']) && is_array($customInput['org_hostname']))
        {
            $input = $customInput['org_hostname'];
        }

        $this->fixtures->create('org_hostname', [
            'org_id'    => self::AXIS_ORG_ID,
            'hostname'  => 'axis.com'
        ] + $input);
        $input = [];

        if (isset($customInput['group']) && is_array($customInput['group']))
        {
            $input = $customInput['group'];
        }

        $this->fixtures->create('group', [
            'id'     => '1AxisbankGrpId',
            'name'   => 'axis_group',
            'org_id' => self::AXIS_ORG_ID,
        ] + $input);
        $input = [];

        $adminRole = $this->fixtures->create('role', [
            'id'     => 'AxiAdminRoleId',
            'org_id' => self::AXIS_ORG_ID,
            'name'   => Config::get('heimdall.default_role_name'),
        ]);

        $this->fixtures->create('role', [
            'id'     => 'AxiMngerRoleId',
            'org_id' => self::AXIS_ORG_ID,
            'name'   => 'Admin',
        ]);

        $adminRole->permissions()->attach($permissions);

        $admin = $this->fixtures->create('admin', [
            'id'     => 'AxisbSprAdmnId',
            'org_id' => self::AXIS_ORG_ID,
            'email'  => 'superadmin@axis.com'
        ]);

        $admin->roles()->attach($adminRole);

        $this->fixtures->create('admin_token', [
            'id'         => 'SuprAxisbToken',
            'admin_id'   => 'AxisbSprAdmnId',
            'token'      => Hash::make(self::DEFAULT_TOKEN),
            'created_at' => Carbon::now()->getTimestamp(),
            'expires_at' => Carbon::now()->addYears(10)->timestamp,
        ]);

        return $org;
    }

    public function createCurlecOrg()
    {
        $permissions = (new PermissionEntity)->getAllPermissions();

        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', [
            'id'                      => 'KjWRtYXwpK6VfK',
            'email'                   => 'admin@curlec.com',
            'from_email'              => 'noreplay@curlec.com',
            'cross_org_access'        => true,
            'default_pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
            'custom_code'             => 'curlec',
            'display_name'            => "Curlec",
            'payment_apps_logo_url'   => 'https://rzp-1415-prod-dashboard-activation.s3.ap-south-1.amazonaws.com/org_KjWRtYXwpK6VfK/payment_apps_logo/phplelIPA',
        ]);

        $org->permissions()->attach($permissions);

        $this->fixtures->create('org_hostname', [
            'org_id'    => $org->getId(),
            'hostname'  => 'curlec.com'
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'    => $org->getId(),
            'hostname'  => 'curlec.in'
        ]);

        $this->fixtures->create('group', [
            'id'     => '1CurlecGroupId',
            'name'   => 'curlec_group',
            'org_id' => $org->getId(),
        ]);

        $adminRole = $this->fixtures->create('role', [
            'id'     => 'CurlecAdRoleId',
            'org_id' => $org->getId(),
            'name'   => Config::get('heimdall.default_role_name'),
        ]);

        $this->fixtures->create('role', [
            'id'     => 'CurMngerRoleId',
            'org_id' => $org->getId(),
            'name'   => 'Admin',
        ]);

        $adminRole->permissions()->attach($permissions);

        $admin = $this->fixtures->create('admin', [
            'id'     => 'CurleSprAdmnId',
            'org_id' => $org->getId(),
            'email'  => 'superadmin@curlec.com'
        ]);

        $admin->roles()->attach($adminRole);

        $this->fixtures->create('admin_token', [
            'id'         => 'SuprCurleToken',
            'admin_id'   => 'CurleSprAdmnId',
            'token'      => Hash::make(self::DEFAULT_TOKEN),
            'created_at' => Carbon::now()->getTimestamp(),
            'expires_at' => Carbon::now()->addYears(10)->timestamp,
        ]);

        return $org;
    }

    public function createRazorpayOrg()
    {
        $permissions = $this->fixtures->create('permission:default_permissions');

        // Default organisation to be used for tests
        // org is synced in test and live dbs
        $org = $this->fixtures->create('org', [
            'id'                      => self::RZP_ORG,
            'email'                   => 'admin@razorpay.com',
            'cross_org_access'        => true,
            'custom_code'             => 'rzp',
            'default_pricing_plan_id' => '1In3Yh5Mluj605',
        ]);

        $org->permissions()->attach($permissions);

        $this->fixtures->create('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.in'
        ]);

        // hostname is not synced in test and live by default
        // create test record
        $this->fixtures->create('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.com'
        ]);

        $this->fixtures->create('group', [
            'id'     => self::DEFAULT_GRP,
            'name'   => 'razorpay_group',
            'org_id' => self::RZP_ORG,
        ]);

        $this->createAdminForRazorpayOrg($permissions);

        return $org;
    }

    public function createRazorpayOrgLive()
    {
        $permissions = $this->fixtures->on('live')->create('permission:default_permissions_live');

        // Default organisation to be used for tests
        $org = $this->getDbEntity('org', ['id' => self::RZP_ORG], 'live');

        $org->permissions()->attach($permissions);

        $this->fixtures->on('live')->create('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.in'
        ]);

        $this->fixtures->on('live')->create('org_hostname', [
            'org_id'    => self::RZP_ORG,
            'hostname'  => 'dashboard.razorpay.com'
        ]);

        $this->fixtures->on('live')->create('group', [
            'id'     => self::DEFAULT_GRP,
            'name'   => 'razorpay_group',
            'org_id' => self::RZP_ORG,
        ]);

        $this->createAdminForRazorpayOrgLive($permissions);

        return $org;
    }

    public function createAdminForRazorpayOrg($permissions = null)
    {
        if (empty($permissions) === true)
        {
            $permissions = $this->fixtures->create('permission:default_permissions');
        }

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
            'id'         => self::DEFAULT_TOKEN_PRINCIPAL,
            'admin_id'   => self::SUPER_ADMIN,
            'token'      => Hash::make(self::DEFAULT_TOKEN),
            'created_at' => Carbon::now()->getTimestamp(),
            'expires_at' => Carbon::now()->addYears(10)->timestamp,
        ]);
    }

    public function createAdminForRazorpayOrgLive($permissions = null)
    {
        if (empty($permissions) === true)
        {
            $permissions = $this->fixtures->create('permission:default_permissions_live');
        }

        $adminRole = $this->fixtures->on('live')->create('role', [
            'id'     => self::ADMIN_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => Config::get('heimdall.default_role_name'),
        ]);

        $this->fixtures->on('live')->create('role', [
            'id'     => self::MANAGER_ROLE,
            'org_id' => self::RZP_ORG,
            'name'   => 'Admin',
        ]);

        $adminRole->permissions()->attach($permissions);

        $admin = $this->fixtures->on('live')->create('admin', [
            'id'     => self::SUPER_ADMIN,
            'org_id' => self::RZP_ORG,
            'email'  => 'superadmin@razorpay.com'
        ]);

        $admin->roles()->attach($adminRole);

        $this->fixtures->on('live')->create('admin_token', [
            'id'         => self::DEFAULT_TOKEN_PRINCIPAL,
            'admin_id'   => self::SUPER_ADMIN,
            'token'      => Hash::make(self::DEFAULT_TOKEN),
            'created_at' => Carbon::now()->getTimestamp(),
            'expires_at' => Carbon::now()->addYears(10)->timestamp,
        ]);
    }

    public function createWorkflowUsers($attributes)
    {
        $org = $attributes['org'];

        $now = Carbon::now()->getTimestamp();

        $makerRole = $this->fixtures->create('role', [
            'id'     => self::MAKER_ROLE,
            'org_id' => $org->getId(),
            'name'   => 'Maker',
        ]);

        $checkerRole = $this->fixtures->create('role', [
            'id'     => self::CHECKER_ROLE,
            'org_id' => $org->getId(),
            'name'   => 'Checker',
        ]);

        $adminMaker = $this->fixtures->create('admin', [
            'id'     => self::MAKER_ADMIN,
            'org_id' => $org->getId(),
            'email'  => 'maker@razorpay.com',
        ]);

        $adminChecker = $this->fixtures->create('admin', [
            'id'     => self::CHECKER_ADMIN,
            'org_id' => $org->getId(),
            'email'  => 'checker@razorpay.com',
        ]);

        $adminMaker->roles()->attach($makerRole);

        $adminChecker->roles()->attach($checkerRole);

        $this->fixtures->create('admin_token', [
            'id'         => self::MAKER_TOKEN_PRINCIPAL,
            'admin_id'   => self::MAKER_ADMIN,
            'token'      => Hash::make(self::MAKER_TOKEN),
            'created_at' => $now,
            'expires_at' => Carbon::now()->addYear()->timestamp,
        ]);

        $this->fixtures->create('admin_token', [
            'id'         => self::CHECKER_TOKEN_PRINCIPAL,
            'admin_id'   => self::CHECKER_ADMIN,
            'token'      => Hash::make(self::CHECKER_TOKEN),
            'created_at' => $now,
            'expires_at' => Carbon::now()->addYear()->timestamp,
        ]);

        return $org;
    }

    public function addFeatures($featureNames, $id = '10000000000000')
    {
        $features = collect();

        foreach ((array) $featureNames as $featureName) {
            $attributes = [
                'name'          => $featureName,
                'entity_type'   => 'org',
                'entity_id'     => $id
            ];
            $features->push($this->fixtures->create('feature', $attributes));
        }

        return $features;
    }
}
