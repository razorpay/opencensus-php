import React from 'react';
import { render } from 'test-utils';
import { waitFor } from '@testing-library/react';

import OptimizerAccountDetails from 'merchant/views/Marketplace/OptimizerAccounts/Details';

import * as allFetch from 'merchant/utils/ajax';
import { ACCOUNTS } from './mockData';

describe('Optimizer Accounts -> OptimizerAccountDetails', () => {
  beforeEach(() => {
    jest
      .spyOn(allFetch, 'merchantFetch')
      .mockReturnValue(Promise.resolve({ success: true, data: { data: ACCOUNTS } }));
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const MOCK_PROPS = {
    id: 'ONrJjKP9UkjAFh',
    showNotification: jest.fn(),
    openModal: jest.fn(),
    closeModal: jest.fn(),
  };

  const renderApp = (props = MOCK_PROPS) => render(<OptimizerAccountDetails {...props} />);

  test('should render without errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  test('should render account details', async () => {
    const { getByText, getAllByRole } = renderApp();
    expect(getByText('Account ID:')).toBeInTheDocument();
    expect(getByText('ONrJjKP9UkjAFh')).toBeInTheDocument();
    expect(getByText('Name')).toBeInTheDocument();
    await waitFor(() => {
      expect(getByText('random vendor account')).toBeInTheDocument();
    });
    expect(getByText('Account Status')).toBeInTheDocument();
    expect(getByText('Activated')).toBeInTheDocument();
    expect(getByText('Gateway Linked Account ID')).toBeInTheDocument();
    expect(getByText('payu for card Account ID')).toBeInTheDocument();
    expect(getByText('account_id_1')).toBeInTheDocument();
    expect(getByText('billdesk for upi Account ID')).toBeInTheDocument();
    expect(getByText('account_id_2')).toBeInTheDocument();
    expect(getAllByRole('button', { name: 'Change' })).toHaveLength(2);
  });
});
