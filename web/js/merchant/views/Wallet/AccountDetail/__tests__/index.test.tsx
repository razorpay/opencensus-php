import React from 'react';

import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { QueryCache, ReactQueryCacheProvider } from 'react-query';
import { MemoryRouter, Route, Switch } from 'react-router-dom';

import AccountDetail from 'merchant/views/Wallet/AccountDetail';

import { render, screen } from '@testing-library/react';
import { waitForLoadingToFinish } from 'test-utils';

describe('Wallet: AccountDetail component', () => {
  it('should render account details and limits graph', async () => {
    render(
      <BladeProvider themeTokens={paymentTheme}>
        <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZs']}>
          <AccountDetail />
        </MemoryRouter>
      </BladeProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByText('Goutam B Seervi')).toBeInTheDocument();
    expect(screen.getByText('goutambseervi@gmail.com')).toBeInTheDocument();
    expect(screen.getByText('+91 9353231953')).toBeInTheDocument();
    expect(screen.getByText('Sep 18, 2002')).toBeInTheDocument();
    expect(screen.getByText('Wallet_PPI')).toBeInTheDocument();

    await waitForLoadingToFinish();

    expect(screen.getByTestId('amount')?.textContent).toBe('₹200');
    expect(screen.getByTestId('utilisation-graph')).toBeInTheDocument();
  });

  it('should render error message when api fails', async () => {
    render(
      <ReactQueryCacheProvider
        queryCache={new QueryCache({ defaultConfig: { queries: { retry: false } } })}
      >
        <BladeProvider themeTokens={paymentTheme}>
          <MemoryRouter initialEntries={['/wallet/accounts/iacc_I9eCvXfHx7nzZz']}>
            <Switch>
              <Route path="/wallet/accounts/:id" component={AccountDetail} />
            </Switch>
          </MemoryRouter>
        </BladeProvider>
      </ReactQueryCacheProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByTestId('error-message')).toBeInTheDocument();
  });
});
