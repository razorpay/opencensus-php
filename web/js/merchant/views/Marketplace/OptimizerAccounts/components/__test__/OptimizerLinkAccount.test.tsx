import React from 'react';
import { render, userEvent } from 'test-utils';

import { OptimizerLinkAccount } from 'merchant/views/Marketplace/OptimizerAccounts/components/OptimizerLinkAccount';

import { ACCOUNTS } from 'merchant/views/Marketplace/OptimizerAccounts/__test__/mockData';

describe('Optimizer Accounts -> OptimizerLinkAccount', () => {
  const MOCK_PROPS = {
    isOpen: true,
    closeModal: jest.fn(),
    optimizerAccounts: ACCOUNTS,
    providers: [],
    accountLinkedSuccess: jest.fn(),
  };

  const renderApp = (props = MOCK_PROPS) => render(<OptimizerLinkAccount {...props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render link account modal', async () => {
    const { getByText, getByRole } = renderApp();
    expect(getByText('Link Account')).toBeInTheDocument();

    const accountIdInput = getByRole('combobox', { name: 'Account ID' });
    expect(accountIdInput).toBeInTheDocument();
    await userEvent.click(accountIdInput);
    expect(getByText('Create new account')).toBeInTheDocument();
    expect(getByText('This will generate a new ID once you finish linking')).toBeInTheDocument();
    expect(getByText('ONrJjKP9UkjAFh')).toBeInTheDocument();

    expect(getByText('Account Name')).toBeInTheDocument();
    const providerInput = getByRole('combobox', { name: 'Provider' });
    expect(providerInput).toBeInTheDocument();
    expect(getByText('Gateway linked account ID')).toBeInTheDocument();

    const linkButton = getByRole('button', { name: 'Link' });
    expect(linkButton).toBeInTheDocument();
    expect(linkButton).toBeDisabled();
  });
});
