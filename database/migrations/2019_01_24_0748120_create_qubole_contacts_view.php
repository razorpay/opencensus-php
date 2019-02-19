<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Contact\Entity as Contact;

class CreateQuboleContactsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Contact::ID,
            Contact::MERCHANT_ID,
            Contact::NAME,
            Contact::TYPE,
            Contact::REFERENCE_ID,
            Contact::NOTES,
            Contact::ACTIVE,
            Contact::CREATED_AT,
            Contact::UPDATED_AT,
            Contact::DELETED_AT,
        ];

        $sha2_columns = [
            Contact::CONTACT,
            Contact::EMAIL,
        ];

        $sha2_columns = array_map(function ($v)
        {
            return "sha2({$v}, 256) as {$v}";
        },
        $sha2_columns);

        $columns = array_merge($columns, $sha2_columns);

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_contacts_view AS
                        SELECT ' . $columnStr .
                        ' FROM `' . Table::CONTACT . '`';

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_contacts_view');
    }
}