<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Offer;
use RZP\Models\Offer\Coupon\Entity as Coupon;

class CreateCoupons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::COUPON, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Coupon::ID, Coupon::ID_LENGTH)
                  ->primary();

            $table->char(Coupon::OFFER_ID, Coupon::ID_LENGTH);

            $table->integer(Coupon::EXPIRES_AT);

            $table->integer(Coupon::CREATED_AT);

            $table->integer(Coupon::UPDATED_AT);

            $table->foreign(Coupon::OFFER_ID)
                  ->references(Offer\Entity::ID)
                  ->on(Table::OFFER)
                  ->on_delete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::COUPON, function ($table)
        {
            $table->dropForeign(Table::COUPON . '_' . Coupon::OFFER_ID.'_foreign');
        });

        Schema::drop(Table::COUPON);
    }
}
