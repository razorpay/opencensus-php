<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\QrCode\Entity as QrCode;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;

class CreateQrCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::QR_CODE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(QrCode::ID, QrCode::ID_LENGTH)
                  ->primary();

            $table->char(QrCode::MERCHANT_ID, QrCode::ID_LENGTH);

            $table->char(QrCode::ENTITY_ID, QrCode::ID_LENGTH);

            $table->string(QrCode::ENTITY_TYPE, 50);

            $table->integer(QrCode::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->text(QrCode::QR_STRING);

            $table->integer(QrCode::CREATED_AT);

            $table->integer(QrCode::UPDATED_AT);

            $table->index(QrCode::ENTITY_ID);

            $table->index(QrCode::ENTITY_TYPE);

            $table->index(QrCode::CREATED_AT);

            $table->index(QrCode::UPDATED_AT);

            $table->foreign(QrCode::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

        });

        // This needs to be done here because migrations are run in order of
        // timestamps and qr code table gets created after virtualaccount.
        Schema::table(Table::VIRTUAL_ACCOUNT, function(Blueprint $table)
        {
            $table->foreign(VirtualAccount::QR_CODE_ID)
                  ->references('id')
                  ->on(Table::QR_CODE)
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
        Schema::table(Table::QR_CODE, function($table)
        {
            $table->dropForeign(Table::QR_CODE . '_' . QrCode::MERCHANT_ID . '_foreign');
        });

        Schema::table(Table::VIRTUAL_ACCOUNT, function(Blueprint $table)
        {
            $table->dropForeign(Table::VIRTUAL_ACCOUNT . '_' . VirtualAccount::QR_CODE_ID . '_foreign');
        });

        Schema::drop(Table::QR_CODE);
    }
}
