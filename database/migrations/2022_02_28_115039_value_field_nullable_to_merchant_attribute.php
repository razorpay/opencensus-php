<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ValueFieldNullableToMerchantAttribute extends Migration
{
    /**
     * Run the migrations.
     * Converting merchant attribute value from non null to null
     * @return void
     */
    public function up()
    {
        Schema::table('merchant_attributes', function (Blueprint $table) {
            $table->string('value')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant_attributes', function (Blueprint $table) {
            $table->string('value')->nullable(false)->change();
        });
    }
}
