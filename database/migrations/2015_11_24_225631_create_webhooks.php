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

            $table->string(Webhook::URL);

            $table->integer(Webhook::EVENTS);

            $table->integer(Webhook::FAILURE_COUNT)
                  ->default(0);

            $table->integer(Webhook::LAST_SUCCESSFUL_AT)
                  ->nullable();

            $table->integer(Webhook::CREATED_AT);
            $table->integer(Webhook::UPDATED_AT);

            $table->text(Webhook::SECRET)
                  ->nullable();

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
                TABLE::WEBHOOK.'_'.Webhook::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::WEBHOOK);
    }
}
