import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

export type NewRoutes =
  | ROUTES_INFO.BALANCES
  | ROUTES_INFO.REMINDERS
  | ROUTES_INFO.CREDITS
  | ROUTES_INFO.FEE_BEARER
  | ROUTES_INFO.FAILED_PAYMENTS_RETRY
  | ROUTES_INFO.TRANSACTION_LIMITS
  | ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS;

export type NewRoutesPaymentsAndRefundSettingsInterface = NewRoutes[];
