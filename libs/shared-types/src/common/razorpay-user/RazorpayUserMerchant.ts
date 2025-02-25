import { RazorpayUserMerchantMethods } from "./RazorpayUserMerchantMethods";





/**
 * Represents merchant details within RazorpayUser.
 */
export type RazorpayUserMerchant = {
    /** Merchant ID */
    id: string;
  
    /** Merchant name */
    name: string;
  
    /** Merchant billing label */
    billing_label: string;
  
    /** Merchant email */
    email: string;
  
    /** Boolean flag indicating if the merchant is activated */
    activated: boolean;
  
    /** Timestamp of merchant activation, if any */
    activated_at: number | null;
  
    /** Boolean flag indicating if the merchant is archived */
    archived_at: number | null;
  
    /** Boolean flag indicating if the merchant is suspended */
    suspended_at: number | null;
  
    /** Boolean flag for key access */
    has_key_access: boolean;
  
    /** Merchant logo URL */
    logo_url: string | null;
  
    /** Display name for the merchant */
    display_name: string | null;
  
    /** Refund source for the merchant */
    refund_source: string;
  
    /** Partner export type for the merchant */
    partner_type: string;
  
    /** Boolean flag for restricted merchant access */
    restricted: boolean;
  
    /** Timestamp for merchant creation */
    created_at: number;
  
    /** Timestamp for merchant update */
    updated_at: number;
  
    /** Boolean flag for second factor authentication */
    second_factor_auth: boolean;
  
    /** Parent ID for the merchant, if any */
    parent_id: string | null;
  
    /** Parent name for the merchant, if any */
    parent_name: string | null;
  
    /** Country code for the merchant */
    country_code: string;
  
    /** Role of the user within the merchant */
    role: string;
  
    /** Product export type for the merchant */
    product: string;
  
    /** Banking role for the merchant */
    banking_role: string | null;
  
    /** Payment methods available for the merchant */
    methods: RazorpayUserMerchantMethods;
  };
  