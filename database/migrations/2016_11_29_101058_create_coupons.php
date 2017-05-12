<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Coupon\Entity as Coupon;
use RZP\Models\Merchant;

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

            $table->char(Payment::MERCHANT_ID, Payment::ID_LENGTH)
                  ->nullable();

            $table->char(Coupon::ENTITY_ID, Coupon::ID_LENGTH);

            $table->char(Coupon::ENTITY_TYPE);

            $table->integer(Coupon::EXPIRES_AT);

            $table->integer(Coupon::CREATED_AT);

            $table->integer(Coupon::UPDATED_AT);


            $table->foreign(COUPON::MERCHANT_ID)
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
        Schema::table(Table::COUPON, function($table)
        {
            $table->dropForeign(Table::COUPON.'_'.COUPON::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::COUPON);
    }
}
