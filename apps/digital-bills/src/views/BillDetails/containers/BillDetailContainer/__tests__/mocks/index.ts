import type {
  Channel,
  BillStatus,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

export const CHANNEL_MOCK = [
  {
    attemptedAt: '2021-12-03T10:15:30Z',
    channel: 'SMS' as Channel,
    deliveredAt: '2021-12-03T10:15:30Z',
    receiver: '+91-1234 5678 21',
    createdAt: '2021-12-03T10:15:30Z',
    readAt: '2021-12-03T10:15:30Z',
    status: 'DELIVERED' as BillStatus,
  },
];

export const DELIVERY_REPORT_MOCK = {
  sms: CHANNEL_MOCK,
  email: CHANNEL_MOCK,
  whatsapp: CHANNEL_MOCK,
};
