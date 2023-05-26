import React from 'react';

import { render, screen } from '@testing-library/react';
import { waitForLoadingToFinish } from 'common/services/test/test-utils';

import AccountBalanceContainer from 'merchant/views/Wallet/AccountDetail/containers/AccountBalanceContainer';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';

describe('Wallet: AccountBalanceContainer tests', () => {
  it('should render amount and graph', async () => {
    render(
      <BladeProvider themeTokens={paymentTheme}>
        <AccountBalanceContainer account_id="iacc_I9eCvXfHx7nzZs" mode="test" />
      </BladeProvider>,
    );

    await waitForLoadingToFinish();

    expect(screen.getByTestId('amount')?.textContent).toBe('₹200');
    expect(screen.getByTestId('utilisation-graph')).toBeInTheDocument();
  });
});
