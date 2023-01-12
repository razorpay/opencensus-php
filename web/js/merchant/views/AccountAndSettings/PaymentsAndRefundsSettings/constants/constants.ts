import {
  ROUTES_INFO,
  OldAndNewRouteMapInterface,
} from 'merchant/views/AccountAndSettings/typings/routes';
import { NewRoutesPaymentsAndRefundSettingsInterface } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/typings';

export const newRoutes: NewRoutesPaymentsAndRefundSettingsInterface = [
  ROUTES_INFO.REMINDERS,
  ROUTES_INFO.BALANCES,
  ROUTES_INFO.CREDITS,
  ROUTES_INFO.FEE_BEARER,
  ROUTES_INFO.FAILED_PAYMENTS_RETRY,
  ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS,
  ROUTES_INFO.TRANSACTION_LIMITS,
];

export const newAndOldRouteMap: OldAndNewRouteMapInterface = {
  [ROUTES_INFO.CREDITS]: '/credits',
  [ROUTES_INFO.BALANCES]: '/addfunds',
  [ROUTES_INFO.REMINDERS]: '/reminders',
  [ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS]: '/config',
  [ROUTES_INFO.FAILED_PAYMENTS_RETRY]: '/config',
  [ROUTES_INFO.TRANSACTION_LIMITS]: '/profile',
  [ROUTES_INFO.FEE_BEARER]: '/config',
};
