import React from 'react';
import * as sharedUtils from '@libs/shared-utils';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import RefundsTable from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsTable';
import 'jest-location-mock';

export const useMobileSpy = jest.spyOn(sharedUtils, 'useMobile');

export const desktopColumns = [
  'Refund ID',
  'Payment ID',
  'Created on',
  'Amount',
  'Status',
  'Actions',
];

export const mobileColumns = ['Amount', 'Status', 'Actions'];

const createMockRefund = () => {
  const id = `rfnd_${Math.floor(Math.random() * 100)}`;
  const payment_id = `payment_id_${Math.floor(Math.random() * 100)}`;

  const random = Math.floor(Math.random() * 3);
  let arn = null;
  let rrn = null;
  if (random === 1) {
    arn = `arn_${Math.floor(Math.random() * 100)}`;
  } else if (random === 2) {
    rrn = `rrn_${Math.floor(Math.random() * 100)}`;
  }

  return {
    id,
    entity: 'refund',
    amount: 20000,
    currency: 'INR',
    payment_id,
    batch_id: null,
    acquirer_data: {
      arn,
      rrn,
    },
    createdAt: 1665222570,
    status: 'processing',
  };
};

export const mockFetchRefundItems = (n = 25) => {
  const items = [];
  for (let i = 0; i < n; i++) {
    items.push(createMockRefund());
  }
  return items;
};

export const renderApp = (props = {}) => {
  return render(<RefundsTable location={{}} {...props} />);
};
