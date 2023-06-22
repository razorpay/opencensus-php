import React from 'react';
import { render, server, screen, waitFor, userEvent } from 'test-utils';
import SuccessRate from 'merchant/views/Transactions/SuccessRate/containers/SuccessRate';
import {
  errorApiHandler,
  ongoingDowntimesHandler,
  resolvedDowntimesHandler,
  srApiHandler,
} from './mocks/handlers';
import { Provider } from 'react-redux';
import store from 'merchant/store';
import * as services from 'merchant/views/Transactions/SuccessRate/service';

jest.mock('merchant/views/Transactions/SuccessRate/helper', () => ({
  ...(jest.requireActual('merchant/views/Transactions/SuccessRate/helper') as typeof Object),
  checkIfAdmin: () => true,
}));

const App = () => {
  return (
    <Provider store={store}>
      <SuccessRate />
    </Provider>
  );
};

describe('SuccessRate Admin', () => {
  beforeEach(() => {
    server.use(
      resolvedDowntimesHandler({ isSuccess: true }),
      ongoingDowntimesHandler({ isSuccess: true }),
      srApiHandler({ isSuccess: true }),
      errorApiHandler({ isSuccess: true }),
    );

    window.rzp_user = {
      ...window.rzp_user,
      splitz_experiments: {
        M4UtH6eWYiFiuA: {
          variables: {
            result: 'on',
          },
        },
      },
    };

    render(<App />);
  });

  test('should render Admin panel if flag is true', async () => {
    await waitFor(() => {
      expect(screen.getByTestId('admin-search')).toBeVisible();
    });
  });

  test('should trigger search call upon clicking "Set Merchant" button', async () => {
    const srFetchAllSpy = jest.spyOn(services, 'getSR');
    await waitFor(() => {
      expect(screen.getByTestId('admin-search')).toBeVisible();
    });
    const input = screen.getByPlaceholderText('Search Merchant ID') as HTMLInputElement;
    await userEvent.type(input, 'DWnCsGeq9ClyNX');
    await userEvent.click(screen.getByTestId('admin-search-set-btn'));
    await waitFor(() => {
      screen.getByLabelText('Loading Content');
    });
    expect(screen.getByTestId('Overall-tab')).toHaveClass('active');
    expect(srFetchAllSpy).toHaveBeenLastCalledWith(
      expect.objectContaining({
        merchant_id: 'DWnCsGeq9ClyNX',
      }),
    );
  });

  test('should reset SR dashboard upon clicking "Reset" button', async () => {
    await waitFor(() => {
      expect(screen.getByTestId('admin-search')).toBeVisible();
    });
    await userEvent.click(screen.getByTestId('admin-search-reset-btn'));
    expect(screen.getByTestId('Overall-tab')).toHaveClass('active');
  });
});
