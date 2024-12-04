export const TODAY = 'today';
export const LAST_7_DAYS = 'last7Days';
export const LAST_30_DAYS = 'last30Days';
export const LAST_90_DAYS = 'last90Days';
export const CURRENT_YEAR_JAN_TILL_DATE = 'currentYearJanTillDate';
export const THIS_FINANCIAL_YEAR = 'thisFinancialYear';

export const paymentStatusVariantMap = {
  created: {
    variant: 'notice',
  },
  captured: {
    variant: 'positive',
  },
  authenticated: {
    variant: 'neutral',
  },
  authorized: {
    variant: 'neutral',
  },
  failed: {
    variant: 'negative',
  },
  refunded: {
    variant: 'information',
  },
  pending: {
    variant: 'primary',
  },
};
