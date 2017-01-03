<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\P2p\Entity as P2p;
use RZP\Models\Merchant\Entity as Merchant;

class CreateP2p extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::P2P, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(P2p::ID, P2p::ID_LENGTH)
                  ->primary();

            $table->char(P2p::TXN_ID, 35);

            $table->char(P2p::SOURCE_ID, P2p::ID_LENGTH);

            $table->char(P2p::SOURCE_TYPE, 20);

            $table->char(P2p::SINK_ID, P2p::ID_LENGTH);

            $table->char(P2p::SINK_TYPE, 20);

            $table->char(P2p::MERCHANT_ID, 14);

            $table->char(P2p::CUSTOMER_ID, 14);

            $table->integer(P2p::AMOUNT)
                  ->unsigned();

            $table->string(P2p::STATUS);

            $table->text(P2p::DESCRIPTION)
                  ->nullable();

            $table->char(P2p::TYPE)
                  ->nullable();

            $table->string(P2p::GATEWAY)
                  ->nullable();

            $table->text(P2p::NOTES)
                  ->nullable();

            $table->char(P2p::CURRENCY, 3);

            $table->string(P2p::INTERNAL_ERROR_CODE)
                  ->nullable();

            $table->string(P2p::ERROR_CODE, 100)
                  ->nullable();

            $table->string(P2p::ERROR_DESCRIPTION, 255)
                  ->nullable();

            $table->integer(P2p::CREATED_AT);
            $table->integer(P2p::UPDATED_AT);

            $table->index(P2p::STATUS);
            $table->index(P2p::CREATED_AT);
            $table->index(P2p::GATEWAY);

            $table->foreign(P2p::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::P2P, function($table)
        {
            $table->dropForeign(Table::P2P.'_'.P2p::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::P2P);
    }
}

