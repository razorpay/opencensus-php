<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\VirtualAccount\Status;
use RZP\Models\VirtualAccount\Entity as VirtualAccount;

class CreateVirtualAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::VIRTUAL_ACCOUNT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(VirtualAccount::ID, VirtualAccount::ID_LENGTH)
                  ->primary();

            $table->string(VirtualAccount::STATUS)
                  ->default(Status::ACTIVE);

            $table->string(VirtualAccount::NAME)
                  ->nullable();

            $table->string(VirtualAccount::DESCRIPTOR)
                  ->nullable();

            $table->text(VirtualAccount::NOTES);

            $table->text(VirtualAccount::DESCRIPTION)
                  ->nullable();

            $table->bigInteger(VirtualAccount::AMOUNT_EXPECTED)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(VirtualAccount::AMOUNT_RECEIVED)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(VirtualAccount::AMOUNT_PAID)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(VirtualAccount::AMOUNT_REVERSED)
                  ->unsigned()
                  ->nullable();

            $table->string(VirtualAccount::BANK_ACCOUNT_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::BANK_ACCOUNT_ID2, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::OFFLINE_CHALLAN_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::QR_CODE_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::VPA_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::CUSTOMER_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::ENTITY_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::ENTITY_TYPE)
                  ->nullable();

            $table->string(VirtualAccount::MERCHANT_ID, VirtualAccount::ID_LENGTH);

            // Todo: Remove null-able after code deploy and back-filling
            $table->string(VirtualAccount::BALANCE_ID, VirtualAccount::ID_LENGTH)
                  ->nullable();

            $table->string(VirtualAccount::SOURCE)
                  ->nullable()
                  ->default(null);

            $table->integer(VirtualAccount::CREATED_AT);
            $table->integer(VirtualAccount::UPDATED_AT);

            $table->integer(VirtualAccount::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->integer(VirtualAccount::CLOSE_BY)
                  ->unsigned()
                  ->nullable();

            $table->integer(VirtualAccount::CLOSED_AT)
                  ->unsigned()
                  ->nullable();

            $table->foreign(VirtualAccount::CUSTOMER_ID)
                  ->references('id')
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(VirtualAccount::MERCHANT_ID)
                  ->references('id')
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(VirtualAccount::BANK_ACCOUNT_ID)
                  ->references('id')
                  ->on(Table::BANK_ACCOUNT)
                  ->on_delete('restrict');

            $table->index(VirtualAccount::VPA_ID);
            $table->index(VirtualAccount::OFFLINE_CHALLAN_ID);
            $table->index(VirtualAccount::DESCRIPTOR);
            $table->index(VirtualAccount::STATUS);
            $table->index(VirtualAccount::QR_CODE_ID);
            $table->index(VirtualAccount::CREATED_AT);
            $table->index(VirtualAccount::UPDATED_AT);
            $table->index(VirtualAccount::DELETED_AT);
            $table->index(VirtualAccount::CLOSE_BY);
            $table->index([VirtualAccount::ENTITY_ID, VirtualAccount::ENTITY_TYPE]);
            $table->index([VirtualAccount::MERCHANT_ID, VirtualAccount::CREATED_AT]);
            $table->index(VirtualAccount::BALANCE_ID, VirtualAccount::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::VIRTUAL_ACCOUNT, function($table)
        {
            $table->dropForeign(Table::VIRTUAL_ACCOUNT . '_' . VirtualAccount::CUSTOMER_ID . '_foreign');

            $table->dropForeign(Table::VIRTUAL_ACCOUNT . '_' . VirtualAccount::BANK_ACCOUNT_ID . '_foreign');

            $table->dropForeign(Table::VIRTUAL_ACCOUNT . '_' . VirtualAccount::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::VIRTUAL_ACCOUNT);
    }
}
