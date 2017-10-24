<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
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
        Schema::create(Table::DISPUTE_FILES, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->string(DisputeFileEntity::ID, DisputeFileEntity::ID_LENGTH)
                ->primary();

            $table->string(DisputeFileEntity::DISPUTE_ID, DisputeFileEntity::ID_LENGTH);

            $table->string(DisputeFileEntity::URL, 255);

            $table->integer(DisputeFileEntity::CREATED_AT);

            $table->integer(DisputeFileEntity::UPDATED_AT);

            $table->index(DisputeFileEntity::DISPUTE_ID);
            $table->index(DisputeFileEntity::CREATED_AT);
            $table->index(DisputeFileEntity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::DISPUTE_FILES);
    }
}
