import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import RefundsList from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsList';
import * as Details from 'apps/self-serve/src/App/Transactions/v2/common/components/Details/Details';
import 'jest-location-mock';

export const handleDetailsClickSpy = jest.spyOn(Details, 'handleDetailsClick');

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsListFilter',
  () =>
    function RefundsListFilter({ loading, onSubmit }) {
      return loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Refunds List Filter{' '}
          <button onClick={() => onSubmit({ status: 'processing' })}>Search</button>
        </div>
      );
    },
);

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsTable',
  () =>
    function RefundsTable({ loading, onRowClick }) {
      return loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Refunds Table
          <button type="button" onClick={() => onRowClick('id_1234')}>
            Table Row
          </button>
        </div>
      );
    },
);

export const renderApp = () => render(<RefundsList />);
