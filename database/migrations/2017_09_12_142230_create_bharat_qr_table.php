<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\BharatQr\Entity as BharatQr;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;

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

            $table->integer(BharatQr::IDENTIFIER_PADDING);

            $table->integer(BharatQr::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->string(BharatQr::METHOD);

            $table->text(BharatQr::QR_STRING);

            $table->integer(BharatQr::CREATED_AT);

            $table->integer(BharatQr::UPDATED_AT);

            $table->index(BharatQr::IDENTIFIER_PADDING);

            $table->index(BharatQr::ENTITY_ID);

            $table->index(BharatQr::ENTITY_TYPE);

            $table->index(BharatQr::CREATED_AT);

            $table->index(BharatQr::UPDATED_AT);

            $table->foreign(BharatQr::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

        });

        DB::statement('ALTER TABLE bharat_qr CHANGE identifier_padding identifier_padding INT(10) AUTO_INCREMENT');

        // This needs to be done here because migrations are run in order of
        // timestamps and bharat qr table gets created after virtualaccount.
        Schema::table(Table::VIRTUAL_ACCOUNT, function(Blueprint $table)
        {
            $table->foreign(VirtualAccount::BHARAT_QR_ID)
                  ->references('id')
                  ->on(Table::BHARAT_QR)
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
            $table->dropForeign(Table::BHARAT_QR . '_' . BharatQr::MERCHANT_ID . '_foreign');
        });

        Schema::table(Table::VIRTUAL_ACCOUNT, function(Blueprint $table)
        {
            $table->dropForeign(Table::VIRTUAL_ACCOUNT . '_' . VirtualAccount::BHARAT_QR_ID . '_foreign');
        });

        Schema::drop(Table::BHARAT_QR);
    }
}
