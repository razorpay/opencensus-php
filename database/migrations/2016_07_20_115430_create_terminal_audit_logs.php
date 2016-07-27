<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Terminal\AuditLog\Entity as AuditLog;

class CreateTerminalAuditLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TERMINAL_AUDITLOG, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(AuditLog::ID, AuditLog::ID_LENGTH)
                ->primary();

            $table->char(AuditLog::PAYMENT_ID, AuditLog::ID_LENGTH);

            $table->char(AuditLog::TERMINAL_ID, AuditLog::ID_LENGTH);

            $table->boolean(AuditLog::STATUS)
                ->default(1);

            $table->double(AuditLog::RESPONSE_TIME,8,5)
                ->default(0);

            $table->integer(AuditLog::STATUS_CODE)
                ->default(0);

            $table->text(AuditLog::STATUS_MSG)
                ->nullable();

            $table->tinyInteger(AuditLog::PAYMENT_TYPE)
                ->default(0);

            $table->foreign(AuditLog::PAYMENT_ID)
                ->references(Payment\Entity::ID)
                ->on(Table::PAYMENT)
                ->on_delete('restrict');

            $table->foreign(AuditLog::TERMINAL_ID)
                ->references(Terminal\Entity::ID)
                ->on(Table::TERMINAL)
                ->on_delete('restrict');


            $table->integer(AuditLog::CREATED_AT);

            $table->integer(AuditLog::UPDATED_AT);

            $table->index(AuditLog::TERMINAL_ID);

            $table->index(AuditLog::CREATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TERMINAL_AUDITLOG, function($table)
        {
            $table->dropForeign(
                TABLE::TERMINAL_AUDITLOG.'_'.AuditLog::TERMINAL_ID.'_foreign');

            $table->dropForeign(
                TABLE::TERMINAL_AUDITLOG.'_'.AuditLog::PAYMENT_ID.'_foreign');
        });

        Schema::drop(Table::TERMINAL_AUDITLOG);
    }
}
