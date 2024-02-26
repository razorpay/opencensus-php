import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as analytics from 'common/utils/analytics';
import PaymentsAllInvites from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/PaymentsClients/AllInvites';
import {
  allInvitesData,
  allInvitesDataEmpty,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/fixtures';
import {
  allInvitesListSuccess,
  resendInviteHandler,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';
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

const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');
const showNotificationsSpy = jest.spyOn(NotificationsActions, 'showNotification');

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
  test(`should render the list once the data is fetched and is not empty for payments`, async () => {
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
  });

  test(`should render the empty screen once the data is fetched and is empty for payments`, async () => {
    server.use(allInvitesListSuccess(allInvitesDataEmpty));
    renderApp();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    expect(screen.getByText('No Invites Found!')).toBeInTheDocument();
  });

  test(`should fire resend api when Resend Invite clicked for payments`, async () => {
    server.use(allInvitesListSuccess());
    server.use(resendInviteHandler());

    renderApp();

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
    server.use(allInvitesListSuccess());
    renderApp();
    // Wait for data table spinner
    await waitForLoadingToFinishByLabel();
    await waitFor(() => {
      expect(screen.getByText('Last Invited On')).toBeInTheDocument();
    });
    expect(screen.getByText(allInvitesData.data.items[0].name)).toBeInTheDocument();

    const countInput = screen.getByLabelText('Count');
    expect(countInput).toBeInTheDocument();
    await userEvent.type(countInput, '1');

    const searchButton = screen.getByRole('button', { name: 'Search' });
    expect(searchButton).toBeInTheDocument();
    await userEvent.click(searchButton);

    await waitFor(() => {
      expect(screen.queryByText(allInvitesData.data.items[0].name)).toBeInTheDocument();
    });
    // test tracking event
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Dashboard Affiliates List Filter Section Cta',
        properties: expect.objectContaining({ productType, action: 'Search' }),
      }),
    );
  });
});
