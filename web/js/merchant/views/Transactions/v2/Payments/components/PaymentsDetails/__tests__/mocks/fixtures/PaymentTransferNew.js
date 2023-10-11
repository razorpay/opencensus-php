import React from 'react';
import { render } from 'test-utils';
import PaymentTransferNew from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransferNew';
import * as models from 'merchant/views/Transactions/model';

export const fetchTransfersFnSpy = jest.spyOn(models, 'fetchTransfersFn');

export const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

jest.mock('merchant/views/Marketplace/Transfers/New', () => ({ onCreate, onClose }) => (
  <div>
    New Payment Transfer
    <button onClick={onCreate}>Create Transfer</button>
    <button onClick={onClose}>Close</button>
  </div>
));

export const renderApp = () =>
  render(<PaymentTransferNew id="paymentId" />, {
    initialState: {
      session: {
        user: {
          isFeatureEnabled: () => true,
          isAllowedView: () => true,
        },
      },
    },
  });
