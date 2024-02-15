import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import PaymentsAllInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AllInvites';
import {
  allInvitesData,
  allInvitesDataEmpty,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/fixtures';
import { allInvitesListSuccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, server, waitFor, waitForLoadingToFinishByLabel } from 'test-utils';

const productType = PRODUCT_TYPE.PG;

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: true,
  isPlatformPartnerInviteFlowEnabled: false,
};
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
      isPartner,
      isFeatureEnabled,
      ...userExtra,
    },
    orgExtra,
  });

  return render(<PaymentsAllInvites />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

describe('PaymentsAllInvites', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  test(`should render the list once the data is fetched and is not empty for ${productType}`, async () => {
    server.use(allInvitesListSuccess());
    renderApp();
    await waitForLoadingToFinishByLabel();

    await waitFor(() => {
      // table column should render
      expect(screen.queryByText('Last Invited On')).toBeInTheDocument();
    });

    // Filters and columns
    expect(screen.getAllByText('Name')).toHaveLength(2);
    expect(screen.getAllByText('Phone Number')).toHaveLength(1);
    expect(screen.getAllByText('Email ID')).toHaveLength(2);
    expect(screen.getAllByText('Contact')).toHaveLength(1);

    const {
      data: { items },
    } = allInvitesData;
    expect(screen.getByText(items[0].name)).toBeInTheDocument();
    expect(screen.getByText(items[0].email)).toBeInTheDocument();
    expect(screen.getByText(items[1].name)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();

    expect(screen.getByText('Actions')).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Resend Invite' })).toHaveLength(2);
  });

  test(`should render the empty screen once the data is fetched and is empty for ${productType}`, async () => {
    server.use(allInvitesListSuccess(allInvitesDataEmpty));
    renderApp();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });
});
