import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as analytics from 'merchant/views/PartnerDashboard/Home/Components/POS/analytics';
import PartnerManageTeam from 'merchant/views/PartnerDashboard/PartnerManageTeam';
import { render, screen, server, userEvent, waitFor, waitForLoadingToFinish } from 'test-utils';

import { defaultUserExtra } from './mocks/fixtures';
import {
  fetchMerchantInvitationsHandler,
  fetchMerchantTeamMembersHandler,
  sendMerchantInvitationError,
  sendMerchantInvitationSuccess,
} from './mocks/once-handlers';
const trackInviteNewMemberModalLoadedSpy = jest.spyOn(analytics, 'trackInviteNewMemberModalLoaded');
const trackInviteNewMemberModalClickedSpy = jest.spyOn(
  analytics,
  'trackInviteNewMemberModalClicked',
);

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    (
      <Component
        {...props}
        splitz={{
          abExperiments: {
            inviteTeamMember2fa: {
              variables: {
                result: 'off',
              },
            },
          },
        }}
      />
    ),
  useSplitzService: () => ({}),
}));

jest.mock('common/ui/HeaderAction', () => ({
  __esModule: true,
  default: ({ children }) => {
    return <div>{children}</div>;
  },
}));

const defaultPartnerDashboardExperiments = {
  isPartnershipsForPosEnabled: true,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

jest.mock('common/ui/HeaderAction', () => ({
  __esModule: true,
  default: ({ children }) => {
    return <div>{children}</div>;
  },
}));

const renderApp = (
  { userExtra = {}, orgExtra = {} }: { userExtra?: any; orgExtra?: any } = {},
  experiments = {},
) => {
  mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments, ...experiments };
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      ...defaultUserExtra,
      ...userExtra,
      user: {
        ...defaultUserExtra.user,
        ...userExtra.user,
      },
      merchant: {
        ...defaultUserExtra.merchant,
        ...userExtra.merchant,
      },
    },
    orgExtra,
  });

  return render(<PartnerManageTeam />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};
describe('PartnerManageTeam', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  beforeEach(() => {
    server.use(fetchMerchantInvitationsHandler());
    server.use(fetchMerchantTeamMembersHandler());
  });
  test('Should render PartnerManageTeam', async () => {
    renderApp();
    expect(screen.getByText('Add your POS Partner Agents Now!')).toBeInTheDocument();
    await waitForLoadingToFinish();
    expect(screen.getByText('QA Reseller User Name')).toBeInTheDocument();
    expect(screen.getByText('test2@razorpay.com')).toBeInTheDocument();
  });

  test('Should show alert if choosing non-POS role', async () => {
    renderApp();
    await waitForLoadingToFinish();
    await userEvent.click(screen.getByRole('button', { name: 'Invite New Member' }));
    expect(
      screen.getByText('Can only view POS section under Affiliated Accounts'),
    ).toBeInTheDocument();

    await userEvent.selectOptions(
      // Find the select element
      screen.getByRole('combobox'),
      // Find and select the Ireland option
      screen.getByRole('option', { name: 'Manager' }),
    );
    expect(
      screen.getByText("You're adding a role which is associated with your merchant profile"),
    ).toBeInTheDocument();
  });

  test('Should be able to invite POS Agent Role in Invite Modal with analytics', async () => {
    server.use(sendMerchantInvitationSuccess());
    renderApp();
    await waitForLoadingToFinish();
    await userEvent.click(screen.getByRole('button', { name: 'Invite New Member' }));
    await waitFor(() => {
      expect(screen.getByText('Member Details')).toBeInTheDocument();
    });

    expect(screen.getByRole('option', { name: 'POS Partner Agent' })).toBeInTheDocument();
    await userEvent.type(screen.getByPlaceholderText('Email'), 'test@email.com');

    expect(trackInviteNewMemberModalLoadedSpy).toHaveBeenCalled();

    await userEvent.click(screen.getByRole('button', { name: 'Send Invitation' }));
    expect(trackInviteNewMemberModalClickedSpy).toHaveBeenCalled();
    await waitFor(() => {
      expect(screen.getByTestId('Notification--success')).toHaveTextContent(
        'Invitation has been successfully sent to test@email.com',
      );
    });
  });
  test('Should show error on invite failure', async () => {
    server.use(sendMerchantInvitationError());
    renderApp();
    await userEvent.click(screen.getByRole('button', { name: 'Invite New Member' }));
    await waitFor(() => {
      expect(screen.getByText('Member Details')).toBeInTheDocument();
    });
    await userEvent.type(screen.getByPlaceholderText('Email'), 'test@email.com');
    await userEvent.click(screen.getByRole('button', { name: 'Send Invitation' }));

    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toHaveTextContent(
        'Invitation is already sent to this email',
      );
    });
  });
});
