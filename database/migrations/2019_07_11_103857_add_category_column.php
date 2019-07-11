<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Gateway\Rule\Entity as Rule;

class AddCategoryColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gateway_rules', function (Blueprint $table) {
            $table->string("category")
                ->nullable();

            $table->index(Rule::CATEGORY);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gateway_rules', function (Blueprint $table) {
            $table->dropColumn("category");
        });
    }
}
