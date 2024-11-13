import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import {
  QueryClient,
  QueryClientProvider as ReactQueryClientProvider,
} from '@tanstack/react-query';
import { fireEvent, waitFor, waitForElementToBeRemoved, within } from '@testing-library/dom';
import { render, screen } from '@testing-library/react';
import React from 'react';
import ManageTeamPosEkyc from '../ManageTeamPosEkyc';
import {
  cancelInvitationHandler,
  deleteUserHandler,
  fetchInvitesHandler,
  fetchMerchantUsersHandler,
  sendInviteHandler,
  server,
  updateInvitationHandler,
} from './mocks/apiHandlers';
import { createInviteMock, MOCK_INVITATION_A, MOCK_USER_C } from './mocks/mocks';

const queryClient = new QueryClient({});

const MODAL_TEXT = 'Enter employee details and create link to invite them';
const MODAL_TEXT_UPDATE = 'Edit employee details and update';

const renderApp = () => {
  const renderObj = render(
    <ReactQueryClientProvider client={queryClient}>
      <BladeProvider themeTokens={bladeTheme}>
        <ManageTeamPosEkyc />
      </BladeProvider>
    </ReactQueryClientProvider>,
  );
  return renderObj;
};

const mockShowNotification = jest.fn();

jest.mock('shell/commonStore', () => ({
  ...(jest.requireActual('shell/commonStore') as object),
  useStore: (callbackFn) =>
    callbackFn({
      session: {
        user: {
          name: 'Channel Partner Name',
          merchant: {
            country_code: 'IN',
          },
        },
      },
      showNotification: mockShowNotification,
    }),
}));

describe('Manage Team POS eKYC', () => {
  beforeEach(() => {
    queryClient.clear();
    server.listen({ onUnhandledRequest: 'bypass' });
    server.use(
      fetchInvitesHandler(),
      fetchMerchantUsersHandler(),
      sendInviteHandler(),
      updateInvitationHandler(),
      cancelInvitationHandler(),
      deleteUserHandler(),
    );
  });

  test('should render the list of invitations and users with the correct status', async () => {
    renderApp();
    expect(await screen.findByText(/Add your POS Partner Agents Now!/i)).toBeInTheDocument(); //wait for table content to load

    expect(await screen.findByText(MOCK_INVITATION_A.metadata.name)).toBeInTheDocument();
    // expect(screen.getByText(MOCK_INVITATION_A.metadata.name)).toBeInTheDocument();
    expect(
      screen.getByText(MOCK_INVITATION_A.contact_mobile.replace('+91', '')),
    ).toBeInTheDocument();
    const mockInvitationStatus = screen.getByTestId(`${MOCK_INVITATION_A.id}-status`);
    expect(within(mockInvitationStatus).getByText(/Awaiting/i)).toBeInTheDocument();

    expect(screen.getByText(MOCK_USER_C.metadata.name)).toBeInTheDocument();
    expect(screen.getByText(MOCK_USER_C.contact_mobile.replace('+91', ''))).toBeInTheDocument();
    const confirmedUserStatus = screen.getByTestId(`${MOCK_USER_C.id}-status`);
    expect(within(confirmedUserStatus).getByText(/Onboarded/i)).toBeInTheDocument();
  });

  test('should be able to invite new members', async () => {
    renderApp();
    expect(await screen.findByText(/Add your POS Partner Agents Now!/i)).toBeInTheDocument(); //wait for table content to load
    expect(screen.queryByText(MODAL_TEXT)).not.toBeInTheDocument();
    expect(screen.queryByText(createInviteMock.metadata.name)).not.toBeInTheDocument();
    fireEvent.click(screen.getByText(/Invite new member/i));
    expect(await screen.findByText(MODAL_TEXT)).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText(/Enter name of new member/i), {
      target: { value: createInviteMock.metadata.name },
    });

    fireEvent.change(screen.getByLabelText(/Enter phone number/i), {
      target: { value: createInviteMock.contact_mobile },
    });

    fireEvent.click(screen.getByText(/Send Invite/i));

    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'success',
        message: `Invitation has been successfully sent to ${createInviteMock.contact_mobile}`,
      });
    });

    expect(screen.getByText(createInviteMock.metadata.name)).toBeInTheDocument();
    expect(screen.getByText(createInviteMock.contact_mobile)).toBeInTheDocument();
    const invitedUserStatus = screen.getByTestId(`${createInviteMock.id}-status`);
    expect(within(invitedUserStatus).getByText(/Awaiting/i)).toBeInTheDocument();
  });

  test('should be able to edit existing invitations', async () => {
    renderApp();
    expect(await screen.findByText(/Add your POS Partner Agents Now!/i)).toBeInTheDocument(); //wait for table content to load
    const mockUserCtas = screen.getByTestId(`${MOCK_INVITATION_A.id}-ctas`);
    expect(screen.getByText(MOCK_INVITATION_A.metadata.name)).toBeInTheDocument();

    expect(screen.queryByText(MODAL_TEXT)).not.toBeInTheDocument();
    fireEvent.click(within(mockUserCtas).getByText(/Edit/i));
    expect(await screen.findByText(MODAL_TEXT_UPDATE)).toBeInTheDocument();
    fireEvent.change(screen.getByLabelText(/Member Name/i), {
      target: { value: 'updated username' },
    });
    fireEvent.click(
      screen.getByRole('button', {
        name: /Update Details/i,
      }),
    );
    await waitForElementToBeRemoved(screen.getByText(MODAL_TEXT_UPDATE));
    expect(screen.getByText('updated username')).toBeInTheDocument();
  });

  test('should be able to delete members and invitations', async () => {
    renderApp();
    expect(await screen.findByText(/Add your POS Partner Agents Now!/i)).toBeInTheDocument(); //wait for table content to load
    const mockInvitationCtas = screen.getByTestId(`${MOCK_INVITATION_A.id}-ctas`);
    const mockUserCtas = screen.getByTestId(`${MOCK_USER_C.id}-ctas`);

    expect(screen.getByText(MOCK_INVITATION_A.metadata.name)).toBeInTheDocument();
    expect(screen.getByText(MOCK_USER_C.metadata.name)).toBeInTheDocument();

    fireEvent.click(within(mockInvitationCtas).getByText(/Delete/i));
    expect(
      await screen.findByText('Are you sure you want to cancel this invitation?'),
    ).toBeInTheDocument();
    fireEvent.click(
      screen.getByRole('button', {
        name: /Yes/i,
      }),
    );
    await waitForElementToBeRemoved(screen.getByText(MOCK_INVITATION_A.metadata.name));

    fireEvent.click(within(mockUserCtas).getByText(/Delete/i));
    expect(
      await screen.findByText('Are you sure you want to remove this member from the team?'),
    ).toBeInTheDocument();
    fireEvent.click(
      screen.getByRole('button', {
        name: /Yes/i,
      }),
    );
    await waitForElementToBeRemoved(screen.getByText(MOCK_USER_C.metadata.name));
  });

  test('should sort the list by name when clicking on the column header', async () => {
    renderApp();
    expect(await screen.findByText(/Add your POS Partner Agents Now!/i)).toBeInTheDocument();

    const nameHeader = screen.getByRole('columnheader', {
      name: /Name/i,
    });
    fireEvent.click(nameHeader);

    const firstUserNameAsc = screen.getAllByText(/Mock username/)[0];
    expect(within(firstUserNameAsc).getByText('Mock username C')).toBeInTheDocument();

    fireEvent.click(nameHeader);

    const firstUserNameDesc = screen.getAllByText(/mockName/)[0];
    expect(within(firstUserNameDesc).getByText('mockNameB')).toBeInTheDocument();
  });
});
