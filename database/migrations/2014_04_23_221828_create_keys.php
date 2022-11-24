<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Key\Entity as Key;
use RZP\Models\Merchant;

class CreateKeys extends Migration {

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::KEY, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Key::ID, Key::ID_LENGTH)
                  ->primary();

            $table->char(Key::MERCHANT_ID, Key::ID_LENGTH);

            $table->string(Key::SECRET, 256);

            $table->integer(Key::CREATED_AT);
            $table->integer(Key::UPDATED_AT);
            $table->integer(Key::EXPIRED_AT)
                  ->nullable();

            $table->foreign(Key::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Key::CREATED_AT);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::KEY);
    }
}
