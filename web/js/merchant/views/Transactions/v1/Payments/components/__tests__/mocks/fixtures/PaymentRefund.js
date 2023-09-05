import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentRefund from 'merchant/views/Transactions/v1/Payments/components/PaymentRefund';
import store from 'merchant/store';
import cloneDeep from 'lodash/cloneDeep';

const storeData = store.getState();
export const getStateSpy = jest.spyOn(store, 'getState');
getStateSpy.mockImplementation(() => {
  const clonedStore = cloneDeep(storeData);
  clonedStore.session.user = {
    ...clonedStore.session.user,
    isRefundAllowed: true,
    isPaymentsExtraRefundDetailsEnabled: true,
    isOrgAllowedFunctionality: jest.fn(() => true),
  };
  return clonedStore;
});

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
  openRefundModal: jest.fn(),
  onToggleClick: jest.fn(),
};

export const App = (props) => {
  return <PaymentRefund {...defaultProps} {...props} />;
};
