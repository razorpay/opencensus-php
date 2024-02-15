import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as downloadSubmerchantsActions from 'merchant/reducers/submerchant';
import CapitalClients from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/CapitalClients';
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

import {
  createBureauLinkSuccess,
  createBureauLinkError,
  capitalApplicationsErrorHandler,
} from './mocks/once-handlers';

const productType = PRODUCT_TYPE.CAPITAL;

const defaultPartnerDashboardExperiments = {
  isPartnershipCapitalBureauLinkEnabled: true,
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
      isPartnershipForCapitalEnabled: true,
      ...userExtra,
    },
    orgExtra,
  });

  return render(<CapitalClients />, {
    showModal: true,
    initialState: { session },
    renderViaRouteGuard: false,
  });
};

const downloadSubMerchant = jest.spyOn(downloadSubmerchantsActions, 'downloadSubmerchants');
describe('CapitalClients', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  test(`should render the list once the data is fetched and is not empty for ${productType}`, async () => {
    renderApp();
    await waitForLoadingToFinishByLabel();
    await waitFor(() => {
      expect(screen.getAllByText('Account Name')).toHaveLength(2);
    });
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(false);
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

    expect(screen.getByText('Bureau Submission')).toBeInTheDocument();
    expect(screen.getByText('Income Proof Submission')).toBeInTheDocument();
    expect(screen.getByText('Not Available')).toBeInTheDocument();
    expect(screen.getByText('Actions')).toBeInTheDocument();
    expect(screen.getAllByRole('button', { name: 'Create Bureau Link' })).toHaveLength(3);

    await userEvent.click(screen.getByText('Export All (CSV)'));
    await userEvent.click(screen.getByRole('button', { name: 'Generate Report' }));
    await waitFor(() => {
      expect(downloadSubMerchant).toBeCalled();
    });
  });

  test(`should render the empty screen once the data is fetched and is empty for ${productType}`, async () => {
    server.use(acceptedInvitesListHandler(emptyAccountsListResponse));
    renderApp();
    // Wait for products spinner
    await waitForLoadingToFinishByLabel();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(mockSetIsAcceptedInvitesEmpty).toHaveBeenCalledWith(true);
    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });

  test('should show error notification and status as Not Available if bulk API return error', async () => {
    server.use(capitalApplicationsErrorHandler());
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('There was an error while fetching Status')).toBeInTheDocument();
    });
    await waitForLoadingToFinishByLabel();
    expect(screen.getAllByText('Not Available')).toHaveLength(3);
  });

  test('should open modal if Create Bureau button is clicked and API call is success', async () => {
    server.use(createBureauLinkSuccess());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Actions')).toBeInTheDocument();
    });
    const BureauButtons = screen.getAllByRole('button', { name: 'Create Bureau Link' });
    expect(BureauButtons).toHaveLength(3);
    const enabledButton = BureauButtons[1]; // Button will be enabled only if the activation status is bureau submission
    const disabledButton = BureauButtons[0]; // activation status is not available
    expect(enabledButton).not.toHaveAttribute('disabled');
    expect(disabledButton).toHaveAttribute('disabled');
    await userEvent.click(enabledButton);
    await waitFor(() => {
      expect(screen.getByText('Line Of Credit Bureau'));
    });
    expect(enabledButton).toHaveAttribute('disabled');
  });

  test('should show error if create bureau button is clicked and API throws error', async () => {
    server.use(createBureauLinkError());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Actions')).toBeInTheDocument();
    });
    const BureauButtons = screen.getAllByRole('button', { name: 'Create Bureau Link' });
    expect(BureauButtons).toHaveLength(3);
    const enabledButton = BureauButtons[1];
    expect(enabledButton).not.toHaveAttribute('disabled');
    await userEvent.click(enabledButton);
    await waitFor(() => {
      expect(screen.getByText('There was an error')).toBeInTheDocument();
    });
  });
});
