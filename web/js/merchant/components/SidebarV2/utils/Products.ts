import {
  AffordabilityIcon,
  AlertTriangleIcon,
  AppStoreIcon,
  AtSignIcon,
  BankIcon,
  CashIcon,
  CheckCircleIcon,
  CodeSnippetIcon,
  CreditCardIcon,
  DashboardIcon,
  FileTextIcon,
  type IconComponent,
  MagicCheckoutIcon,
  MyAccountIcon,
  TagIcon,
  UsersIcon,
  OptimizerIcon,
  PaymentButtonsIcon,
  PaymentLinksIcon,
  PaymentPagesIcon,
  PosIcon,
  QRCodeIcon,
  RazorpayXIcon,
  RefreshIcon,
  ReportsIcon,
  RoutesIcon,
  RupeeIcon,
  SettingsIcon,
  SettlementsIcon,
  ShoppingBagIcon,
  StorefrontIcon,
  TransactionsIcon,
  TrendingUpIcon,
  WalletIcon,
  ZapIcon,
  BuildingIcon,
  LayoutIcon,
  SparklesIcon,
  MagicKonnectIcon,
  BillMeIcon,
} from '@razorpay/blade/components';
import magicKonnectLogo from 'assets/magicKonnectLogo.png';

import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { isMobileResolution } from 'common/utils/rzp-utils';
import {
  SIDEEBAR_PRODUCTS_TITLES,
  ROUTE_L1_PRODUCTS_TITLES,
  ROUTE_ACCOUNTS_L2_PRODUCTS_TITLES,
} from 'merchant/components/SidebarV2/constants/constants';
import { ConfigTagType } from 'merchant/constants/tags';
import { isOrgFeatureExist } from 'merchant/models/User';
import { isBillMeMerchant } from 'merchant/utils/omniUtils';
import { canViewCashAdvanceProduct, canViewLOCEMIProduct } from 'merchant/views/Capital/utils';
import { isPosExperimentEnabled } from 'merchant/views/POS/helpers';
import { checkReconSaasEnabled } from 'merchant/views/Reconciliations/utils';

export type ExtraConfig = {
  abExperiments: any;
  isConfigTagEnabled: (path: ConfigTagType) => boolean;
};

const ROUTE_L1_PRODUCTS_DATA = [
  {
    title: ROUTE_L1_PRODUCTS_TITLES.payments,
    href: '/route/payments',
    icon: LayoutIcon,
  },
  {
    title: ROUTE_L1_PRODUCTS_TITLES.transfers,
    href: '/route/transfers',
    icon: LayoutIcon,
  },
  {
    title: ROUTE_L1_PRODUCTS_TITLES.platformfee,
    href: '/route/platformfee',
    icon: LayoutIcon,
  },
  {
    title: ROUTE_L1_PRODUCTS_TITLES.reversals,
    href: '/route/reversals',
    icon: LayoutIcon,
  },
  {
    title: ROUTE_L1_PRODUCTS_TITLES.accounts,
    href: '/route/accounts',
    icon: LayoutIcon,
    items: [
      {
        title: ROUTE_ACCOUNTS_L2_PRODUCTS_TITLES.razorpay,
        href: '/route/accounts',
      },
      {
        title: ROUTE_ACCOUNTS_L2_PRODUCTS_TITLES.optimizer,
        href: '/route/optimizer/accounts',
      },
    ],
  },
  {
    title: ROUTE_L1_PRODUCTS_TITLES.batchupload,
    href: '/route/batchuploads',
    icon: LayoutIcon,
  },
];

export const PRODUCTS_DATA = {
  home: {
    bladeIcon: DashboardIcon,
    icon: 'i-chart',
    additionalCondition: (user: any): boolean => user.isAllowedView('home'),
  },
  transactions: {
    bladeIcon: TransactionsIcon,
    icon: 'i-repeat',
    additionalCondition: (user: any): boolean => user.isAllowedMultiple('payments orders refunds'),
  },
  settlements: {
    bladeIcon: SettlementsIcon,
    icon: 'i-done-all',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('settlements') &&
      !isConfigTagEnabled('settlements.settlement') &&
      user.hideForNIASupportRole,
  },
  reconciliations: {
    bladeIcon: CheckCircleIcon,
    icon: 'i-check-circle-outline',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      checkReconSaasEnabled({ abExperiments }),
  },
  settings: {
    bladeIcon: SettingsIcon,
    icon: 'i-settings',
    additionalCondition: (user: any): boolean =>
      user.isAllowedMultiple('webhooks applications configuration api_keys') &&
      !user.isAccountAndSettingsRevampEnabled,
  },
  developers: {
    bladeIcon: CodeSnippetIcon,
    icon: 'i-developers developers-sidebar-icon',
    additionalCondition: (user: any): boolean =>
      !isMobileResolution() &&
      user.isAllowedView('developers_console') &&
      (user.isDeveloperConsoleEnabled || user.isDeveloperConsoleWebhooksTabEnabled),
  },
  my_account: {
    bladeIcon: MyAccountIcon,
    icon: 'i-account',
    additionalCondition: (user: any): boolean =>
      user.isAllowedMultiple('profile credits add_funds team referrals') &&
      !user.isAccountAndSettingsRevampEnabled,
  },
  reports: {
    bladeIcon: ReportsIcon,
    icon: 'i-books',
    additionalCondition: (user: any): boolean =>
      (user.isAllowedView('reports') || user.isCareHealthOwner) && user.hideForNIASupportRole,
  },
  x_corporate_cards: {
    bladeIcon: CreditCardIcon,
    icon: 'i-credit-card',
    additionalCondition: (user: any): boolean => user.isCardsLOSEnabled,
  },
  working_capital_loans: {
    bladeIcon: RupeeIcon,
    icon: 'i-rupee',
    additionalCondition: (user: any): boolean => user.isNonFldgLoansEnabled,
  },
  checkout_rewards: {
    bladeIcon: CashIcon,
    icon: 'i-rewards',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('checkoutrewards') &&
      !isConfigTagEnabled('checkout_rewards.checkout_rewards'),
  },
  offers: {
    bladeIcon: TagIcon,
    icon: 'i-offer',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('offers') && !isConfigTagEnabled('offers.offers'),
  },
  company_registration: {
    bladeIcon: BuildingIcon,
    icon: 'i-building',
    additionalCondition: (user: any): boolean => user.business_type == '11', // Only Show the Tab to not registered users
  },
  customers: {
    bladeIcon: UsersIcon,
    icon: 'i-people',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('customers') && !isConfigTagEnabled('customers.customers'),
  },
  optimizer: {
    bladeIcon: OptimizerIcon,
    icon: 'i-routing',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('optimizer') &&
      (user.isOptimizerEnabled || user.isOptimizerOnboardingEnabled),
  },
  bbps: {
    bladeIcon: DashboardIcon,
    icon: 'i-chart',
    additionalCondition: (user: any): boolean => user.isAllowedView('bbps') && user.isBbpsEnabled,
  },
  magic_checkout: {
    bladeIcon: MagicCheckoutIcon,
    icon: 'i-magic-checkout',
    additionalCondition: (user: any): boolean => user.isMagicCheckoutEnabled,
  },
  magic_konnect: {
    bladeIcon: MagicKonnectIcon,
    icon: 'i-magic-konnect',
    image: magicKonnectLogo,
    additionalCondition: (user: any, extraConfig: ExtraConfig) =>
      user.isMagicKonnectEnabled && isExperimentEnabled(extraConfig?.abExperiments?.magic_konnect),
  },
  smart_collect: {
    bladeIcon: BankIcon,
    icon: 'i-account-balance',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('virtual_accounts') &&
      !isConfigTagEnabled('smart_collect.virtual_accounts'),
  },
  payment_metrics: {
    bladeIcon: DashboardIcon,
    icon: 'i-chart',
    additionalCondition: (user: {
      isCheckoutAnalyticsEnabled: boolean;
      isOrgRZP: boolean;
      isCountryIndia: boolean;
    }) => user.isCheckoutAnalyticsEnabled && user.isOrgRZP && user.isCountryIndia,
  },
  qr_codes: {
    bladeIcon: QRCodeIcon,
    icon: 'i-qr-code',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('qr_codes') && !isConfigTagEnabled('qr_code.qr_code'),
  },
  affordability: {
    bladeIcon: AffordabilityIcon,
    icon: 'i-affordability',
    additionalCondition: (user: any) => {
      return user.isShowAffordabilityWidget && user.isOrgRZP && user.isCountryIndia;
    },
  },
  subscriptions: {
    bladeIcon: RefreshIcon,
    icon: 'i-refresh',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('subscriptions') && !isConfigTagEnabled('subscriptions.subscription'),
    getHref: ({ routes, user }): string =>
      routes[user.isChargeAtWillEnabled ? 'chargeAtWill' : 'subscriptions'],
  },
  x_payroll: {
    bladeIcon: RazorpayXIcon,
    icon: 'i-razorpayx',
    additionalCondition: (user: any): boolean =>
      user.isShowPayrollWidgetEnabled && user.isOrgRZP && user.isCountryIndia,
  },
  x_banking: {
    bladeIcon: RazorpayXIcon,
    icon: 'i-razorpayx',
    additionalCondition: (user: any): boolean =>
      user.isShowRazorpayXWidgetEnabled && user.isOrgRZP && user.isCountryIndia,
  },
  route: {
    bladeIcon: RoutesIcon,
    icon: 'i-route',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('marketplace') && !isConfigTagEnabled('route.marketplace'),
    items: ROUTE_L1_PRODUCTS_DATA,
  },
  payment_button: {
    bladeIcon: PaymentButtonsIcon,
    icon: 'i-payment-button',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedMultiple('payment_buttons subscription_buttons') &&
      (user.isPaymentButtonEnabledByRazorX || user.isSubscriptionButtonEnabled) &&
      !isConfigTagEnabled('payment_buttons.payment_buttons'),
  },
  api_keys: {
    bladeIcon: CodeSnippetIcon,
    icon: 'i-api-keys-plugins',
    additionalCondition: (user: any): boolean =>
      user.isAllowedView('api_keys') &&
      (user.isProductLedOnboardingRZP || user.isApiKeysRevampEnabled) &&
      user.activated,
  },
  stores: {
    bladeIcon: StorefrontIcon,
    icon: 'i-store-product',
    additionalCondition: (user: any, { abExperiments, isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('stores') &&
      isExperimentEnabled(abExperiments.stores) &&
      !isConfigTagEnabled('stores.stores'),
  },
  payment_pages: {
    bladeIcon: PaymentPagesIcon,
    icon: 'i-payment-pages',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('payment_pages') && !isConfigTagEnabled('payment_pages.payment_pages'),
  },
  payment_links: {
    bladeIcon: PaymentLinksIcon,
    icon: 'i-link',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('payment_links') && !isConfigTagEnabled('payment_links.payment_link'),
  },
  payment_handle: {
    bladeIcon: AtSignIcon,
    icon: 'i-payment-handle',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('payment_handle') &&
      user.isPaymentHandleSplitzEnabled &&
      !isConfigTagEnabled('payments.payment_handle'),
  },
  cash_advance: {
    bladeIcon: RupeeIcon,
    icon: 'i-rupee',
    additionalCondition: canViewCashAdvanceProduct,
  },
  line_of_credit: {
    bladeIcon: RupeeIcon,
    icon: 'i-rupee',
    additionalCondition: canViewLOCEMIProduct,
  },
  invoices: {
    bladeIcon: FileTextIcon,
    icon: 'i-notes',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      user.isAllowedView('invoices') && !isConfigTagEnabled('invoices.invoice'),
  },
  app_store: {
    bladeIcon: AppStoreIcon,
    icon: 'i-app-store',
    additionalCondition: (user: any, { isConfigTagEnabled }: ExtraConfig): boolean =>
      !user.isPartnerAgentRole &&
      !isOrgFeatureExist('hide_razorpay_text_link') &&
      !isConfigTagEnabled?.('app_store.app_store'),
  },
  accountsettings: {
    bladeIcon: SettingsIcon,
    icon: 'i-settings',
    additionalCondition: (user: any) =>
      user.isAllowedMultiple(
        'webhooks applications configuration api_keys profile credits add_funds team referrals',
      ) && user.isAccountAndSettingsRevampEnabled,
  },
  wallet: {
    bladeIcon: WalletIcon,
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
    bladeIcon: PosIcon,
    icon: 'i-pos',
    additionalCondition: (user, { abExperiments }: ExtraConfig) => {
      const isPosOnboardingEnabled = isPosExperimentEnabled({ user, abExperiments });
      return isPosOnboardingEnabled;
    },
  },
  bill_me: {
    bladeIcon: BillMeIcon,
    icon: 'i-bill-me',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) => {
      // TODO: to add 'mode' condition check before Go-Live
      return isBillMeMerchant({ abExperiments });
    },
  },
  gcms_programs: {
    bladeIcon: ZapIcon,
    icon: 'i-program',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_resellers: {
    bladeIcon: StorefrontIcon,
    icon: 'i-reseller',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_orders: {
    bladeIcon: ShoppingBagIcon,
    icon: 'i-order',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_funds: {
    bladeIcon: TrendingUpIcon,
    icon: 'i-funds',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  gcms_reports: {
    bladeIcon: ReportsIcon,
    icon: 'i-reports',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      user.isIssuingDashboardEnabled &&
      isExperimentEnabled(abExperiments.razorpay_gcms) &&
      user.isIssuingGcmsEnabled,
  },
  riskAndFraud: {
    bladeIcon: AlertTriangleIcon,
    icon: 'i-triangle-alert',
    additionalCondition: (user: User): boolean => {
      return user.isRiskAndFraudEnabled;
    },
  },
  assisted_financing: {
    bladeIcon: AtSignIcon,
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
  insight_x: {
    bladeIcon: SparklesIcon,
    icon: 'i i-sparkles text-info',
    additionalCondition: (user: any, { abExperiments }: ExtraConfig) =>
      isExperimentEnabled(abExperiments.insight_x_experiment) &&
      user.isOrgRZP &&
      user.isCountryIndia &&
      !isMobileResolution(),
  },
};

export type ProductTypeProp = {
  title: string;
  product_id: string;
  tags: string[];
  type?: string;
};

export const COMMON_PRODUCTS: Array<ProductTypeProp> = [
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
    title: SIDEEBAR_PRODUCTS_TITLES.insight_x,
    product_id: 'insight_x',
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
    title: SIDEEBAR_PRODUCTS_TITLES.company_registration,
    product_id: 'company_registration',
    tags: ['New'],
  },
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

interface L1ProductItem {
  title: string;
  href: string;
  icon: IconComponent;
  items?: { title: string; href: string }[];
}

const getRouteL1Products = (items: L1ProductItem[], user: any) => {
  if (!user?.isOrgCurlec) {
    return [...items];
  }
  return items.filter((product) => product.title !== ROUTE_L1_PRODUCTS_TITLES.batchupload);
};

export const getL1ProductItems = (productId: string, user: any) => {
  switch (productId) {
    // Only for Optimizer merchants where route is enabled we will show L1 items on Route Product
    case 'route':
      if (user?.isOptimizerEnabled && user?.isOptimizerRouteEnabled) {
        return getRouteL1Products(PRODUCTS_DATA[productId]?.items, user);
      }
      return [];
    default:
      return PRODUCTS_DATA[productId]?.items || [];
  }
};
