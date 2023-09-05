import React from 'react';
import { render } from 'test-utils';
import PaymentsList from 'merchant/views/Transactions/v2/Payments/components/PaymentsList';
import 'jest-location-mock';

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsListFilter',
  () =>
    ({ loading, onSubmit }) =>
      loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Payments List Filter <button onClick={() => onSubmit({ method: 'card' })}>Search</button>
        </div>
      ),
);

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsTable',
  () =>
    ({ loading }) =>
      loading ? <div>Loading...</div> : <div>Payments Table</div>,
);

export const renderApp = () => render(<PaymentsList />);
