import React from 'react';
import AutoRepayIcon from './AutoRepayIcon';
import ArrowDownIcon from './ArrowDownIcon';

export const VIEWS = {
  WITHDRAW: 'WITHDRAW',
  WITHDRAW_SUCCESS: 'WITHDRAW_SUCCESS',
  WITHDRAW_FAIL: 'WITHDRAW_FAIL',
};

export const REPAYMENT_VIEWS = {
  SUMMARY: 'SUMMARY',
  REPAY_METHOD: 'REPAY_METHOD',
  REPAY_AMOUNT: 'REPAY_AMOUNT',
  RESULT: 'RESULT',
  RESULT_SUCCESS: 'RESULT_SUCCESS',
  RESULT_FAILURE: 'RESULT_FAILURE',
};

export const REPAY_AMOUNT_TYPES = {
  NEXT_REPAYABLE: 'NEXT_REPAYABLE',
  TOTAL_OWED: 'TOTAL_OWED',
  CUSTOM: 'CUSTOM',
};

export const REPAY_METHOD_TYPES = {
  SETTLEMENT_BALANCE: 'SETTLEMENT_BALANCE',
  BANK: 'BANK',
};

export const STATUSES = {
  CREATED: 'CREATED',
  INITIATED: 'INITIATED',
  PENDING: 'PENDING',
  PARTIALLY_PROCESSED: 'PARTIALLY_PROCESSED',
  PROCESSED: 'PROCESSED',
  DISBURSED: 'DISBURSED',
  REJECTED: 'REJECTED',
  FAILED: 'FAILED',
  PARTIALLY_REPAID: 'PARTIALLY_REPAID',
  REPAID: 'REPAID',
  MANUALLY_PROCESSED: 'MANUALLY_PROCESSED',
};

export const REPAYMENT_STATUES = {
  STATUS_UNKNOWN: 'STATUS_UNKNOWN',
  STATUS_COLLECTED: 'STATUS_COLLECTED',
  STATUS_SETTLED: 'STATUS_SETTLED',
  STATUS_PENDING: 'STATUS_PENDING',
  STATUS_FAILED: 'STATUS_FAILED',
};

export const StatusPillClasses = {
  [REPAYMENT_STATUES.STATUS_UNKNOWN]: 'bg-light',
  [REPAYMENT_STATUES.STATUS_PENDING]: 'bg-warning',
  [REPAYMENT_STATUES.STATUS_COLLECTED]: 'bg-success',
  [REPAYMENT_STATUES.STATUS_FAILED]: 'bg-danger',
  [STATUSES.CREATED]: 'bg-light',
  [STATUSES.INITIATED]: 'bg-light',
  [STATUSES.PENDING]: 'bg-warning',
  [STATUSES.PARTIALLY_PROCESSED]: 'bg-info',
  [STATUSES.PROCESSED]: 'bg-info',
  [STATUSES.DISBURSED]: 'bg-info',
  [STATUSES.REJECTED]: 'bg-danger',
  [STATUSES.FAILED]: 'bg-danger',
  [STATUSES.PARTIALLY_REPAID]: 'bg-light',
  [STATUSES.REPAID]: 'bg-success',
  [STATUSES.MANUALLY_PROCESSED]: 'bg-light',
};

export const STATUS_DESCRIPTIONS = {
  [STATUSES.INITIATED]: 'Withdrawal request has been created, and yet to be shared with bank.',
  [STATUSES.PROCESSED]: 'The amount has been successfully disbursed to your bank account.',
  [STATUSES.DISBURSED]: 'The amount has been successfully disbursed to your bank account.',
  [STATUSES.INITIATED]: 'The disbursal of the requested withdrawal is pending on the bank.',
  [STATUSES.CREATED]: 'The withdrawal request has been successfully sent to the bank.',
  [STATUSES.FAILED]: 'Transfer didn’t happen for some reason on the bank side.',
  [STATUSES.REJECTED]: 'Transfer didn’t happen for some reason on the bank side.',
  [STATUSES.PARTIALLY_REPAID]: 'Some part of the repayment has been only collected.',
  [STATUSES.REPAID]: 'The entire repayment amount has been successfully repaid.',
};

export const STATUS_LABELS = {
  [REPAYMENT_STATUES.STATUS_UNKNOWN]: 'Unknown',
  [REPAYMENT_STATUES.STATUS_PENDING]: 'Pending',
  [REPAYMENT_STATUES.STATUS_COLLECTED]: 'Repaid',
  [REPAYMENT_STATUES.STATUS_FAILED]: 'Failed',
  [STATUSES.CREATED]: 'Requested',
  [STATUSES.INITIATED]: 'Requested',
  [STATUSES.PARTIALLY_PROCESSED]: 'Partially Processed',
  [STATUSES.MANUALLY_PROCESSED]: 'Processed',
  [STATUSES.PROCESSED]: 'Processed',
  [STATUSES.DISBURSED]: 'Disbursed',
  [STATUSES.REJECTED]: 'Rejected',
  [STATUSES.FAILED]: 'Failed',
  [STATUSES.PARTIALLY_REPAID]: 'Partially Repaid',
  [STATUSES.REPAID]: 'Repaid',
  [STATUSES.PENDING]: 'Pending',
};

export const STATUS_FILTER_OPTIONS = {
  [STATUSES.CREATED]: 'Created',
  [STATUSES.INITIATED]: STATUS_LABELS[[STATUSES.INITIATED]],
  [STATUSES.PARTIALLY_PROCESSED]: STATUS_LABELS[[STATUSES.PARTIALLY_PROCESSED]],
  [STATUSES.PROCESSED]: STATUS_LABELS[[STATUSES.PROCESSED]],
  [STATUSES.DISBURSED]: STATUS_LABELS[[STATUSES.DISBURSED]],
  [STATUSES.REJECTED]: STATUS_LABELS[[STATUSES.REJECTED]],
  [STATUSES.FAILED]: STATUS_LABELS[[STATUSES.FAILED]],
  [STATUSES.PARTIALLY_REPAID]: STATUS_LABELS[[STATUSES.PARTIALLY_REPAID]],
  [STATUSES.REPAID]: STATUS_LABELS[[STATUSES.REPAID]],
};

export const REPAYMENT_FILTER_STATUS_OPTIONS = {
  [REPAYMENT_STATUES.STATUS_PENDING]: STATUS_LABELS[[REPAYMENT_STATUES.STATUS_PENDING]],
  [REPAYMENT_STATUES.STATUS_COLLECTED]: STATUS_LABELS[[REPAYMENT_STATUES.STATUS_COLLECTED]],
  [REPAYMENT_STATUES.STATUS_FAILED]: STATUS_LABELS[[REPAYMENT_STATUES.STATUS_FAILED]],
};

export const ESTIMATED_AMOUNT_REQUIREMENTS = {
  R100K_TO_500K: '1,00,000 to 5,00,000',
  R500K_TO_1000K: '5,00,000 to 10,00,000',
  R50K_TO_100K: '50,000 to 1,00,000',
  RLESS_THAN_50K: 'Less than 50,000',
  RMORE_THAN_1000K: 'More than 10,00,000',
};

export const CLOSE_OPTIONS = [
  'Need more guidance with feature',
  'Pricing is too High',
  'I want to automate this withdrawal',
  'Other reasons',
];

export const AUTOMATED_WITHDRAWAL_DISABLE_OPTIONS = [
  { label: 'Want to choose withdrawal amount', value: 'Want to choose withdrawal amount' },
  { label: 'Want to choose withdrwal tenure', value: 'Want to choose withdrwal tenure' },
  { label: 'Need more guidance with feature', value: 'Need more guidance with feature' },
  { label: 'Don’t want to be automated', value: 'Don’t want to be automated' },
  { label: 'Other reasons', value: 'Other reasons' },
];
export const CASH_ADVANCE_BASE_URL = '/capital/cash-advance/';

export const CASH_ADVANCE_SECTIONS = {
  OVERVIEW: 'overview',
  WITHDRAWALS: 'withdrawals',
  REPAYMENTS: 'repayments',
  REPAYMENTS_SCHEDULE: 'repayments-schedule',
};

export const WITHDRAW_ERROR_TYPES = {
  MIN_WITHDRAWAL_ERROR: 'MIN_WITHDRAWAL_ERROR',
  MAX_WITHDRAWAL_ERROR: 'MAX_WITHDRAWAL_ERROR',
};

export const PAYMENT_REFERENCE_TYPES = {
  PAYMENT_GATEWAY: 'payment_gateway',
  MANUAL: 'manual',
};

export const PAYMENT_MODES = {
  MANUAL: 'PAYMENT_MODE_MANUAL',
  AUTO_COLLECTION: 'PAYMENT_MODE_AUTOCOLLECTION',
};

export const CASH_ADVANCE_CAROUSEL_VIEW_RULES = {
  NO_WITHDRAWALS: 'NO_WITHDRAWALS',
  FIRST_INITIATED_WITHDRAWAL: 'FIRST_INITIATED_WITHDRAWAL',
  FIRST_PROCESSED_WITHDRAWAL: 'FIRST_PROCESSED_WITHDRAWAL',
  FIRST_NON_REPAID_DISBURSED_WITHDRAWAL: 'FIRST_NON_REPAID_DISBURSED_WITHDRAWAL',
  FIRST_AUTO_REPAID_DISBURSED_WITHDRAWAL: 'FIRST_AUTO_REPAID_DISBURSED_WITHDRAWAL',
  FIRST_MANUAL_REPAID_DISBURSED_WITHDRAWAL: 'FIRST_MANUAL_REPAID_DISBURSED_WITHDRAWAL',
  FIRST_WITHDRAWAL_LAST_REPAYMENT_PENDING: 'FIRST_WITHDRAWAL_LAST_REPAYMENT_PENDING',
  EXHAUSTED_WITHDRAWAL_BALANCE: 'EXHAUSTED_WITHDRAWAL_BALANCE',
  ELIGIBLE_YET_INACTIVE_LAST_FEW_DAYS: 'ELIGIBLE_YET_INACTIVE_LAST_FEW_DAYS',
  REPAY_FAILED_MORE_THAN_THREE: 'REPAY_FAILED_MORE_THAN_THREE',
  REPAYMENTS_TAB_SHOW_DUE: 'REPAYMENTS_TAB_SHOW_DUE',
  REPAYMENTS_TAB_SHOW_NO_DUE: 'REPAYMENTS_TAB_SHOW_NO_DUE',
  TODAY_FIRST_REPAYMENT_FAILURE: 'TODAY_FIRST_REPAYMENT_FAILURE',
  FULL_DAY_REPAYMENT_FAILURE: 'FULL_DAY_REPAYMENT_FAILURE',
  THREE_FULL_DAY_REPAYMENT_FAILURE: 'THREE_FULL_DAY_REPAYMENT_FAILURE',
  REPAYMENT_TAB_VIEW: 'REPAYMENT_TAB_VIEW',
};

export const NOOP = () => {};

export const DEFAULT_COUNT = 25;

export const DEFAULT_PERIOD_OPTIONS = [
  { label: 'All', name: 'all' },
  { label: 'Today', name: 'today' },
  { label: 'Yesterday', name: 'yesterday' },
  { label: 'Last 7 days', name: 'last_7_days' },
  { label: 'Last Month', name: 'last_month' },
  { label: 'Daily', name: 'daily' },
  { label: 'Monthly', name: 'monthly' },
  { label: 'Custom', name: 'dateRange' },
];

export const BALANCE_TYPES = {
  PRINCIPAL: 'PRINCIPAL',
  INTEREST: 'INTEREST',
  CHARGE: 'CHARGE',
};

export const CASH_ADVANCE_CAROUSEL_SLIDES = {
  NON_ZERO_DUE_AMOUNT_PROMPT: 'NON_ZERO_DUE_AMOUNT_PROMPT',
  ZERO_OUTSTANDING_BALANCE: 'ZERO_OUTSTANDING_BALANCE',
  WITHDRAW_PROMPT_DUE_TO_INACTIVITY: 'WITHDRAW_PROMPT_DUE_TO_INACTIVITY',
  REGULAR_WITHDRAWAL_BENEFIT_PROMPT: 'REGULAR_WITHDRAWAL_BENEFIT_PROMPT',
  AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT: 'AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT',
  FULL_DAY_AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT: 'FULL_DAY_AUTO_REPAY_FAILED_MANUAL_REPAY_PROMPT',
  THREE_DAY_REPAYMENT_FAILED_PROMPT: 'THREE_DAY_REPAYMENT_FAILED_PROMPT',
};

export const SLIDE_COLORS = {
  blue: '#0A65D0',
  green: '#1B8565',
  red: '#A04E4E',
};

export const BASE_SLIDE_CONTENT_BY_VARIANT = {
  [SLIDE_COLORS.red]: {
    backgroundPattern: true,
    color: SLIDE_COLORS.red,
    cta: {
      text: 'Repay',
      actionId: 'REPAY',
    },
    calendarColorVariant: 'purple',
    secondaryIcon: <AutoRepayIcon fill={SLIDE_COLORS.red} />,
    rightDataPair: [],
  },
  [SLIDE_COLORS.green]: {
    backgroundPattern: true,
    color: SLIDE_COLORS.green,
    cta: {
      text: 'Withdraw',
      actionId: 'WITHDRAW',
    },
    rightDataPair: [],
    calendarColorVariant: 'pink',
    secondaryIcon: <ArrowDownIcon />,
  },
  [SLIDE_COLORS.blue]: {
    backgroundPattern: true,
    color: SLIDE_COLORS.blue,
    calendarColorVariant: 'pink',
    secondaryIcon: <AutoRepayIcon fill={SLIDE_COLORS.blue} />,
  },
};

export const SCHEDULED_REPAYMENT_LINKS = [
  {
    to: `/capital/cash-advance/${CASH_ADVANCE_SECTIONS.OVERVIEW}`,
    text: 'Overview',
  },
  {
    to: `/capital/cash-advance/${CASH_ADVANCE_SECTIONS.WITHDRAWALS}`,
    text: 'Withdrawals',
  },
  {
    to: `/capital/cash-advance/${CASH_ADVANCE_SECTIONS.REPAYMENTS}`,
    text: 'Repayments',
  },
];

export const COLLECTIONS_PRODUCT_TYPES = {
  CASH_ADVANCE: 'PRODUCT_TYPE_LOC',
  CARDS: 'PRODUCT_TYPE_CARDS',
  LOANS: 'PRODUCT_TYPE_LOANS',
};

export const COLLECTIONS_PAYMENT_REFERENCE_TYPE = {
  ORDER: 'PAYMENT_REFERENCE_TYPE_ORDER',
  CREDIT_REPAYMENT: 'PAYMENT_REFERENCE_TYPE_CREDIT_REPAYMENT',
};

export const COLLECTIONS_BALANCE_TYPE = {
  BALANCE_TYPE_INTEREST: 'BALANCE_TYPE_INTEREST',
  BALANCE_TYPE_PRINCIPAL: 'BALANCE_TYPE_PRINCIPAL',
  BALANCE_TYPE_CHARGE: 'BALANCE_TYPE_CHARGE',
};

export const REPAYMENT_CREATION_SOURCES = {
  USER: 'CREATION_SOURCE_TYPE_USER',
};

export const REPAYMENT_USER_METHODS_TYPE = {
  upi: 'UPI',
  netbanking: 'Netbanking',
};

export const COLLECTIONS_PRODUCT_ENTITY_TYPE = {
  WITHDRAWALS: 'PRODUCT_ENTITY_TYPE_WITHDRAWAL',
};

export const CASH_ADVANCE_FIRST_LOGIN_KEY = 'CASH_ADVANCE_FIRST_LOGIN';

export const REPAYMENT_FREQUENCY_TYPES = {
  CUSTOM: 'CUSTOM',
  BIMONTHLY: 'BIMONTHLY',
  MONTHLY: 'MONTHLY',
};

export const ONHOLD_REASONS = {
  NOT_MIGRATED_TO_GROMOR: 'not_migrated_to_gromor',
  CLD_RISK_POLICY: 'cld_risk_policy',
  END_OF_CREDIT_LINE_TENURE: 'end_of_credit_line_tenure',
  DISABLE_LOC_POST_DPD: 'disable_loc_post_dpd', // available in user.featureflags
};

export const REPAYMENT_TYPES = {
  FLAT_INTEREST: 'FLAT_INTEREST',
  REDUCING_INTEREST: 'REDUCING_INTEREST',
};
