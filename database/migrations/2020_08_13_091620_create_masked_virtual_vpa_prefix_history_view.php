<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedVirtualVpaPrefixHistoryView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'merchant_id',
            'current_prefix',
            'previous_prefix',
            'terminal_id',
            'is_active',
            'deactivated_at',
            'id',
            'created_at',
            'virtual_vpa_prefix_id',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_virtual_vpa_prefix_history_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::VIRTUAL_VPA_PREFIX_HISTORY;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_virtual_vpa_prefix_history_view');
    }
}
