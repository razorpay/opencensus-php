<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateQuboleSessionsView extends Migration
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
            'user_id',
            'user_agent',
            'last_activity'
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_sessions_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::SESSION;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_sessions_view');
    }
}
