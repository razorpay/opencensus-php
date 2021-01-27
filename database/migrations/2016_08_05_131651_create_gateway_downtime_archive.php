<?php

use RZP\Constants\Table;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Terminal\Entity as Terminal;
use RZP\Models\Merchant\Entity as Merchant;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Gateway\Downtime\Entity as Downtime;

class CreateGatewayDowntimeArchive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_DOWNTIME_ARCHIVE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Downtime::ID, Downtime::ID_LENGTH)
                  ->primary();

            $table->string(Downtime::GATEWAY, 255)
                ->default(Downtime::ALL);

            $table->string(Downtime::ISSUER, 50)
                  ->default(Downtime::UNKNOWN);

            $table->string(Downtime::ACQUIRER, 30)
                  ->default(Downtime::UNKNOWN);

            $table->string(Downtime::REASON_CODE, 30);

            $table->string(Downtime::SOURCE, 30);

            $table->char(Downtime::TERMINAL_ID, Terminal::ID_LENGTH)
                  ->nullable();

            $table->string(Downtime::CARD_TYPE, 10)
                ->default(Downtime::UNKNOWN);

            $table->string(Downtime::NETWORK, 10)
                ->default(Downtime::UNKNOWN);

            $table->string(Downtime::METHOD, 30);

            $table->string(Downtime::PSP, 255)
                ->nullable();

            $table->string(Downtime::VPA_HANDLE, 255)
                ->nullable();

            $table->text(Downtime::COMMENT)
                  ->nullable();

            $table->integer(Downtime::BEGIN);

            $table->integer(Downtime::END)
                  ->nullable();

            $table->tinyInteger(Downtime::SCHEDULED)
                  ->default(0);

            $table->tinyInteger(Downtime::PARTIAL)
                  ->default(0);

            $table->char(Downtime::MERCHANT_ID, Merchant::ID_LENGTH)
                ->nullable();

            $table->integer(Downtime::CREATED_AT);

            $table->integer(Downtime::UPDATED_AT);

            $table->foreign(Downtime::TERMINAL_ID)
                  ->references(Terminal::ID)
                  ->on(Table::TERMINAL)
                  ->onDelete('restrict');

            $table->index(Downtime::ISSUER);

            $table->index(Downtime::ACQUIRER);

            $table->index(Downtime::GATEWAY);

            $table->index(Downtime::BEGIN);

            $table->index(Downtime::END);

            $table->index(Downtime::METHOD);

            $table->index(Downtime::CREATED_AT);

            $table->index(Downtime::REASON_CODE);

            $table->index(Downtime::SOURCE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::GATEWAY_DOWNTIME_ARCHIVE, function($table)
        {
            $table->dropForeign(
                Table::GATEWAY_DOWNTIME_ARCHIVE . '_' . Downtime::TERMINAL_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_DOWNTIME_ARCHIVE);
    }
}
