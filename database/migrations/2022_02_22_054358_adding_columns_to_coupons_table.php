<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Coupon\Entity as Coupon;
use RZP\Constants\Table;
class AddingColumnsToCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::COUPON, function (Blueprint $table) {
            $table->boolean(Coupon::IS_INTERNAL)
                ->nullable();
            $table->json(Coupon::ALERTS)
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            //
        });
    }
}
