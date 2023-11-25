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
  isOrgAllowedFunctionality: (featureName: string) => boolean;
  findTag: (tag: string) => boolean;
  isPartner: (args: string) => boolean;
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
  isPartnershipsInviteFlowEnabled: boolean;
  isInternationalMethodsHidden: boolean;
  isDirectTransferEnabled: boolean;
};

type UserProperties = {
  id: string;
  international: boolean;
  business_website: string;
  activation_status: string;
  international_activation_flow: string;
  current: string;
  email: string;
  contact_email: string;
  transaction_report_email: string;
  name: string;
  user: {
    name: string;
    email?: string;
    contact_mobile?: string;
  };
  merchant: {
    hold_funds: boolean;
    max_payment_amount: number;
    currency: string;
  };
  business_subcategory: BUSINESS_SUBCATEGORIES | string;
  isTransacted: boolean;
  isAllowedView: (args: string) => boolean;
  business_registered_address?: string;
  business_registered_address_l2?: string;
  business_operation_city?: string;
  business_operation_district?: string;
  business_registered_state?: string;
  business_registered_country?: string;
  business_registered_pin?: string;
};

// as user properties are not available initially
type User = Getters & Partial<UserProperties>;

export default User;
