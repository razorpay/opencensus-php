import React from 'react';
import { render, screen, waitFor, server } from 'common/services/test/test-utils';
import AccountsList from 'merchant/views/Marketplace/Accounts/List';
import { accountsListSuccess } from 'merchant/views/Marketplace/Accounts/__tests__/mocks/handlers';
import {
  accountsData,
  location,
} from 'merchant/views/Marketplace/Accounts/__tests__/mocks/fixtures';
import { getInitialReduxState } from 'merchant/views/mocks/fixtures';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';
import * as analytics from 'common/utils/analytics';
import TwoFaVerificationContextProvider from 'common/ui/TwoFactorVerification/TwoFactorVerificationProvider';

jest.mock('merchant/views/Marketplace/Accounts/components/AccountsList', () => ({
  __esModule: true,
  default: ({ accounts, isLoading }) => (
    <div>
      {isLoading ? (
        <div data-testId="spinner" />
      ) : (
        <div>
          {accounts.map((item, index) => {
            return <div key={index}>{item.id}</div>;
          })}
        </div>
      )}
    </div>
  ),
}));

const defaultReduxState = getInitialReduxState({
  isAllowedEdit: () => true,
  isRouteLinkedAccountCreationDisabled: true,
  id: 'testUserId',
});

describe('Reversal List', () => {
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  const renderApp = (state = defaultReduxState) => {
    render(
      <TwoFaVerificationContextProvider>
        <AccountsList location={location} />
      </TwoFaVerificationContextProvider>,
      {
        initialState: state,
        renderViaRouteGuard: false,
      },
    );
  };

  test('should render table once data is loaded', async () => {
    server.use(accountsListSuccess());
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText(accountsData.items[0].id)).toBeInTheDocument();
    });
  });

  test('should capture Linked account tab opened event', async () => {
    server.use(accountsListSuccess());
    renderApp();
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        screen: 'Linked account page',
        objectName: 'linked account',
        actionName: 'tab opened',
        properties: {
          mid: 'testUserId',
        },
        toLumberjack: true,
      });
    });
  });

  test('should render disabled Add account button when fee_bearer is customer', async () => {
    server.use(accountsListSuccess());
    const reduxState = { ...defaultReduxState };
    reduxState.session.user.merchant.fee_bearer = FEE_BEARER_TYPES.CUSTOMER;
    renderApp(reduxState);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText(accountsData.items[0].id)).toBeInTheDocument();
    });
    expect(screen.getByRole('button', { name: 'Add Account' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Add Account' })).toBeDisabled();
    expect(
      screen.getByText(
        'Route is not supported for merchants accepting payments as per the convenience fee model. To enable, click',
        { exact: false },
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toHaveAttribute(
      'href',
      '/app/payments-and-refunds-settings/capture-refund-settings',
    );
  });
});
