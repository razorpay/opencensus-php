import React from 'react';
import { render, waitForLoadingToFinish, screen, waitFor } from 'common/services/test/test-utils';

import Transactions from 'merchant/views/Wallet/Transactions';

describe('Wallet > Transactions > List Table', () => {
  test('Should render table with expected number of rows', async () => {
    render(<Transactions />);

    await waitFor(() => {
      expect(screen.getAllByRole('rowgroup')?.[0]?.children.length).toBe(1);
    });
  });

  test('Should render table with expected columns and data', async () => {
    render(<Transactions />);

    await waitForLoadingToFinish();

    expect(screen.getByText('Transaction Id', { selector: 'th' })).toBeInTheDocument();
    expect(screen.getByText('itxn_MSQSunez0tjxDX')).toBeInTheDocument();
    expect(screen.getByText('Reference Id', { selector: 'th' })).toBeInTheDocument();
    expect(screen.getByText('Account Id')).toBeInTheDocument();
    expect(screen.getByText('iacc_MSQShu0g115l39')).toBeInTheDocument();
    expect(screen.getByText('Source')).toBeInTheDocument();
    expect(screen.getByText('ipay_MSQSumyGI3Hl1j')).toBeInTheDocument();
    expect(screen.getByText('Type')).toBeInTheDocument();
    expect(screen.getByText('Debit')).toBeInTheDocument();
    expect(screen.getByText('Created At')).toBeInTheDocument();
    expect(screen.getByText('Amount')).toBeInTheDocument();
    expect(screen.getByText('1')).toBeInTheDocument();
  });
});
