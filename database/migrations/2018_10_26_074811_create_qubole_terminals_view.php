<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Terminal\Entity as Terminal;

class CreateQuboleTerminalsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Terminal::ID,
            Terminal::MERCHANT_ID,
            Terminal::ORG_ID,
            Terminal::PROCURER,
            Terminal::CATEGORY,
            Terminal::NETWORK_CATEGORY,
            Terminal::NOTES,
            Terminal::ENABLED,
            Terminal::STATUS,
            Terminal::GATEWAY,
            Terminal::GATEWAY_MERCHANT_ID,
            Terminal::GATEWAY_TERMINAL_ID,
            Terminal::GATEWAY_ACQUIRER,
            Terminal::CARD,
            Terminal::NETBANKING,
            Terminal::UPI,
            Terminal::EMANDATE,
            Terminal::AEPS,
            Terminal::EMI,
            Terminal::RECURRING,
            Terminal::CAPABILITY,
            Terminal::INTERNATIONAL,
            Terminal::TPV,
            Terminal::TYPE,
            Terminal::CREATED_AT,
            Terminal::UPDATED_AT,
            Terminal::DELETED_AT,
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_terminals_view AS
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
        DB::statement('DROP VIEW IF EXISTS qubole_terminals_view');
    }
}
