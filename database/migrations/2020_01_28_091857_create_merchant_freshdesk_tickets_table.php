<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\FreshdeskTicket\Entity;

class CreateMerchantFreshdeskTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_FRESHDESK_TICKETS, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::TICKET_ID);

            $table->char(Entity::TYPE);

            $table->char(Entity::CREATED_BY, 64)->nullable();

            $table->text(Entity::TICKET_DETAILS);

            $table->tinyInteger(Entity::STATUS)->default(0);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::TICKET_ID);

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
        Schema::dropIfExists(Table::MERCHANT_FRESHDESK_TICKETS);
    }
}
