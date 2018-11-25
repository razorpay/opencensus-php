<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payee\Entity as Payee;
use RZP\Models\Merchant\Entity as Merchant;

class CreatePayees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYEE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payee::ID, 14)
                  ->primary();

            $table->char(Payee::MERCHANT_ID, 14);

            $table->string(Payee::NAME, 50)
                  ->nullable();

            $table->string(Payee::CONTACT, 20)
                  ->nullable();

            $table->string(Payee::EMAIL, 255)
                  ->nullable();

            $table->text(Payee::NOTES);

            $table->tinyInteger(Payee::ACTIVE)
                  ->default(1);

            $table->integer(Payee::CREATED_AT);

            $table->integer(Payee::UPDATED_AT);

            $table->integer(Payee::DELETED_AT)
                  ->nullable();

            $table->index(Payee::EMAIL);

            $table->index([Payee::CONTACT, Payee::EMAIL, Payee::MERCHANT_ID]);

            $table->index([Payee::CONTACT, Payee::MERCHANT_ID]);

            $table->index([Payee::MERCHANT_ID, Payee::CREATED_AT]);

            $table->index(Payee::DELETED_AT);

            $table->foreign(Payee::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
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
        Schema::table(Table::PAYEE, function($table)
        {
            $table->dropForeign(Table::PAYEE . '_' . Payee::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::PAYEE);
    }
}
