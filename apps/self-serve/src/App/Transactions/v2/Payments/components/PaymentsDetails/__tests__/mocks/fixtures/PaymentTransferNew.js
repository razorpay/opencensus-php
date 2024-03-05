// TODO: Fix this component, currently its out of scope
import React from 'react';
// import * as models from 'apps/self-serve/src/App/Transactions/model';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import PaymentTransferNew from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentTransferNew';

export const fetchTransfersFnSpy = jest.fn();

export const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

// eslint-disable-next-line react/display-name
jest.mock('apps/self-serve/src/App/Marketplace/Transfers/New', () => ({ onCreate, onClose }) => (
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
