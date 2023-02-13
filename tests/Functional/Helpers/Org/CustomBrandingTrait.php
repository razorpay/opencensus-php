<?php

namespace RZP\Tests\Functional\Helpers\Org;

use Mockery;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\Entity;

/**
 * Trait CustomBrandingOrgTrait - contains helper functions for tests related to custom branding enabled orgs
 * @package RZP\Tests\Functional\Helpers
 */

trait CustomBrandingTrait
{
    protected function assertRazorpayOrgMailData($viewData)
    {
        $this->assertNull($viewData['email_logo']);

        $this->assertEquals('Razorpay', $viewData['org_name']);

        $this->assertNull($viewData['checkout_logo']);

        $this->assertFalse($viewData['custom_branding']);

        return true;
    }

    protected function createCustomBrandingOrgAndAssignMerchant($merchantId = '10000000000000', $orgData = [])
    {
        $org = $this->setupCustomBrandingOrg($orgData);

        $this->fixtures->edit('merchant', $merchantId, [
            'org_id'    => $org->getId(),
        ]);

        return $org;
    }

    protected function assertCustomBrandingMailViewData($org, $viewData)
    {
        $this->assertTrue($org->isFeatureEnabled('org_custom_branding'));

        $this->assertEquals($org->getEmailLogo(), $viewData['email_logo']);

        $this->assertEquals($org->getDisplayName(), $viewData['org_name']);

        $this->assertEquals($org->getCheckoutLogo(), $viewData['checkout_logo']);

        $this->assertTrue($viewData['custom_branding']);

        return true;
    }

    protected function setupCustomBrandingOrg(array $orgData)
    {
        $defaultOrgData = [
            'email_logo_url'    => 'https://www.xyz.com/email_logo.png',
            'display_name'      => 'random_org_name',
            'checkout_logo_url' => 'https://www.xyz.com/checkout_logo.png',
        ];

        $org = $this->fixtures->create('org', array_merge($defaultOrgData, $orgData));

        $this->fixtures->create('org_hostname', [
            'org_id'    => $org->getId(),
            'hostname'  => 'www.xyz.com'
        ]);

        $this->enableCustomBrandingForOrg($org);

        return $org;
    }

    protected function enableCustomBrandingForOrg($org)
    {
        return $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => $org->getId(),
            'name'          => 'org_custom_branding',
        ]);
    }
}

?>
