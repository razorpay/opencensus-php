import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { rest } from 'msw';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import PreClosure from 'merchant/views/Capital/CashAdvance/components/WithdrawalDetails/PreClosure';
import * as Utils from 'merchant/views/Capital/CashAdvance/utils';
import { server } from 'test-utils';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };

const defaultAbExperiments = {
  capitalPreclosureEdiExp: variantOn,
};

let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

export const queryClient = new QueryClient();

describe('WithdrawDetails - PreClosure', () => {
  beforeEach(() => {
    mockAbExperiments = defaultAbExperiments;
  });
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
        <BladeProvider themeTokens={paymentTheme}>
          <QueryClientProvider client={queryClient}>
            <PreClosure withdrawalId="123" dueDate="2023-11-28T18:29:59Z" />
          </QueryClientProvider>
        </BladeProvider>
      </Provider>
    );
  };
  it('should not render preclosure if experiment is disabled', () => {
    mockAbExperiments = { capitalPreclosureEdiExp: variantOff };
    server.use(
      rest.post('*/GetPreClosureAmount', (_, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: { principal: '100000', interest: '2799', total: '102799', currency: 'INR' },
          }),
          ctx.delay(50),
        );
      }),
      rest.post('*', (_, res, ctx) => {
        return res(ctx.status(200), ctx.json({}), ctx.delay(50));
      }),
    );
    render(<App />);
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
    expect(
      screen.queryByText(/Want to close all dues for this withdrawal?/),
    ).not.toBeInTheDocument();
  });
  it('should render preclosure section if experiment is enabled', async () => {
    server.use(
      rest.post('*/GetPreClosureAmount', (_, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: { principal: '100000', interest: '2799', total: '102799', currency: 'INR' },
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

    expect(screen.getByText(/Want to close all dues for this withdrawal?/)).toBeInTheDocument();
    const precloseButton = screen.getByRole('button');
    expect(precloseButton).toBeInTheDocument();
    expect(precloseButton).toBeEnabled();

    const handleRepaymentSpy = jest
      .spyOn(Utils, 'handleRepayment')
      .mockReturnValue(Promise.resolve());

    fireEvent.click(precloseButton);
    expect(precloseButton).toBeDisabled();

    expect(handleRepaymentSpy).toBeCalledWith({
      repayAmount: 102799,
      merchantId: 1,
      withdrawalId: '123',
      metadata: {
        pre_closure: {
          is_pre_closure: true,
        },
      },
    });

    await waitFor(() => expect(precloseButton).toBeEnabled());
  });
});
