import { durationOptionsMap } from 'merchant/views/Transactions/v2/common/constants';
import {
  endOfDay,
  generateOptions,
  getFromTime,
} from 'merchant/views/Transactions/v2/common/utils';

export const refundsDurationOptionsMap = { ...durationOptionsMap };
export const refundsDurationSectionOptions = generateOptions(refundsDurationOptionsMap);
export const refundsDurationSectionName = 'Duration';
export const refundsDurationOptions = [
  {
    section: {
      name: refundsDurationSectionName,
      options: refundsDurationSectionOptions,
    },
  },
];
export const defaultCustomDuration = {
  from: getFromTime('last90Days').unix(),
  to: endOfDay.unix(),
};

export const statusOptionsMap = {
  all: 'All',
  processing: 'Processing',
  processed: 'Processed',
  failed: 'Failed',
};
export const statusSectionOptions = generateOptions(statusOptionsMap);
export const statusSectionName = 'Status';
export const statusOptions = [
  {
    section: {
      name: statusSectionName,
      options: statusSectionOptions,
    },
  },
];

export const searchByOptionsMap = {
  payment_id: 'Payment ID',
  id: 'Refund ID',
};
export const searchBySectionOptions = generateOptions(searchByOptionsMap);
export const searchBySectionName = 'Search by';
export const searchByOptions = [
  {
    section: {
      name: searchBySectionName,
      options: searchBySectionOptions,
    },
  },
];
