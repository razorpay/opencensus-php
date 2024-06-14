import React from 'react';
import { render } from 'test-utils';

import RefundsList from 'merchant/views/Transactions/v2/Refunds/components/RefundsList';
import * as Details from 'merchant/views/Transactions/v2/common/components/Details/Details';
import 'jest-location-mock';

export const handleDetailsClickSpy = jest.spyOn(Details, 'handleDetailsClick');

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
    ({ loading, onRowClick }) =>
      loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Refunds Table
          <button type="button" onClick={() => onRowClick({ id: 'id_1234' })}>
            Table Row
          </button>
        </div>
      ),
);

export const renderApp = () => render(<RefundsList />);
