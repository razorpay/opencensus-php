import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as downloadSubmerchantsActions from 'merchant/reducers/submerchant';
import PaymentsAcceptedInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AcceptedInvites';
import {
  accountsListResponse,
  emptyAccountsListResponse,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import { acceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: true,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const mockSetIsAcceptedInvitesEmpty = jest.fn();
jest.mock(
  'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/WelcomeScreenContainer/hooks/useWelcomeScreenData',
  () => ({
    __esModule: true,
    default: () => ({
      isFilterSearchUsed: false,
      setIsAcceptedInvitesEmpty: mockSetIsAcceptedInvitesEmpty,
    }),
  }),
);

const isPartner = jest.fn();
const isFeatureEnabled = jest.fn();

const renderApp = (
  { userExtra = {}, orgExtra = {}, isRzpOrg = true } = {},
  props = {},
  experiments = {},
) => {
  mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments, ...experiments };
  const session = getInitialUserOrgState({
    isRzpOrg,
    userExtra: {
      isPartner,
      isFeatureEnabled,
      isSubMerchantKycEnabled: true,
      ...userExtra,
    },
    orgExtra,
  });

  return render(<PaymentsAcceptedInvites {...props} />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

const downloadSubMerchant = jest.spyOn(downloadSubmerchantsActions, 'downloadSubmerchants');
describe('PaymentsAcceptedInvites for Razorpay', () => {
  // Note: isSubMerchantKycEnabled is true for Razorpay owner users
  describe('in Partnerships Invite Flow', () => {
    beforeEach(() => {
      isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
      isFeatureEnabled.mockImplementation(() => false);
    });
    afterEach(() => {
      jest.clearAllMocks();
    });
    test(`should render the empty screen once the data is fetched and is empty for payments`, async () => {
      server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
      renderApp();

      // Wait for data table spinner
      await waitForLoadingToFinishByLabel();
      expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(true);
      expect(
        screen.getByText(
          'All accepted invites will be visible here once the client has accepted the invite sent by you.',
        ),
      ).toBeInTheDocument();
    });

    test(`should render the list once the data is fetched and is not empty for payments`, async () => {
      const { history } = renderApp();
      history.push = jest.fn();
      await waitForLoadingToFinishByLabel();
      await waitFor(() => {
        expect(screen.queryByText('Invite Accepted On')).toBeInTheDocument();
      });
      expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(false);

      // Filters and columns
      expect(screen.getAllByText('Account ID')).toHaveLength(2);
      expect(screen.getAllByText('Name')).toHaveLength(2);
      expect(screen.getAllByText('Contact')).toHaveLength(2);
      expect(screen.queryByText('Settlement Status')).toBeNull();
      expect(screen.getByText('Actions')).toBeInTheDocument();
      expect(screen.queryAllByRole('button', { name: 'Request for KYC' })).toHaveLength(3);

      const { items } = accountsListResponse;
      expect(screen.getByText(items[0].id)).toBeInTheDocument();
      expect(screen.getByText(items[0].name)).toBeInTheDocument();
      expect(screen.getByText(items[0].email)).toBeInTheDocument();
      expect(screen.getByText(items[1].id)).toBeInTheDocument();
      expect(screen.getByText(items[1].name)).toBeInTheDocument();
      expect(screen.getByText(items[1].email)).toBeInTheDocument();

      await userEvent.click(screen.getByText(items[0].id));
      // Check navigation to details panel
      expect(history.push.mock.calls[0][0]).toStrictEqual({
        hash: '',
        pathname: `/partners/submerchants/${items[0].id}`,
        search: '',
      });

      await userEvent.click(screen.getByText('Export All (CSV)'));
      await userEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
      await waitFor(() => {
        expect(downloadSubMerchant).toBeCalled();
      });
    });

    test(`render correct columns when partner_sub_kyc_access = false for platform partners for payments`, async () => {
      isFeatureEnabled.mockImplementation((flag) => flag !== 'partner_sub_kyc_access');
      isPartner.mockImplementation(
        (partner_type = 'pure_platform') => partner_type === 'pure_platform',
      );
      renderApp();
      await waitForLoadingToFinishByLabel();
      await waitFor(() => {
        expect(screen.queryByText('Invite Accepted On')).toBeInTheDocument();
      });
      expect(screen.queryByText('Actions')).toBeNull();
    });
    test(`render correct columns when partner_sub_kyc_access = true for platform partners for payments`, async () => {
      isFeatureEnabled.mockImplementation((flag) => flag === 'partner_sub_kyc_access');
      isPartner.mockImplementation(
        (partner_type = 'pure_platform') => partner_type === 'pure_platform',
      );
      renderApp();
      await waitForLoadingToFinishByLabel();
      await waitFor(() => {
        expect(screen.queryByText('Invite Accepted On')).toBeInTheDocument();
      });
      expect(screen.getByText('Actions')).toBeInTheDocument();
      expect(screen.queryAllByRole('button', { name: 'Perform KYC' })).toHaveLength(3);
    });
  });

  describe('in legacy Partnerships Referral Flow', () => {
    beforeEach(() => {
      isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
      isFeatureEnabled.mockImplementation(() => false);
    });
    afterEach(() => {
      jest.clearAllMocks();
    });
    const legacyExperimentsValues = {
      isPartnershipsInviteFlowEnabled: false,
      isPlatformPartnerInviteFlowEnabled: false,
    };
    // Rzp
    test(`render correct columns for reseller for payments`, async () => {
      const { history } = renderApp({}, {}, legacyExperimentsValues);
      history.push = jest.fn();
      await waitForLoadingToFinishByLabel();
      await waitFor(() => {
        expect(screen.queryByText('Added On')).toBeInTheDocument();
      });
      expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(false);
      // Filters and columns
      expect(screen.queryByText('Settlement Status')).toBeNull();
      expect(screen.queryByText('Switch Account')).toBeNull();
      expect(screen.getAllByText('Account ID')).toHaveLength(2);
      expect(screen.getAllByText('Name')).toHaveLength(2);
      expect(screen.getAllByText('Contact')).toHaveLength(2);
      expect(screen.getByText('Actions')).toBeInTheDocument();
      expect(screen.queryAllByRole('button', { name: 'Request for KYC' })).toHaveLength(3);

      const { items } = accountsListResponse;

      await userEvent.click(screen.getByText(items[0].id));
      expect(history.push.mock.calls[0][0]).toStrictEqual({
        hash: '',
        pathname: `/partners/submerchants/${items[0].id}`,
        search: '',
      });
    });

    test('render correct columns and CTAs for platform partner', async () => {
      isPartner.mockImplementation(
        (partner_type = 'pure_platform') => partner_type === 'pure_platform',
      );
      const { history } = renderApp({}, {}, legacyExperimentsValues);
      history.push = jest.fn();

      await waitForLoadingToFinishByLabel();
      await waitFor(() => {
        expect(screen.queryByText('Settlement Status')).toBeInTheDocument();
      });
      // Filters and columns
      expect(screen.getAllByText('Switch Account')).toHaveLength(1);
      expect(screen.getAllByText('App Id')).toHaveLength(1);
      expect(screen.getAllByText('Application ID')).toHaveLength(1);

      const { items } = accountsListResponse;

      await userEvent.click(screen.getByText(items[0].id));
      expect(history.push).not.toHaveBeenCalled();

      // Check Details Panel link
      await userEvent.click(screen.getByText(items[0].name));
      expect(history.push.mock.calls[0][0]).toStrictEqual({
        hash: '',
        pathname: `/partners/submerchants/${items[0].id}/${items[0].application.id}`,
        search: '',
      });

      // Check App ID link
      await userEvent.click(screen.getByText(items[0].application.id));
      expect(history.push.mock.calls[1][0]).toStrictEqual({
        hash: '',
        pathname: `/partners/applications/${items[0].application.id}`,
        search: '',
      });
    });
    test('render correct columns and CTAs for aggregator partner', async () => {
      isPartner.mockImplementation((partner_type = 'aggregator') => partner_type === 'aggregator');
      renderApp({}, {}, legacyExperimentsValues);
      await waitFor(() => {
        expect(screen.queryByText('Settlement Status')).toBeInTheDocument();
      });
      // Filters and columns
      expect(screen.queryByText('App Id')).toBeNull();
      expect(screen.queryByText('Actions')).toBeNull();
      expect(screen.queryAllByRole('button', { name: 'Request for KYC' })).toHaveLength(0);

      expect(screen.getAllByText('Account ID')).toHaveLength(2);
      expect(screen.getAllByText('Name')).toHaveLength(2);
      expect(screen.getAllByText('Contact')).toHaveLength(2);
      expect(screen.getAllByText('Switch Account')).toHaveLength(1);
      expect(screen.queryAllByRole('button', { name: 'Switch' })).toHaveLength(3);
    });
    test('render correct columns and CTAs with no switch access for aggregator partner', async () => {
      isPartner.mockImplementation((partner_type = 'aggregator') => partner_type === 'aggregator');
      const { items } = accountsListResponse;
      const customResponse = {
        ...accountsListResponse,
        items: [
          // No Access
          { ...items[0], dashboard_access: false, activated: false },
          // No Access
          { ...items[1], dashboard_access: false, activated: true },
          // Allow Access: switching before activation for razorpay
          { ...items[2], dashboard_access: true, activated: false },
        ],
      };
      server.use(acceptedInvitesListHandler(customResponse));
      renderApp({}, {}, legacyExperimentsValues);
      await waitFor(() => {
        expect(screen.queryByText('Settlement Status')).toBeInTheDocument();
      });

      expect(screen.getAllByText('Switch Account')).toHaveLength(1);
      expect(screen.queryAllByRole('button', { name: 'Switch' })).toHaveLength(1);
      expect(screen.getAllByText('No Access')).toHaveLength(2);
    });
  });
});

describe('PaymentsAcceptedInvites for Curlec', () => {
  beforeEach(() => {
    isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
    isFeatureEnabled.mockImplementation(() => false);
  });
  afterEach(() => {
    jest.clearAllMocks();
  });
  const legacyExperimentsValues = {
    isPartnershipsInviteFlowEnabled: false,
    isPlatformPartnerInviteFlowEnabled: false,
  };
  // Note: isSubMerchantKycEnabled is always false for Curlec
  const curlecUserExtra = { isSubMerchantKycEnabled: false };
  const isRzpOrg = false;
  test(`render correct columns for reseller for payments for curlec`, async () => {
    renderApp({ userExtra: curlecUserExtra, isRzpOrg }, {}, legacyExperimentsValues);

    await waitForLoadingToFinishByLabel();
    await waitFor(() => {
      expect(screen.queryByText('Settlement Status')).toBeInTheDocument();
    });
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(false);
    // Filters and columns
    expect(screen.getAllByText('Account ID')).toHaveLength(2);
    expect(screen.getAllByText('Name')).toHaveLength(2);
    expect(screen.getAllByText('Registered Email')).toHaveLength(1);
    expect(screen.getAllByText('Email ID')).toHaveLength(1);
    expect(screen.getAllByText('Added On')).toHaveLength(1);
  });

  test('render correct columns and CTAs with no switch access for aggregator partner', async () => {
    isPartner.mockImplementation((partner_type = 'aggregator') => partner_type === 'aggregator');
    const { items } = accountsListResponse;
    const customResponse = {
      ...accountsListResponse,
      items: [
        // No Access
        { ...items[0], dashboard_access: false, activated: false },
        // No Access
        { ...items[1], dashboard_access: false, activated: true },
        // No Access: don't allow before activation for curlec
        { ...items[2], dashboard_access: true, activated: false },
      ],
    };
    server.use(acceptedInvitesListHandler(customResponse));
    renderApp({ userExtra: curlecUserExtra, isRzpOrg }, {}, legacyExperimentsValues);
    await waitFor(() => {
      expect(screen.queryByText('Settlement Status')).toBeInTheDocument();
    });

    expect(screen.getAllByText('Switch Account')).toHaveLength(1);
    expect(screen.queryAllByRole('button', { name: 'Switch' })).toHaveLength(0);
    expect(screen.getAllByText('No Access')).toHaveLength(3);
  });
});
