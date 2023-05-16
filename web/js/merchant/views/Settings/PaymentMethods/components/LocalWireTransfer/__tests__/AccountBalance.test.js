import { render, screen, userEvent, waitFor } from 'test-utils';
import { getMockedFetchAccountBalance } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/__tests__/mocks/fixtures';

import * as services from 'merchant/reducers/b2bExports/actions';
import * as notifications from 'merchant_common/reducers/notifications';
import * as modals from 'merchant_common/reducers/modals';

import AccountBalance from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/AccountBalance';

jest.mock('merchant/reducers/b2bExports/actions');

const renderComponent = (props = {}, initialState = {}) => {
  return render(<AccountBalance {...props} />, { initialState });
};

describe('Tests when account balance is not present', () => {
  const openModal = jest.spyOn(modals, 'openModal');
  const showNotification = jest.spyOn(notifications, 'showNotification');

  beforeEach(() => {
    openModal.mockClear();
    showNotification.mockClear();
  });

  test('Component should render without breaking', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('Check balance button should be visible', () => {
    renderComponent();

    expect(screen.getByText('Check Balance')).toBeInTheDocument();
    expect(
      screen.getByText('Check the available balance in your bank account.'),
    ).toBeInTheDocument();
  });

  test('Withdraw money button should be visible', () => {
    renderComponent();

    expect(screen.getByText('Withdraw Money')).toBeInTheDocument();
    expect(screen.getByText('Withdraw funds from your account')).toBeInTheDocument();
  });

  test('Account balance should be visible after successfull check balance call', async () => {
    services.fetchAccountBalance.mockImplementation(
      getMockedFetchAccountBalance('SUCCESS', {
        success: true,
        data: {
          amount: 10,
          currency: 'usd',
        },
      }),
    );

    renderComponent({ vaCurrency: 'usd' });

    const checkBalanceButton = screen.getByText('Check Balance');
    await userEvent.click(checkBalanceButton);

    await waitFor(() => expect(services.fetchAccountBalance).toHaveBeenCalled());
    expect(services.fetchAccountBalance).toHaveBeenCalledWith('usd');

    await waitFor(() => expect(screen.getByText('$ 10')).toBeInTheDocument());
    expect(screen.getByText('Refresh')).toBeInTheDocument();
  });

  test('Refresh button should call the fetchAccountBalance function again', async () => {
    services.fetchAccountBalance.mockImplementation(
      getMockedFetchAccountBalance('SUCCESS', {
        success: true,
        data: {
          amount: 10,
          currency: 'usd',
        },
      }),
    );

    renderComponent({ vaCurrency: 'usd' });

    const checkBalanceButton = screen.getByText('Check Balance');
    await userEvent.click(checkBalanceButton);

    await waitFor(() => expect(screen.getByText('$ 10')).toBeInTheDocument());
    expect(screen.getByText('Refresh')).toBeInTheDocument();

    const refreshButton = screen.getByText('Refresh');
    await userEvent.click(refreshButton);
    await waitFor(() => expect(services.fetchAccountBalance).toHaveBeenCalled());
  });

  test('Withdraw money should open BankWithdrawlModal', async () => {
    renderComponent({ vaCurrency: 'usd' });

    const withdrawButton = screen.getByText('Withdraw Money');
    await userEvent.click(withdrawButton);

    await waitFor(() => expect(openModal).toHaveBeenCalled());
  });

  test('Error should be visible when check balance api fails', async () => {
    services.fetchAccountBalance.mockImplementation(
      () => () =>
        Promise.reject({
          status_code: 500,
          success: false,
        }),
    );

    renderComponent({ vaCurrency: 'usd' });

    const checkBalanceButton = screen.getByText('Check Balance');
    await userEvent.click(checkBalanceButton);

    await waitFor(() => expect(services.fetchAccountBalance).toHaveBeenCalled());

    await waitFor(() => expect(showNotification).toHaveBeenCalled());
    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: 'Failed to check account balance. Try again after sometime.',
    });
  });
});
