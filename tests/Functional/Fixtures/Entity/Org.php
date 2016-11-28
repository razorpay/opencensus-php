<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

class Org extends Base
{
    public function setUp()
    {
        $this->fixtures->create('merchant:razorpay_organisation');
    }

    public function createDefaultTestOrganisation()
    {
        // Default organisation to be used for tests
        $this->fixtures->create('org', [
            'id'            => 'HDFCbankOrgnId',
            'hostname'      => 'hdfcbank.com',
            'email'         => 'test@hdfcbank.com',
            'email_domains' => 'hdfcbank.com'
        ]);
    }

    public function createRazorpayOrganisation()
    {
        // Default organisation to be used for tests
        $org = $this->fixtures->create('org', ['id' => 'RazorpayOrgnId']);

        $this->fixtures->create('group', [
            'id'     => '1RazorpayGrpId',
            'name'   => 'razorpay_group',
            'org_id' => 'RazorpayOrgnId'
        ]);

        $this->fixtures->create('roles', [
            'id'     => 'RzpAdminRoleId',
            'org_id' => 'RazorpayOrgnId',
            'name'   => 'admin'
        ]);

        $this->fixtures->create('roles', [
            'id'     => 'RzpMngerRoleId',
            'org_id' => 'RazorpayOrgnId'
        ]);

        return $org;
    }
}
