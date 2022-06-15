<?php

namespace RZP\Tests\Functional\Fixtures\Entity;


class RoleAccessPolicyMap extends Base
{
    public function create(array $attributes = [])
    {
        $defaultAttributes =[
            'role_id' => '100customRole3',
            'authz_roles' => ['account_info_view_only', 'payout_admin', 'fundaccount_admin', 'contact_admin', 'workflow_view_only', 'payout_link_admin', 'payout_view_only', 'fundaccount_view_only', 'contact_view_only', 'payout_link_view_only', 'invoice_admin', 'invoice_view_only', 'tax_admin', 'tax_view_only', 'dev_controls_admin', 'dev_controls_view_only'],
            'access_policy_ids' => ['JYchc9jb1LVu6s', 'JYchcAZ2EWCXJc', 'JYchcBJB9ylQLD', 'JYchcByBlRi81M', 'JYchcCdFh67JeP', 'JYchcDI5gj6Nig', 'JYchcDuEVxsHDM', 'JYchcEXuRnk6hX', 'JYchcFCM9vxX6B', 'JYchcFoKjA6nRY', 'JYchcGSGK4JRJ0', 'JYchcH5cimTMym', 'JYchcHgkGD5nuT', 'JYchcIHh6Dl6Bh', 'JYchcJ1QCcIwYj', 'JYchcJiV33hebD'],
        ];
        $attributes = array_merge($defaultAttributes, $attributes);

        return parent::create($attributes);
    }
}
