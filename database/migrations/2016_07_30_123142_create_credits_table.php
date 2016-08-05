<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits\Entity as Credits;

class CreateCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDITS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Credits::ID);

            $table->string(Credits::CAMPAIGN, 255);
            $table->string(Credits::MERCHANT_ID, Merchant\Entity::ID_LENGTH);
            $table->integer(Credits::VALUE)->default(0);
            $table->text(Credits::NOTES);

            $table->integer(Credits::CREATED_AT);
            $table->integer(Credits::UPDATED_AT);

            $table->index(Credits::CREATED_AT);
            $table->index(Credits::CAMPAIGN);

            $table->foreign(Credits::MERCHANT_ID)
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
        Schema::drop(Table::CREDITS, function (Blueprint $table)
        {
            $table->dropForeign(
                Table::CREDITS.'_'.Credits::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::CREDITS);
    }
}
