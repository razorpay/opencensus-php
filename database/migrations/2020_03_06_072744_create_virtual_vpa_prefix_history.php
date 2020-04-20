<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\VirtualVpaPrefixHistory\Entity;

class CreateVirtualVpaPrefixHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::VIRTUAL_VPA_PREFIX_HISTORY, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::VIRTUAL_VPA_PREFIX_ID, Entity::ID_LENGTH);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::CURRENT_PREFIX, 255);

            $table->string(Entity::PREVIOUS_PREFIX, 255)
                  ->nullable()
                  ->default(null);

            $table->char(Entity::TERMINAL_ID, Entity::ID_LENGTH);

            $table->boolean(Entity::IS_ACTIVE)
                  ->default(0);

            $table->integer(Entity::DEACTIVATED_AT)
                  ->nullable()
                  ->default(null);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index([Entity::MERCHANT_ID, Entity::VIRTUAL_VPA_PREFIX_ID], 'vpa_prefix_history_merchant_id_vpa_prefix_id_index');
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::VIRTUAL_VPA_PREFIX_HISTORY);
    }
}
