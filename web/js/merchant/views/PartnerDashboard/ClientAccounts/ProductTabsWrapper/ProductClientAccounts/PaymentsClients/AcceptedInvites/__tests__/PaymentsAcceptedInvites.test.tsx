import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as downloadSubmerchantsActions from 'merchant/reducers/submerchant';
import PaymentsAcceptedInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AcceptedInvites';
import {
  accountsListResponse,
  emptyAccountsListResponse,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import { acceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

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

  return render(<PaymentsAcceptedInvites />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

const downloadSubMerchant = jest.spyOn(downloadSubmerchantsActions, 'downloadSubmerchants');
describe('PaymentsAcceptedInvites', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  test(`should render the list once the data is fetched and is not empty for ${productType}`, async () => {
    renderApp();
    await waitForLoadingToFinishByLabel();
    await waitFor(() => {
      expect(screen.queryByText('Invite Accepted On')).toBeInTheDocument();
    });
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(false);

    // Filters and columns
    expect(screen.getAllByText('Account ID')).toHaveLength(2);
    expect(screen.getAllByText('Name')).toHaveLength(2);
    expect(screen.getAllByText('Contact')).toHaveLength(2);

    const { items } = accountsListResponse;
    expect(screen.getByText(items[0].id)).toBeInTheDocument();
    expect(screen.getByText(items[0].name)).toBeInTheDocument();
    expect(screen.getByText(items[0].email)).toBeInTheDocument();
    expect(screen.getByText(items[1].id)).toBeInTheDocument();
    expect(screen.getByText(items[1].name)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();

    expect(screen.getByText('Actions')).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Request for KYC' })).toHaveLength(3);

    await userEvent.click(screen.getByText('Export All (CSV)'));
    await userEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
    await waitFor(() => {
      expect(downloadSubMerchant).toBeCalled();
    });
  });

  test(`should render the empty screen once the data is fetched and is empty for ${productType}`, async () => {
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
});
