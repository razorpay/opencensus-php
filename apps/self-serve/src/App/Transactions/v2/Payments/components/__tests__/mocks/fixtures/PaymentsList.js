import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import PaymentsList from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsList';
import * as Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import 'jest-location-mock';

export const handleDetailsClickSpy = jest.spyOn(Details, 'handleDetailsClick');

const initialState = {
  session: {
    user: {
      isOmniChannelMerchant: false,
    },
  },
};

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsListFilter',
  () =>
    function PaymentsListFilter({ loading, onSubmit }) {
      return loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Payments List Filter <button onClick={() => onSubmit({ method: 'card' })}>Search</button>
        </div>
      );
    },
);

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable',
  () =>
    function PaymentsTable({ loading, onRowClick, isDisabled }) {
      return loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Payments Table
          <button
            type="button"
            onClick={() => !isDisabled({ status: 'captured' }) && onRowClick('id_1234')}
          >
            Table Row
          </button>
        </div>
      );
    },
);

export const renderApp = () =>
  render(<PaymentsList />, {
    renderViaRouteGuard: false,
    initialState,
  });
