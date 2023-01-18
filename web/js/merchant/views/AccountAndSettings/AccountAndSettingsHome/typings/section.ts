export enum SectionCardDataFields {
  PAYMENT_METHODS = 'payment_methods',
  WEBSITE_APP_SETTINGS = 'website_app_settings',
  BUSINESS_SETTINGS = 'business_settings',
  PAYMENTS_REFUNDS = 'payments_refunds',
  BANK_ACCOUNTS_SETTLEMENTS = 'bank_accounts_settlements',
  NOTIFICATION_SETTINGS = 'notification_settings',
  CHECKOUT_SETTINGS = 'checkout_settings',
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
}

export enum BusinessSettingsFields {
  CONTACT_DETAILS = 'contact_details',
  BUSINESS_DETAILS = 'business_details',
  GST_DETAILS = 'gst_details',
  CUSTOMER_SUPPORT_DETAILS = 'customer_support_details',
  ACCOUNT_DETAILS = 'account_details',
  MANAGE_TEAM = 'manage_team',
  SUPPORT_TICKETS = 'support_tickets',
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

export enum BankAccountSettlementFields {
  BANK_ACCOUNT_DETAILS = 'bank_account_details',
  SETTLEMENT_DETAILS = 'settlement_details',
  FIRS = 'forward_inwards_remittance_statement',
}
