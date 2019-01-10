<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Customer\Entity as Customer;

class CreateQuboleCustomersView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Customer::ID,
            Customer::MERCHANT_ID,
            Customer::GSTIN,
            Customer::NOTES,
            Customer::ACTIVE,
            Customer::GLOBAL_CUSTOMER_ID,
            Customer::CREATED_AT,
            Customer::UPDATED_AT,
            Customer::DELETED_AT,
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_customers_view AS
                        SELECT ' . $columnStr .
                        ' FROM `' . Table::CUSTOMER . '`';

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_customers_view');
    }
}