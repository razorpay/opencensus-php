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
        Schema::create('merchant_documents', function (Blueprint $table) {
            $table->char('id', 14)->primary();
            $table->char('file_store_id', 14)->nullable();
            $table->enum('source', ['API', 'UFH'])->default('API');
            $table->char('merchant_id', 14);
            $table->string('document_type', 255);
            $table->string('entity_type', 255);
            $table->string('ocr_verify', 30)->nullable();
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->integer('deleted_at')->nullable();
            $table->char('validation_id', 14)->nullable();
            $table->char('entity_id', 14)->nullable();
            $table->unsignedInteger('document_date')->nullable();
            $table->char('upload_by_admin_id', 14)->nullable();
            $table->char('audit_id', 14)->nullable();
            $table->json('metadata')->nullable();

            // Indexes
            $table->index('merchant_id');
            $table->index('file_store_id');
            $table->index('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_documents');
    }
};
