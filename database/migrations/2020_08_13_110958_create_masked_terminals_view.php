<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedTerminalsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'id',
            'category',
            'gateway_terminal_password',
            '"*redacted*" AS mc_mpan',
            'bank_transfer',
            'emi_subvention',
            'corporate',
            'cred',
            'network_category',
            'notes',
            'gateway_terminal_password2',
            '"*redacted*" AS visa_mpan',
            'emandate',
            'recurring',
            'expected',
            'enabled_banks',
            'enabled',
            'gateway_access_code',
            '"*redacted*" AS rupay_mpan',
            'nach',
            'capability',
            'currency',
            'merchant_id',
            '"*redacted*" AS account_number',
            'status',
            'gateway_secure_secret',
            '"*redacted*" AS vpa',
            'aeps',
            'international',
            'org_id',
            'ifsc_code',
            'gateway',
            'gateway_secure_secret2',
            'card',
            'emi',
            'shared',
            'procurer',
            'virtual_upi_root',
            'gateway_merchant_id',
            'gateway_recon_password',
            'netbanking',
            'cardless_emi',
            'tpv',
            'deleted_at',
            'used_count',
            'virtual_upi_merchant_prefix',
            'gateway_merchant_id2',
            'gateway_acquirer',
            'upi',
            'paylater',
            'type',
            'account_type',
            'used',
            'virtual_upi_handle',
            'gateway_terminal_id',
            'gateway_client_certificate',
            'omnichannel',
            'emi_duration',
            'mode',
            'sync_status',
            'created_at',
            'updated_at',
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_terminals_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::TERMINAL;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_terminals_view');
    }
}
