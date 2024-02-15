import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import POSAllInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, server, waitFor, waitForLoadingToFinishByLabel } from 'test-utils';

import { allInvitesDataPOS, allInvitesDataEmptyPOS } from './mocks/fixtures';
import { allInvitesListSuccessPOS, fetchPartnerAgentUsersHandler } from './mocks/once-handlers';

const productType = PRODUCT_TYPE.POS;

const defaultPartnerDashboardExperiments = {};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const isPartner = jest.fn();
const isFeatureEnabled = jest.fn();

const renderApp = ({ userExtra = {}, orgExtra = {} } = {}, experiments = {}) => {
  mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments, ...experiments };
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      id: 'testUserId',
      user: {
        id: 'testUserId',
      },
      isPartner,
      isFeatureEnabled,
      ...userExtra,
    },
    orgExtra,
  });

  return render(<POSAllInvites />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

describe('POSAllInvites', () => {
  beforeEach(() => {
    server.use(fetchPartnerAgentUsersHandler());
  });
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  test(`should render the list once the data is fetched and is not empty for ${productType}`, async () => {
    server.use(allInvitesListSuccessPOS());
    renderApp();
    // Wait for agents mapping
    await waitForLoadingToFinishByLabel();

    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();

    await waitFor(() => {
      // table column should render
      expect(screen.queryByText('Last Invited On')).toBeInTheDocument();
    });

    // Filters and columns
    expect(screen.getAllByText('Name')).toHaveLength(2);
    expect(screen.getAllByText('Email ID')).toHaveLength(2);
    expect(screen.getAllByText('Invited By')).toHaveLength(2);
    expect(screen.getAllByText('Contact')).toHaveLength(2);

    const {
      data: { items },
    } = allInvitesDataPOS;
    expect(screen.getByText(items[0].name)).toBeInTheDocument();
    expect(screen.getByText(items[0].email)).toBeInTheDocument();

    expect(screen.getByText(items[1].name)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();

    // Filter + table value
    expect(screen.getAllByText('Self')).toHaveLength(2);
    expect(screen.getAllByText('Test Inviter 1')).toHaveLength(2);
    expect(screen.getAllByText('Test Inviter 2')).toHaveLength(1);

    expect(screen.getByText('Actions')).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Resend Invite' })).toHaveLength(2);
  });

  test(`should render the empty screen once the data is fetched and is empty for ${productType}`, async () => {
    server.use(allInvitesListSuccessPOS(allInvitesDataEmptyPOS));
    renderApp();
    // Wait for agents mapping
    await waitForLoadingToFinishByLabel();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });
});
