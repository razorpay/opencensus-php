import React from 'react';
import { render } from 'test-utils';

import { ChangeAccountDetails } from 'merchant/views/Marketplace/OptimizerAccounts/components/ChangeAccountDetails';

describe('Optimizer Accounts -> ChangeAccountDetails', () => {
  const MOCK_PROPS = {
    closeModal: jest.fn(),
    accountId: 'ONrJjKP9UkjAFh',
    providerId: 'PHrThH7HErdjpU',
    providerName: 'payu for card',
    gatewayAccountId: 'account_id_1',
    showNotification: jest.fn(),
    updateDetails: jest.fn(),
  };

  const renderApp = (props = MOCK_PROPS) => render(<ChangeAccountDetails {...props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render gateway account details', () => {
    const { getByText, getByRole, getByDisplayValue } = renderApp();
    expect(getByRole('heading', { name: 'Change details' })).toBeInTheDocument();
    expect(getByRole('button', { name: 'close' })).toBeInTheDocument();
    expect(getByText('Provider')).toBeInTheDocument();
    expect(getByText('payu for card')).toBeInTheDocument();
    expect(getByText('Gateway linked account ID')).toBeInTheDocument();
    expect(getByDisplayValue('account_id_1')).toBeInTheDocument();
    expect(getByRole('button', { name: 'Save' })).toBeInTheDocument();
  });
});
