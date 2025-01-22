import React from 'react';
import { render } from 'test-utils';

import { OptimizerAccountsTable } from 'merchant/views/Marketplace/OptimizerAccounts/components/OptimizerAccountsTable';

import { ACCOUNTS } from 'merchant/views/Marketplace/OptimizerAccounts/__test__/mockData';

describe('Optimizer Accounts -> OptimizerAccountsTable', () => {
  const MOCK_PROPS = {
    optimizerAccounts: ACCOUNTS,
    isLoading: false,
    isRefreshing: false,
  };

  const renderApp = (props = MOCK_PROPS) => render(<OptimizerAccountsTable {...props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render headers in table', () => {
    const { getByText } = renderApp();
    ['Account ID', 'Name', 'Account Status', 'Provider'].forEach((item) =>
      expect(getByText(item)).toBeInTheDocument(),
    );
  });

  test('should render accounts in table', () => {
    const { getByText } = renderApp();
    expect(getByText('ONrJjKP9UkjAFh')).toBeInTheDocument();
    expect(getByText('random vendor account')).toBeInTheDocument();
    expect(getByText('Activated')).toBeInTheDocument();
    expect(getByText('payu for card, billdesk for ...')).toBeInTheDocument();
  });
});
