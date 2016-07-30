<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant\FreeCredits\Entity as FreeCredits;
use RZP\Constants\Table;

class CreateFreeCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FREE_CREDITS, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->increments(FreeCredits::ID);

            $table->char(FreeCredits::CAMPAIGN, 255);
            $table->char(FreeCredits::MERCHANT_ID, FreeCredits::ID_LENGTH);

            $table->foreign(FreeCredits::MERCHANT_ID)
                ->references(FreeCredits::ID)
                ->on(Table::MERCHANT)
                ->on_delete('restrict');

            $table->integer(FreeCredits::CREDITS)->default(0);
            $table->text(FreeCredits::NOTES);

            // Timstamp logs of the model
            $table->integer(FreeCredits::CREATED_AT);
            $table->integer(FreeCredits::UPDATED_AT);

            // Indices
            $table->index(FreeCredits::CREATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::FREE_CREDITS, function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(
                Table::FREE_CREDITS.'_'.FreeCredits::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::FREE_CREDITS);
    }
}
