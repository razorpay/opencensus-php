import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import TwoFaVerificationContextProvider from 'common/ui/TwoFactorVerification/TwoFactorVerificationProvider';
import {
  fetchMerchantInvitationsHandler,
  fetchMerchantTeamMembersHandler,
  sendMerchantInvitationSuccess,
} from 'merchant/views/PartnerDashboard/PartnerManageTeam/__tests__/mocks/once-handlers';
import { render, screen, server, userEvent, waitFor, waitForLoadingToFinish } from 'test-utils';

import { defaultUserExtra } from './mocks/fixtures';
import { fetch2FaStatus, fetchUserPassword, verifyOtp } from './mocks/handlers';
import ManageTeamContainer from '../';

jest.mock('common/splitz', () => ({
  withSplitzService: jest.fn((Component) => (props) => (
    <Component
      {...props}
      splitz={{
        abExperiments: {
          enable_2fa_for_protected_flows: {
            variables: {
              result: 'on',
            },
          },
        },
      }}
    />
  )),
  useSplitzService: () => ({}),
}));

function renderApp() {
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      ...defaultUserExtra,
      user: {
        ...defaultUserExtra.user,
      },
      merchant: {
        ...defaultUserExtra.merchant,
      },
    },
    orgExtra: {},
  });

  return render(
    <TwoFaVerificationContextProvider>
      <ManageTeamContainer />
    </TwoFaVerificationContextProvider>,
    {
      showModal: true,
      initialState: { session: { ...session, mode: 'live' } },
      renderViaRouteGuard: false,
    },
  );
}

describe('ManageTeamContainer', () => {
  test('should run 2FA before Inviting a New Member', async () => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: jest.fn().mockReturnValue({
        initiated: jest.fn(),
        success: jest.fn(),
      }),
    };
    window.rzp_user = defaultUserExtra.user;
    server.use(fetchMerchantInvitationsHandler());
    server.use(fetchMerchantTeamMembersHandler());
    server.use(fetchUserPassword());
    server.use(fetch2FaStatus());
    server.use(verifyOtp());
    server.use(sendMerchantInvitationSuccess());

    renderApp();

    await waitForLoadingToFinish();
    const inviteMemberBtn = screen.getByRole('button', { name: 'Invite New Member' });
    expect(inviteMemberBtn).toBeEnabled();
    await userEvent.click(inviteMemberBtn);

    expect(
      await screen.findByRole('heading', { level: 3, name: '2-Step Verification' }),
    ).toBeInTheDocument();

    await userEvent.type(screen.getByTestId('otp-input-1'), '1');
    await userEvent.type(screen.getByTestId('otp-input-2'), '2');
    await userEvent.type(screen.getByTestId('otp-input-3'), '3');
    await userEvent.type(screen.getByTestId('otp-input-4'), '4');
    await userEvent.type(screen.getByTestId('otp-input-5'), '5');
    await userEvent.type(screen.getByTestId('otp-input-6'), '6');

    const submitBtn = screen.getByRole('button', { name: 'Confirm' });
    expect(submitBtn).toBeEnabled();
    await userEvent.click(submitBtn);

    expect(
      await screen.findByRole('heading', { level: 3, name: 'Invite New Member' }),
    ).toBeInTheDocument();

    await userEvent.type(screen.getByPlaceholderText('Email'), 'test@email.com');
    await userEvent.click(screen.getByRole('button', { name: 'Send Invitation' }));
    await waitFor(() => {
      expect(screen.getByTestId('Notification--success')).toHaveTextContent(
        'Invitation has been successfully sent to test@email.com',
      );
    });
  });
});
