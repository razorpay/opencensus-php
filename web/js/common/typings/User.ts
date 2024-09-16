import { CountryCodeType } from '@razorpay/i18nify-js';

// Todo: delete this file, it's available in @dashboard/shared-utils
export enum BUSINESS_SUBCATEGORIES {
  Aviation = 'aviation',
  Charity = 'charity',
  Food_Court = 'food_court',
  Online_Food_Ordering = 'online_food_ordering',
  Restaurant = 'restaurant',
  Catering = 'catering',
  Alcohol = 'alcohol',
  Restaurant_Search_and_Booking = 'restaurant_search_and_booking',
  Nbfc = 'nbfc',
  Lending = 'lending',
  Pharmacy = 'pharmacy',
  Health_Products = 'health_products',
  Healthcare_Marketplace = 'healthcare_marketplace',
  Medical_Equipment_And_Supply_Stores = 'medical_equipment_and_supply_stores',
  Trading = 'trading',
  Financial_Advisor = 'financial_advisor',
  Securities = 'securities',
  Commodities = 'commodities',
  Forex = 'forex',
  Mutual_Fund = 'mutual_fund',
  Internet_Provider = 'internet_provider',
  Broadband = 'broadband',
  Facility_Management = 'facility_management',
  Coworking = 'coworking',
  Space_Rental = 'space_rental',
  Game_Developer = 'game_developer',
  Esports = 'esports',
  Online_Casino = 'online_casino',
  Fantasy_Sports = 'fantasy_sports',
  Gaming_Marketplace = 'gaming_marketplace',
}

type Getters = {
  findTag: (tag: string) => boolean;
  isAllowedView: (args: string) => boolean;
  isFeatureEnabled: (args: string) => boolean;
  isOrgAllowedFunctionality: (featureName: string) => boolean;
  isPartner: (...args: string[]) => boolean;
  isApmOnboardingEnabled: boolean;
  isAccountAndSettingsRevampEnabled: boolean;
  isIERevampEnabled: boolean;
  isAccepted: boolean;
  isUnregisteredBusiness: boolean;
  isRevampedReportsEnabled: {
    merchant: boolean;
    partner: boolean;
    la: boolean;
    schedules: boolean;
    overviewRecents: boolean;
  };
  isMarketplaceEnabled: boolean;
  isSupportRole: boolean;
  isSettlementV3RevampEnabled: boolean;
  isSingleReconEnabled: boolean;
  isOptimizerEnabled: boolean;
  isOptimizerRZPVASEnabled: boolean;
  isWebsiteComplianceFlowEnabled: boolean;
  isBundlePricingEnabled: boolean;
  isCustomReportExtensionsEnabled: boolean;
  isPaymentPageFileUploadEnabled: boolean;
  isSodexoInstrumentEnabled: boolean;
  isSearchv2Phase1Enabled: boolean;
  isRefundPendingStatusEnabled: boolean;
  isOrgCurlec: boolean;
  isOrgRZP: boolean;
  isPartnershipForCapitalEnabled: boolean;
  isInternationalMethodsHidden: boolean;
  isDirectTransferEnabled: boolean;
  isHidePIDetails: boolean;
  isSubMerchantKycEnabled: boolean;
  isPartnerAgentRole: boolean;
  isPartnerRole: boolean;
  isRiskAndFraudEnabled: boolean;
  isOmniChannelMerchant: boolean;
  isOmniEnabledMerchant: boolean;
  isRazorxAnnouncementEnabled: boolean;
  isActivated: boolean;
  isHideMonthlyInvoiceEnabled: boolean;
  isINCountry: boolean;
  isSGCountry: boolean;
  isVasTestingMerchant: boolean;
  isMultiCouponsEnabled: boolean;
  isAdminOrOwner: boolean;
  isOwner: boolean;
  isAdditionalDomainWhitelistSelfServeOn: boolean;
  isRRNSearchEnabled: boolean;
  isMagicCouponEngineEnabled: boolean;
};

type Merchant = {
  id: string;
  hold_funds: boolean;
  max_payment_amount: number;
  currency: string;
  pricing_plan_id?: string;
  name: string;
  country_code: CountryCodeType;
  display_name: string;
  email: string;
};

type UserProperties = {
  id: string;
  international: boolean;
  business_website: string;
  activation_status: string;
  additional_websites: [];
  appstore_url: string;
  international_activation_flow: string;
  current: string;
  email: string;
  contact_email: string;
  contact_mobile: number;
  transaction_report_email: string;
  name: string;
  role: string;
  user: {
    id: string;
    name: string;
    email?: string;
    contact_mobile?: string;
    signup_campaign?: string;
  };
  merchant: Merchant;
  merchants: {
    [key: string]: Merchant;
  };
  business_type: string;
  business_subcategory: BUSINESS_SUBCATEGORIES | string;
  isTransacted: boolean;
  pos_activation_status?: string;
  pos_kyc_deadline_date?: number;
  documents: {
    shop_front?: [];
    shop_interior?: [];
  };
  created_at: number;
  submitted: boolean;
  pos_activation_flow: 'blacklist' | 'whitelist' | 'greylist';
  kyc_terms_and_conditions_checked?: boolean;
  business_registered_address?: string;
  business_registered_address_l2?: string;
  business_operation_city?: string;
  business_operation_district?: string;
  business_registered_state?: string;
  business_registered_country?: string;
  business_registered_pin?: string;
  merchant_business_detail: {
    website_details: {
      physical_store: boolean;
    };
  };
  tags: string[];
  is_pgos_merchant: boolean;
  configTags: any;
  logo_url?: string;
  playstore_url: string;
  has_key_access: boolean;
};

// as user properties are not available initially
type User = Getters & Partial<UserProperties>;

export default User;
