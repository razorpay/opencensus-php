<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Invitation\Entity as Invitation;

class CreateQuboleInvitationsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Invitation::ID,
            Invitation::MERCHANT_ID,
            Invitation::USER_ID,
            Invitation::PRODUCT,
            Invitation::ROLE,
            Invitation::DELETED_AT,
            Invitation::CREATED_AT,
            Invitation::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_invitations_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::INVITATION;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_invitations_view');
    }
}
