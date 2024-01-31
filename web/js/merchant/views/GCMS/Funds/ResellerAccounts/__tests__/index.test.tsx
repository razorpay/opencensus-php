import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import ResellerAccounts from 'merchant/views/GCMS/Funds/ResellerAccounts';
import { render } from 'test-utils';

import { gcmsFundsResellerAccountsResponse } from './mocks/fixtures';
import { GcmsTestWrapper } from 'merchant/views/GCMS/shared/test-utils';

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
    <GcmsTestWrapper>
      <Provider store={storeWithInitialState(storeState)}>
        <ResellerAccounts />
      </Provider>
    </GcmsTestWrapper>,
  );
};

describe('GCMS: Funds: ResellerAccounts', () => {
  test('should render table with expected number of rows', async () => {
    const numberOfItems = gcmsFundsResellerAccountsResponse.data.items.length;

    renderResellerAccounts();

    await waitFor(() => {
      expect(screen.getByText('Reseller Name', { selector: 'th' })).toBeInTheDocument();
      expect(screen.getByText('Reseller ID', { selector: 'th' })).toBeInTheDocument();
      expect(screen.getByText('Total Available Fund', { selector: 'th' })).toBeInTheDocument();
      expect(screen.getByText(`Showing 1 - ${numberOfItems}`)).toBeInTheDocument();
      expect(screen.getAllByTestId('fund-amount').length).toBe(numberOfItems);
    });
  });

  test('should render table with expected columns and data', async () => {
    renderResellerAccounts();

    await waitFor(() => {
      expect(screen.getByText('Reseller Name', { selector: 'th' })).toBeInTheDocument();
      expect(screen.getByText('Reseller ID', { selector: 'th' })).toBeInTheDocument();
      expect(screen.getByText('Total Available Fund', { selector: 'th' })).toBeInTheDocument();
      expect(screen.getByText('Ibacoo')).toBeInTheDocument();
    });
  });
});
