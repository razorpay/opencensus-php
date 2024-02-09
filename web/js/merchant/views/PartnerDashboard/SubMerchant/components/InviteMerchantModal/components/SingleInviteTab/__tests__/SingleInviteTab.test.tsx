import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import * as api from 'merchant/views/PartnerDashboard/SubMerchant/api';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import SingleInviteTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/SingleInviteTab';
import * as analytics from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/analytics';
import * as kycAccessFtux from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import {
  createSubmerchantInviteErrorHandler,
  createSubmerchantInviteSuccessHandler,
} from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';

const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const getHasSelectedKycAccessSpy = jest.spyOn(kycAccessFtux, 'getHasSelectedKycAccess');
const setHasSelectedKycAccessSpy = jest.spyOn(kycAccessFtux, 'setHasSelectedKycAccess');
const createSubmerchantInviteSpy = jest.spyOn(api, 'createSubmerchantInvite');
const trackEmailFlowCTAClickedSpy = jest.spyOn(analytics, 'trackEmailFlowCTAClicked');
const trackInviteFlowFieldEditStartedSpy = jest.spyOn(analytics, 'trackInviteFlowFieldEditStarted');
const trackInviteFlowGenericErrorSpy = jest.spyOn(analytics, 'trackInviteFlowGenericError');
const trackInviteFlowSuccessfulInviteSpy = jest.spyOn(analytics, 'trackInviteFlowSuccessfulInvite');
const trackInviteFlowValidationErrorSpy = jest.spyOn(analytics, 'trackInviteFlowValidationError');
const trackSubmerchantReferViaEmailSpy = jest.spyOn(analytics, 'trackSubmerchantReferViaEmail');
const trackInviteFlowOptOutFormSpy = jest.spyOn(analytics, 'trackInviteFlowOptOutForm');

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  onAddSuccess: jest.fn(),
  setShowHeaderAndTabs: jest.fn(),
  onInviteTabsBackClick: jest.fn(),
  onDismiss: jest.fn(),
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('SingleInviteTab', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<SingleInviteTab {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });
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

  test('should correctly render typical partnerships invite flow', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => true);
    renderApp({}, { userExtra: { isPartnershipsInviteFlowEnabled: true } });

    const { name, email } = await fillFormEssentials();
    const contact_no = '9123123123';
    await userEvent.type(screen.getByLabelText('Contact number (optional)'), contact_no);
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    expect(createSubmerchantInviteSpy).toHaveBeenCalledWith({
      name,
      email,
      contact_no,
      request_kyc_access: true,
      product: 'primary',
      partner_id: 'K0KQSNE7BypZ5VE',
    });
    // Check that we moved to next step
    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    expect(defaultProps.onAddSuccess).toHaveBeenCalled();
    expect(trackInviteFlowSuccessfulInviteSpy).toHaveBeenCalled();
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
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    const productType = PRODUCT_TYPE.CAPITAL;
    const message = 'Something went wrong';
    server.use(createSubmerchantInviteErrorHandler(message));
    renderApp({ productType });

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    expect(trackInviteFlowValidationErrorSpy).toHaveBeenCalled();

    // Check back button
    await userEvent.click(screen.getByRole('button', { name: 'Back' }));
    expect(defaultProps.onInviteTabsBackClick).toHaveBeenCalled();

    await fillFormEssentials();
    expect(trackInviteFlowFieldEditStartedSpy).toHaveBeenCalled();
    await userEvent.click(screen.getByRole('button', { name: 'Next' }));

    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));
    expect(trackInviteFlowValidationErrorSpy).toHaveBeenCalled();
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));

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

  test('should show ftux opt-in success screen on selecting yes in kyc access', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    renderApp();
    expect(screen.queryByText('I want to assist my client with their KYC')).not.toBeInTheDocument();
    await fillFormEssentials();

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
    expect(setHasSelectedKycAccessSpy).toHaveBeenCalledWith(true, PRODUCT_TYPE.PG);
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);

    expect(screen.getByText('Invite successfully sent')).toBeInTheDocument();
    expect(screen.getByText('What next?')).toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'Got it' }));
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test('should show ftux opt-out form on success screen on selecting no in kyc access', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    const productType = PRODUCT_TYPE.PG;
    const inviteFlow = INVITE_TAB_TYPES.SINGLE_INVITE;
    renderApp({ productType });
    await fillFormEssentials();

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
    expect(setHasSelectedKycAccessSpy).toHaveBeenCalledWith(false, PRODUCT_TYPE.PG);
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);

    // expect(screen.getByText('Before you finish, we have something to ask')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Something else'));
    const customReason = 'Test';
    await userEvent.type(screen.getByPlaceholderText('Elaborate on your reasons'), customReason);

    await userEvent.click(screen.getByRole('button', { name: 'Submit & Close' }));
    expect(trackInviteFlowOptOutFormSpy).toHaveBeenCalledWith({
      productType,
      inviteFlow,
      radioValue: 'something_else',
      customReason,
    });
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test('should show ftux opt-out form on success screen on selecting no in kyc access if productType is POS', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    const productType = PRODUCT_TYPE.POS;
    const inviteFlow = INVITE_TAB_TYPES.SINGLE_INVITE;
    renderApp({ productType });
    await fillFormEssentials();

    await userEvent.click(screen.getByRole('button', { name: 'Next' }));
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
    expect(setHasSelectedKycAccessSpy).toHaveBeenCalledWith(false, PRODUCT_TYPE.POS);
    expect(defaultProps.setShowHeaderAndTabs).toHaveBeenCalledWith(false);

    await userEvent.click(screen.getByText('Something else'));
    const customReason = 'Test';
    await userEvent.type(screen.getByPlaceholderText('Elaborate on your reasons'), customReason);

    await userEvent.click(screen.getByRole('button', { name: 'Submit & Close' }));
    expect(trackInviteFlowOptOutFormSpy).toHaveBeenCalledWith({
      productType,
      inviteFlow,
      radioValue: 'something_else',
      customReason,
    });
    expect(defaultProps.onDismiss).toHaveBeenCalled();
  });

  test('should show non-ftux notification on success if kycAccessValue was true', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => true);
    renderApp();
    await fillFormEssentials();
    // uncheck the box
    await userEvent.click(screen.getByText('I want to assist my client with their KYC'));
    await userEvent.click(screen.getByRole('button', { name: 'Send Invite' }));

    await waitFor(() => {
      expect(screen.queryByLabelText('Loading')).not.toBeInTheDocument();
    });
    // check notification is shown
    expect(showNotificationSpy).toHaveBeenCalled();
    expect(defaultProps.onDismiss).toHaveBeenCalled();

    // check that unchecked value is reflected
    expect(setHasSelectedKycAccessSpy).toHaveBeenCalledWith(false, PRODUCT_TYPE.PG);
  });

  test('should show FAQ modal on clicking Know more', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => true);
    renderApp();
    await userEvent.click(screen.getByText('Know more about the feature'));
    expect(screen.getByText('Perform KYC on behalf of your client')).toBeInTheDocument();
    expect(screen.getByText('Why should I assist my client with their KYC?')).toBeInTheDocument();
  });
});
