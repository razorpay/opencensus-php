<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Webhook\Entity as Webhook;
use RZP\Models\Merchant;

class CreateWebhooks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::WEBHOOK, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Webhook::ID, Webhook::ID_LENGTH)
                  ->primary();

            $table->char(Webhook::MERCHANT_ID, Webhook::ID_LENGTH);

            $table->tinyInteger(Webhook::ACTIVE)
                  ->default(1);

            $table->tinyInteger(Webhook::DISABLE_ON_FAILURE)
                  ->default(1);

            $table->string(Webhook::URL);

            $table->integer(Webhook::EVENTS);

            $table->string(Webhook::ENTITY_TYPE, 100)
                  ->nullable();

            $table->char(Webhook::ENTITY_ID, Webhook::ID_LENGTH)
                  ->nullable();

            $table->integer(Webhook::FAILURE_COUNT)
                  ->default(0);

            $table->integer(Webhook::LAST_SUCCESSFUL_AT)
                  ->nullable();

            $table->integer(Webhook::CREATED_AT);
            $table->integer(Webhook::UPDATED_AT);

            $table->text(Webhook::SECRET)
                  ->nullable();

            $table->unique([Webhook::MERCHANT_ID, Webhook::ENTITY_ID]);

            $table->index(Webhook::ACTIVE);
            $table->index(Webhook::CREATED_AT);

            $table->foreign(Webhook::MERCHANT_ID)
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
        Schema::table(Table::WEBHOOK, function($table)
        {
            $table->dropForeign(
                Table::WEBHOOK.'_'.Webhook::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::WEBHOOK);
    }
}
