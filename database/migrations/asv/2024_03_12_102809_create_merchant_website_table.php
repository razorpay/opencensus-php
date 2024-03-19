<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_website', function (Blueprint $table) {
            $table->char('id', 14)->charset('utf8mb4')->collation('utf8mb4_bin');
            $table->char('merchant_id', 14)->charset('utf8mb4')->collation('utf8mb4_bin');
            $table->string('deliverable_type', 255)->nullable();
            $table->string('shipping_period', 255)->nullable();
            $table->string('refund_request_period', 255)->nullable();
            $table->string('refund_process_period', 255)->nullable();
            $table->string('warranty_period', 255)->nullable();
            $table->char('status', 50)->nullable();
            $table->tinyInteger('grace_period')->nullable();
            $table->tinyInteger('send_communication')->default(1);
            $table->json('merchant_website_details')->nullable();
            $table->json('admin_website_details')->nullable();
            $table->json('additional_data')->nullable();
            $table->char('audit_id', 14)->charset('utf8mb4')->collation('utf8mb4_bin');

            $table->integer('created_at')->nullable(false);
            $table->integer('updated_at')->nullable(false);

            $table->primary('id');
            $table->index('merchant_id', 'merchant_website_merchant_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_website');
    }
};
