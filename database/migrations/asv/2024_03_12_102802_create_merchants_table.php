<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\Merchant\Entity;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(Table::MERCHANT, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->char('id', 14)->charset('utf8')->collation('utf8_bin')->primary();
            $table->string('name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('email', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('second_factor_auth')->default(0);
            $table->tinyInteger('restricted')->default(0);
            $table->char('parent_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('legal_entity_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('activated')->default(0);
            $table->integer('activated_at')->nullable();
            $table->integer('archived_at')->nullable();
            $table->integer('suspended_at')->nullable();
            $table->tinyInteger('live')->default(0);
            $table->string('live_disable_reason', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('hold_funds')->default(0);
            $table->string('hold_funds_reason', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('pricing_plan_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('category', 4)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('whitelisted_ips_live', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('whitelisted_ips_test', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('whitelisted_domains')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('dashboard_whitelisted_ips_live')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('dashboard_whitelisted_ips_test')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('partnership_url')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('category2', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('invoice_code', 12)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('notes')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('org_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('international')->default(0);
            $table->string('billing_label', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('display_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('channel', 32)->charset('utf8')->collation('utf8_bin')->default('kotak');
            $table->integer('settlement_schedule')->default(3);
            $table->char('settlement_schedule_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('transaction_report_email', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->unsignedTinyInteger('fee_bearer')->default(0);
            $table->tinyInteger('fee_model')->default(0);
            $table->unsignedBigInteger('fee_credits_threshold')->nullable();
            $table->tinyInteger('refund_source')->default(0);
            $table->tinyInteger('linked_account_kyc')->default(0);
            $table->tinyInteger('has_key_access')->default(0);
            $table->string('partner_type', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('brand_color', 6)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('handle', 4)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('activation_source', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('business_banking')->default(0);
            $table->tinyInteger('auto_capture_late_auth')->default(0);
            $table->text('logo_url')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('icon_url')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('invoice_label_field', 50)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->unsignedTinyInteger('risk_rating');
            $table->unsignedTinyInteger('risk_threshold')->nullable();
            $table->tinyInteger('receipt_email_enabled')->nullable();
            $table->unsignedBigInteger('max_payment_amount')->nullable();
            $table->tinyInteger(Entity::RECEIPT_EMAIL_TRIGGER_EVENT)->unsigned()->default(1);
            $table->integer('auto_refund_delay')->nullable();
            $table->enum('default_refund_speed', ['normal', 'optimum', 'instant'])->default('normal');
            $table->tinyInteger('convert_currency')->nullable();
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->string('external_id', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('product_international', 50)->charset('utf8')->collation('utf8_bin')->default('0000000000');
            $table->integer('free_payouts_consumed')->default(0);
            $table->unsignedInteger('free_payouts_consumed_last_reset_at')->nullable();
            $table->string('signup_source', 32)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('account_code', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->unsignedBigInteger('refund_credits_threshold')->nullable();
            $table->unsignedBigInteger('amount_credits_threshold')->nullable();
            $table->string('purpose_code', 5)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('fetch_coupons_url', 256)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('coupon_validity_url', 256)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('signup_via_email')->default(1);
            $table->unsignedBigInteger('balance_threshold')->nullable();
            $table->unsignedBigInteger('max_international_payment_amount')->nullable();
            $table->char('audit_id', 14)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
            $table->char('country_code', 2)->collation('utf8_bin')->default('IN');

            // Indexes
            $table->index('activated_at');
            $table->index('pricing_plan_id');
            $table->index('risk_rating');
            $table->index('email');
            $table->index('settlement_schedule_id');
            $table->index('org_id');
            $table->index('auto_refund_delay');
            $table->index('parent_id');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('legal_entity_id');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(Table::MERCHANT);
    }
};
