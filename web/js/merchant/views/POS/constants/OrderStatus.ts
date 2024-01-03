import { CheckIcon, ClockIcon, SlashIcon } from '@razorpay/blade/components';
import moment from 'moment';

import { OrderDetailsItem, OrderStatusMetaData, OrderStatusTypes } from 'merchant/views/POS/types';

export const ORDER_STATUS_META_DATA: Record<OrderStatusTypes, OrderStatusMetaData> = {
  ORDER_CONFIRMED: {
    key: 'ORDER_CONFIRMED',
    name: 'ORDER CONFIRMED',
    icon: ClockIcon,
    variant: 'notice',
  },
  ORDER_RECEIVED: {
    key: 'ORDER_RECEIVED',
    name: 'ORDER RECEIVED',
    icon: ClockIcon,
    variant: 'notice',
  },
  ORDER_DELIVERED: {
    key: 'ORDER_DELIVERED',
    name: 'DELIVERED',
    icon: CheckIcon,
    variant: 'positive',
  },
  ORDER_REJECTED: {
    key: 'ORDER_REJECTED',
    name: 'ORDER REJECTED',
    icon: SlashIcon,
    variant: 'negative',
  },
  REFUND_PENDING: {
    key: 'REFUND_PENDING',
    name: 'REFUND PENDING',
    icon: ClockIcon,
    variant: 'notice',
  },
  REFUND_INITIATED: {
    key: 'REFUND_INITIATED',
    name: 'REFUND INITIATED',
    icon: ClockIcon,
    variant: 'notice',
  },
  REFUND_COMPLETED: {
    key: 'REFUND_COMPLETED',
    name: 'AMOUNT REFUNDED',
    icon: CheckIcon,
    variant: 'positive',
  },
};

export const ORDER_STATUS_TIMELINE_ITEMS = {
  ORDER_RECEIVED: [
    {
      stage: ORDER_STATUS_META_DATA.ORDER_RECEIVED,
      status: 'done',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_CONFIRMED,
      status: 'active',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_DELIVERED,
      status: 'pending',
      descriptionFn: () => null,
    },
  ],
  ORDER_DELIVERED: [
    {
      stage: ORDER_STATUS_META_DATA.ORDER_RECEIVED,
      status: 'done',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_CONFIRMED,
      status: 'done',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_DELIVERED,
      status: 'active',
      descriptionFn: ({ delivered_at }: OrderDetailsItem) =>
        moment.unix(delivered_at).format('MMMM DD, YYYY'),
    },
  ],
  ORDER_REJECTED: [
    {
      stage: ORDER_STATUS_META_DATA.ORDER_RECEIVED,
      status: 'done',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_REJECTED,
      status: 'failed',
      descriptionFn: ({ rejection_reasons }: OrderDetailsItem) =>
        rejection_reasons?.error_description,
    },
    {
      stage: ORDER_STATUS_META_DATA.REFUND_PENDING,
      status: 'active',
      descriptionFn: () => null,
    },
  ],
  REFUND_INITIATED: [
    {
      stage: ORDER_STATUS_META_DATA.ORDER_RECEIVED,
      status: 'done',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_REJECTED,
      status: 'failed',
      descriptionFn: ({ rejection_reasons }: OrderDetailsItem) =>
        rejection_reasons?.error_description,
    },
    {
      stage: ORDER_STATUS_META_DATA.REFUND_INITIATED,
      status: 'active',
      descriptionFn: ({ refund }: OrderDetailsItem) =>
        refund ? moment.unix(refund.created_at).format('MMMM DD, YYYY') : null,
    },
  ],
  REFUND_COMPLETED: [
    {
      stage: ORDER_STATUS_META_DATA.ORDER_RECEIVED,
      status: 'done',
      descriptionFn: ({ created_at }: OrderDetailsItem) =>
        moment.unix(created_at).format('MMMM DD, YYYY'),
    },
    {
      stage: ORDER_STATUS_META_DATA.ORDER_REJECTED,
      status: 'failed',
      descriptionFn: ({ rejection_reasons }: OrderDetailsItem) =>
        rejection_reasons?.error_description,
    },
    {
      stage: ORDER_STATUS_META_DATA.REFUND_COMPLETED,
      status: 'active',
      descriptionFn: ({ refund }: OrderDetailsItem) =>
        refund ? moment.unix(refund.created_at).format('MMMM DD, YYYY') : null,
    },
  ],
};
