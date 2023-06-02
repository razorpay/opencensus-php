import { render, screen, server, userEvent, waitFor } from 'test-utils';
import {
  acceptInvitationHandler,
  rejectInvitationHandler,
} from 'merchant/views/AccountAndSettings/BusinessSettings/__test__/fixtures/handlers';
import TeamInvitations from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/TeamInvitations';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

const mockInvite = {
  id: '2373393',
  email: 'test.user@razorpay.com',
  role: 'operations',
  user_id: 'K0KQSNE7BypZ5VE',
  merchant_id: '2373393BnSTzODQ',
  product: 'primary',
  is_draft: 0,
  merchant_name: 'RZP QA TEST',
};

const stateWithInvite = {
  session: {
    user: {
      id: 'BnSTzODddwskmQ',
      user: {
        invitations: [mockInvite],
      },
    },
    org: {},
  },
};

describe('Team Invitations', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  const renderApp = (initialState) => {
    render(<TeamInvitations />, {
      initialState,
    });
  };

  test('should show invitations', () => {
    renderApp(stateWithInvite);
    expect(screen.getByText(mockInvite.merchant_name));
    expect(
      screen.getByRole('button', {
        name: 'Accept',
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Reject',
      }),
    ).toBeInTheDocument();
  });

  test('should render invitations component', () => {
    renderApp();
    expect(screen.getByText('Invitations')).toBeInTheDocument();
  });

  describe('Accept Invitation', () => {
    test('should show success message on success response', async () => {
      server.use(acceptInvitationHandler());
      renderApp(stateWithInvite);
      const acceptCTA = screen.getByRole('button', {
        name: 'Accept',
      });
      await userEvent.click(acceptCTA);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          message: 'You have accepted the invite.',
          type: 'success',
        });
      });
    });

    test('should show error message on failure response', async () => {
      const mockError = 'Some Error Occured while accepting invite';
      server.use(
        acceptInvitationHandler({
          isErrorCase: true,
          errors: mockError,
        }),
      );
      renderApp(stateWithInvite);
      const acceptCTA = screen.getByRole('button', {
        name: 'Accept',
      });
      await userEvent.click(acceptCTA);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          message: mockError,
          type: 'error',
        });
      });
    });
  });

  describe('Reject Invitations', () => {
    test('should show success message on success response', async () => {
      server.use(rejectInvitationHandler());
      renderApp(stateWithInvite);
      const rejectCTA = screen.getByRole('button', {
        name: 'Reject',
      });
      await userEvent.click(rejectCTA);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          message: 'You have rejected the invite.',
          type: 'success',
        });
      });
    });

    test('should show error message on failure response', async () => {
      const mockError = 'Some Error Occured while rejecting invite';
      server.use(
        rejectInvitationHandler({
          isErrorCase: true,
          errors: mockError,
        }),
      );
      renderApp(stateWithInvite);
      const rejectCTA = screen.getByRole('button', {
        name: 'Reject',
      });
      await userEvent.click(rejectCTA);
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          message: mockError,
          type: 'error',
        });
      });
    });
  });
});
