import {
  durationOptionsMap,
  ALL_LABEL,
  ALL_VALUE,
} from 'merchant/views/Transactions/v2/common/constants';
import { generateOptions } from 'merchant/views/Transactions/v2/common/utils';

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
export const paymentChannelOptionsMap = {
  [ALL_VALUE]: ALL_LABEL,
  in_person: 'In Person',
  online: 'Online',
};
export const channelSectionName = 'Channel';
export const paymentChannelSectionOptions = generateOptions(paymentChannelOptionsMap);
export const paymentChannelOptions = [
  {
    section: {
      name: channelSectionName,
      options: paymentChannelSectionOptions,
    },
  },
];
