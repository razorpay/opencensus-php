import React from 'react';
import { render } from 'test-utils';
import PaymentTransfers from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransfers';
import { mockPaymentTransfers } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/handlers';
import * as models from 'merchant/views/Transactions/model';

jest.mock('common/hooks/useMobile', () => ({
  ...jest.requireActual('common/hooks/useMobile'),
  useMobile: jest.fn(),
}));

export const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

export const fetchTransfersFnSpy = jest.spyOn(models, 'fetchTransfersFn');

export const defaultPaymentDetails = {
  id: 'paymentId',
  status: 'captured',
  amount: 10000,
  amount_transferred: 9000,
};

export const renderApp = ({ paymentDetails } = { paymentDetails: defaultPaymentDetails }) =>
  render(<PaymentTransfers paymentDetails={paymentDetails} />, {
    initialState: {
      session: {
        user: {
          isFeatureEnabled: () => true,
          findTag: () => false,
          isAllowedEdit: () => true,
        },
      },
    },
  });

beforeEach(() => mockPaymentTransfers());
