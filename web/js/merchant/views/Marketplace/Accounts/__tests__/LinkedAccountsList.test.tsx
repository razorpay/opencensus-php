import React from 'react';
import { render, screen, waitFor, server } from 'common/services/test/test-utils';
import AccountsList from 'merchant/views/Marketplace/Accounts/List';
import { accountsListSuccess } from './mocks/handlers';
import { accountsData } from './mocks/fixtures';

const location = {
  search: '',
};

const state = {
  session: {
    user: {
      isAllowedEdit: true,
      isRouteLinkedAccountCreationDisabled: true,
    },
  },
};

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

jest.mock('merchant/components/ShowWhen', () => ({
  __esModule: true,
  default: ({ children }) => <div>{children}</div>,
  showWhenUtil: ({ children }) => <div>{children}</div>,
}));

describe('Reversal List', () => {
  const renderApp = () => {
    render(<AccountsList location={location} />, { initialState: state });
  };

  test('should render table once data is loaded', async () => {
    server.use(accountsListSuccess());
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText(accountsData.items[0].id)).toBeInTheDocument();
    });
  });
});
