import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

export const ROUTE_MAP = {
  [ROUTES_INFO.BRANDING]: 'Branding',
  [ROUTES_INFO.FLASH_CHECKOUT]: 'Flash checkout',
  [ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE]: 'Skip mandatory summary page',
  [ROUTES_INFO.TRUSTED_BADGE]: 'Trusted badge',
  [ROUTES_INFO.EMAIL_NOTIFICATIONS]: 'Email',
  [ROUTES_INFO.SMS_NOTIFICATIONS]: 'SMS',
  [ROUTES_INFO.WHATSAPP_NOTIFICATIONS]: 'WhatsApp',
  [ROUTES_INFO.API_KEYS]: 'API keys',
  [ROUTES_INFO.WEBSITE_APP_SETTINGS]: 'Website & app settings',
  [ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS]: 'Business website details',
  [ROUTES_INFO.WEBHOOKS]: 'Webhooks',
  [ROUTES_INFO.BALANCES]: 'Balances',
  [ROUTES_INFO.CREDITS]: 'Credits',
  [ROUTES_INFO.REMINDERS]: 'Reminders',
  [ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS]: 'Capture and refund settings',
  [ROUTES_INFO.TRANSACTION_LIMITS]: 'Transaction limits',
  [ROUTES_INFO.FEE_BEARER]: 'Fee bearer',
  [ROUTES_INFO.FAILED_PAYMENTS_RETRY]: 'Failed payments retry',
  [ROUTES_INFO.CONTACT_DETAILS]: 'Contact details',
  [ROUTES_INFO.ACCOUNT_DETAILS]: 'Account details',
  [ROUTES_INFO.BUSINESS_DETAILS]: 'Business details',
  [ROUTES_INFO.GST_DETAILS]: 'GST details',
  [ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS]: 'Customer support details',
  [ROUTES_INFO.MANAGE_TEAM_DETAILS]: 'Manage team',
  [ROUTES_INFO.SUPPORT_TICKETS_MERCHANT]: 'Support history',
  [ROUTES_INFO.SUPPORT_TICKETS_AGENT]: 'Support history',
  [ROUTES_INFO.BANK_ACCOUNT_DETAILS]: 'Bank account details',
  [ROUTES_INFO.SETTLEMENT_DETAILS]: 'Settlement details',
  [ROUTES_INFO.FIRS]: 'Forward inwards remittance statement',
  [ROUTES_INFO.TEAM_INVITATIONS]: 'Invitations',
};

export const accountAndSettingsLink = {
  label: 'Account & Settings',
  link: ROUTES_INFO.ACCOUNT_AND_SETTINGS,
};
