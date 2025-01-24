import {
  convertToMajorUnit as i18ConvertToMajorUnit,
  convertToMinorUnit as i18ConvertToMinorUnit,
  formatNumber,
} from '@razorpay/i18nify-js';

import { getItem, setItem } from 'common/utils/localStorage';

export const SCREENS = {
  WITHDRAW: 'WITHDRAW',
  SUCCESS: 'SUCCESS',
  CANCEL_REASONS: 'CANCEL_REASONS',
} as const;

export const MODAL_PADDING = 'spacing.6';

/** Used to override side-nav drawer */
export const MODAL_ZINDEX = 1111;

export const MODAL_HEADER_BG = 'surface.background.gray.subtle';

export const SETTLEMENT_TYPES = {
  ODS: 'ODS',
  ROUTE: 'ROUTE',
} as const;

export const SETTLEMENT_TYPE_SMART = 'settlement_payout_type_smart';
export const SETTLEMENT_TYPE_INSTANT = 'settlement_payout_type_instant';

export type SettlementTypes = keyof typeof SETTLEMENT_TYPES;

export type SettlementTransactionType =
  | typeof SETTLEMENT_TYPE_SMART
  | typeof SETTLEMENT_TYPE_INSTANT
  | '';

/**
 * Address JS floating-point precision errors
 * eg: Input 1200.10 returns 120009.99999999999
 * fixed by rounding the value here
 */
export const convertToMinorUnit = (
  amount: number,
  options: {
    currency: 'INR';
  },
) => {
  return Math.round(i18ConvertToMinorUnit(amount, { currency: options.currency }));
};

/**
 * Address JS floating-point precision errors and supports decimal truncation
 */
export const convertToMajorUnit = (
  amount: number,
  {
    currency,
    keepDecimal = true,
  }: {
    currency: 'INR';
    keepDecimal?: boolean;
  },
) => {
  let amountInRs = i18ConvertToMajorUnit(amount, { currency });
  amountInRs = parseFloat(amountInRs.toFixed(2)); // Don't use toFixed(0) for decimal removal as it rounds value. eg:- 12.99 to 13
  return keepDecimal ? amountInRs : Math.floor(amountInRs);
};

/**
 * Important: Returns Rupees string value that can't be converted back to number again
 * eg:- 1,23,000 or ₹1,23,000 from minor unit value
 *  */
export const formatAmount = (
  amountInPaise: number,
  currency: 'INR',
  keepDecimal = false,
  includeCurrencySymbol = true,
) => {
  const decimal = keepDecimal ? 2 : 0;
  return formatNumber(convertToMajorUnit(amountInPaise, { currency, keepDecimal }), {
    currency: includeCurrencySymbol ? currency : undefined,
    intlOptions: {
      minimumFractionDigits: decimal,
      maximumFractionDigits: decimal,
    },
  });
};

export const midLimitGTMViewedStatus = {
  isViewed: () => Boolean(getItem('ods_gtm_viewed')),
  setViewed: () => setItem('ods_gtm_viewed', 'true'),
};

export const MIN_SMART_SETTLEMENT_AMOUNT = 50000000;
export const MAX_IMPS_AMOUNT = 5000000000;
