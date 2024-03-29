import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { rest } from 'msw';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import RepayNow from 'merchant/views/Capital/CashAdvance/components/WithdrawalDetails/RepayNow';
import * as Utils from 'merchant/views/Capital/CashAdvance/utils';
import { server } from 'test-utils';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

export const queryClient = new QueryClient();

describe('WithdrawDetails - Repay Now', () => {
  const App = ({
    initialState = {
      session: {
        user: {
          current: 1,
        },
      },
    },
  }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <BladeProvider themeTokens={bladeTheme}>
          <QueryClientProvider client={queryClient}>
            <RepayNow withdrawalId="123" />
          </QueryClientProvider>
        </BladeProvider>
      </Provider>
    );
  };

  it('should render correctly', async () => {
    server.use(
      rest.post('*/GetWithdrawalRepaymentSummary', (_, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            data: {
              total_outstanding: 0,
              dpd_amount: 10000,
              next_due_date: 1695396373,
            },
          }),
          ctx.delay(50),
        );
      }),
      rest.post('*', (_, res, ctx) => {
        return res(ctx.status(200), ctx.json({}), ctx.delay(50));
      }),
    );

    render(<App />);
    expect(screen.getByRole('progressbar')).toBeInTheDocument();

    await waitFor(() => expect(screen.queryByRole('progressbar')).not.toBeInTheDocument());

    expect(
      screen.getByText(/Repayments take upto 4 hours to process after collection/),
    ).toBeInTheDocument();
    expect(screen.getByText(/Pending due amount/)).toBeInTheDocument();
    expect(screen.getByTestId('repay-amount')).toBeInTheDocument();
    expect(screen.getByText(/Auto-collection scheduled/)).toBeInTheDocument();

    const repayButton = screen.getByRole('button');
    expect(repayButton).toBeInTheDocument();
    expect(repayButton).toBeEnabled();

    const handleRepaymentSpy = jest
      .spyOn(Utils, 'handleRepayment')
      .mockReturnValue(Promise.resolve());

    fireEvent.click(repayButton);
    expect(repayButton).toBeDisabled();

    expect(handleRepaymentSpy).toBeCalledWith({
      repayAmount: 10000,
      merchantId: 1,
      withdrawalId: '123',
    });
  });
});
