import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  METRIC_COUNT,
  METRIC_VALUE,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';

import { SelectedGraphOption } from './types';
import { generateChartOptions } from './utils';

export const TOTAL_SALES_VALUE = 'total_sales';
export const VALUE_OF_REPORTED_ENTITY = 'entity';
export const ENTITY_RATIO = 'entity_ratio';
export const CHART_ORDER = [TOTAL_SALES_VALUE, VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO];

export const DEFAULT_CHART_OPTIONS: {
  [x: string]: SelectedGraphOption[];
} = {
  [FRAUD]: [VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO],
  [DISPUTES]: [VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO],
  [RISK_DECLINED]: [ENTITY_RATIO],
};

const FRAUD_VALUE_OPTIONS = [
  'Total sales value',
  'Value of reported frauds',
  'Fraud-to-sales ratio',
];

const FRAUD_COUNT_OPTIONS = [
  'Number of transactions',
  'Number of reported frauds',
  'Fraud-to-sales ratio',
];

const DISPUTES_VALUE_OPTIONS = [
  'Total sales value',
  'Value of reported disputes',
  'Disputes-to-sales ratio',
];

const DISPUTES_COUNT_OPTIONS = [
  'Number of transactions',
  'Number of reported disputes',
  'Disputes-to-sales ratio',
];

const RISK_DECLINED_OPTIONS = ['Risk decline rate'];

export const CHART_OPTIONS_MAPPING = {
  [FRAUD]: {
    [METRIC_COUNT]: generateChartOptions(FRAUD_COUNT_OPTIONS, [
      TOTAL_SALES_VALUE,
      VALUE_OF_REPORTED_ENTITY,
      ENTITY_RATIO,
    ]),
    [METRIC_VALUE]: generateChartOptions(FRAUD_VALUE_OPTIONS, [
      TOTAL_SALES_VALUE,
      VALUE_OF_REPORTED_ENTITY,
      ENTITY_RATIO,
    ]),
  },
  [DISPUTES]: {
    [METRIC_COUNT]: generateChartOptions(DISPUTES_COUNT_OPTIONS, [
      TOTAL_SALES_VALUE,
      VALUE_OF_REPORTED_ENTITY,
      ENTITY_RATIO,
    ]),
    [METRIC_VALUE]: generateChartOptions(DISPUTES_VALUE_OPTIONS, [
      TOTAL_SALES_VALUE,
      VALUE_OF_REPORTED_ENTITY,
      ENTITY_RATIO,
    ]),
  },
  [RISK_DECLINED]: {
    [METRIC_COUNT]: generateChartOptions(RISK_DECLINED_OPTIONS, [ENTITY_RATIO]),
    [METRIC_VALUE]: generateChartOptions(RISK_DECLINED_OPTIONS, [ENTITY_RATIO]),
  },
};

export const CHART_COLORS_MAPPING = {
  [TOTAL_SALES_VALUE]: '#75A3FF',
  [VALUE_OF_REPORTED_ENTITY]: '#FD9D96',
  [ENTITY_RATIO]: '#1566F1',
};

export const LEGEND_COLORS_MAPPING = {
  ...CHART_COLORS_MAPPING,
  [ENTITY_RATIO]: 'surface.background.primary.intense', // Blade icon will only accept theme color
};

export const FRAUD_CHART_LABEL = {
  [METRIC_COUNT]: {
    x: 'fraud_ratio_analytics',
    y1: 'Number of transactions / reported frauds',
    y2: 'Fraud-to-sales ratio (in %)',
  },
  [METRIC_VALUE]: {
    x: 'fraud_ratio_analytics',
    y1: 'Volume of total sales / frauds (in ₹)',
    y2: 'Fraud-to-sales ratio (in %)',
  },
};

export const DISPUTES_CHART_LABEL = {
  [METRIC_COUNT]: {
    x: 'disputes_ratio_analytics',
    y1: 'Number of transactions / reported disputes',
    y2: 'Fraud-to-sales ratio (in %)',
  },
  [METRIC_VALUE]: {
    x: 'disputes_ratio_analytics',
    y1: 'Volume of total sales / disputes (in ₹)',
    y2: 'Fraud-to-sales ratio (in %)',
  },
};

export const RISK_DECLINED_CHART_LABEL = {
  [METRIC_COUNT]: {
    x: 'risk_declined_ratio_analytics',
    y1: 'Dispute-to-sales ratio (in %)',
  },
  [METRIC_VALUE]: {
    x: 'risk_declined_ratio_analytics',
    y1: 'Dispute-to-sales ratio (in %)',
  },
};

export const CHART_LABELS_MAPPING = {
  [FRAUD]: FRAUD_CHART_LABEL,
  [DISPUTES]: DISPUTES_CHART_LABEL,
  [RISK_DECLINED]: RISK_DECLINED_CHART_LABEL,
};
