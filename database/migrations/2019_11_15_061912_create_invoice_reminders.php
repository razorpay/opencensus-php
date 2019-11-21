<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Invoice\Reminder\Entity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INVOICE_REMINDER, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::INVOICE_ID, Entity::ID_LENGTH);

            $table->char(Entity::REMINDER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::REMINDER_STATUS, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::INVOICE_ID);

            $table->index(Entity::REMINDER_ID);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::INVOICE_REMINDER);
    }
}
