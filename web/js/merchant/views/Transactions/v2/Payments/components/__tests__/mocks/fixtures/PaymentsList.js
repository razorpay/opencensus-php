import React from 'react';
import { render } from 'test-utils';

import PaymentsList from 'merchant/views/Transactions/v2/Payments/components/PaymentsList';
import * as Details from 'merchant/views/Transactions/v2/common/components/Details/Details';
import 'jest-location-mock';

export const handleDetailsClickSpy = jest.spyOn(Details, 'handleDetailsClick');

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
    ({ loading, onRowClick }) =>
      loading ? (
        <div>Loading...</div>
      ) : (
        <div>
          Payments Table
          <button type="button" onClick={() => onRowClick('id_1234')}>
            Table Row
          </button>
        </div>
      ),
);

export const renderApp = () => render(<PaymentsList />);
