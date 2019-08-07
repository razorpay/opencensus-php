<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Nodal\Statement\Entity as NodalStatement;

class CreateQuboleNodalStatementsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            NodalStatement::ID,
            NodalStatement::BANK_NAME,
            NodalStatement::SENDER_ACCOUNT_NUMBER,
            NodalStatement::PARTICULARS,
            NodalStatement::BANK_REFERENCE_NUMBER,
            NodalStatement::DEBIT,
            NodalStatement::CREDIT,
            NodalStatement::BALANCE,
            NodalStatement::TRANSACTION_DATE,
            NodalStatement::PROCESSED_ON,
            NodalStatement::MODE,
            NodalStatement::REFERENCE1,
            NodalStatement::REFERENCE2,
            NodalStatement::CREATED_AT,
            NodalStatement::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_nodal_statements_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::NODAL_STATEMENT;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_nodal_statements_view');
    }
}
