const docsDomain = 'https://razorpay.com/docs';
export const utmParam = '?utm_source=razorpay-dashboard&utm_medium=docs-link&utm_campaign=dash-exp';
const accountSettings = 'account-settings';
const paymentDashboard = 'payments/dashboard';
const paymentInvoices = 'payments/invoices';
const paymentRoute = 'payments/route';
const checkoutSettings = 'checkout-settings';
const businessSettings = 'business-settings';
const notificationsSettings = 'notification-settings';
const paymentsAndRefundsSettings = 'payments-and-refunds-settings';
const webAppSettings = 'website-app-settings';
const bandAccountsSettlements = 'bank-accounts-settlements';
const internationalSettings = 'international-settings';
const partners = 'partners';

export const docsUrl = {
  ACCOUNT_SETTING_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/${utmParam}`,
  BANK_ACCOUNT_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/bank-account-details/${utmParam}`,
  SETTLEMENT_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/settlement-details/${utmParam}`,
  ACCOUNT_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/account-details/${utmParam}`,
  BUSINESS_DETAILS_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/business-details/${utmParam}`,
  GST_DETAILS_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/gst-details/${utmParam}`,
  CUSTOMER_SUPPORT_DETAILS_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/customer-support-details/${utmParam}`,
  MANAGE_TEAM_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/manage-team/${utmParam}`,
  INVITATION_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/manage-team/${utmParam}`,
  SUPPORT_TICKET_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/support-tickets/${utmParam}`,
  ACTIVATION_DETAILS_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/activation-details/${utmParam}`,
  BRANDING_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/branding/${utmParam}`,
  FLASH_CHECKOUT_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/flash-checkout/${utmParam}`,
  MANDATE_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/mandate-summary-page/${utmParam}`,
  TRUSTED_BADGE_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/trusted-business/${utmParam}`,
  EMAIL_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/email-sms-whatsapp/${utmParam}#manage-email-notifications`,
  SMS_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/email-sms-whatsapp/${utmParam}#manage-sms-notifications`,
  WHATSAPP_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/email-sms-whatsapp/${utmParam}#manage-whatsapp-notifications`,
  BALANCE_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/balances/${utmParam}`,
  CREDIT_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/credits/${utmParam}`,
  REMAINDER_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/reminders/${utmParam}`,
  TRANSACTION_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/transaction-limits/${utmParam}`,
  CAPTURE_REFUND_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/capture-refund/${utmParam}`,
  BUSINESS_DETAIL_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/business-website-details/${utmParam}`,
  API_KEY_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/api-keys/${utmParam}`,
  WEBHOOK_DOC_URL: `${docsDomain}/${paymentDashboard}/${accountSettings}/webhooks/${utmParam}`,
  CREATE_INVOICE_DOC_URL: `${docsDomain}/${paymentInvoices}/create/${utmParam}`,
  ROUTE_TRANSFER_DOC_URL: `${docsDomain}/${paymentRoute}/transfer-funds-to-linked-accounts/${utmParam}`,
  ROUTE_REVERSAL_DOC_URL: `${docsDomain}/${paymentRoute}/reversal/${utmParam}`,
  ROUTE_ACCOUNT_DOC_URL: `${docsDomain}/${paymentRoute}/linked-account/${utmParam}#add-and-manage-linked-accounts`,
  OPTIMIZER_DOC_URL: `${docsDomain}/payments/optimizer/add-payment-providers/${utmParam}`,
  PRODUCT_DOC_URL: `${docsDomain}/payments/payment-pages/${utmParam}`,
  CREATE_QR_CODE: `${docsDomain}/payments/qr-codes/create/${utmParam}`,
  SMARTCOLLECT_DOC_URL: `${docsDomain}/payments/smart-collect/create/${utmParam}`,
  DASHBOARD_TRANSACTION_URL_DOC: `${docsDomain}/${paymentDashboard}/${utmParam}#transactions`,
  REPORT_URL: `${docsDomain}/${paymentDashboard}/reports/${utmParam}#download-reports`,
  CREATE_SUBS_BUTTON_DOC_URL: `${docsDomain}/payments/payment-button/subscription-buttons/embed/${utmParam}`,
  AFFILIATE_ACCOUNT_PAYMENT: `${docsDomain}/partners/resellers/add-submerchants/${utmParam}`,
  EARNING_URL: `${docsDomain}/partners/commissions/${utmParam}`,
  PLAYBOOK_URL: `${docsDomain}/partners/playbook/${utmParam}`,
  CREATE_ITEM_DOC_URL: `${docsDomain}/${paymentInvoices}/items/dashboard/${utmParam}`,
  AFFORDABILITY_DOC_URL: `${docsDomain}/payments/payment-gateway/affordability/${utmParam}`,
  SETTLEMENTS_DOC_URL: `${docsDomain}/payments/settlements/dashboard/${utmParam}`,
  CREATE_PAYMENT_BUTTON_DOC_URL: `${docsDomain}/payments/payment-button/quick-pay/${utmParam}`,
  FIRS_DOC_URL: `${docsDomain}/payments/dashboard/account-settings/firs/${utmParam}`,
  INVITATION_PAYMENT_CODES_DOC_URL: `${docsDomain}/payments/dashboard/account-settings/international-payment-codes/${utmParam}`,
  OPTIMIZER_RULE_DOC_URL: `${docsDomain}/payments/optimizer/create-custom-rule/${utmParam}`,
  POS_PARTNERSHIP_DOC_URL: `${docsDomain}/partners/pos/${utmParam}`,
  PAYMENT_CONFIGURATION_DOC_URL: `${docsDomain}/payments/dashboard/account-settings/payment-configuration/`,
  CHECKOUT_FEATURE_DOC_URL: `${docsDomain}/payments/dashboard/account-settings/checkout-features/`,
  CHECKOUT_STYLING_DOC_URL: `${docsDomain}/payments/dashboard/account-settings/checkout-styling/`,
};

export const docsUrlTabs = {
  [`/${checkoutSettings}/branding`]: docsUrl.BRANDING_DOC_URL,
  [`/${checkoutSettings}/flash-checkout`]: docsUrl.FLASH_CHECKOUT_DOC_URL,
  [`/${checkoutSettings}/skip-mandatory-summary-page`]: docsUrl.MANDATE_DOC_URL,
  [`/${checkoutSettings}/trustedbadge`]: docsUrl.TRUSTED_BADGE_DOC_URL,
  [`/${checkoutSettings}/payment-configuration`]: docsUrl.PAYMENT_CONFIGURATION_DOC_URL,
  [`/${checkoutSettings}/checkout-features`]: docsUrl.CHECKOUT_FEATURE_DOC_URL,
  [`/${checkoutSettings}/checkout-styling`]: docsUrl.CHECKOUT_STYLING_DOC_URL,
  [`/${businessSettings}/contact`]: docsUrl.ACCOUNT_DOC_URL,
  [`/${businessSettings}/business`]: docsUrl.BUSINESS_DETAILS_DOC_URL,
  [`/${businessSettings}/customer-support`]: docsUrl.CUSTOMER_SUPPORT_DETAILS_URL,
  [`/${businessSettings}/gst`]: docsUrl.GST_DETAILS_DOC_URL,
  [`/${businessSettings}/team`]: docsUrl.MANAGE_TEAM_DOC_URL,
  [`/${businessSettings}/invitations`]: docsUrl.INVITATION_DOC_URL,
  [`/${businessSettings}/ticket-support/tickets/merchant`]: docsUrl.SUPPORT_TICKET_DOC_URL,
  [`/${businessSettings}/account-activation-details`]: docsUrl.ACTIVATION_DETAILS_DOC_URL,
  [`/${notificationsSettings}/email`]: docsUrl.EMAIL_DOC_URL,
  [`/${notificationsSettings}/sms`]: docsUrl.SMS_DOC_URL,
  [`/${notificationsSettings}/sms`]: docsUrl.WHATSAPP_DOC_URL,
  [`/${paymentsAndRefundsSettings}/balances`]: docsUrl.BALANCE_DOC_URL,
  [`/${paymentsAndRefundsSettings}/credits`]: docsUrl.CREDIT_DOC_URL,
  [`/${paymentsAndRefundsSettings}/reminders`]: docsUrl.REMAINDER_DOC_URL,
  [`/${paymentsAndRefundsSettings}/transaction-limits`]: docsUrl.TRANSACTION_DOC_URL,
  [`/${paymentsAndRefundsSettings}/capture-refund-settings`]: docsUrl.CAPTURE_REFUND_DOC_URL,
  [`/${webAppSettings}/business-website-details`]: docsUrl.BUSINESS_DETAIL_DOC_URL,
  [`/${webAppSettings}/api-keys`]: docsUrl.API_KEY_DOC_URL, // nosemgrep : secrets.generic.api-key.string.string
  [`/${webAppSettings}/webhooks`]: docsUrl.WEBHOOK_DOC_URL,
  [`/${bandAccountsSettlements}/bank-account-details`]: docsUrl.BANK_ACCOUNT_DOC_URL,
  [`/${bandAccountsSettlements}/settlement-details`]: docsUrl.SETTLEMENT_DOC_URL,
  [`/${internationalSettings}/firs`]: docsUrl.FIRS_DOC_URL,
  [`/${internationalSettings}/international-payment-codes`]:
    docsUrl.INVITATION_PAYMENT_CODES_DOC_URL,
  [`/${partners}/submerchants`]: docsUrl.AFFILIATE_ACCOUNT_PAYMENT,
  [`/${partners}/submerchants/pos`]: docsUrl.POS_PARTNERSHIP_DOC_URL,
  [`/${partners}/earnings/daily`]: docsUrl.EARNING_URL,
};
