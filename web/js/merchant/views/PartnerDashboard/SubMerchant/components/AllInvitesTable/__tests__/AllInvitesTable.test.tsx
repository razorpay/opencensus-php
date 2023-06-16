import React from 'react';
import { render, screen, waitFor, server, userEvent } from 'common/services/test/test-utils';
import AllInvitesTable from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable';
import { allInvitesData, allInvitesDataEmpty } from './mocks/fixtures';
import { allInvitesListSuccess, allInvitesListError, resendInviteHandler } from './mocks/handlers';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const showNotificationsSpy = jest.spyOn(NotificationsActions, 'showNotification');
const location = {
  search: '',
};
describe('AllInvitesTable', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });
  const renderApp = () => {
    // eslint-disable-next-line
    // @ts-ignore
    render(<AllInvitesTable location={location} />);
  };

  test('should render spinner if loading', () => {
    server.use(allInvitesListSuccess());
    renderApp();
    const spinner = screen.getByTestId('all-invites-spinner');
    expect(spinner).toBeInTheDocument();
  });

  test('should render invites list once the data is fetched', async () => {
    server.use(allInvitesListSuccess());
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Last Invited On')).toBeInTheDocument();
    });

    // Filter and Column name
    expect(screen.queryAllByText('Name').length).toEqual(2);
    expect(screen.queryAllByText('Email ID').length).toEqual(2);
    expect(screen.getByText('Contact')).toBeInTheDocument();

    expect(screen.getByText(allInvitesData.data.items[0].email)).toBeInTheDocument();
  });

  test('should render empty table if items are empty', async () => {
    server.use(allInvitesListSuccess(allInvitesDataEmpty));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Last Invited On')).toBeInTheDocument();
      expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
    });
    expect(screen.queryByText(allInvitesData.data.items[0].email)).not.toBeInTheDocument();
  });

  test('should render filtered data after clicking search', async () => {
    server.use(allInvitesListSuccess());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Last Invited On')).toBeInTheDocument();
    });
    expect(screen.getByText(allInvitesData.data.items[0].name)).toBeInTheDocument();

    const countInput = screen.getByLabelText('Count');
    expect(countInput).toBeInTheDocument();
    await userEvent.type(countInput, '1');

    const searchButton = screen.getByRole('button', { name: 'Search' });
    expect(searchButton).toBeInTheDocument();
    await userEvent.click(searchButton);

    await waitFor(() => {
      expect(screen.queryByText(allInvitesData.data.items[0].name)).toBeInTheDocument();
    });
  });

  test('should fire resend api when resend Invite clicked', async () => {
    server.use(allInvitesListSuccess());
    server.use(resendInviteHandler());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Last Invited On')).toBeInTheDocument();
    });

    const firstResendButton = screen.queryAllByRole('button', { name: 'Resend Invite' })[0];
    await userEvent.click(firstResendButton);

    await waitFor(() => {
      expect(showNotificationsSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'success',
          message: 'Invite is resent successfully',
        }),
      );
    });
    expect(screen.getByText('Invite is resent successfully')).toBeInTheDocument();
  });

  // todo skipping this for now because it is getting failed because of retry option of react-query.
  test.skip('should render error notification if invites API throws an error', async () => {
    server.use(allInvitesListError());
    renderApp();
    await waitFor(() => {
      expect(showNotificationsSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
          message: 'There was an error',
        }),
      );
    });
    expect(screen.getByText('There was an error')).toBeInTheDocument();
  });
});
