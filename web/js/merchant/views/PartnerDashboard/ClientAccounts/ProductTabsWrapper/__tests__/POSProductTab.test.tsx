import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import TwoFaVerificationContextProvider from 'common/ui/TwoFactorVerification/TwoFactorVerificationProvider';
import ProductTabsWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper';
import { emptyAccountsListResponsePOS } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AcceptedInvites/__tests__/mocks/fixtures';
import {
  allInvitesDataEmptyPOS,
  allInvitesDataPOS,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/__tests__/mocks/fixtures';
import { allInvitesListSuccessPOS } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/__tests__/mocks/once-handlers';
import { acceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import { fetchReferralsHandler } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';
import { allInvitesListSuccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';
import { PRODUCT_ROUTE_PATH_PREFIX, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

import { staticUserExtra, staticLocation } from './mocks/fixtures';
const productType = PRODUCT_TYPE.POS;
const productPrefix = PRODUCT_ROUTE_PATH_PREFIX[productType];

const defaultLocation = {
  ...staticLocation,
  pathname: `/partners/submerchants${productPrefix}`,
};
// To Render POS Clients via Route
const initialEntries = [productPrefix];

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
const defaultUserExtra = {
  ...staticUserExtra,
  isPartner,
  isFeatureEnabled,
};
let mockLocation = defaultLocation;
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useLocation: () => mockLocation,
}));

const renderApp = (
  { userExtra = {}, orgExtra = {}, initialEntries = ['/'] }: any = {},
  props = {},
  experiments = {},
) => {
  mockPartnerDashboardExperiments = {
    ...defaultPartnerDashboardExperiments,
    ...experiments,
  };
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      ...defaultUserExtra,
      ...userExtra,
    },
    orgExtra,
  });
  render(
    <TwoFaVerificationContextProvider>
      <ProductTabsWrapper {...props} />
    </TwoFaVerificationContextProvider>,
    {
      showModal: true,
      initialEntries,
      path: '',
      initialState: {
        session,
      },
    },
  );
};

describe('POS Invites List Conditions', () => {
  beforeEach(() => {
    document.execCommand = jest.fn();
    server.use(fetchReferralsHandler());
    isPartner.mockImplementation((partner_type = 'reseller') => partner_type === 'reseller');
  });
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
    mockLocation = defaultLocation;
  });

  test(`should render empty table when accepted invites is empty and all invites is non empty`, async () => {
    server.use(acceptedInvitesListHandler(emptyAccountsListResponsePOS));
    server.use(allInvitesListSuccessPOS(allInvitesDataPOS));
    renderApp(
      { initialEntries },
      { productType },
      { isPartnershipsInviteFlowEnabled: true, isPartnershipsForPosEnabled: true },
    );
    // Welcome check spinner
    await waitForLoadingToFinishByLabel();
    // Accepted invites spinner
    await waitForLoadingToFinishByLabel();

    // Loads the table with empty screen
    await waitFor(() => {
      expect(screen.getByText('Invite Accepted On')).toBeInTheDocument();
    });
    expect(
      screen.getByText(
        'You can now opt in to perform KYC for the client when you invite them onto Razorpay',
      ),
    ).toBeInTheDocument();
  });

  test(`should render empty table for pos when filters are used and accepted invites + all invites is empty`, async () => {
    mockLocation = {
      ...mockLocation,
      search: '?name=ABC123&count=28',
    };
    server.use(acceptedInvitesListHandler(emptyAccountsListResponsePOS));
    server.use(allInvitesListSuccess(allInvitesDataEmptyPOS));
    renderApp(
      {
        initialEntries,
      },
      { productType },
      { isPartnershipsInviteFlowEnabled: true, isPartnershipsForPosEnabled: true },
    );
    // Welcome check spinner
    await waitForLoadingToFinishByLabel();

    // Loads the table with empty screen
    await waitFor(() => {
      expect(screen.getByText('Invite Accepted On')).toBeInTheDocument();
    });
    expect(screen.queryByText('Welcome to Partner Dashboard')).not.toBeInTheDocument();

    expect(screen.getByText('No Results Found')).toBeInTheDocument();
  });

  test(`should render welcome screen for pos when accepted invites + all invites empty`, async () => {
    server.use(acceptedInvitesListHandler(emptyAccountsListResponsePOS));
    server.use(allInvitesListSuccessPOS(allInvitesDataEmptyPOS));
    renderApp(
      { initialEntries },
      { productType },
      { isPartnershipsInviteFlowEnabled: true, isPartnershipsForPosEnabled: true },
    );

    // Welcome check spinner
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
