import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  METRIC_COUNT,
  METRIC_VALUE,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { generateChartOptions } from './utils';

export const TOTAL_SALES_VALUE = 'total_sales';
export const VALUE_OF_REPORTED_ENTITY = 'entity';
export const ENTITY_RATIO = 'entity_ratio';
export const CHART_ORDER = [TOTAL_SALES_VALUE, VALUE_OF_REPORTED_ENTITY, ENTITY_RATIO];

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
  [ENTITY_RATIO]: 'brand.primary.500', // Blade icon will only accept theme color
};
