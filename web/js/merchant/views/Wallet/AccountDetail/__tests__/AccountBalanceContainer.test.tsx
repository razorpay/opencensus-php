import React from 'react';

import { render, screen, waitFor } from '@testing-library/react';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
} from '@tanstack/react-query';

import AccountBalanceContainer from 'merchant/views/Wallet/AccountDetail/containers/AccountBalanceContainer';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

export const queryClient = new QueryClient();

describe('Wallet: AccountBalanceContainer tests', () => {
  it('should render amount and graph', async () => {
    render(
      <ReactQueryClientProvider client={queryClient}>
        <BladeProvider themeTokens={paymentTheme}>
          <AccountBalanceContainer account_id="iacc_I9eCvXfHx7nzZs" mode="test" />
        </BladeProvider>
      </ReactQueryClientProvider>,
    );

    await waitFor(() => {
      expect(screen.getByTestId('amount')?.textContent).toBe('₹200.00');
      expect(screen.getByTestId('utilisation-graph')).toBeInTheDocument();
    });
  });
});
