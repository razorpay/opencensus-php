import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import ResellerAccounts from 'merchant/views/GCMS/Funds/ResellerAccounts';
import { GCMSTestPageRenderer } from 'merchant/views/GCMS/shared/test-utils';
import { render } from 'test-utils';

import { gcmsFundsResellerAccountsResponse } from './mocks/fixtures';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

const storeState = {
  session: {
    user: {
      current: 'NDnRD3epJ6P60L',
    },
    mode: 'test',
  },
};

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderResellerAccounts = () => {
  render(
    <GCMSTestPageRenderer>
      <Provider store={storeWithInitialState(storeState)}>
        <ResellerAccounts />
      </Provider>
    </GCMSTestPageRenderer>,
  );
};

describe('GCMS: Funds: ResellerAccounts', () => {
  test('should render table with expected number of rows', async () => {
    const numberOfItems = gcmsFundsResellerAccountsResponse.data.items.length;

    renderResellerAccounts();

    await waitFor(() => {
      expect(screen.getByText('Reseller Name', { selector: 'p' })).toBeInTheDocument();
      expect(screen.getByText('Reseller ID', { selector: 'p' })).toBeInTheDocument();
      expect(screen.getByText('Total Available Fund', { selector: 'p' })).toBeInTheDocument();
      expect(screen.getAllByTestId('fund-amount').length).toBe(numberOfItems);
    });
  });

  test('should render table with expected columns and data', async () => {
    renderResellerAccounts();

    await waitFor(() => {
      expect(screen.getByText('Reseller Name', { selector: 'p' })).toBeInTheDocument();
      expect(screen.getByText('Reseller ID', { selector: 'p' })).toBeInTheDocument();
      expect(screen.getByText('Total Available Fund', { selector: 'p' })).toBeInTheDocument();
      expect(screen.getByText('Ibacoo')).toBeInTheDocument();
    });
  });

  test('should clear filters on clear button click', async () => {
    renderResellerAccounts();

    const searchInput = screen.getByPlaceholderText(/search reseller name/i);
    const clearButton = screen.getByRole('button', { name: 'Clear' });

    await userEvent.type(searchInput, 'abc');
    userEvent.click(clearButton);

    await waitFor(() => {
      expect(searchInput).toHaveValue('');
    });
  });
});
