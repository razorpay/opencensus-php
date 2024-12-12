import {
  testBreadCrumb,
  testConditionalLinks,
  testRedirectionWhenAccountAndSettingsIsNotEnabled,
} from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import BusinessSettings from 'merchant/views/AccountAndSettings/BusinessSettings/BusinessSettings';
import { render, screen, waitFor } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as conditionalUtils from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import * as gstUtils from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/utils';

jest.mock('merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v1', () => ({
  __esModule: true,
  default: () => <>AccountDetails</>,
}));

jest.mock('merchant/views/AccountAndSettings/BusinessSettings/Tabs/ActivationDetails', () => ({
  __esModule: true,
  default: () => <>ActivationDetails</>,
}));

jest.mock('merchant/views/AccountAndSettings/BusinessSettings/Tabs/BusinessDetails', () => ({
  __esModule: true,
  default: () => <>BusinessDetails</>,
}));

jest.mock('merchant/views/Account/Profile/components/GST', () => ({
  __esModule: true,
  default: () => <>GSTDetails</>,
}));

jest.mock('merchant/views/Account/Profile/components/SupportDetails', () => ({
  __esModule: true,
  default: () => <>CustomerSupportDetails</>,
}));

jest.mock('merchant/views/Account/ManageTeam', () => ({
  __esModule: true,
  default: () => <>TeamDetails</>,
}));

jest.mock('merchant/views/AccountAndSettings/BusinessSettings/Tabs/SupportTickets', () => ({
  __esModule: true,
  default: () => <>SupportTickets</>,
}));

jest.mock('merchant/views/TicketSupport/components/Conversations', () => ({
  __esModule: true,
  default: () => <>Conversations</>,
}));

jest.mock('merchant/views/AccountAndSettings/BusinessSettings/Tabs/TeamInvitations', () => ({
  __esModule: true,
  default: () => <>TeamInvitations</>,
}));

jest.mock('merchant/views/AccountAndSettings/utils/conditionUtils', () => ({
  isGstDetailsEnabled: jest.fn(),
  isTeamManagementAllowed: jest.fn(),
  isAccountDetailsEnabled: jest.fn(),
  isSupportTicketEnabled: jest.fn(),
  shouldShowTeamInvitations: jest.fn(),
}));

const useGSTUpdateExperimentSpy = jest.spyOn(gstUtils, 'useGSTUpdateExperiment');
useGSTUpdateExperimentSpy.mockReturnValue({
  isGSTUpdateEnabled: false,
});

const isOrgAllowedFunctionality = jest.fn();

const renderApp = ({ user, pathname } = {}) => {
  return render(<BusinessSettings />, {
    initialEntries: [pathname ?? ROUTES_INFO.ACCOUNT_DETAILS],
    initialState: {
      session: {
        user: {
          isAccountAndSettingsRevampEnabled: true,
          isOrgAllowedFunctionality,
          ...user,
        },
        org: {},
      },
    },
  });
};

describe('Business Settings', () => {
  test('should render dashboard banner and test banner', () => {
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
  });

  test.skip('should use appropriate component for each route', async () => {
    renderApp();
    await waitFor(() => {
      // Since components are lazy loaded
      expect(screen.queryByRole('loader')).not.toBeInTheDocument();
      [
        { path: ROUTES_INFO.ACCOUNT_DETAILS, component: 'AccountDetails' },
        { path: ROUTES_INFO.ACTIVATION_DETAILS, component: 'ActivationDetails' },
        { path: ROUTES_INFO.BUSINESS_DETAILS, component: 'BusinessDetails' },
        { path: ROUTES_INFO.GST_DETAILS, component: 'GSTDetails' },
        { path: ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS, component: 'CustomerSupportDetails' },
        { path: ROUTES_INFO.MANAGE_TEAM_DETAILS, component: 'TeamDetails' },
        { path: '/business-settings/ticket-support/tickets', component: 'SupportTickets' },
        {
          path: '/business-settings/ticket-support/:instance/:id/:ticketType/conversation',
          component: 'Conversations',
        },
      ].forEach(({ path, component }) => {
        expect(screen.getByTestId(path)).toHaveTextContent(component);
      });
    });
  });

  test.skip('should show TeamInvitations when shouldShowTeamInvitations is true', async () => {
    conditionalUtils.shouldShowTeamInvitations.mockReturnValue(true);
    renderApp();
    await waitFor(() => {
      // Since components are lazy loaded
      expect(screen.queryByRole('loader')).not.toBeInTheDocument();
    });
    expect(screen.getByText('TeamInvitations')).toBeInTheDocument();
  });

  testBreadCrumb(renderApp, 'Account details', ROUTES_INFO.ACCOUNT_DETAILS);

  test('should render default links', () => {
    renderApp();
    [
      {
        path: ROUTES_INFO.ACCOUNT_DETAILS,
        name: 'Account details',
      },
      {
        path: ROUTES_INFO.BUSINESS_DETAILS,
        name: 'Business details',
      },
      {
        path: ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS,
        name: 'Customer support details',
      },
    ].forEach(({ path, name }) => {
      const link = screen.getByRole('link', { name });
      expect(link).toBeInTheDocument();
      expect(link).toHaveAttribute('href', path);
    });
    [('Account details', 'GST details', 'Manage team')].forEach((linkName) => {
      expect(screen.queryByRole('link', { name: linkName })).not.toBeInTheDocument();
    });
  });

  describe('Conditional Links', () => {
    testConditionalLinks(renderApp, [
      ['Activation details', 'isAccountDetailsEnabled', ROUTES_INFO.ACTIVATION_DETAILS],
      ['GST details', 'isGstDetailsEnabled', ROUTES_INFO.GST_DETAILS],
      ['Manage team', 'isTeamManagementAllowed', ROUTES_INFO.MANAGE_TEAM_DETAILS],
      ['Support Tickets', 'isSupportTicketEnabled', '/business-settings/ticket-support/tickets'],
      ['Invitations', 'shouldShowTeamInvitations', ROUTES_INFO.TEAM_INVITATIONS],
    ]);

    test('should render Support history as link name when isSupportTicketEnabled and isMobileSignupCareActive', () => {
      conditionalUtils.isSupportTicketEnabled.mockReturnValueOnce(true);
      renderApp({
        user: {
          isMobileSignupCareActive: true,
        },
      });
      const link = screen.getByRole('link', { name: 'Support History' });
      expect(link).toBeInTheDocument();
      expect(link).toHaveAttribute('href', '/business-settings/ticket-support/tickets');
    });
  });

  describe('When is Account And Settings Revamp is not enabled', () => {
    testRedirectionWhenAccountAndSettingsIsNotEnabled(renderApp, [
      ['/team', ROUTES_INFO.MANAGE_TEAM_DETAILS],
      ['/profile', 'some-route'],
    ]);
  });
});
