import React from 'react';
import { Badge, Text } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';

import { getFormattedAmountNew } from 'common/utils/rzp-utils';
import { ORDERS_STATUS } from 'merchant/views/GCMS/shared/constants';

import { convertUnixToShortDate } from '../shared/utils';

export const RESELLER_ORDER_LIST_COLUMNS = [
  {
    label: 'Order ID',
    value: (order) => (
      <NavLink key={order.orderId} to="#">
        {order.id}
      </NavLink>
    ),
  },
  {
    label: 'Order Date',
    value: (order) => <Text>{convertUnixToShortDate(order.created_at)}</Text>,
  },
  {
    label: 'Total Quantity',
    value: (order) => <Text>{order.total_quantity}</Text>,
  },
  {
    label: 'Total Value',
    value: (order) => <Text>{getFormattedAmountNew(order.total_amount, 10)}</Text>,
  },
  {
    label: 'Status',
    value: (order) => (
      <Badge color={ORDERS_STATUS[order.status].color}>{ORDERS_STATUS[order.status].label}</Badge>
    ),
  },
];
