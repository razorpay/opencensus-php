<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer\GatewayToken\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant;
use RZP\Models\Terminal;

class CreateGatewayTokens extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_TOKEN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::TERMINAL_ID, Entity::ID_LENGTH);

            $table->char(Entity::TOKEN_ID, Entity::ID_LENGTH);

            $table->string(Entity::REFERENCE, 20)
                  ->nullable();

            $table->text(Entity::ACCESS_TOKEN)
                  ->nullable();

            $table->text(Entity::REFRESH_TOKEN)
                  ->nullable();

            $table->tinyInteger(Entity::RECURRING)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::REFERENCE);
            $table->index(Entity::CREATED_AT);

            $table->foreign(Entity::TOKEN_ID)
                  ->references(Token\Entity::ID)
                  ->on(Table::TOKEN)
                  ->on_delete('restrict');

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::TERMINAL_ID)
                  ->references(Terminal\Entity::ID)
                  ->on(Table::TERMINAL)
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
        Schema::table(Table::GATEWAY_TOKEN, function($table)
        {
            $table->dropForeign(Table::GATEWAY_TOKEN . '_' . Entity::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::GATEWAY_TOKEN . '_' . Entity::TOKEN_ID . '_foreign');

            $table->dropForeign(Table::GATEWAY_TOKEN . '_' . Entity::TERMINAL_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_TOKEN);
    }
}
