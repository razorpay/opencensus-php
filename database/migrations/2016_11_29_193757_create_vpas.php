<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Vpa\Entity;
use RZP\Models\Merchant\Entity as Merchant;

class CreateVpas extends Migration
{
    const UTF8MB4 = 'utf8mb4';

    const UTF8MB4_0900_AI_CI = 'utf8mb4_0900_ai_ci';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::VPA, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::ENTITY_TYPE, 40)
                  ->nullable();

            // Used the charset and collation present currently on production for username and handle
            $table->char(Entity::USERNAME, 255)
                  ->charset(self::UTF8MB4)
                  ->collation(self::UTF8MB4_0900_AI_CI);

            $table->char(Entity::HANDLE, 255)
                  ->charset(self::UTF8MB4)
                  ->collation(self::UTF8MB4_0900_AI_CI);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index([Entity::USERNAME, Entity::HANDLE]);

            $table->index(Entity::MERCHANT_ID);

            $table->integer(Entity::FTS_FUND_ACCOUNT_ID)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::FTS_FUND_ACCOUNT_ID);

            $table->index([Entity::ENTITY_ID, Entity::ENTITY_TYPE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::VPA, function($table)
        {
            $table->dropForeign(Table::VPA . '_' . Entity::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::VPA);
    }
}
