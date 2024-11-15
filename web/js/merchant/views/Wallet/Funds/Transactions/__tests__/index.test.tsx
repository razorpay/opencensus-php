import React from 'react';
import moment from 'moment';

import Transactions from 'merchant/views/Wallet/Funds/Transactions';
import { SessionContext } from 'merchant/views/Wallet/context';
import { render, waitForLoadingToFinish, screen } from 'test-utils';

describe('Wallet: Funds transactions tab', () => {
  it('should render expected elements in the tab', async () => {
    render(
      <SessionContext.Provider
        value={{
          mode: 'test',
          location: {
            pathname: '/wallet/funds/transactions',
          },
          merchant_id: '100000000000',
        }}
      >
        <Transactions />
      </SessionContext.Provider>,
    );

    await waitForLoadingToFinish('table-spinner');

    expect(screen.getByTestId('amount-card')?.textContent).toBe('₹ 200.00');
    expect(screen.getAllByRole('rowgroup')?.[0]?.children.length).toBe(1);

    expect(screen.getAllByRole('row')?.[0]?.children?.[0]?.textContent).toBe('I9eCvXfHx7nzZf');
    expect(screen.getAllByRole('row')?.[0]?.children?.[1]?.textContent).toBe(
      moment(Date.now()).format('ll'),
    );
    expect(screen.getAllByRole('row')?.[0]?.children?.[2]?.textContent).toBe(
      '₹ 200.00₹ - Indian Rupee (INR)',
    );
    expect(screen.getAllByRole('row')?.[0]?.children?.[3]?.textContent).toBe('Debit');
    expect(screen.getAllByRole('row')?.[0]?.children?.[4]?.textContent).toBe('');
  });
});
