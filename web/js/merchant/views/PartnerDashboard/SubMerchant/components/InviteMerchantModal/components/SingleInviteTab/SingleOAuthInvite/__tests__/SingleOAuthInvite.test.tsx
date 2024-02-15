import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import {
  createSubmerchantInviteErrorHandler,
  createSubmerchantInviteSuccessHandler,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import * as api from 'merchant/views/PartnerDashboard/SubMerchant/api';
import SingleOAuthInvite from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab/SingleOAuthInvite';
import * as analytics from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, screen, server, userEvent, waitFor } from 'test-utils';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const createSubmerchantInviteSpy = jest.spyOn(api, 'createSubmerchantInvite');
const trackEmailFlowCTAClickedSpy = jest.spyOn(analytics, 'trackEmailFlowCTAClicked');
const trackInviteFlowGenericErrorSpy = jest.spyOn(analytics, 'trackInviteFlowGenericError');
const trackInviteFlowSuccessfulInviteSpy = jest.spyOn(analytics, 'trackInviteFlowSuccessfulInvite');
const trackInviteFlowValidationErrorSpy = jest.spyOn(analytics, 'trackInviteFlowValidationError');
const trackSubmerchantReferViaEmailSpy = jest.spyOn(analytics, 'trackSubmerchantReferViaEmail');

const defaultPartnerDashboardExperiments = {
  isPartnershipsInviteFlowEnabled: false,
  isPlatformPartnerInviteFlowEnabled: false,
};
let mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
jest.mock('merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments', () => ({
  __esModule: true,
  default: () => mockPartnerDashboardExperiments,
}));

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  onAddSuccess: jest.fn(),
  setShowHeaderAndTabs: jest.fn(),
  goToAppSelectionStep: jest.fn(),
  onDismiss: jest.fn(),
  selectedApp: {
    application_id: 'string_application_id',
    client_id: 'string_client_id',
    redirect_uri: 'string_redirect_uri',
    name: 'string_name',
  },
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('SingleOAuthInvite', () => {
  afterEach(() => {
    jest.clearAllMocks();
    mockPartnerDashboardExperiments = defaultPartnerDashboardExperiments;
  });
  const renderApp = (
    props = {},
    { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {},
    experiments = {},
  ) => {
    mockPartnerDashboardExperiments = { ...defaultPartnerDashboardExperiments, ...experiments };
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<SingleOAuthInvite {...defaultProps} {...props} />, { initialState: { session } });
  };
  beforeEach(() => {
    server.use(createSubmerchantInviteSuccessHandler());
  });

  const fillFormEssentials = async () => {
    const name = 'Test Name';
    const email = 'test@email.com';
    await userEvent.type(screen.getByLabelText('Client Name'), name);
    await userEvent.type(screen.getByLabelText('Email ID'), email);
    return { name, email };
  };

  test('should correctly render typical partnerships oauth invite flow', async () => {
    renderApp({}, {}, { isPartnershipsInviteFlowEnabled: true });

    const { name, email } = await fillFormEssentials();
    const contact_no = '9123123123';
    await userEvent.type(screen.getByLabelText('Contact number (optional)'), contact_no);
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    expect(createSubmerchantInviteSpy).toHaveBeenCalledWith({
      name,
      email,
      contact_no,
      product: 'primary',
      partner_id: 'K0KQSNE7BypZ5VE',
      metadata: {
        application_id: 'string_application_id',
        client_id: 'string_client_id',
        oauth_referral: true,
        redirect_uri: 'string_redirect_uri',
        scope: 'read_write',
      },
    });
    // Check that we moved to next step
    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(defaultProps.onAddSuccess).toHaveBeenCalled();
    expect(trackInviteFlowSuccessfulInviteSpy).toHaveBeenCalled();
    expect(screen.getByText('Invite successfully sent')).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Got it' }));
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test('should show notification on api error in sending an invite', async () => {
    const message = 'Something went wrong';
    server.use(createSubmerchantInviteErrorHandler(message));

    renderApp();
    await fillFormEssentials();
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));
    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(showNotificationSpy).toHaveBeenCalledWith({ message, type: 'error' });
  });

  test('should fire correct ARD events', async () => {
    const productType = PRODUCT_TYPE.PG;
    const message = 'Something went wrong';
    server.use(createSubmerchantInviteErrorHandler(message));
    renderApp({ productType });

    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));
    expect(trackInviteFlowValidationErrorSpy).toHaveBeenCalled();

    // Check change button
    await userEvent.click(screen.getByText('Change'));
    expect(defaultProps.goToAppSelectionStep).toHaveBeenCalled();

    await fillFormEssentials();

    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));
    expect(trackEmailFlowCTAClickedSpy).toHaveBeenCalledWith({
      ctaClicked: 'Send Invite',
      productType,
    });
    expect(trackSubmerchantReferViaEmailSpy).toHaveBeenCalled();

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(showNotificationSpy).toHaveBeenCalledWith({ message, type: 'error' });
    expect(trackInviteFlowGenericErrorSpy).toHaveBeenCalled();
  });
});
