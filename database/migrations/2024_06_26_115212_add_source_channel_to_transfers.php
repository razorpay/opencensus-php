<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Transfer\Entity;
use RZP\Constants\Table;


class AddSourceChannelToTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::TRANSFER, function (Blueprint $table) {
            $table->char(Entity::SOURCE_CHANNEL, 15)
                ->default('online');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TRANSFER, function (Blueprint $table) {
            $table->dropColumn(Entity::SOURCE_CHANNEL);
        });
    }
}
