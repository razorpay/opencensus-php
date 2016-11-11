<?php

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        DB::table('permissions')->delete();

        $this->seed();
    }

    private function seed()
    {
        $permissions = [
            "view_all_merchants",
            "view_merchant",
            "view_merchant_balance",
            "view_merchant_details",
            "view_merchant_features",
            "view_merchant_banks",
            "view_networks",
            "view_merchant_bank_accounts",
            "view_merchant_login",
            "view_activity",
            "view_pricing_list",
            "view_merchant_pricing_rules",
            "view_merchant_hdfc_excel",
            "view_beneficiary_file",
            "view_merchant_screenshot",
            "view_all_merchant_aggregations",
            "view_merchant_aggregations",
            "view_merchant_tags",
            "create_pricing_plan",
            "set_pricing_rules",
            "delete_pricing_plan_rules",
            "delete_emi_plan",
            "create_emi_plan",
            "create_merchant_lock",
            "create_merchant_unlock",
            "edit_merchant",
            "edit_merchant_tags",
            "edit_merchant_features",
            "edit_merchant_comments",
            "edit_merchant_banks",
            "create_merchant_adjustments",
            "edit_activate_merchant",
            "edit_merchant_enable_live",
            "edit_merchant_disable_live",
            "edit_merchant_archive",
            "edit_merchant_unarchive",
            "edit_merchant_methods",
            "edit_merchant_international",
            "edit_merchant_terminal",
            "edit_merchant_pricing",
            "view_merchant_company_info",
            "view_merchant_credits_log",
            "add_merchant_credits",
            "delete_merchant_credits",
            "edit_merchant_screenshot",
            "view_payment_verify",
            "edit_verify_payments",
            "edit_authorized_failed_payment",
            "view_refund_payments",
            "edit_authorized_refund_payment",
            "edit_payment_refund",
            "edit_payment_capture",
            "edit_merchant_confirm",
            "create_beneficiary_file",
            "create_netbanking_refund",
            "create_settlement_initiate",
            "delete_terminal",
            "edit_terminal",
            "create_settlements_reconcile",
            "create_reconciliate",
            "view_entities",
            "view_entity",

            // UAM

            // ORG
            "create_org",
            "view_all_org",
            "view_org",
            "edit_org",

            // Roles
            "view_all_admin",
            "edit_admin",
            "create_admin",
            "delete_admin",

            "role_get_multiple",
            "role_create",
            "group_get_multiple",
            "group_create",
            "group_get",
            "group_admins_create",
            "add_permission",
            "permission_get_multiple"
        ];

        DB::transaction(function() use ($permissions)
        {
            foreach ($permissions as $value) {
                DB::table('permissions')->insert(
                    [
                        'id'          => str_random(14),
                        'name'        => $value,
                        'description' => 'Some description',
                        'created_at'  => time(),
                        'updated_at'  => time()
                    ]);
            }

        });
    }
}
