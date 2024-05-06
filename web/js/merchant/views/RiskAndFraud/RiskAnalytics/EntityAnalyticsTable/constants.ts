import { DISPUTES, FRAUD } from '../constants';

export const TABLE_METRIC_VALUE = 'amount';
export const TABLE_METRIC_COUNT = 'count';
export const DEFAULT_TABLE_METRIC = 'amount';

export const ANALYTICS_TABLE_HEADER = {
  [FRAUD]: {
    [TABLE_METRIC_VALUE]: 'with highest volume of frauds',
    [TABLE_METRIC_COUNT]: 'with highest number of frauds',
  },
  [DISPUTES]: {
    [TABLE_METRIC_VALUE]: 'with highest volume of disputes',
    [TABLE_METRIC_COUNT]: 'with highest number of disputes',
  },
};

export const ANALYTICS_TABLE_COLUMNS = {
  [FRAUD]: {
    [TABLE_METRIC_VALUE]: ['Payment volume', 'Fraud volume', 'Fraud rate'],
    [TABLE_METRIC_COUNT]: ['No. of txns', 'No. of frauds', 'Fraud rate'],
  },
  [DISPUTES]: {
    [TABLE_METRIC_VALUE]: ['Payment volume', 'Disputes volume', 'Disputes rate'],
    [TABLE_METRIC_COUNT]: ['No. of txns', 'No. of disputes', 'Disputes rate'],
  },
};

export const TABLE_METRIC_OPTIONS = [
  { label: 'Count', value: TABLE_METRIC_COUNT },
  { label: 'Value (in ₹)', value: TABLE_METRIC_VALUE },
];
