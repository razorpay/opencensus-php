import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render as rootRender, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';

import AccountDetail from 'merchant/views/Wallet/AccountDetail';
import { waitForLoadingToFinish } from 'test-utils';

const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

describe('Wallet: AccountDetail component', () => {
  it('should render account details and limits graph', async () => {
    rootRender(
      <QueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={paymentTheme}>
          <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZs']}>
            <Routes>
              <Route path="/wallet/accounts/:id" element={<AccountDetail />} />
            </Routes>
          </MemoryRouter>
        </BladeProvider>
      </QueryClientProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Razor')).toBeInTheDocument();
    expect(screen.getByText('acme.corp@email.com')).toBeInTheDocument();
    expect(screen.getByText('9999999999')).toBeInTheDocument();
    expect(screen.getByText('Jun 5, 2023')).toBeInTheDocument();
    expect(screen.getByText('Razorpay_Employee_Wallet_Program')).toBeInTheDocument();
    expect(screen.getByText('account')).toBeInTheDocument();
  });

  it('should not render limits graph when rendering container account', async () => {
    rootRender(
      <QueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={paymentTheme}>
          <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZt']}>
            <Routes>
              <Route path="/wallet/accounts/:id" element={<AccountDetail />} />
            </Routes>
          </MemoryRouter>
        </BladeProvider>
      </QueryClientProvider>,
    );

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
      <QueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={paymentTheme}>
          <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZz']}>
            <Routes>
              <Route path="/wallet/accounts/:id" element={<AccountDetail />} />
            </Routes>
          </MemoryRouter>
        </BladeProvider>
      </QueryClientProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByTestId('error-message')).toBeInTheDocument();
  });
});
