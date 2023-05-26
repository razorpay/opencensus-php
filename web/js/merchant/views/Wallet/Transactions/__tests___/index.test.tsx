import React from 'react';
import { render, waitForLoadingToFinish, screen } from 'common/services/test/test-utils';

import Transactions from 'merchant/views/Wallet/Transactions';

describe('Wallet > Transactions > List Table', () => {
  test('Should render table with expected number of rows', async () => {
    render(<Transactions />);

    await waitForLoadingToFinish();

    expect(screen.getAllByRole('rowgroup')?.[0]?.children.length).toBe(1);
  });

  test('Should render table with expected columns and data', async () => {
    render(<Transactions />);

    await waitForLoadingToFinish();

    expect(screen.getByText('Transaction ID')).toBeInTheDocument();
    expect(screen.getByText('I9eCvXfHx7nzZf')).toBeInTheDocument();
    expect(screen.getByText('Reference ID')).toBeInTheDocument();
    expect(screen.getByText('Account ID')).toBeInTheDocument();
    expect(screen.getByText('iacc_I9eCvXfHx7nzZf')).toBeInTheDocument();
    expect(screen.getByText('Source')).toBeInTheDocument();
    expect(screen.getByText('merchant')).toBeInTheDocument();
    expect(screen.getByText('Type')).toBeInTheDocument();
    expect(screen.getByText('Debit')).toBeInTheDocument();
    expect(screen.getByText('Created At')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('200')).toBeInTheDocument();
  });
});
