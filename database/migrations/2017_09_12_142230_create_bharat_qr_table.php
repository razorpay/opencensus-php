<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BharatQr\Entity as BharatQr;
use RZP\Models\Merchant;

class CreateBharatQrTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BHARAT_QR, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BharatQr::ID, BharatQr::ID_LENGTH)
                  ->primary();

            $table->char(BharatQr::MERCHANT_ID, BharatQr::ID_LENGTH);

            $table->char(BharatQr::ENTITY_ID, BharatQr::ID_LENGTH);

            $table->string(BharatQr::ENTITY_TYPE, 50);

            $table->string(BharatQr::VISA_IDENTIFIER);

            $table->string(BharatQr::MASTER_CARD_IDENTIFIER);

            $table->integer(BharatQr::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->string(BharatQr::METHOD);

            $table->text(BharatQr::QR_STRING);

            $table->integer(BharatQr::CREATED_AT);

            $table->integer(BharatQr::UPDATED_AT);

            $table->index(BharatQr::ENTITY_ID);

            $table->index(BharatQr::ENTITY_TYPE);

            $table->index(BharatQr::CREATED_AT);

            $table->index(BharatQr::UPDATED_AT);

            $table->foreign(BharatQr::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
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
        Schema::table(Table::BHARAT_QR, function($table)
        {
            $table->dropForeign(Table::BHARAT_QR.'_'.BharatQr::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::BHARAT_QR);
    }
}
