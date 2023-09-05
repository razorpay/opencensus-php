import React from 'react';
import { render } from 'test-utils';
import RefundsList from 'merchant/views/Transactions/v2/Refunds/components/RefundsList';
import 'jest-location-mock';

jest.mock(
  'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter',
  () =>
    ({ loading, onSubmit }) =>
      loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Refunds List Filter{' '}
          <button onClick={() => onSubmit({ status: 'processing' })}>Search</button>
        </div>
      ),
);

jest.mock(
  'merchant/views/Transactions/v2/Refunds/components/RefundsTable',
  () =>
    ({ loading }) =>
      loading ? <div>Loading...</div> : <div>Refunds Table</div>,
);

export const renderApp = () => render(<RefundsList />);
