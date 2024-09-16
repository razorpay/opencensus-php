import React from 'react';
import 'jest-location-mock';
import DisputeList from 'apps/self-serve/src/App/Transactions/v2/Disputes/DisputeList';
import { render } from 'apps/self-serve/src/services/test/test-utils';

export const columns = ['Dispute ID', 'Amount', 'Stage', 'Respond By', 'Raised on', 'Status'];

const createMockDisputes = () => {
  const id = `disp_${Math.floor(Math.random() * 100)}`;
  const payment_id = `pay_${Math.floor(Math.random() * 100)}`;

  return {
    id,
    entity: 'dispute',
    payment_id,
    amount: 50,
    currency: 'IN',
    respond_by: 1721068200,
    status: 'won',
    phase: 'chargeback',
    created_at: 1720521415,
    resourceUrl: 'disputes',
  };
};

export const mockDisputeItems = (n = 25) => {
  const items = [];
  for (let i = 0; i < n; i++) {
    items.push(createMockDisputes());
  }
  return items;
};

export const renderApp = (props = {}) => {
  return render(<DisputeList location={{}} {...props} />);
};
