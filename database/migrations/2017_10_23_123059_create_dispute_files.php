x<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Dispute\Entity as DisputeEntity;
use RZP\Models\Dispute\File\Entity as DisputeFileEntity;

class CreateDisputeFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DISPUTE_FILE, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->string(DisputeFileEntity::ID, DisputeFileEntity::ID_LENGTH)
                ->primary();

            $table->string(DisputeFileEntity::DISPUTE_ID, DisputeEntity::ID_LENGTH);

            $table->string(DisputeFileEntity::URL, 255);

            $table->string(DisputeFileEntity::NAME, 50);

            $table->string(DisputeFileEntity::CATEGORY, 50)
                ->nullable();

            $table->integer(DisputeFileEntity::CREATED_AT);

            $table->integer(DisputeFileEntity::UPDATED_AT);

            $table->index(DisputeFileEntity::DISPUTE_ID);
            $table->index(DisputeFileEntity::CREATED_AT);
            $table->index(DisputeFileEntity::UPDATED_AT);

            $table->foreign(DisputeFileEntity::DISPUTE_ID)
                ->references(DisputeEntity::ID)
                ->on(Table::DISPUTE)
                ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::DISPUTE_FILE, function($table)
        {
            $table->dropForeign(Table::DISPUTE_FILE.'_'.DisputeFileEntity::DISPUTE_ID   .'_foreign');
        });

        Schema::dropIfExists(Table::DISPUTE_FILE);
    }
}
