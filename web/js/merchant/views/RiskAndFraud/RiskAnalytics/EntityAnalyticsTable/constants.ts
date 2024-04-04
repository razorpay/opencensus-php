import { DISPUTES, FRAUD } from '../constants';

export const ANALYTICS_TABLE_HEADER = {
  [FRAUD]: 'with highest number of frauds',
  [DISPUTES]: 'with highest number of disputes',
};

export const ANALYTICS_TABLE_COLUMNS = {
  [FRAUD]: ['No. of txns', 'No. of frauds', 'Fraud rate'],
  [DISPUTES]: ['No. of txns', 'No. of disputes', 'Disputes rate'],
};

export const TABLE_METRIC_OPTIONS = [
  { label: 'Count', value: 'count' },
  { label: 'Value (in ₹)', value: 'amount' },
];
