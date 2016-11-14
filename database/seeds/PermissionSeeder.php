<?php

use Illuminate\Database\Seeder;
use RZP\Constants\Table;

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

        DB::table(Table::PERMISSION)->delete();

        $this->seed();
    }

    private function seed()
    {
        $permissions = [
            'view_all_merchants' => '',
            'view_merchant' => '',
            'view_merchant_balance' => '',
            'view_merchant_details' => '',
            'view_merchant_features' => '',
            'view_merchant_banks' => '',
            'view_networks' => '',
            'view_merchant_bank_accounts' => '',
            'view_merchant_login' => '',
            'view_activity' => '',
            'view_pricing_list' => '',
            'view_merchant_pricing_rules' => '',
            'view_merchant_hdfc_excel' => '',
            'view_beneficiary_file' => '',
            'view_merchant_screenshot' => '',
            'view_all_merchant_aggregations' => '',
            'view_merchant_aggregations' => '',
            'view_merchant_tags' => '',
            'create_pricing_plan' => '',
            'set_pricing_rules' => '',
            'delete_pricing_plan_rules' => '',
            'delete_emi_plan' => '',
            'create_emi_plan' => '',
            'create_merchant_lock' => '',
            'create_merchant_unlock' => '',
            'edit_merchant' => '',
            'edit_merchant_tags' => '',
            'edit_merchant_features' => '',
            'edit_merchant_comments' => '',
            'edit_merchant_banks' => '',
            'edit_iin_rule' => '',
            'create_merchant_adjustments' => '',
            'edit_activate_merchant' => '',
            'edit_merchant_enable_live' => '',
            'edit_merchant_disable_live' => '',
            'edit_merchant_archive' => '',
            'edit_merchant_unarchive' => '',
            'edit_merchant_methods' => '',
            'edit_merchant_international' => '',
            'edit_merchant_terminal' => '',
            'edit_merchant_pricing' => '',
            'view_merchant_company_info' => '',
            'view_merchant_credits_log' => '',
            'add_merchant_credits' => '',
            'delete_merchant_credits' => '',
            'edit_merchant_screenshot' => '',
            'view_payment_verify' => '',
            'edit_verify_payments' => '',
            'edit_authorized_failed_payment' => '',
            'view_refund_payments' => '',
            'edit_authorized_refund_payment' => '',
            'edit_payment_refund' => '',
            'edit_payment_capture' => '',
            'edit_merchant_confirm' => '',
            'create_beneficiary_file' => '',
            'create_netbanking_refund' => '',
            'create_settlement_initiate' => '',
            'delete_terminal' => '',
            'edit_terminal' => '',
            'create_settlements_reconcile' => '',
            'create_reconciliate' => '',
            'view_entities' => '',
            'view_entity' => '',

            // UAM

            // ORG
            'create_org' => '',
            'view_all_org' => '',
            'view_org' => '',
            'edit_org' => '',

            // Roles
            'view_all_role' => '',
            'create_role' => '',
            'edit_role' => '',
            'delete_role' => '',
            'create_group' => '',
            'view_group' => '',
            'edot_group' => '',
            'view_all_group' => '',
            'create_group_admins' => '',
            'view_all_admin' => '',
            'edit_admin' => '',
            'delete_admin' => '',
            'create_admin' => '',
            'create_permission' => '',
            'view_all_permission' => '',
            'view_activation_form' => '',
            'edit_merchant_confirm' => '',
            'edit_merchant_lock_activation' => '',
            'edit_merchant_unlock_activation' => '',
            'edit_merchant_release_funds' => '',
            'edit_merchant_enable_live' => '',
            'edit_merchant_disable_live' => '',
            'edit_merchant_enable_receipt' => '',
            'edit_merchant_disable_receipt' => '',
            'assign_merchant_terminal' => '',
            'assign_merchant_banks' => '',
            'add_merchant_adjustment' => '',
            'edit_merchant_email' => '',
            'merchant_autofill_form' => '',
            'edit_merchant_mark_referred' => '',
            'view_as_entity' => '',
            'view_merchant_referrer' => '',
            'view_merchant_balance_test' => '',
            'view_merchant_balance_live' => '',
            'add_reconciliation_file' => '',
            'add_settlement_reconciliation' => '',
            'send_newsletter' => '',
            'trigger_dummy_error' => '',
            'make_api_call' => '',
        ];

        DB::transaction(function() use ($permissions)
        {
            foreach ($permissions as $key => $value) {
                $id = str_random(14);

                DB::table(Table::PERMISSION)->insert([
                    'id'          => $id,
                    'name'        => $key,
                    'description' => $value,
                    'created_at'  => time(),
                    'updated_at'  => time()
                ]);

                DB::table(Table::PERMISSION_MAP)->insert([
                    [
                        'permission_id'     => $id,
                        'entity_id'         => '6dLbNSpv5XbC5F',
                        'entity_type'       => 'role',
                    ]
                ]);
            }

        });
    }
}
