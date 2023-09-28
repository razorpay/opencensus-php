import React from 'react';

import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { QueryCache, ReactQueryCacheProvider } from 'react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

import AccountDetail from 'merchant/views/Wallet/AccountDetail';
import { render as rootRender } from '@testing-library/react';

import { waitForLoadingToFinish, render, screen, waitFor } from 'test-utils';

describe('Wallet: AccountDetail component', () => {
  it('should render account details and limits graph', async () => {
    rootRender(
      <BladeProvider themeTokens={paymentTheme}>
        <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZs']}>
          <AccountDetail />
        </MemoryRouter>
      </BladeProvider>,
    );

    await waitFor(() => {
      expect(screen.getByText('Razor')).toBeInTheDocument();
      expect(screen.getByText('acme.corp@email.com')).toBeInTheDocument();
      expect(screen.getByText('9999999999')).toBeInTheDocument();
      expect(screen.getByText('Jun 5, 2023')).toBeInTheDocument();
      expect(screen.getByText('Razorpay_Employee_Wallet_Program')).toBeInTheDocument();
      expect(screen.getByText('account')).toBeInTheDocument();
      expect(screen.getByTestId('amount')?.textContent).toBe('₹200.00');
      expect(screen.getByTestId('utilisation-graph')).toBeInTheDocument();
    });
  });

  it('should not render limits graph when rendering container account', async () => {
    render(<AccountDetail />, {
      initialEntries: ['/wallet/accounts/iacc_I9eCvXfHx7nzZt'],
      path: '/wallet/accounts/:id',
    });

    await waitForLoadingToFinish();

    expect(screen.getByText('Container User')).toBeInTheDocument();
    expect(screen.getByText('container@razorpay.com')).toBeInTheDocument();
    expect(screen.getByText('9999999999')).toBeInTheDocument();
    expect(screen.getByText('Jun 5, 2023')).toBeInTheDocument();
    expect(screen.getByText('Container_Program')).toBeInTheDocument();
    expect(screen.getByText('container')).toBeInTheDocument();
    expect(screen.queryByTestId('utilisation-graph')).toBeNull();
  });

  it('should render error message when api fails', async () => {
    rootRender(
      <ReactQueryCacheProvider
        queryCache={new QueryCache({ defaultConfig: { queries: { retry: false } } })}
      >
        <BladeProvider themeTokens={paymentTheme}>
          <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZz']}>
            <Routes>
              <Route path="/wallet/accounts/:id" element={<AccountDetail />} />
            </Routes>
          </MemoryRouter>
        </BladeProvider>
      </ReactQueryCacheProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByTestId('error-message')).toBeInTheDocument();
  });
});
