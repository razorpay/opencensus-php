import { isMobileResolution } from 'common/utils/rzp-utils';
import { isOrgFeatureExist } from 'merchant/models/User';
import { canViewCashAdvanceProduct, canViewLOCEMIProduct } from 'merchant/views/Capital/utils';
import { SIDEEBAR_PRODUCTS_TITLES } from 'merchant/components/SidebarV2/constants/constants';

export const PRODUCTS_DATA = {
  home: {
    icon: 'i-chart',
    additionalCondition: (user: any): boolean => user.isAllowedView('home'),
  },
  transactions: {
    icon: 'i-repeat',
    additionalCondition: (user: any): boolean => user.isAllowedMultiple('payments orders refunds'),
  },
  settlements: {
    icon: 'i-done-all',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('settlements') &&
      !user.findTag('i18_hide_settlements') &&
      user.hideForNIASupportRole,
  },
  settings: {
    icon: 'i-settings',
    additionalCondition: (user: any): boolean =>
      user.isAllowedMultiple('webhooks applications configuration api_keys') &&
      !user.isAccountAndSettingsRevampEnabled,
  },
  developers: {
    icon: 'i-developers developers-sidebar-icon',
    additionalCondition: (user: any): boolean =>
      !isMobileResolution() &&
      user.isAllowedView('developers_console') &&
      (user.isDeveloperConsoleEnabled || user.isDeveloperConsoleWebhooksTabEnabled),
  },
  my_account: {
    icon: 'i-account',
    additionalCondition: (user: any): boolean =>
      user.isAllowedMultiple('profile credits add_funds team referrals') &&
      !user.isAccountAndSettingsRevampEnabled,
  },
  reports: {
    icon: 'i-books',
    additionalCondition: (user: any): boolean =>
      (user.isAllowedView('reports') || user.isCareHealthOwner) && user.hideForNIASupportRole,
  },
  x_corporate_cards: {
    icon: 'i-credit-card',
    additionalCondition: (user: any): boolean => user.isCardsLOSEnabled,
  },
  loans: {
    icon: 'i-rupee',
    additionalCondition: (user: any): boolean => user.isAllowedView('loans') && user.isLoansEnabled,
  },
  working_capital_loans: {
    icon: 'i-rupee',
    additionalCondition: (user: any): boolean => user.isNonFldgLoansEnabled,
  },
  checkout_rewards: {
    icon: 'i-rewards',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('checkoutrewards') && !user.findTag('i18_hide_checkoutrewards'),
  },
  offers: {
    icon: 'i-offer',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('offers') && !user.findTag('i18_hide_offers'),
  },
  customers: {
    icon: 'i-people',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('customers') && !user.findTag('i18_hide_customers'),
  },
  optimizer: {
    icon: 'i-routing',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('optimizer') &&
      (user.isOptimizerEnabled || user.isOptimizerOnboardingEnabled),
  },
  bbps: {
    icon: 'i-chart',
    additionalCondition: (user: any): boolean => user.isAllowedView('bbps') && user.isBbpsEnabled,
  },
  magic_checkout: {
    icon: 'i-magic-checkout',
    additionalCondition: (user: any): boolean => user.isMagicCheckoutEnabled,
  },
  smart_collect: {
    icon: 'i-account-balance',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('virtual_accounts') && !user.findTag('i18_hide_virtual_accounts'),
  },
  qr_codes: {
    icon: 'i-qr-code',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('qr_codes') && !user.findTag('i18_hide_qr_codes'),
  },
  affordability: {
    icon: 'i-affordability',
    additionalCondition: (user: any) => {
      return user.isShowAffordabilityWidget && user.isOrgRZP;
    },
  },
  subscriptions: {
    icon: 'i-refresh',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('subscriptions') && !user.findTag('i18_hide_subscription'),
    getHref: ({ routes, user }) =>
      routes[user.isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions'],
  },
  x_payroll: {
    icon: 'i-razorpayx',
    additionalCondition: (user: any): boolean => user.isShowPayrollWidgetEnabled && user.isOrgRZP,
  },
  x_banking: {
    icon: 'i-razorpayx',
    additionalCondition: (user: any): boolean => user.isShowRazorpayXWidgetEnabled && user.isOrgRZP,
  },
  route: {
    icon: 'i-route',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('marketplace') && !user.findTag('i18_hide_marketplace'),
  },
  payment_button: {
    icon: 'i-payment-button',
    additionalCondition: (user: any): boolean =>
      user.isAllowedMultiple('payment_buttons subscription_buttons') &&
      (user.isPaymentButtonEnabledByRazorX || user.isSubscriptionButtonEnabled) &&
      !user.findTag('i18_hide_payment_buttons'),
  },
  api_keys: {
    icon: 'i-api-keys-plugins',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('api_keys') &&
      (user.isProductLedOnboardingRZP || user.isApiKeysRevampEnabled) &&
      user.activated,
  },
  stores: {
    icon: 'i-store-product',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('stores') && user.isStoresEnabled && !user.findTag('i18_hide_stores'),
  },
  payment_pages: {
    icon: 'i-payment-pages',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('payment_pages') && !user.findTag('i18_hide_payment_pages'),
  },
  payment_links: {
    icon: 'i-link',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('payment_links') && !user.findTag('i18_hide_payment_links'),
  },
  payment_handle: {
    icon: 'i-payment-handle',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('payment_handle') &&
      user.isPaymentHandleSplitzEnabled &&
      !user.findTag('i18_hide_payment_handle'),
  },
  cash_advance: {
    icon: 'i-rupee',
    additionalCondition: canViewCashAdvanceProduct,
  },
  line_of_credit: {
    icon: 'i-rupee',
    additionalCondition: canViewLOCEMIProduct,
  },
  invoices: {
    icon: 'i-notes',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('invoices') && !user.findTag('i18_hide_invoices'),
  },
  app_store: {
    icon: 'i-app-store',
    additionalCondition: (): boolean => !isOrgFeatureExist('hide_razorpay_text_link'),
  },
  accountsettings: {
    icon: 'i-settings',
    additionalCondition: (user: any) =>
      user.isAllowedMultiple(
        'webhooks applications configuration api_keys profile credits add_funds team referrals',
      ) && user.isAccountAndSettingsRevampEnabled,
  },
  wallet: {
    icon: 'i-wallet',
    additionalCondition: (user: any) =>
      (user.isIssuingDashboardEnabled ||
        user.isIssuingBulkUploadEnabled ||
        user.isIssuingFundsTabEnabled) &&
      user.isAccountAndSettingsRevampEnabled,
  },
  internationalPaymentsBtn: {
    additionalCondition: (user: any) => user.isShowInternationalPaymentBtnExpEnabled,
  },
};

export const COMMON_PRODUCTS = [
  {
    title: SIDEEBAR_PRODUCTS_TITLES.home,
    product_id: 'home',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.transactions,
    product_id: 'transactions',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.settlements,
    product_id: 'settlements',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.reports,
    product_id: 'reports',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.my_account,
    product_id: 'my_account',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.settings,
    product_id: 'settings',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.accountsettings,
    product_id: 'accountsettings',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.internationalPaymentsBtn,
    product_id: 'internationalPaymentsBtn',
    type: 'linkButton',
    tags: [],
  },
];

export const CUSTOMERS_PRODUCTS = [
  {
    title: SIDEEBAR_PRODUCTS_TITLES.customers,
    product_id: 'customers',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.offers,
    product_id: 'offers',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.api_keys,
    product_id: 'api_keys',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.developers,
    product_id: 'developers',
    tags: [],
  },
  {
    title: SIDEEBAR_PRODUCTS_TITLES.app_store,
    product_id: 'app_store',
    tags: ['New'],
  },
];
