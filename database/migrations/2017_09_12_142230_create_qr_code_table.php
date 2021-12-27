<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\QrCode\Entity as QrCode;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;
use RZP\Models\QrCode\NonVirtualAccountQrCode\Entity as NonVaQrCode;

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

            $table->char(QrCode::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(QrCode::REFERENCE)
                  ->nullable();

            $table->char(QrCode::PROVIDER, 50);

            $table->string(QrCode::ENTITY_ID, QrCode::ID_LENGTH)
                  ->nullable();

            $table->string(QrCode::ENTITY_TYPE, 50)
                  ->nullable();

            $table->integer(QrCode::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->text(QrCode::QR_STRING)
                  ->nullable();

            $table->string(QrCode::SHORT_URL, 255)
                  ->nullable();

            $table->tinyInteger(QrCode::MPANS_TOKENIZED)
                  ->nullable();

            $table->integer(QrCode::CREATED_AT);

            $table->integer(QrCode::UPDATED_AT);

            $table->string(NonVaQrCode::NAME)
                  ->nullable();

            $table->string(NonVaQrCode::USAGE_TYPE, 14)
                  ->nullable();

            $table->string(NonVaQrCode::STATUS, 6)
                  ->nullable();

            $table->string(NonVaQrCode::REQUEST_SOURCE, 9)
                  ->nullable();

            $table->text(NonVaQrCode::DESCRIPTION)
                  ->nullable();

            $table->bigInteger(NonVaQrCode::PAYMENTS_AMOUNT_RECEIVED)
                  ->default(0);

            $table->integer(NonVaQrCode::PAYMENTS_RECEIVED_COUNT)
                  ->default(0);

            $table->boolean(NonVaQrCode::FIXED_AMOUNT)
                  ->default(false);

            $table->json(NonVaQrCode::NOTES)
                  ->nullable();

            $table->json(NonVaQrCode::TAX_INVOICE)
                  ->nullable();

            $table->string(NonVaQrCode::CUSTOMER_ID, Customer\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(NonVaQrCode::CLOSE_REASON, 10)
                  ->nullable();

            $table->integer(NonVaQrCode::CLOSE_BY)
                  ->nullable();

            $table->integer(NonVaQrCode::CLOSED_AT)
                  ->nullable();

            $table->index(QrCode::REFERENCE);

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
