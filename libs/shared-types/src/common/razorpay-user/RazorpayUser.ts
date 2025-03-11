import { RazorpayUserBusinessSubCategory } from './RazorpayUserBusinessSubCategory';
import { RazorpayUserExperimentResult } from './RazorpayUserExperimentResult';
import { RazorpayUserMerchant } from './RazorpayUserMerchant';
import { RazorpayUserMerchantAvgOrderValue } from './RazorpayUserMerchantAvgOrderValue';
import { RazorpayUserMerchantBusinessDetail } from './RazorpayUserMerchantBusinessDetail';
import { RazorpayUserMerchantMethods } from './RazorpayUserMerchantMethods';
import { RazorpayUserPreSignup } from './RazorpayUserPreSignup';
import { RazorpayUserStakeholder } from './RazorpayUserStakeholder';
import { RazorpayUserUser } from './RazorpayUserUser';

/**
 * Main Razorpay user export type with nested objects.
 */
type RazorpayUserType = {
  /** Unique identifier for the current merchant session */
  current: string;

  /** User object containing user-specific details */
  user: RazorpayUserUser;

  /** Pre-signup information */
  pre_signup: RazorpayUserPreSignup;

  /** Boolean flag indicating if pre-signup is complete */
  pre_signup_complete: boolean;

  /** Object containing experiments information */
  experiments: Record<string, RazorpayUserExperimentResult>;

  /** List of merchants associated with the user */
  merchants: RazorpayUserMerchant[];

  /** Sign-up campaign identifier, if any */
  signup_campaign: string | null;

  /** Boolean flag indicating if two-factor authentication is verified */
  two_fa_verified: boolean;

  /** Boolean flag indicating if OAuth login is enabled */
  oauth_login: boolean;

  /** Object for managing Splitz experiments */
  splitz_experiments: Record<string, RazorpayUserExperimentResult>;

  /** Partner intent object, if any */
  partner_intent: string | null;

  instant_activations: true;
  business_parent_category: unknown;
  promoter_pan_name_suggested: unknown;
  business_name_suggested: unknown;
  business_registered_address_suggested: unknown;
  allowed_next_pos_activation_status: unknown;
  pos_activation_flow: unknown;
  is_pgos_merchant: boolean;
  playstore_url: string;
  appstore_url: string;
  isTransacted: boolean;
  isSubMerchant: boolean;
  credit_balance: unknown[];
  verification_error_codes: unknown[];
  status_change_agent: unknown;
  lock_common_fields: unknown[];
  merchant_business_detail: RazorpayUserMerchantBusinessDetail;
  isDedupe: boolean;
  isAutoKycDone: boolean;
  isHardLimitReached: boolean;
  activationStatusChangeLogs: Record<
    string,
    {
      activation_status: string;
    }
  >[];
  posActivationStatusChangeLogs: unknown;
  dedupe: {
    isMatch: boolean;
    isUnderReview: boolean;
  };
  features: string[];
  tags: string[];
  merchant_verification_detail: unknown[];
  isRiskyMerchant: boolean;
  merchant_avg_order_value: RazorpayUserMerchantAvgOrderValue;
  stakeholder: RazorpayUserStakeholder;
  can_submit: boolean;
  live: boolean;
  international: boolean;
  verification: {
    status: string;
    activation_progress: number;
    disabled_reason: string;
  };

  /** Indicates if the current user is the primary owner of the merchant account */
  primaryOwner: boolean;

  /** The waitlist number for current account activation (if any) */
  current_account_waitlist_number: unknown;

  /** Name of the contact person */
  contact_name: string;

  /** Email of the contact person */
  contact_email: string;

  /** Mobile number of the contact person, can be null if not provided */
  contact_mobile: string | null;

  /** Landline number of the contact person (if any) */
  contact_landline: unknown;

  /** Business type identifier */
  business_type: string;

  /** Business name registered for the merchant */
  business_name: string;

  /** Description of the business (if available) */
  business_description: unknown;

  /** Doing Business As (DBA) name (if different from business_name) */
  business_dba: unknown;

  /** Website of the business */
  business_website: string;

  /** Indicates if the business operates internationally */
  business_international: boolean;

  /** Payment details of the business */
  business_paymentdetails: unknown;

  /** Registered address of the business */
  business_registered_address: unknown;

  /** Registered address line 2 of the business */
  business_registered_address_l2: unknown;

  /** Registered country of the business */
  business_registered_country: unknown;

  /** Registered state of the business */
  business_registered_state: unknown;

  /** Registered city of the business */
  business_registered_city: unknown;

  /** Registered district of the business */
  business_registered_district: unknown;

  /** Registered postal code of the business */
  business_registered_pin: unknown;

  /** Operational address of the business */
  business_operation_address: unknown;

  /** Operational address line 2 of the business */
  business_operation_address_l2: unknown;

  /** Operational country of the business */
  business_operation_country: unknown;

  /** Operational state of the business */
  business_operation_state: unknown;

  /** Operational city of the business */
  business_operation_city: unknown;

  /** Operational district of the business */
  business_operation_district: unknown;

  /** Operational postal code of the business */
  business_operation_pin: unknown;

  /** PAN of the business promoter */
  promoter_pan: unknown;

  /** Name associated with the promoter PAN */
  promoter_pan_name: unknown;

  /** Date of establishment of the business */
  business_doe: unknown;

  /** GSTIN of the business (if registered) */
  gstin: unknown;

  /** Provisional GSTIN of the business (if available) */
  p_gstin: unknown;

  /** Corporate Identity Number (CIN) of the business (if applicable) */
  company_cin: unknown;

  /** PAN of the company */
  company_pan: unknown;

  /** Name associated with the company PAN */
  company_pan_name: unknown;

  /** Category of the business */
  business_category: string | null;

  /** Subcategory of the business */
  business_subcategory: RazorpayUserBusinessSubCategory;

  /** Business model of the company */
  business_model: unknown;

  /** Transaction volume of the business */
  transaction_volume: unknown;

  /** Transaction value of the business */
  transaction_value: unknown;

  /** "About Us" section of the business website */
  website_about: unknown;

  /** Contact information on the business website */
  website_contact: unknown;

  /** Privacy policy URL of the business website */
  website_privacy: unknown;

  /** Terms and conditions URL of the business website */
  website_terms: unknown;

  /** Refund policy URL of the business website */
  website_refund: unknown;

  /** Pricing details URL of the business website */
  website_pricing: unknown;

  /** Login page URL of the business website */
  website_login: unknown;

  /** Steps finished during the activation process */
  steps_finished: string;

  /** Progress made in the activation process (out of 100) */
  activation_progress: number;

  /** Status indicating if the account is locked */
  locked: number;

  /** Current status of the account activation process */
  activation_status: string;

  /** Status of bank details verification */
  bank_details_verification_status: unknown;

  /** Status of proof of address (POA) verification */
  poa_verification_status: unknown;

  /** Status of proof of identity (POI) verification */
  poi_verification_status: unknown;

  /** Mode of clarification required for activation */
  clarification_mode: unknown;

  /** Status indicating if the account is archived */
  archived: number;

  /** List of allowed next activation statuses */
  allowed_next_activation_statuses: unknown[];

  /** Status of marketplace activation */
  marketplace_activation_status: unknown;

  /** Status of virtual account activation */
  virtual_accounts_activation_status: unknown;

  /** Status of subscription activation */
  subscriptions_activation_status: unknown;

  /** Indicates if the account has been submitted for verification */
  submitted: number;

  /** Timestamp when the account was submitted */
  submitted_at: unknown;

  /** Email where transaction reports are sent */
  transaction_report_email: string | null;

  /** Bank account number of the merchant */
  bank_account_number: unknown;

  /** Name of the bank where the account is held */
  bank_name: unknown;

  /** Name associated with the bank account */
  bank_account_name: unknown;

  /** Type of bank account (e.g., current, savings) */
  bank_account_type: unknown;

  /** Branch of the bank */
  bank_branch: unknown;

  /** IFSC code of the bank branch */
  bank_branch_ifsc: unknown;

  /** Branch code of the bank */
  bank_branch_code: unknown;

  /** Type of branch code */
  bank_branch_code_type: unknown;

  /** Beneficiary address line 1 */
  bank_beneficiary_address1: unknown;

  /** Beneficiary address line 2 */
  bank_beneficiary_address2: unknown;

  /** Beneficiary address line 3 */
  bank_beneficiary_address3: unknown;

  /** City of the beneficiary address */
  bank_beneficiary_city: unknown;

  /** State of the beneficiary address */
  bank_beneficiary_state: unknown;

  /** Postal code of the beneficiary address */
  bank_beneficiary_pin: unknown;

  /** Department associated with the merchant */
  department: unknown;

  /** Activation flow type for the account */
  activation_flow: unknown;

  /** International activation flow type */
  international_activation_flow: unknown;

  /** Status indicating if a live transaction has been completed */
  live_transaction_done: number;

  /** Reasons for KYC clarification (if applicable) */
  kyc_clarification_reasons: {
    nc_count: number;
  };

  /** Additional details required for KYC verification */
  kyc_additional_details: unknown;

  /** List of additional websites associated with the merchant */
  additional_websites: unknown[];

  /** Year the business was established */
  estd_year: unknown;

  /** Residential address of the authorized signatory */
  authorized_signatory_residential_address: unknown;

  /** Date of birth of the authorized signatory */
  authorized_signatory_dob: unknown;

  /** Platform associated with the merchant */
  platform: unknown;

  /** Fund account validation ID (if available) */
  fund_account_validation_id: unknown;

  /** Status of GSTIN verification */
  gstin_verification_status: unknown;

  /** Date the business was established */
  date_of_establishment: unknown;

  /** Milestone reached in the activation form process */
  activation_form_milestone: unknown;

  /** Status of company PAN verification */
  company_pan_verification_status: unknown;

  /** Status of company CIN verification */
  cin_verification_status: unknown;

  /** Status of company PAN document verification */
  company_pan_doc_verification_status: unknown;

  /** Status of personal PAN document verification */
  personal_pan_doc_verification_status: unknown;

  /** Status of bank details document verification */
  bank_details_doc_verification_status: unknown;

  /** Status of MSME document verification */
  msme_doc_verification_status: unknown;

  /** Shop establishment number */
  shop_establishment_number: unknown;

  /** Status of shop establishment verification */
  shop_establishment_verification_status: unknown;

  /** List of client applications associated with the merchant */
  client_applications: unknown[];

  /** Suggested business postal code (if any) */
  business_suggested_pin: unknown;

  /** Suggested business address (if any) */
  business_suggested_address: unknown;

  /** Fraud type associated with the business (if any) */
  fraud_type: unknown;

  /** Business ID associated with BAS (Business Administration System) */
  bas_business_id: unknown;

  /** Importer Exporter Code (IEC) of the business */
  iec_code: unknown;

  /** Industry category code for the business */
  industry_category_code: unknown;

  /** Type of industry category code */
  industry_category_code_type: unknown;

  /** Status of POS (Point of Sale) activation */
  pos_activation_status: string;

  /** Fuzzy score for bank details matching */
  bank_details_fuzzy_score: unknown;

  /** List of documents associated with the merchant */
  documents: unknown[];

  /** Indicates if the shop establishment is in a verifiable zone */
  shop_establishment_verifiable_zone: boolean;

  /** Payment methods available for the user */
  methods: RazorpayUserMerchantMethods;

  /** List of active campaigns */
  campaigns: unknown[];

  /** Merchant ID */
  id: string;

  /** Name of the merchant */
  name: string;

  /** Billing label for the merchant */
  billing_label: string;

  /** Merchant email address */
  email: string;

  /** Indicates if the merchant is activated (1 means active, 0 means inactive) */
  activated: number;

  /** Timestamp of merchant activation (or activation date as string) */
  activated_at: number | string;

  /** Timestamp when the merchant was archived (or archive date as string) */
  archived_at: number | string;

  /** Timestamp when the merchant was suspended (or suspension date as string) */
  suspended_at: number | string;

  /** Indicates if the merchant has key access */
  has_key_access: boolean;

  /** URL of the merchant's logo (or null if not set) */
  logo_url: null | string;

  /** Display name for the merchant (or null if not set) */
  display_name: null | string;

  /** Refund source for the merchant (e.g., balance, bank) */
  refund_source: string;

  /** Partner type (e.g., 'pure_platform') */
  partner_type: 'pure_platform' | string;

  /** Indicates if the merchant has restricted access */
  restricted: string;

  /** Timestamp when the merchant account was created */
  created_at: number;

  /** Timestamp when the merchant account was last updated */
  updated_at: number;

  /** Indicates if the merchant has two-factor authentication enabled */
  second_factor_auth: boolean;

  /** Parent ID of the merchant (or null if not applicable) */
  parent_id: null | string;

  /** Parent name of the merchant (or null if not applicable) */
  parent_name: null | string;

  /** Country code of the merchant's registered country */
  country_code: string;

  /** Role of the merchant user (e.g., 'owner') */
  role: 'owner' | string;

  /** Product associated with the merchant (e.g., 'primary') */
  product: 'primary';

  /** Banking role associated with the merchant (or null if not set) */
  banking_role: null | string;

  mcc_verification_status: string;

  isCountryIndia: boolean;

  fee_based_gating: {
    is_eligible?: boolean;
  };

  rekyc_status: string;
  bdd_verification_status: string;
  manual_rekyc: {
    status: {
      created_at: string;
      rekyc_status: string;
    }[];
  };

  merchant: {
    hold_funds: boolean;
    max_payment_amount: number;
    currency: string;
    country_code: string;
    id: string;
  };
};

// Making it as partial as we can't predict the exact response always.
// With this, devs will add necessary conditions to validate
export type RazorpayUser = Partial<RazorpayUserType>;
