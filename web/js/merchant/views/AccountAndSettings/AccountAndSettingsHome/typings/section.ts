export enum SectionCardDataFields {
  PAYMENT_METHODS = 'payment_methods',
  WEBSITE_APP_SETTINGS = 'website_app_settings',
  BUSINESS_SETTINGS = 'business_settings',
  PAYMENTS_REFUNDS = 'payments_refunds',
  BANK_ACCOUNTS_SETTLEMENTS = 'bank_accounts_settlements',
  NOTIFICATION_SETTINGS = 'notification_settings',
  CHECKOUT_SETTINGS = 'checkout_settings',
  PRICING = 'pricing',
}

export enum PaymentMethodsFields {
  CARDS = 'cards',
  UPI = 'upi',
  NETBANKING = 'netbanking',
  EMI = 'emi',
  WALLET = 'wallet',
  PAYLATER = 'paylater',
  INTERNATIONAL = 'international',
}

export enum WebsiteAppSettingsFields {
  WEBSITE_APP_DETAIL = 'website_app_detail',
  API_KEYS = 'api_keys',
  WEBHOOKS = 'webhooks',
  BUSINESS_WEBSITE_DETAILS = 'business_website_details',
  APPLICATIONS = 'applications',
}

export enum BusinessSettingsFields {
  BUSINESS_DETAILS = 'business_details',
  GST_DETAILS = 'gst_details',
  CUSTOMER_SUPPORT_DETAILS = 'customer_support_details',
  ACCOUNT_DETAILS = 'account_details',
  MANAGE_TEAM = 'manage_team',
  SUPPORT_TICKETS = 'support_tickets',
  INVITATIONS = 'invitations',
  ACTIVATION_DETAILS = 'Activation_details',
}

export enum PaymentRefundsFields {
  BALANCES = 'balances',
  CREDITS = 'credits',
  REMINDERS = 'reminders',
  TRANSACTION_LIMITS = 'transaction_limits',
  FEE_BEARER = 'fee_bearer',
  CAPTURE_REFUND_SETTINGS = 'capture_refund_settings',
  FAILED_PAYMENTS_RETRY = 'failed_payments_retry',
}

export enum NotificationSettingsFields {
  EMAIL = 'email',
  SMS = 'sms',
  WHATSAPP = 'whatsapp',
}

export enum CheckoutSettingsFields {
  BRANDING = 'branding',
  FLASH_CHECKOUT = 'flash_checkout',
  SKIP_MANDATE_SUMMARY_PAGE = 'skip_mandate_summary_page',
  TRUSTED_BADGE = 'trusted_badge',
}

export enum PricingFields {
  PRICING_PLANS = 'pricing_plans',
}

export enum BankAccountSettlementFields {
  BANK_ACCOUNT_DETAILS = 'bank_account_details',
  SETTLEMENT_DETAILS = 'settlement_details',
  FIRS = 'forward_inwards_remittance_statement',
}

export const PaymentMethodsTitles: Record<PaymentMethodsFields, string> = {
  [PaymentMethodsFields.CARDS]: 'Cards',
  [PaymentMethodsFields.UPI]: 'UPI/QR',
  [PaymentMethodsFields.NETBANKING]: 'Netbanking',
  [PaymentMethodsFields.EMI]: 'EMI',
  [PaymentMethodsFields.WALLET]: 'Wallet',
  [PaymentMethodsFields.PAYLATER]: 'Pay Later',
  [PaymentMethodsFields.INTERNATIONAL]: 'International payments',
};

export const WebsiteAppSettingsTitles: Record<WebsiteAppSettingsFields, string> = {
  [WebsiteAppSettingsFields.API_KEYS]: 'API keys',
  [WebsiteAppSettingsFields.APPLICATIONS]: 'Applications',
  [WebsiteAppSettingsFields.BUSINESS_WEBSITE_DETAILS]: 'Business website detail',
  [WebsiteAppSettingsFields.WEBHOOKS]: 'Webhooks',
  [WebsiteAppSettingsFields.WEBSITE_APP_DETAIL]: 'Website/App detail',
};

export const BusinessSettingsTitles: Record<BusinessSettingsFields, string> = {
  [BusinessSettingsFields.ACCOUNT_DETAILS]: 'Account details',
  [BusinessSettingsFields.ACTIVATION_DETAILS]: 'Activation details',
  [BusinessSettingsFields.BUSINESS_DETAILS]: 'Business details',
  [BusinessSettingsFields.CUSTOMER_SUPPORT_DETAILS]: 'Customer support details',
  [BusinessSettingsFields.GST_DETAILS]: 'GST details',
  [BusinessSettingsFields.INVITATIONS]: 'Invitations',
  [BusinessSettingsFields.MANAGE_TEAM]: 'Manage team',
  [BusinessSettingsFields.SUPPORT_TICKETS]: 'Support tickets',
};

export const PaymentRefundsTitles: Record<PaymentRefundsFields, string> = {
  [PaymentRefundsFields.BALANCES]: 'Balances',
  [PaymentRefundsFields.CAPTURE_REFUND_SETTINGS]: 'Capture and refund settings',
  [PaymentRefundsFields.CREDITS]: 'Credits',
  [PaymentRefundsFields.FAILED_PAYMENTS_RETRY]: 'Failed payments retry',
  [PaymentRefundsFields.FEE_BEARER]: 'Fee bearer',
  [PaymentRefundsFields.REMINDERS]: 'Reminders',
  [PaymentRefundsFields.TRANSACTION_LIMITS]: 'Transaction limits',
};

export const BankAccountSettlementTitles: Record<BankAccountSettlementFields, string> = {
  [BankAccountSettlementFields.BANK_ACCOUNT_DETAILS]: 'Bank account details',
  [BankAccountSettlementFields.FIRS]: 'Forward inwards remittance statement',
  [BankAccountSettlementFields.SETTLEMENT_DETAILS]: 'Settlement details',
};

export const NotificationSettingsTitles: Record<NotificationSettingsFields, string> = {
  [NotificationSettingsFields.EMAIL]: 'Email',
  [NotificationSettingsFields.SMS]: 'SMS',
  [NotificationSettingsFields.WHATSAPP]: 'WhatsApp',
};

export const CheckoutSettingsTitles: Record<CheckoutSettingsFields, string> = {
  [CheckoutSettingsFields.BRANDING]: 'Branding',
  [CheckoutSettingsFields.FLASH_CHECKOUT]: 'Flash checkout',
  [CheckoutSettingsFields.SKIP_MANDATE_SUMMARY_PAGE]: 'Skip mandate summary page',
  [CheckoutSettingsFields.TRUSTED_BADGE]: 'Trusted badge',
};

export const PricingTitles: Record<PricingFields, string> = {
  [PricingFields.PRICING_PLANS]: 'Pricing Plans',
};

type ACCOUNT_N_SETTINGS_TITLES =
  | PaymentMethodsFields
  | WebsiteAppSettingsFields
  | BusinessSettingsFields
  | PaymentRefundsFields
  | BankAccountSettlementFields
  | NotificationSettingsFields
  | CheckoutSettingsFields
  | PricingFields;

export const ACCOUNT_N_SETTINGS_TITLES: Record<ACCOUNT_N_SETTINGS_TITLES, string> = {
  ...PaymentMethodsTitles,
  ...WebsiteAppSettingsTitles,
  ...BusinessSettingsTitles,
  ...PaymentRefundsTitles,
  ...BankAccountSettlementTitles,
  ...NotificationSettingsTitles,
  ...CheckoutSettingsTitles,
  ...PricingTitles,
};
