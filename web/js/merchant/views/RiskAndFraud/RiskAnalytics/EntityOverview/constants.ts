import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import { OverviewTab, RatioDuration } from './types';

export const OVERVIEW_HEADER = 'For International card payments ';
export const DEFAULT_OVERVIEW_TAB = FRAUD;

export const OVERVIEW_TABS: OverviewTab = {
  [FRAUD]: {
    title: 'Fraud-to-sales ratio',
    valueKey: 'fraud_to_sales_ratio',
    comparisionKey: 'industry_fraud_to_sales_ratio',
    popoverContent:
      'Fraud transactions reported by card networks as a % of total captured transactions in a given time period',
  },
  [DISPUTES]: {
    title: 'Dispute-to-sales ratio',
    valueKey: 'disputes_to_sales_ratio',
    comparisionKey: 'industry_disputes_to_sales_ratio',
    popoverContent:
      'Disputed transactions received in a given period as a % of total captured transactions in the same time period',
  },
  [RISK_DECLINED]: {
    title: 'Risk decline rate',
    valueKey: 'risk_declined_to_sales_ratio',
    comparisionKey: 'industry_risk_declined_to_sales_ratio',
    popoverContent:
      'Transactions declined due to high risk as a % of overall attempted transactions for a given time period',
  },
};

export const RATIO_DURATION: RatioDuration = {
  duration: 6,
  unit: 'months',
};
