import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import ProductTabsWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper';
import { emptyAccountsListResponsePOS } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AcceptedInvites/__tests__/mocks/fixtures';
import {
  allInvitesDataEmptyPOS,
  allInvitesDataPOS,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/__tests__/mocks/fixtures';
import { allInvitesListSuccessPOS } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/__tests__/mocks/once-handlers';
import { emptyAccountsListResponse } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import { acceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import { fetchReferralsHandler } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';
import {
  allInvitesData,
  allInvitesDataEmpty,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/fixtures';
import { allInvitesListSuccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

const mockIsConfigTagEnabled = jest.fn();
jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component i18={{ isConfigTagEnabled: mockIsConfigTagEnabled }} {...props} />,
  useI18Service: () => ({
    isConfigTagEnabled: mockIsConfigTagEnabled,
  }),
}));

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
  isPartnershipsForPosEnabled: false,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const isPartner = jest.fn();
const isFeatureEnabled = jest.fn();
// Mock required for announcements -
const instantActivation = { isWhitelistFlow: false };

const location = {
  search: '',
  pathname: '/partners/submerchants',
};

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useLocation: () => location,
}));

const renderApp = ({ userExtra = {}, orgExtra = {} } = {}, props = {}, experiments = {}) => {
  mockPartnerDashboardExperiments = {
    ...defaultPartnerDashboardExperiments,
    ...experiments,
  };
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      id: 'testUserId',
      // For ShowWhen to work -
      userRole: 'owner',
      isAuthenticated: true,
      isOrgRZP: true,
      isPartner,
      isFeatureEnabled,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
      ...userExtra,
    },
    orgExtra,
  });
  render(<ProductTabsWrapper {...props} />, {
    showModal: true,
    initialState: {
      session,
    },
  });
};

describe('ProductTabsWrapper', () => {
  beforeEach(() => {
    document.execCommand = jest.fn();
    server.use(fetchReferralsHandler());
    isPartner.mockImplementation((value = 'reseller') => value === 'reseller');
  });
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });

  describe('SideHeader', () => {
    test('should render Refer merchant modal after clicking Refer button', async () => {
      const productType = PRODUCT_TYPE.PG;
      renderApp({}, { productType });

      await userEvent.click(screen.getByRole('button', { name: 'Share Referral Link' }));
      await userEvent.click(screen.getByText('Razorpay Payments'));

      await waitFor(() => {
        expect(screen.getByRole('button', { name: 'Copy Link' })).toBeInTheDocument();
      });
    });

    test('should render Add merchant modal after clicking Add button', async () => {
      const productType = PRODUCT_TYPE.X;
      renderApp({}, { productType });

      await userEvent.click(screen.getByRole('button', { name: 'Add New Clients' }));

      await waitFor(() => {
        expect(screen.getByText('Add New Merchants - RazorpayX')).toBeInTheDocument();
      });
    });
  });

  describe('PG Invites List Conditions', () => {
    const productType = PRODUCT_TYPE.PG;
    test(`should render empty table when accepted invites is empty and all invites is non empty`, async () => {
      server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
      server.use(allInvitesListSuccess(allInvitesData));
      renderApp({}, { productType }, { isPartnershipsInviteFlowEnabled: true });
      // Welcome spinner
      await waitForLoadingToFinishByLabel();
      // Accepted invites spinner
      await waitForLoadingToFinishByLabel();

      // Loads the table with empty screen
      await waitFor(() => {
        expect(screen.getByText('Invite Accepted On')).toBeInTheDocument();
      });
      expect(
        screen.getByText(
          'All accepted invites will be visible here once the client has accepted the invite sent by you.',
        ),
      ).toBeInTheDocument();
    });

    test(`should render welcome screen for ${productType} when accepted invites + all invites empty`, async () => {
      server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
      server.use(allInvitesListSuccess(allInvitesDataEmpty));
      renderApp({}, { productType }, { isPartnershipsInviteFlowEnabled: true });
      // Welcome spinner
      await waitForLoadingToFinishByLabel();

      await waitFor(() => {
        expect(screen.getByText('Welcome to Partner Dashboard')).toBeInTheDocument();
      });
      // No table column render
      expect(screen.queryByText('Account ID')).not.toBeInTheDocument();

      expect(screen.getByText('Invite a client by adding their details')).toBeInTheDocument();
      expect(screen.queryAllByText('Add New Clients')).toHaveLength(2);

      // Copy link behavior
      expect(screen.getByText('Copy Link')).toBeInTheDocument();
      await userEvent.click(screen.getByRole('button', { name: 'Copy Link' }));
      expect(document.execCommand).toHaveBeenCalled();

      await userEvent.click(screen.getAllByRole('button', { name: 'Add New Clients' })[1]);
      await waitFor(() => {
        expect(screen.getByText('Add New Clients - Razorpay Payments')).toBeInTheDocument();
      });
    });
  });

  describe('POS Invites List Conditions', () => {
    const productType = PRODUCT_TYPE.POS;
    test(`should render empty table when accepted invites is empty and all invites is non empty`, async () => {
      server.use(acceptedInvitesListHandler(emptyAccountsListResponsePOS));
      server.use(allInvitesListSuccessPOS(allInvitesDataPOS));
      renderApp(
        {},
        { productType },
        { isPartnershipsInviteFlowEnabled: true, isPartnershipsForPosEnabled: true },
      );
      // Welcome spinner
      await waitForLoadingToFinishByLabel();
      // Accepted invites spinner
      await waitForLoadingToFinishByLabel();

      // Loads the table with empty screen
      await waitFor(() => {
        expect(screen.getByText('Invite Accepted On')).toBeInTheDocument();
      });
      expect(
        screen.getByText(
          'All accepted invites will be visible here once the client has accepted the invite sent by you.',
        ),
      ).toBeInTheDocument();
    });

    test(`should render welcome screen for ${productType} when accepted invites + all invites empty`, async () => {
      server.use(acceptedInvitesListHandler(emptyAccountsListResponsePOS));
      server.use(allInvitesListSuccessPOS(allInvitesDataEmptyPOS));
      renderApp(
        {},
        { productType },
        { isPartnershipsInviteFlowEnabled: true, isPartnershipsForPosEnabled: true },
      );

      // Welcome spinner
      await waitForLoadingToFinishByLabel();
      // Accepted invites spinner
      await waitForLoadingToFinishByLabel();

      await waitFor(() => {
        expect(screen.getByText('Welcome to Partner POS Dashboard')).toBeInTheDocument();
      });
      // No table column render
      expect(screen.queryByText('Account ID')).not.toBeInTheDocument();

      expect(screen.getByText('Invite a client by adding their details')).toBeInTheDocument();
      expect(screen.queryAllByText('Add New Clients')).toHaveLength(2);
      expect(screen.queryByText('Copy Link')).not.toBeInTheDocument();

      await userEvent.click(screen.getAllByRole('button', { name: 'Add New Clients' })[1]);

      await waitFor(() => {
        expect(screen.getByText('Add New Clients - Razorpay POS')).toBeInTheDocument();
      });
    });
  });

  describe('Capital Invites List Conditions', () => {
    const productType = PRODUCT_TYPE.CAPITAL;

    test.todo(`legacy flow tests for ${productType}`);
  });
});
