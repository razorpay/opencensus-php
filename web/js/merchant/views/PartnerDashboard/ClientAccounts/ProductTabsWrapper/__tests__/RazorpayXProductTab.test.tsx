import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import ProductTabsWrapper from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper';
import { emptyAccountsListResponse } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import { acceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import { fetchReferralsHandler } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';
import { PRODUCT_ROUTE_PREFIX, PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

import { staticUserExtra, staticLocation } from './mocks/fixtures';
const productType = PRODUCT_TYPE.X;
const productPrefix = PRODUCT_ROUTE_PREFIX[productType];
const defaultLocation = {
  ...staticLocation,
  pathname: `/partners/submerchants${productPrefix}`,
};

// To Render RazorpayX Clients via Route
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
  render(<ProductTabsWrapper {...props} />, {
    showModal: true,
    initialEntries,
    path: '',
    initialState: {
      session,
    },
  });
};

describe('RazorpayX Invites List Conditions', () => {
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

  test(`should render empty table for RazorpayX when filters are used and accepted invites is empty`, async () => {
    mockLocation = {
      ...defaultLocation,
      search: '?name=ABC123&count=28',
    };
    server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
    renderApp({ initialEntries }, { productType }, { isPartnershipsInviteFlowEnabled: true });
    // Welcome check spinner
    await waitForLoadingToFinishByLabel();

    // Loads the table with empty screen
    await waitFor(() => {
      expect(screen.getByText('Added On')).toBeInTheDocument();
    });
    expect(screen.queryByText('Welcome to Partner Dashboard')).not.toBeInTheDocument();

    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });

  test(`should render welcome screen for payments when accepted invites is empty`, async () => {
    server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
    renderApp({ initialEntries }, { productType }, { isPartnershipsInviteFlowEnabled: true });
    // Welcome check spinner
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
      expect(screen.getByText('Add New Merchants - RazorpayX')).toBeInTheDocument();
    });
  });
});
