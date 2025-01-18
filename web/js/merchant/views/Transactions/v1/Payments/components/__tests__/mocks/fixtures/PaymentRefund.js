import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import cloneDeep from 'lodash/cloneDeep';

import store from 'merchant/store';
import PaymentRefund from 'merchant/views/Transactions/v1/Payments/components/PaymentRefund';

const storeData = store.getState();
export const getStateSpy = jest.spyOn(store, 'getState');
getStateSpy.mockImplementation(() => {
  const clonedStore = cloneDeep(storeData);
  clonedStore.session.user = {
    ...clonedStore.session.user,
    isRefundAllowed: true,
    isOrgAllowedFunctionality: jest.fn(() => true),
  };
  return clonedStore;
});

export const mockAbExperiments = {};
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

export const disputes = {
  items: [
    {
      id: 'qweqr123',
      status: 'open',
      phase: 'dispute',
      amount_deducted: 100,
    },
    {
      id: 'er2efe3dwf',
      status: 'open',
    },
    {
      id: 'er2efe3dwf',
      status: 'closed',
    },
  ],
};

export const defaultProps = {
  payment: {
    disputes,
  },
  refunds: {},
  isOptimizerView: false,
  openRefundModal: jest.fn(),
  onToggleClick: jest.fn(),
};

export const App = (props) => {
  mockAbExperiments.payments_extra_refund_details = { variables: { result: 'on' } };
  return <PaymentRefund {...defaultProps} {...props} />;
};
