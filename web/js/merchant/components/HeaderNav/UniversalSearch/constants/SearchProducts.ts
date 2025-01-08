import {
  EligibleProducts,
  EligibleProductsTypes,
  ProductType,
} from 'merchant/components/HeaderNav/UniversalSearch/typings';
import { SIDEEBAR_PRODUCTS_TITLES } from 'merchant/components/SidebarV2/constants/constants';
import { BASE_ROUTES as SIDEBAR_ROUTES } from 'merchant/components/SidebarV2/utils/href';
import { ExtraConfig, PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
import { isBillMeMerchant } from 'merchant/utils/omniUtils';
import { AccountNSettingsIcons } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/config/section';
import {
  ACCOUNT_N_SETTINGS_TITLES,
  PaymentMethodsFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { ROUTES_INFO as ACCOUNT_N_SETTINGS_ROUTES } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  isAccountDetailsEnabled,
  isApiKeyEnabled,
  isBalancesEnabled,
  isBankAccountDetailsAllowed,
  isCheckoutV2SettingsAllowed,
  isConfigurationViewAllowed,
  isCreditsEnabled,
  isEmailNotificationEnabled,
  isFailedPaymentRetryEnabled,
  isFlashCheckoutAllowed,
  isGstDetailsEnabled,
  isPaymentCaptureAndRefundEnabled,
  isPaymentMethodEnabled,
  isProfileViewAllowed,
  isReminderEnabled,
  isSkipMandatorySummaryPageAllowed,
  isSmsNotificationEnabled,
  isTeamManagementAllowed,
  isTrustedBadgeAllowed,
  isWebhookEnabled,
  isWebsiteDetailsEnabled,
  isWhatsappNotificationEnabled,
  shouldShowFeeBearerSelfServe,
  shouldShowFIRCSection,
  shouldShowTeamInvitations,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';

const {
  invoices,
  transactions,
  customers,
  offers,
  reports,
  accountsettings,
  developers,
  app_store,
  payment_pages,
  payment_button,
  route,
  subscriptions,
  qr_codes,
  smart_collect,
  checkout_rewards,
  payment_handle,
  x_banking,
  x_corporate_cards,
  line_of_credit,
  capital_loans,
  settlements,
  payment_links,
  affordability,
  optimizer,
  x_payroll,
  cash_advance,
  magic_checkout,
  magic_konnect,
  riskAndFraud,
  bill_me,
} = PRODUCTS_DATA;

const paymentMethodCondition = ({ instruments, user, mode, paymentMethod }): boolean =>
  instruments.find((each) => each.slug === paymentMethod) && isPaymentMethodEnabled(user, mode);

const isProductViewAllowed = (user: any, item): boolean => user.isAllowedView(item);

const isSubscriptionsViewAllowed = (user: any, extraConfig: ExtraConfig): boolean =>
  subscriptions.additionalCondition(user, extraConfig) && !user.isChargeAtWillEnabled;

const isRecurringPaymentsViewAllowed = (user: any): boolean =>
  user.isAllowedView('subscriptions') &&
  user.isChargeAtWillEnabled &&
  user.isRegistrationLinkTokenAndPaymentsEnabled;

const SEARCH_PRODUCTS_TITLES = {
  ...SIDEEBAR_PRODUCTS_TITLES,
  ...ACCOUNT_N_SETTINGS_TITLES,
  refunds: 'Refunds',
  upi: 'UPI',
  orders: 'Orders',
  disputes: 'Disputes',
  batch_refunds: 'Batch refunds',
  api_logs: 'API logs',
  plans: 'Plans',
  items: 'Items',
  request_terminal: 'Request terminal',
  add_payment_methods: 'Add payment methods',
  email_notifications: 'Email notification',
  sms_notifications: 'SMS notification',
  whatsapp_notifications: 'WhatsApp notification',
  failed_payment_recovery: 'Failed payments recovery',
};

type ACCOUNT_N_SETTINGS_ROUTES_TYPE = {
  [key in keyof typeof ACCOUNT_N_SETTINGS_ROUTES]: string;
};
const ACCOUNT_AND_SETTINGS_URLS: ACCOUNT_N_SETTINGS_ROUTES_TYPE =
  {} as ACCOUNT_N_SETTINGS_ROUTES_TYPE;
for (const [key, value] of Object.entries(ACCOUNT_N_SETTINGS_ROUTES)) {
  ACCOUNT_AND_SETTINGS_URLS[key] = value;
}
const SEARCH_PRODUCTS_URL = {
  ...SIDEBAR_ROUTES,
  ...ACCOUNT_AND_SETTINGS_URLS,
  refunds: '/refunds',
  orders: '/orders',
  disputes: '/disputes',
  batch_refunds: '/refunds/batchuploads',
  plans: '/plans',
  items: '/items',
  recurring_payments: '/recurring_payments',
};

export const SEARCH_PRODUCTS: EligibleProducts[] = [
  {
    title: SEARCH_PRODUCTS_TITLES.transactions,
    url: SEARCH_PRODUCTS_URL.transactions,
    tags: [
      { value: 'Payment' },
      { value: 'Failed' },
      { value: 'Payment ID' },
      { value: 'Failure' },
      { value: 'rejected' },
      { value: 'payment status' },
      { value: 'Transactions' },
      { value: 'failure reason' },
      { value: 'Payments' },
      { value: 'Rejected transaction' },
      { value: 'Failed transaction' },
      { value: 'Failed payment' },
    ],
    icon: transactions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      transactions.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.refunds,
    url: SEARCH_PRODUCTS_URL.refunds,
    tags: [
      { value: 'Refund' },
      { value: 'Refund ID' },
      { value: 'Failed' },
      { value: 'Refund failure' },
      { value: 'refund status' },
      { value: 'Bulk refunds' },
    ],
    group: ['in: Transactions'],
    icon: transactions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isProductViewAllowed(user, 'refunds'),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.batch_refunds,
    url: SEARCH_PRODUCTS_URL.batch_refunds,
    tags: [{ value: 'Refund' }, { value: 'Refund ID' }, { value: 'Failed' }],
    group: ['in: Transactions'],
    icon: transactions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isProductViewAllowed(user, 'refunds_batch_uploads'),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.orders,
    url: SEARCH_PRODUCTS_URL.orders,
    tags: [{ value: 'Order' }, { value: 'Orders API' }],
    group: ['in: Transactions'],
    icon: transactions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isProductViewAllowed(user, 'orders'),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.disputes,
    url: SEARCH_PRODUCTS_URL.disputes,
    tags: [{ value: 'Chargeback' }, { value: 'Dispute' }],
    group: ['in: Transactions'],
    icon: transactions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isProductViewAllowed(user, 'refunds'),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.settlements,
    url: SEARCH_PRODUCTS_URL.settlements,
    tags: [
      { value: 'Payouts' },
      { value: 'Settlement cycle' },
      { value: 'Failed' },
      { value: 'Delayed' },
      { value: 'Skipped' },
      { value: 'Status' },
      { value: 'money settled' },
      { value: 'instant' },
      { value: 'settlement amount' },
      { value: 'settlement ID' },
      { value: 'instant settlement' },
      { value: 'Enable Instant settlement' },
    ],
    icon: settlements.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      settlements.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.customers,
    url: SEARCH_PRODUCTS_URL.customers,
    tags: [{ value: 'Customer' }, { value: 'Add customer' }, { value: 'Users' }],
    icon: customers.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      customers.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.offers,
    url: SEARCH_PRODUCTS_URL.offers,
    tags: [{ value: 'Promotions' }, { value: 'Offer ID' }],
    icon: offers.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      offers.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.reports,
    url: SEARCH_PRODUCTS_URL.reports,
    tags: [
      { value: 'Download report' },
      { value: 'Settlements report' },
      { value: 'instant settlement' },
    ],
    icon: reports.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      reports.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.accountsettings,
    url: SEARCH_PRODUCTS_URL.accountsettings,
    tags: [
      { value: 'email' },
      { value: 'Settings' },
      { value: 'Account' },
      { value: 'Setting' },
      { value: 'Profile' },
      { value: 'Update profile' },
      { value: 'Change profile' },
      { value: 'login' },
      { value: 'update' },
      { value: '2 step verification' },
      { value: '2FA' },
      { value: 'factor' },
      { value: 'authentication' },
      { value: 'update email' },
    ],
    icon: accountsettings.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      accountsettings.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.api_logs,
    url: SEARCH_PRODUCTS_URL.developers,
    tags: [{ value: 'API requests' }, { value: 'API failure' }],
    group: ['in: Developers'],
    icon: developers.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      developers.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.developers,
    url: SEARCH_PRODUCTS_URL.developers,
    tags: [
      { value: 'Developer settings' },
      { value: 'API failure' },
      { value: 'API' },
      { value: 'logs' },
      { value: 'Settings' },
    ],
    group: ['in: Developers'],
    icon: developers.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      developers.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.app_store,
    url: SEARCH_PRODUCTS_URL.app_store,
    tags: [
      { value: 'Apps' },
      { value: 'Download apps' },
      { value: 'gallabox' },
      { value: 'pabbly' },
      { value: 'dronaHQ' },
      { value: 'haptik' },
      { value: 'thrive' },
      { value: 'integromat' },
      { value: 'slack' },
      { value: 'zapier' },
      { value: 'aisensy' },
      { value: 'raven' },
      { value: 'callerdesk' },
      { value: 'payment links bot' },
      { value: 'zoho' },
      { value: 'intuit quickbooks' },
      { value: 'shopify' },
      { value: 'woocommerce' },
      { value: 'wix' },
      { value: 'getvantage' },
      { value: 'prestashop' },
      { value: 'magento' },
      { value: 'apnapay' },
      { value: 'leadcart' },
      { value: 'rista' },
      { value: 'dukaan' },
      { value: 'shiprocket' },
      { value: 'msmex' },
      { value: 'kylas' },
    ],
    icon: app_store.icon,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      app_store.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.add_payment_methods,
    url: SEARCH_PRODUCTS_URL.PAYMENT_METHODS,
    tags: [
      { value: 'Request terminal' },
      { value: 'Payment' },
      { value: 'Instrument' },
      { value: 'Method' },
      { value: 'payment method' },
    ],
    icon: AccountNSettingsIcons.payment_methods,
    group: ['in: Account & Settings'],
    additionalCondition: ({ user, mode }: EligibleProductsTypes): boolean =>
      isPaymentMethodEnabled(user, mode),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.cards,
    url: SEARCH_PRODUCTS_URL.CARDS,
    tags: [
      { value: 'Credit Card' },
      { value: 'Visa' },
      { value: 'Master Card' },
      { value: 'Rupay' },
      { value: 'Maestro' },
      { value: 'Amex' },
      { value: 'Diners Club' },
      { value: 'Card' },
    ],
    group: ['in: Payment methods'],
    icon: AccountNSettingsIcons.payment_methods,
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.CARDS,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.upi,
    url: SEARCH_PRODUCTS_URL.UPI_QR,
    tags: [
      { value: 'upi' },
      { value: 'UPI App' },
      { value: 'Google Pay' },
      { value: 'Phone Pe' },
      { value: 'BHIM' },
    ],
    icon: AccountNSettingsIcons.payment_methods,
    group: ['in: Payment methods'],
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.UPI,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.netbanking,
    url: SEARCH_PRODUCTS_URL.NETBANKING,
    tags: [
      { value: 'HDFC' },
      { value: 'AXIS' },
      { value: 'ICICI' },
      { value: 'SBI' },
      { value: 'State Bank' },
      { value: 'Union Bank' },
    ],
    icon: AccountNSettingsIcons.payment_methods,
    group: ['in: Payment methods'],
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.NETBANKING,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.emi,
    url: SEARCH_PRODUCTS_URL.EMI,
    tags: [
      { value: 'Credit Card EMI' },
      { value: 'Debit Card EMI' },
      { value: 'Zest money' },
      { value: 'Early salary' },
      { value: 'Axio' },
    ],
    icon: AccountNSettingsIcons.payment_methods,
    group: ['in: Payment methods'],
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.EMI,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.wallet,
    url: SEARCH_PRODUCTS_URL.WALLET,
    tags: [{ value: 'Freecharge' }, { value: 'Paytm' }, { value: 'Phonepe' }],
    icon: AccountNSettingsIcons.payment_methods,
    group: ['in: Payment methods'],
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.WALLET,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.paylater,
    url: SEARCH_PRODUCTS_URL.PAY_LATER,
    tags: [{ value: 'BNPL' }, { value: 'Flexipay' }, { value: 'Simpl' }, { value: 'Lazypay' }],
    icon: AccountNSettingsIcons.payment_methods,
    group: ['in: Payment methods'],
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.PAYLATER,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.international,
    url: SEARCH_PRODUCTS_URL.INTERNATIONAL_PAYMENTS,
    group: ['in: Account & Settings'],
    tags: [
      { value: 'International activation' },
      { value: 'payment' },
      { value: 'Abroad' },
      { value: 'PayPal' },
      { value: 'activate account' },
      { value: 'Foreign' },
      { value: 'my activation status' },
      { value: 'submit activation document' },
      { value: 'payments' },
    ],
    icon: AccountNSettingsIcons.payment_methods,
    additionalCondition: ({ user, mode, instruments }: EligibleProductsTypes): boolean =>
      paymentMethodCondition({
        user,
        mode,
        instruments,
        paymentMethod: PaymentMethodsFields.INTERNATIONAL,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.website_app_detail,
    url: SEARCH_PRODUCTS_URL.WEBSITE_APP_SETTINGS,
    tags: [
      { value: 'Update website' },
      { value: 'Update website' },
      { value: 'Add website' },
      { value: 'Change website' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.website_app_settings,
    additionalCondition: ({
      user,
      websiteSectionDetailsData,
      extraConfig,
    }: EligibleProductsTypes): boolean =>
      isWebsiteDetailsEnabled({ user, websiteSectionDetailsData, extraConfig }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.business_website_details,
    url: SEARCH_PRODUCTS_URL.BUSINESS_WEBSITE_SETTINGS,
    tags: [
      { value: 'Update website' },
      { value: 'Update website' },
      { value: 'Add website' },
      { value: 'Change website' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.website_app_settings,
    additionalCondition: (): boolean => true,
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.api_keys,
    url: SEARCH_PRODUCTS_URL.API_KEYS,
    tags: [{ value: 'Generate API Keys' }, { value: 'Create API Key' }, { value: 'Live Key' }],
    icon: AccountNSettingsIcons.website_app_settings,
    group: ['in: Account & Settings'],
    additionalCondition: ({ user }: EligibleProductsTypes): boolean => isApiKeyEnabled(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.webhooks,
    url: SEARCH_PRODUCTS_URL.WEBHOOKS,
    tags: [
      { value: 'Add webhook' },
      { value: 'Push API' },
      { value: 'Webcallback' },
      { value: 'alert' },
    ],
    icon: AccountNSettingsIcons.website_app_settings,
    group: ['in: Account & Settings'],
    additionalCondition: ({ user }: EligibleProductsTypes): boolean => isWebhookEnabled(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.account_details,
    url: SEARCH_PRODUCTS_URL.ACCOUNT_DETAILS,
    tags: [
      { value: 'Contact name' },
      { value: 'email' },
      { value: 'display name' },
      { value: 'number' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: (): boolean => true,
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.Activation_details,
    url: SEARCH_PRODUCTS_URL.ACTIVATION_DETAILS,
    tags: [
      { value: 'Account Status' },
      { value: 'KYC form' },
      { value: 'Activation status' },
      { value: 'Onboarding form' },
      { value: 'my activation status' },
      { value: 'activate account' },
      { value: 'submit KYC form' },
      { value: 'submit activation document' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isAccountDetailsEnabled(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.business_details,
    url: SEARCH_PRODUCTS_URL.BUSINESS_DETAILS,
    tags: [{ value: 'Business name' }, { value: 'Business type' }, { value: 'Registration date' }],
    icon: AccountNSettingsIcons.business_settings,
    group: ['in: Account & Settings'],
    additionalCondition: (): boolean => true,
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.gst_details,
    url: SEARCH_PRODUCTS_URL.GST_DETAILS,
    tags: [{ value: 'GST' }, { value: 'Change GST' }, { value: 'edit GST' }, { value: 'tax' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isGstDetailsEnabled(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.customer_support_details,
    url: SEARCH_PRODUCTS_URL.CUSTOMER_SUPPORT_DETAILS,
    tags: [
      { value: 'Support details' },
      { value: 'email' },
      { value: 'Contact help' },
      { value: 'Query' },
      { value: 'Ticket' },
      { value: 'Help' },
      { value: 'Agent' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: (): boolean => true,
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.manage_team,
    url: SEARCH_PRODUCTS_URL.MANAGE_TEAM_DETAILS,
    tags: [{ value: 'Add team member' }, { value: 'Add role' }, { value: 'Add users' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isTeamManagementAllowed(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.invitations,
    url: SEARCH_PRODUCTS_URL.TEAM_INVITATIONS,
    tags: [{ value: 'Accept invite' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      shouldShowTeamInvitations(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.billme_settings,
    url: SEARCH_PRODUCTS_URL.BILLME_SETTINGS,
    tags: [
      { value: 'BillMe' },
      { value: 'Brands' },
      { value: 'Terminals' },
      { value: 'Digital Billing' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: ({
      mode,
      extraConfig: { abExperiments },
    }: EligibleProductsTypes): boolean => mode === 'live' && isBillMeMerchant({ abExperiments }),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.store_settings,
    url: SEARCH_PRODUCTS_URL.STORE_SETTINGS,
    tags: [
      { value: 'BillMe' },
      { value: 'Stores' },
      { value: 'Store Groups' },
      { value: 'Terminals' },
      { value: 'Digital Billing' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.business_settings,
    additionalCondition: ({
      mode,
      extraConfig: { abExperiments },
    }: EligibleProductsTypes): boolean => mode === 'live' && isBillMeMerchant({ abExperiments }),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.balances,
    url: SEARCH_PRODUCTS_URL.BALANCES,
    tags: [
      { value: 'Add fund' },
      { value: 'Reserve balance' },
      { value: 'Current balance' },
      { value: 'add money' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isBalancesEnabled(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.credits,
    url: SEARCH_PRODUCTS_URL.CREDITS,
    tags: [
      { value: 'Add credit' },
      { value: 'Fee credit' },
      { value: 'Refund credit' },
      { value: 'Amount credit' },
      { value: 'add refund money' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isCreditsEnabled(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.reminders,
    url: SEARCH_PRODUCTS_URL.REMINDERS,
    tags: [{ value: 'Payment link reminders' }, { value: 'Payment link' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: ({ extraConfig }: EligibleProductsTypes): boolean =>
      isReminderEnabled(extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.transaction_limits,
    url: SEARCH_PRODUCTS_URL.TRANSACTION_LIMITS,
    tags: [
      { value: 'Increase transaction limit' },
      { value: 'payment limit' },
      { value: 'failed payment' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: (): boolean => true,
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.fee_bearer,
    url: SEARCH_PRODUCTS_URL.FEE_BEARER,
    tags: [
      { value: 'charges' },
      { value: 'Razorpay fee' },
      { value: 'my transaction fee' },
      { value: 'Fee details' },
      { value: 'transaction charges' },
      { value: 'payment fee' },
      { value: 'rejected transaction' },
      { value: 'Failed payment' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: ({ user, allowCFBInternational }: EligibleProductsTypes): boolean =>
      shouldShowFeeBearerSelfServe({
        allowCFBInternational,
        user,
      }),
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.capture_refund_settings,
    url: SEARCH_PRODUCTS_URL.CAPTURE_AND_REFUND_SETTINGS,
    tags: [
      { value: 'refund' },
      { value: 'instant refund' },
      { value: 'Capture payment' },
      { value: 'Automatic capture' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: ({ extraConfig }: EligibleProductsTypes): boolean =>
      isPaymentCaptureAndRefundEnabled(extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.failed_payment_recovery,
    url: SEARCH_PRODUCTS_URL.FAILED_PAYMENTS_RETRY,
    tags: [
      { value: 'failed payment recovery setting' },
      { value: 'failed transaction recovery' },
      { value: 'payment' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.payments_refunds,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isFailedPaymentRetryEnabled(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.bank_account_details,
    url: SEARCH_PRODUCTS_URL.BANK_ACCOUNT_DETAILS,
    tags: [
      { value: 'bank' },
      { value: 'bank account' },
      { value: 'update bank account' },
      { value: 'change bank account' },
      { value: 'Add bank account' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.bank_and_settlements,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isProfileViewAllowed(user) && isBankAccountDetailsAllowed(extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.settlement_details,
    url: SEARCH_PRODUCTS_URL.SETTLEMENT_DETAILS,
    tags: [{ value: 'settlement details' }, { value: 'instant settlement' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.bank_and_settlements,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean => isProfileViewAllowed(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.firs,
    url: SEARCH_PRODUCTS_URL.FIRS,
    tags: [{ value: 'FIRS certificate' }, { value: 'Proof of foreign transfers' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.international_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      shouldShowFIRCSection(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.email_notifications,
    url: SEARCH_PRODUCTS_URL.EMAIL_NOTIFICATIONS,
    tags: [{ value: 'Email notification' }, { value: 'Email settings' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.notification_settings,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isConfigurationViewAllowed(user) && isEmailNotificationEnabled(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.sms_notifications,
    url: SEARCH_PRODUCTS_URL.SMS_NOTIFICATIONS,
    tags: [
      { value: 'Message' },
      { value: 'SMS notification' },
      { value: 'SMS settings' },
      { value: 'SMS number' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.notification_settings,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isConfigurationViewAllowed(user) && isSmsNotificationEnabled(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.whatsapp_notifications,
    url: SEARCH_PRODUCTS_URL.WHATSAPP_NOTIFICATIONS,
    tags: [
      { value: 'Whatsapp notification' },
      { value: 'Whatsapp settings' },
      { value: 'Whatsapp number' },
    ],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.notification_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isConfigurationViewAllowed(user) && isWhatsappNotificationEnabled(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.branding,
    url: SEARCH_PRODUCTS_URL.BRANDING,
    tags: [{ value: 'Theme' }, { value: 'Color' }, { value: 'Logo' }, { value: 'brand name' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      !isCheckoutV2SettingsAllowed(extraConfig) && isConfigurationViewAllowed(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.checkout_styling,
    url: SEARCH_PRODUCTS_URL.CHECKOUT_STYLING,
    tags: [{ value: 'Theme' }, { value: 'Color' }, { value: 'Logo' }, { value: 'brand name' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isCheckoutV2SettingsAllowed(extraConfig) && isConfigurationViewAllowed(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.flash_checkout,
    url: SEARCH_PRODUCTS_URL.FLASH_CHECKOUT,
    tags: [{ value: 'Enable flash checkout' }, { value: 'Flash checkout' }, { value: 'checkout' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      !isCheckoutV2SettingsAllowed(extraConfig) &&
      isConfigurationViewAllowed(user) &&
      isFlashCheckoutAllowed(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.flash_checkout,
    url: SEARCH_PRODUCTS_URL.CHECKOUT_FEATURES,
    tags: [{ value: 'Enable flash checkout' }, { value: 'Flash checkout' }, { value: 'checkout' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isCheckoutV2SettingsAllowed(extraConfig) &&
      isConfigurationViewAllowed(user) &&
      isFlashCheckoutAllowed(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.skip_mandate_summary_page,
    url: SEARCH_PRODUCTS_URL.SKIP_MANDATORY_SUMMARY_PAGE,
    tags: [{ value: 'Mandate summary page' }, { value: 'skip' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      !isCheckoutV2SettingsAllowed(extraConfig) &&
      isConfigurationViewAllowed(user) &&
      isSkipMandatorySummaryPageAllowed(extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.skip_mandate_summary_page,
    url: SEARCH_PRODUCTS_URL.CHECKOUT_FEATURES,
    tags: [{ value: 'Mandate summary page' }, { value: 'skip' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isCheckoutV2SettingsAllowed(extraConfig) &&
      isConfigurationViewAllowed(user) &&
      isSkipMandatorySummaryPageAllowed(extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.trusted_badge,
    url: SEARCH_PRODUCTS_URL.TRUSTED_BADGE,
    tags: [{ value: 'Add trusted badge' }, { value: 'Razorpay trusted badge' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      !isCheckoutV2SettingsAllowed(extraConfig) && isTrustedBadgeAllowed(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.trusted_badge,
    url: SEARCH_PRODUCTS_URL.CHECKOUT_FEATURES,
    tags: [{ value: 'Add trusted badge' }, { value: 'Razorpay trusted badge' }],
    group: ['in: Account & Settings'],
    icon: AccountNSettingsIcons.checkout_settings,
    additionalCondition: ({ user, extraConfig }: EligibleProductsTypes): boolean =>
      isCheckoutV2SettingsAllowed(extraConfig) && isTrustedBadgeAllowed(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.invoices,
    url: SEARCH_PRODUCTS_URL.invoices,
    tags: [
      { value: 'Invoice ID' },
      { value: 'Create invoice' },
      { value: 'Manage invoices' },
      { value: 'product' },
    ],
    icon: invoices.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      invoices.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.items,
    url: SEARCH_PRODUCTS_URL.items,
    tags: [{ value: 'Item' }, { value: 'product' }],
    icon: invoices.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isProductViewAllowed(user, 'invoices'),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.payment_links,
    url: SEARCH_PRODUCTS_URL.payment_links,
    tags: [
      { value: 'Link' },
      { value: 'Create payment link' },
      { value: 'Payment link ID' },
      { value: 'product' },
    ],
    icon: payment_links.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      payment_links.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.payment_pages,
    url: SEARCH_PRODUCTS_URL.payment_pages,
    tags: [
      { value: 'Page' },
      { value: 'Create payment page' },
      { value: 'Create custom page' },
      { value: 'product' },
    ],
    icon: payment_pages.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      payment_pages.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.payment_button,
    url: SEARCH_PRODUCTS_URL.payment_button,
    tags: [{ value: 'product' }, { value: 'Button' }, { value: 'Create payment button' }],
    icon: payment_button.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      payment_button.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.route,
    url: SEARCH_PRODUCTS_URL.route,
    tags: [
      { value: 'Transfer payment' },
      { value: 'Transfers' },
      { value: 'Reversals' },
      { value: 'Create transfer' },
      {
        value: 'money transfer',
      },
      { value: 'product' },
    ],
    icon: route.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      route.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.subscriptions,
    url: SEARCH_PRODUCTS_URL.subscriptions,
    tags: [
      { value: 'Subscription plan' },
      { value: 'Recurring payment' },
      { value: 'Subscription link' },
      {
        value: 'Subscription ID',
      },
      { value: 'product' },
    ],
    icon: subscriptions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      isSubscriptionsViewAllowed(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.subscriptions,
    url: SEARCH_PRODUCTS_URL.recurring_payments,
    tags: [
      { value: 'Subscription plan' },
      { value: 'Recurring payment' },
      { value: 'Subscription link' },
      {
        value: 'Subscription ID',
      },
    ],
    icon: subscriptions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      isRecurringPaymentsViewAllowed(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.plans,
    url: SEARCH_PRODUCTS_URL.plans,
    tags: [{ value: 'plan id' }, { value: 'plan name' }, { value: 'billing' }],
    icon: subscriptions.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      isSubscriptionsViewAllowed(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.pricing_plans,
    url: SEARCH_PRODUCTS_URL.PRICING_PLANS,
    tags: [{ value: 'pricing plans' }],
    icon: AccountNSettingsIcons.pricing,
    group: ['in: Account & Settings'],
    additionalCondition: ({ hasEnrolled }: EligibleProductsTypes): boolean => !!hasEnrolled,
    apiCondition: true,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.qr_codes,
    url: SEARCH_PRODUCTS_URL.qr_codes,
    tags: [
      { value: 'UPI QR code' },
      { value: 'Create QR codes' },
      { value: 'QR Code ID' },
      { value: 'UPI QR product' },
      { value: 'product' },
    ],
    icon: qr_codes.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      qr_codes.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.smart_collect,
    url: SEARCH_PRODUCTS_URL.smart_collect,
    tags: [
      { value: 'Virtual account' },
      { value: 'Create customer identifier' },
      { value: 'Customer identifier ID' },
      { value: 'product' },
    ],
    icon: smart_collect.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      smart_collect.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.checkout_rewards,
    url: SEARCH_PRODUCTS_URL.checkout_rewards,
    tags: [{ value: 'Rewards' }, { value: 'Offers product' }, { value: 'product' }],
    icon: checkout_rewards.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      checkout_rewards.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.affordability,
    url: SEARCH_PRODUCTS_URL.affordability,
    tags: [
      { value: 'Affordability widget' },
      { value: 'EMI plan' },
      { value: 'no cost' },
      { value: 'payment plan' },
    ],
    icon: affordability.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      affordability.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.optimizer,
    url: SEARCH_PRODUCTS_URL.optimizer,
    tags: [
      { value: 'Payment gateway' },
      { value: 'multiple' },
      { value: 'more than 1' },
      { value: 'management' },
      { value: 'product' },
    ],
    icon: optimizer.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      optimizer.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.payment_handle,
    url: SEARCH_PRODUCTS_URL.payment_handle,
    tags: [{ value: 'link' }, { value: '@' }, { value: 'create' }, { value: 'product' }],
    icon: payment_handle.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig: ExtraConfig): boolean =>
      payment_handle.additionalCondition(user, extraConfig),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.x_payroll,
    url: SEARCH_PRODUCTS_URL.x_payroll,
    tags: [
      { value: 'payroll' },
      { value: 'salary' },
      { value: 'HRMS' },
      { value: 'human resource management system' },
      { value: 'x' },
    ],
    icon: x_payroll.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      x_payroll.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.cash_advance,
    url: SEARCH_PRODUCTS_URL.cash_advance,
    tags: [
      { value: 'Advance' },
      { value: 'Loan' },
      { value: 'instant loan' },
      { value: 'Instant settlement' },
    ],
    icon: cash_advance.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      cash_advance.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.magic_checkout,
    url: SEARCH_PRODUCTS_URL.magic_checkout,
    tags: [{ value: 'magic' }, { value: 'checkout' }, { value: 'magic checkout' }],
    icon: magic_checkout.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      magic_checkout.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.magic_konnect,
    url: SEARCH_PRODUCTS_URL.magic_konnect,
    tags: [{ value: 'magic' }, { value: 'konnect' }, { value: 'konnect' }],
    icon: magic_checkout.icon,
    additionalCondition: ({ user }: EligibleProductsTypes, extraConfig): boolean =>
      magic_konnect.additionalCondition(user, extraConfig?.abExperiments),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.x_banking,
    url: SEARCH_PRODUCTS_URL.x_banking,
    tags: [{ value: 'Business' }, { value: 'Banking' }, { value: 'Bank' }, { value: 'X' }],
    icon: x_banking.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      x_banking.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.x_corporate_cards,
    url: SEARCH_PRODUCTS_URL.x_corporate_cards,
    tags: [{ value: 'Business Card' }, { value: 'Get Card' }],
    icon: x_corporate_cards.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      x_corporate_cards.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.line_of_credit,
    url: SEARCH_PRODUCTS_URL.line_of_credit,
    tags: [{ value: 'Business' }, { value: 'Loan' }, { value: 'Credit' }],
    icon: line_of_credit.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      line_of_credit.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.capital_loans,
    url: SEARCH_PRODUCTS_URL.capital_loans,
    tags: [{ value: 'Business' }, { value: 'Loan' }, { value: 'Credit' }],
    icon: capital_loans.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      capital_loans.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.riskAndFraud,
    url: SEARCH_PRODUCTS_URL.riskAndFraud,
    tags: [{ value: 'Disputes' }, { value: 'Risk' }, { value: 'Fraud' }, { value: 'Analytics' }],
    icon: riskAndFraud.icon,
    additionalCondition: ({ user }: EligibleProductsTypes): boolean =>
      riskAndFraud.additionalCondition(user),
    apiCondition: false,
  },
  {
    title: SEARCH_PRODUCTS_TITLES.bill_me,
    url: SEARCH_PRODUCTS_URL.bill_me,
    tags: [
      { value: 'BillMe' },
      { value: 'Digital Billing' },
      { value: 'Bills' },
      { value: 'Stores' },
    ],
    icon: bill_me.icon,
    additionalCondition: ({ extraConfig: { abExperiments } }: EligibleProductsTypes): boolean =>
      // TODO: to add 'mode' condition check before Go-Live
      isBillMeMerchant({ abExperiments }),
    apiCondition: false,
  },
];

export const POPULAR_PRODUCTS: ProductType[] = [
  {
    item: {
      title: SEARCH_PRODUCTS_TITLES.transactions,
      url: SEARCH_PRODUCTS_URL.transactions,
      tags: [],
      icon: transactions.icon,
    },
  },
  {
    item: {
      title: SEARCH_PRODUCTS_TITLES.settlements,
      url: SEARCH_PRODUCTS_URL.settlements,
      tags: [],
      icon: settlements.icon,
    },
  },
  {
    item: {
      title: SEARCH_PRODUCTS_TITLES.accountsettings,
      url: SEARCH_PRODUCTS_URL.ACCOUNT_AND_SETTINGS,
      tags: [],
      icon: accountsettings.icon,
    },
  },
  {
    item: {
      title: SEARCH_PRODUCTS_TITLES.refunds,
      url: SEARCH_PRODUCTS_URL.refunds,
      tags: [],
      group: ['in: Transactions'],
      icon: transactions.icon,
    },
  },
];
