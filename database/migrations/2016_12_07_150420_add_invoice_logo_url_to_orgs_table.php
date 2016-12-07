<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceLogoUrlToOrgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->string('invoice_logo_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orgs', function (Blueprint $table) {
            $table->dropColumn('invoice_logo_url');
        });
    }
}
