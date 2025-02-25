

export type RazorpayUserMerchantBusinessDetail = {
    id: string;
    merchant_id: string;
    website_details: Record<string, unknown>;
    plugin_details: unknown;
    app_urls: unknown;
    blacklisted_products_category: unknown;
    business_parent_category: unknown;
    created_at: number;
    updated_at: number;
    lead_score_components: unknown;
    onboarding_source: unknown;
    pg_use_case: unknown;
    miq_sharing_date: number;
    testing_credentials_date: number;
    metadata: unknown;
    audit_id: null | string;
    gst_details: unknown;
  };