import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import RazorpayXClients from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/RazorpayXClients';
import {
  accountsListResponse,
  emptyAccountsListResponse,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import { acceptedInvitesListHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, server, waitFor, waitForLoadingToFinishByLabel } from 'test-utils';

const productType = PRODUCT_TYPE.X;

const defaultPartnerDashboardExperiments = {};
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

  return render(<RazorpayXClients />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

describe('RazorpayXClients', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  test(`should render the list once the data is fetched and is not empty for ${productType}`, async () => {
    renderApp();
    await waitForLoadingToFinishByLabel();
    await waitFor(() => {
      expect(screen.getAllByText('Account Name')).toHaveLength(1);
      expect(screen.getAllByText('Name')).toHaveLength(1);
    });
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(false);

    // Filters and columns
    expect(screen.getAllByText('Account ID')).toHaveLength(2);
    expect(screen.getByText('Email ID')).toBeInTheDocument();
    expect(screen.getByText('Registered Email')).toBeInTheDocument();

    const { items } = accountsListResponse;
    expect(screen.getByText(items[0].id)).toBeInTheDocument();
    expect(screen.getByText(items[0].name)).toBeInTheDocument();
    expect(screen.getByText(items[0].email)).toBeInTheDocument();
    expect(screen.getByText(items[1].id)).toBeInTheDocument();
    expect(screen.getByText(items[1].name)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();
  });

  test(`should render the empty screen once the data is fetched and is empty for ${productType}`, async () => {
    server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
    renderApp();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(true);
    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });
});
