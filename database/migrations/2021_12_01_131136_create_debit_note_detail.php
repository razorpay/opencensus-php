<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Dispute\DebitNote\Detail\Entity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDebitNoteDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DEBIT_NOTE_DETAIL, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::DEBIT_NOTE_ID, Entity::ID_LENGTH);

            $table->string(Entity::DETAIL_TYPE, 30);

            $table->char(Entity::DETAIL_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::DEBIT_NOTE_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debit_note_detail');
    }
}
