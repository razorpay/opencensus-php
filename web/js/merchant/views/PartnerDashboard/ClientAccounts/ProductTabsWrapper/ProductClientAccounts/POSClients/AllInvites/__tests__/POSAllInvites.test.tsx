import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as analytics from 'common/utils/analytics';
import POSAllInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinishByLabel,
} from 'test-utils';

import { allInvitesDataPOS, allInvitesDataEmptyPOS } from './mocks/fixtures';
import {
  allInvitesListSuccessPOS,
  fetchPartnerAgentUsersHandler,
  resendInviteHandlerPOS,
} from './mocks/once-handlers';

const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');
const showNotificationsSpy = jest.spyOn(NotificationsActions, 'showNotification');

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
  test(`should render the list once the data is fetched and is not empty for pos`, async () => {
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

  test(`should render the empty screen once the data is fetched and is empty for pos`, async () => {
    server.use(allInvitesListSuccessPOS(allInvitesDataEmptyPOS));
    renderApp();
    // Wait for agents mapping
    await waitForLoadingToFinishByLabel();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });
  test(`should fire resend api when Resend Invite clicked for pos`, async () => {
    server.use(allInvitesListSuccessPOS());
    server.use(resendInviteHandlerPOS());

    renderApp();
    // Wait for agents mapping
    await waitForLoadingToFinishByLabel();

    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();

    await waitFor(() => {
      // table column should render
      expect(screen.queryByText('Last Invited On')).toBeInTheDocument();
    });

    const firstResendButton = screen.queryAllByRole('button', { name: 'Resend Invite' })[0];
    await userEvent.click(firstResendButton);

    // test tracking event
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Dashboard Account Level All Invites Tab Action Cta',
        properties: expect.objectContaining({ productType }),
      }),
    );

    // test api response
    await waitFor(() => {
      expect(showNotificationsSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'success',
          message: 'Invite is resent successfully',
        }),
      );
    });
    expect(screen.getByText('Invite is resent successfully')).toBeInTheDocument();
  });

  test('should render filtered data after clicking search', async () => {
    // Load complete data before filter
    server.use(allInvitesListSuccessPOS());
    renderApp();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    await waitFor(() => {
      expect(screen.getByText('Last Invited On')).toBeInTheDocument();
    });
    expect(screen.getByText(allInvitesDataPOS.data.items[0].name)).toBeInTheDocument();

    const emailInput = screen.getByLabelText('Email ID');
    expect(emailInput).toBeInTheDocument();
    await userEvent.type(emailInput, 'email1@gmail.com');

    const searchButton = screen.getByRole('button', { name: 'Search' });
    expect(searchButton).toBeInTheDocument();

    // Render empty data after filter
    server.use(allInvitesListSuccessPOS(allInvitesDataEmptyPOS));

    await userEvent.click(searchButton);

    await waitFor(() => {
      expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
    });

    // test tracking event
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Dashboard Affiliates List Filter Section Cta',
        properties: expect.objectContaining({ productType, action: 'Search' }),
      }),
    );
    const clearButton = screen.getByRole('button', { name: 'Clear' });
    expect(clearButton).toBeInTheDocument();

    // Load complete data again
    server.use(allInvitesListSuccessPOS());
    await userEvent.click(clearButton);

    await waitFor(() => {
      expect(screen.queryByText(allInvitesDataPOS.data.items[0].name)).toBeInTheDocument();
    });

    // test tracking event
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Dashboard Affiliates List Filter Section Cta',
        properties: expect.objectContaining({ productType, action: 'Clear' }),
      }),
    );
  });
});
