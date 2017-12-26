<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Terminal\Mode;

class CreateTerminals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Terminal::ID, Terminal::ID_LENGTH)
                  ->primary();

            $table->char(Terminal::MERCHANT_ID, Terminal::ID_LENGTH);

            $table->integer(Terminal::USED_COUNT)
                  ->unsigned()
                  ->default(0);

            $table->tinyInteger(Terminal::USED)
                  ->default(0);

            $table->integer(Terminal::CATEGORY)
                  ->unsigned()
                  ->nullable();

            $table->string(Terminal::GATEWAY);

            $table->string(Terminal::GATEWAY_MERCHANT_ID)
                  ->nullable();

            $table->string(Terminal::GATEWAY_MERCHANT_ID2)
                  ->nullable();

            $table->string(Terminal::GATEWAY_TERMINAL_ID)
                  ->nullable();

            $table->text(Terminal::GATEWAY_TERMINAL_PASSWORD)
                  ->nullable();

            $table->string(Terminal::GATEWAY_ACCESS_CODE)
                  ->nullable();

            $table->string(Terminal::GATEWAY_SECURE_SECRET)
                  ->nullable();

            $table->text(Terminal::GATEWAY_RECON_PASSWORD)
                  ->nullable();

            $table->string(Terminal::GATEWAY_ACQUIRER)
                  ->nullable();

            $table->text(Terminal::GATEWAY_CLIENT_CERTIFICATE)
                  ->nullable();

            $table->tinyInteger(Terminal::CARD)
                  ->default(0);

            $table->tinyInteger(Terminal::NETBANKING)
                  ->default(0);

            $table->tinyInteger(Terminal::UPI)
                  ->default(0);

            $table->tinyInteger(Terminal::AEPS)
                  ->default(0);

            $table->tinyInteger(Terminal::EMANDATE)
                  ->default(0);

            $table->tinyInteger(Terminal::EMI)
                  ->default(0);

            $table->integer(Terminal::EMI_DURATION)
                  ->nullable();

            $table->string(Terminal::EMI_SUBVENTION)
                  ->nullable();

            $table->tinyInteger(Terminal::RECURRING)
                  ->unsigned()
                  ->default(1);

            $table->tinyInteger(Terminal::INTERNATIONAL)
                  ->default(0);

            $table->tinyInteger(Terminal::SHARED)
                   ->default(0);

            $table->tinyInteger(Terminal::TPV)
                  ->default(0);

            $table->tinyInteger(Terminal::TYPE)
                  ->unsigned()
                  ->default(1);

            $table->tinyInteger(Terminal::MODE)
                  ->default(Mode::DUAL);

            $table->tinyInteger(Terminal::CORPORATE)
                  ->default(0);

            $table->string(Terminal::CURRENCY, 3)
                  ->default(Terminal::DEFAULT_CURRENCY);

            $table->string(Terminal::NETWORK_CATEGORY)
                  ->nullable();

            $table->integer(Terminal::CREATED_AT);

            $table->integer(Terminal::UPDATED_AT);

            $table->integer(Terminal::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(Terminal::ENABLED)
                  ->default(1);

            $table->foreign(Terminal::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            // Needed for future
            //$table->integer(Terminal::PRIORITY)
            //      ->default(5);

            $table->index(Terminal::CATEGORY);
            $table->index(Terminal::GATEWAY);
            $table->index(Terminal::DELETED_AT);
            $table->index(Terminal::ENABLED);
            $table->index(Terminal::NETWORK_CATEGORY);
            $table->index(Terminal::GATEWAY_MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TERMINAL, function($table)
        {
            $table->dropForeign(
                Table::TERMINAL.'_'.Terminal::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::TERMINAL);
    }
}
