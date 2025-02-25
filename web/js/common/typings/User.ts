import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import { LADashboardUser } from '@libs/shared-types/la';
import { StrictMerge } from '@libs/shared-types';

/**
 * @deprecated Please use `RazorpayUserBusinessSubCategory` from `@libs/shared-types` instead. Will be removed soon.
 */
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

/**
 * @warning Please add necessary checks, anything available here should not be assumed available always.
 * @deprecated Please `PaymentsDashboardUser` from `@libs/shared-types/payments` or `LADashboardUser` from `@libs/shared-types/la` instead. For `window.rzp_user` type usage, use `RazorpayUser` from `@libs/shared-types` instead.
 */
type DeprecatedUserType = StrictMerge<PaymentsDashboardUser, LADashboardUser>;

export default DeprecatedUserType;
