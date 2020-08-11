<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Mpan;
use RZP\Models\Merchant;

class CreateMpan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MPAN, function(Blueprint $table)
        {
            $table->string(Mpan\Entity::MPAN)->primary();

            $table->string(Mpan\Entity::NETWORK);

            $table->boolean(Mpan\Entity::ASSIGNED)
                ->default(false);

            $table->char(Mpan\Entity::MERCHANT_ID, \RZP\Models\Merchant\Entity::ID_LENGTH)
                ->nullable();

            $table->integer(Mpan\Entity::CREATED_AT);

            $table->integer(Mpan\Entity::UPDATED_AT);

            $table->index(Mpan\Entity::MPAN);

            $table->index(Mpan\Entity::MERCHANT_ID);

            $table->index(Mpan\Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MPAN);
    }
}
