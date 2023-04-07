import { User } from 'common/typings';
import { showPaymentProviderColumn } from 'merchant/views/Settlements/v3/utils/common';

type HeadersInfo = {
  title: string;
  condition?: (user: User) => boolean;
  tooltip?: string;
}[];

export const settlementListViewHeaders: HeadersInfo = [
  {
    title: 'Created on',
  },
  {
    title: 'Settlement ID',
  },
  {
    title: 'Payment Provider',
    condition: showPaymentProviderColumn,
  },
  {
    title: 'UTR number',
    tooltip:
      'A Unique Transaction Reference (UTR) number available across banks, which can be used to track a specific settlement in your bank account',
  },
  {
    title: 'Net settlement',
  },
  {
    title: 'Status',
  },
  // for adding extra header
  {
    title: '',
  },
];

export const settlementListViewMobileHeaders: HeadersInfo = [
  {
    title: 'Date',
  },
  {
    title: 'Net settlement',
  },
];
