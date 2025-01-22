import React from 'react';
import { render } from 'test-utils';

import { FilterOptimizerAccounts } from 'merchant/views/Marketplace/OptimizerAccounts/components/FilterOptimizerAccounts';

describe('Optimizer Accounts -> FilterOptimizerAccounts', () => {
  const MOCK_PROPS = {
    accountId: '',
    setAccountId: jest.fn(),
    count: '',
    setCount: jest.fn(),
    handleSearch: jest.fn(),
  };

  const renderApp = (props = MOCK_PROPS) => render(<FilterOptimizerAccounts {...props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render filters', () => {
    const { getByText, getByRole } = renderApp();
    expect(getByText('Account ID')).toBeInTheDocument();
    expect(getByText('Count')).toBeInTheDocument();
    expect(getByRole('button', { name: 'Clear' })).toBeInTheDocument();
    expect(getByRole('button', { name: 'Search' })).toBeInTheDocument();
  });
});
