import React from 'react';
// import * as models from 'apps/self-serve/src/App/Transactions/model';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import PaymentTransfers from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransfers';
import { mockPaymentTransfers } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/handlers';

jest.mock('@dashboard/shared-ui/hooks', () => ({
  ...jest.requireActual('@dashboard/shared-ui/hooks'),
  useMobile: jest.fn(),
}));

export const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

export const fetchTransfersFnSpy = jest.fn();

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
