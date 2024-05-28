import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { isMobileResolution } from 'common/utils/rzp-utils';
import { SIDEEBAR_PRODUCTS_TITLES } from 'merchant/components/SidebarV2/constants/constants';
import { ConfigTagType } from 'merchant/constants/tags';
import { isOrgFeatureExist } from 'merchant/models/User';
import {
  canViewCashAdvanceProduct,
  canViewLOCEMIProduct,
  canViewLoans,
} from 'merchant/views/Capital/utils';
import { isPosExperimentEnabled } from 'merchant/views/POS/helpers';
import magicKonnectLogo from 'assets/magicKonnectLogo.png';
import { checkReconSaasEnabled } from 'merchant/views/Reconciliations/utils';

export type ExtraConfig = {
  abExperiments: any;
  isConfigTagEnabled: (path: ConfigTagType) => boolean;
};

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
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('settlements') &&
      !isConfigTagEnabled('settlements.settlement') &&
      user.hideForNIASupportRole,
  },
  reconciliations: {
    icon: 'i-check-circle-outline',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      checkReconSaasEnabled({ abExperiments }),
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
  working_capital_loans: {
    icon: 'i-rupee',
    additionalCondition: (user: any): boolean => user.isNonFldgLoansEnabled,
  },
  checkout_rewards: {
    icon: 'i-rewards',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('checkoutrewards') &&
      !isConfigTagEnabled('checkout_rewards.checkout_rewards'),
  },
  offers: {
    icon: 'i-offer',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('offers') && !isConfigTagEnabled('offers.offers'),
  },
  customers: {
    icon: 'i-people',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('customers') && !isConfigTagEnabled('customers.customers'),
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
  magic_konnect: {
    icon: 'i-magic-konnect',
    image: magicKonnectLogo,
    additionalCondition: (user: any, extraConfig: ExtraConfig) =>
      user.isMagicKonnectEnabled && isExperimentEnabled(extraConfig?.abExperiments?.magic_konnect),
  },
  smart_collect: {
    icon: 'i-account-balance',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('virtual_accounts') &&
      !isConfigTagEnabled('smart_collect.virtual_accounts'),
  },
  payment_metrics: {
    icon: 'i-chart',
    additionalCondition: (user: {
      isCheckoutAnalyticsEnabled: boolean;
      isOrgRZP: boolean;
      isINCountry: boolean;
    }) => user.isCheckoutAnalyticsEnabled && user.isOrgRZP && user.isINCountry,
  },
  qr_codes: {
    icon: 'i-qr-code',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('qr_codes') && !isConfigTagEnabled('qr_code.qr_code'),
  },
  affordability: {
    icon: 'i-affordability',
    additionalCondition: (user: any) => {
      return user.isShowAffordabilityWidget && user.isOrgRZP && user.isINCountry;
    },
  },
  subscriptions: {
    icon: 'i-refresh',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('subscriptions') && !isConfigTagEnabled('subscriptions.subscription'),
    getHref: ({ routes, user }): string =>
      routes[user.isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions'],
  },
  x_payroll: {
    icon: 'i-razorpayx',
    additionalCondition: (user: any): boolean =>
      user.isShowPayrollWidgetEnabled && user.isOrgRZP && user.isINCountry,
  },
  x_banking: {
    icon: 'i-razorpayx',
    additionalCondition: (user: any): boolean =>
      user.isShowRazorpayXWidgetEnabled && user.isOrgRZP && user.isINCountry,
  },
  route: {
    icon: 'i-route',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('marketplace') && !isConfigTagEnabled('route.marketplace'),
  },
  payment_button: {
    icon: 'i-payment-button',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedMultiple('payment_buttons subscription_buttons') &&
      (user.isPaymentButtonEnabledByRazorX || user.isSubscriptionButtonEnabled) &&
      !isConfigTagEnabled('payment_buttons.payment_buttons'),
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
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('stores') && user.isStoresEnabled && !isConfigTagEnabled('stores.stores'),
  },
  payment_pages: {
    icon: 'i-payment-pages',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('payment_pages') && !isConfigTagEnabled('payment_pages.payment_pages'),
  },
  payment_links: {
    icon: 'i-link',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('payment_links') && !isConfigTagEnabled('payment_links.payment_link'),
  },
  payment_handle: {
    icon: 'i-payment-handle',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('payment_handle') &&
      user.isPaymentHandleSplitzEnabled &&
      !isConfigTagEnabled('payments.payment_handle'),
  },
  cash_advance: {
    icon: 'i-rupee',
    additionalCondition: canViewCashAdvanceProduct,
  },
  line_of_credit: {
    icon: 'i-rupee',
    additionalCondition: canViewLOCEMIProduct,
  },
  capital_loans: {
    icon: 'i-rupee',
    additionalCondition: canViewLoans,
  },
  invoices: {
    icon: 'i-notes',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('invoices') && !isConfigTagEnabled('invoices.invoice'),
  },
  app_store: {
    icon: 'i-app-store',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      !isOrgFeatureExist('hide_razorpay_text_link') && !isConfigTagEnabled?.('app_store.app_store'),
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
  pos: {
    icon: 'i-pos',
    additionalCondition: (user, { abExperiments }: ExtraConfig) => {
      const isPosOnboardingEnabled = isPosExperimentEnabled({ user, abExperiments });
      return isPosOnboardingEnabled;
    },
  },
  gcms_programs: {
    icon: 'i-program',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_resellers: {
    icon: 'i-reseller',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_orders: {
    icon: 'i-order',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_funds: {
    icon: 'i-funds',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_reports: {
    icon: 'i-reports',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  riskAndFraud: {
    icon: 'i-triangle-alert',
    additionalCondition: (user: User): boolean => {
      return user.isRiskAndFraudEnabled;
    },
  },
  assisted_financing: {
    icon: 'i-at-sign',
    additionalCondition: (
      user: any,
      { isConfigTagEnabled, abExperiments }: ExtraConfig,
    ): boolean => {
      return (
        isExperimentEnabled(abExperiments.assisted_financing) &&
        user.isAllowedView('payment_links') &&
        !isConfigTagEnabled('payment_links.payment_link') &&
        user.isOrgRZP
      );
    },
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
    title: SIDEEBAR_PRODUCTS_TITLES.reconciliations,
    product_id: 'reconciliations',
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
  {
    title: SIDEEBAR_PRODUCTS_TITLES.riskAndFraud,
    product_id: 'riskAndFraud',
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
