<?php

use RZP\Constants\Table;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Merchant\Entity as Merchant;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Invitation\Entity as Invitation;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INVITATION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Invitation::ID);

            $table->char(Invitation::MERCHANT_ID, Invitation::ID_LENGTH);

            $table->char(Invitation::USER_ID, Invitation::ID_LENGTH)
                  ->nullable();

            $table->string(Invitation::EMAIL);

            $table->string(Invitation::TOKEN, Invitation::TOKEN_LENGTH)
                  ->unique();

            $table->string(Merchant::PRODUCT, 255)
                  ->default('primary');

            $table->string(Invitation::ROLE);

            $table->integer(Invitation::DELETED_AT)
                  ->nullable();

            $table->tinyInteger(Invitation::IS_DRAFT)
                ->nullable();

            $table->integer(Invitation::CREATED_AT);

            $table->integer(Invitation::UPDATED_AT);

            $table->index(Invitation::EMAIL);

            $table->index(Invitation::CREATED_AT);

            $table->index(Invitation::DELETED_AT);

            $table->foreign(Invitation::MERCHANT_ID)
                  ->references(Invitation::ID)
                  ->on(Table::MERCHANT);

            $table->foreign(Invitation::USER_ID)
                  ->references(Invitation::ID)
                  ->on(Table::USER);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::INVITATION, function(Blueprint $table)
        {
            $table->dropForeign(Table::INVITATION . '_' . Invitation::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::INVITATION . '_' . Invitation::USER_ID . '_foreign');
        });

        Schema::drop(Table::INVITATION);
    }
}
