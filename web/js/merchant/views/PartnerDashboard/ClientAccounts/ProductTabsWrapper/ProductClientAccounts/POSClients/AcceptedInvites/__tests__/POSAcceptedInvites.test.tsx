import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as downloadSubmerchantsActions from 'merchant/reducers/submerchant';
import POSAcceptedInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AcceptedInvites';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

import { emptyAccountsListResponsePOS, accountsListResponsePOS } from './mocks/fixtures';
import { acceptedInvitesListHandlerPOS } from './mocks/once-handlers';

const productType = PRODUCT_TYPE.POS;

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

  return render(<POSAcceptedInvites />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

const downloadSubMerchant = jest.spyOn(downloadSubmerchantsActions, 'downloadSubmerchants');
describe('POSAcceptedInvites', () => {
  beforeEach(() => {
    // POS specific mocked data
    server.use(acceptedInvitesListHandlerPOS());
  });
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
    expect(screen.getAllByText('KYC Last Submitted by')).toHaveLength(1);
    expect(screen.getAllByText('Name')).toHaveLength(2);
    expect(screen.getAllByText('Contact')).toHaveLength(2);

    const { items } = accountsListResponsePOS;
    expect(screen.getByText(items[0].id)).toBeInTheDocument();
    expect(screen.getByText(items[0].name)).toBeInTheDocument();
    expect(screen.getByText(items[0].email)).toBeInTheDocument();
    expect(screen.getByText(items[0].pos.last_kyc_performed_by.name)).toBeInTheDocument();

    expect(screen.getByText(items[1].id)).toBeInTheDocument();
    expect(screen.getByText(items[1].name)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();
    expect(screen.getByText(items[1].pos.last_kyc_performed_by.name)).toBeInTheDocument();

    expect(screen.getByText('Actions')).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Request for KYC' })).toHaveLength(3);

    await userEvent.click(screen.getByText('Export All (CSV)'));
    await userEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
    await waitFor(() => {
      expect(downloadSubMerchant).toBeCalled();
    });
  });

  test(`should render the empty screen once the data is fetched and is empty for ${productType}`, async () => {
    server.use(acceptedInvitesListHandlerPOS(emptyAccountsListResponsePOS));
    renderApp();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(true);
    expect(
      screen.getByText(
        'You can now opt in to perform KYC for the client when you invite them onto Razorpay',
      ),
    ).toBeInTheDocument();
  });
});
