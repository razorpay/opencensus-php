import { durationOptionsMap } from 'merchant/views/Transactions/v2/common/constants';
import { generateOptions } from 'merchant/views/Transactions/v2/common/utils';

export const paymentDurationOptionsMap = {
  ...durationOptionsMap,
};
export const paymentDurationSectionOptions = generateOptions(paymentDurationOptionsMap);
export const paymentDurationSectionName = 'Duration';
export const paymentDurationOptions = [
  {
    section: {
      name: paymentDurationSectionName,
      options: paymentDurationSectionOptions,
    },
  },
];

export const searchByOptionsMap = {
  id: 'Payment ID',
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
