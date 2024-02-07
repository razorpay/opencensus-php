import 'react-dates/initialize';
import React from 'react';
import userEvent from '@testing-library/user-event';
import { screen, waitFor } from '@testing-library/react';

import BrandAccount from 'merchant/views/GCMS/Funds/BrandAccount/index';
import { listFundTransactionsResponse } from 'merchant/views/Wallet/Funds/Transactions/__tests__/mocks/fixtures';
import { render } from 'test-utils';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderbrandAccounts = () => {
  render(
    <GCMSTestPageRenderer>
      <BrandAccount />
    </GCMSTestPageRenderer>,
  );
};

describe.skip('GCMS: Brand Transactions', () => {
  it('should render transaction list page', async () => {
    renderbrandAccounts();

    await waitFor(() => {
      expect(screen.getByText('Total Available Fund')).toBeInTheDocument();
      expect(screen.getByText('Transaction Id')).toBeInTheDocument();
      // expect(screen.getAllByText('I9eCvXfHx7nzZf').length).toBe(
      //   listFundTransactionsResponse.data.items.length,
      // );
    });
  });

  it('should show empty screen when no Tranactions are present for a Reference Id', async () => {
    renderbrandAccounts();

    const search = screen.getByRole('textbox', {
      name: '',
    });
    await userEvent.type(search, 'abc');
    userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no transactions yet')).toBeInTheDocument();
    });
  });

  it('should clear all filters and show Transactions for default filters', async () => {
    renderbrandAccounts();

    const search = screen.getByRole('textbox', {
      name: '',
    });
    await userEvent.type(search, 'abc');
    userEvent.click(screen.getByText('Search'));

    await waitFor(() => {
      expect(screen.getByText('There are no transactions yet')).toBeInTheDocument();
    });

    await userEvent.type(search, 'abc');
    userEvent.click(screen.getByText('Clear'));

    await waitFor(() => {
      expect(screen.getAllByText('I9eCvXfHx7nzZf').length).toBe(
        listFundTransactionsResponse.data.items.length,
      );
    });
  });
});
