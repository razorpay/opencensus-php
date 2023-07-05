import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import DetailsAction from 'merchant/views/PartnerDashboard/SubMerchant/components/DetailsAction';
import { submerchantWithKYCAccess as submerchant } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { getInitialUserOrgState } from 'common/tests/utils';
import moment from 'moment';
import * as analyticsUtil from 'merchant/views/PartnerDashboard/SubMerchant/components/utils/analytics';
import * as navigationUtil from 'merchant/views/PartnerDashboard/SubMerchant/utils/navigation';

// TODO: only basic render test added, other tests can be added later.
const trackAcceptedInvitesCtaSpy = jest.spyOn(analyticsUtil, 'trackAcceptedInvitesCta');
const openKYCFormUtilSpy = jest.spyOn(navigationUtil, 'openKYCFormUtil');

const defaultProps = {
  activation_status: submerchant.details.activation_status,
  kyc_access: submerchant.kyc_access,
  submerchant,
  getPannelData: jest.fn(),
  trackUserEvent: jest.fn(),
  isSubMerchantKYCAccess: false,
};

const renderApp = (props, { isRzpOrg = true, ...extra } = {}) => {
  const state = getInitialUserOrgState({
    isRzpOrg,
    ...extra,
  });

  return render(<DetailsAction {...defaultProps} {...props} />, {
    initialState: { session: state },
  });
};

describe('DetailsAction', () => {
  test('should render for approved request', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    renderApp({ kyc_access: { state: 'approved', rejection_count: 1, expiry: notExpiredTime } });
    expect(screen.getByText('Merchant has approved your KYC access request')).toBeVisible();
  });

  test('should trigger Perform KYC successfully', async () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    renderApp(
      {
        isSubMerchantKYCAccess: true,
        kyc_access: { state: 'approved', rejection_count: 1, expiry: notExpiredTime },
      },
      { isRzpOrg: true, userExtra: { isPartnershipsInviteFlowEnabled: true } },
    );

    const performKycButton = screen.getByRole('button', { name: 'Perform KYC' });
    await userEvent.click(performKycButton);

    // test tracking
    expect(trackAcceptedInvitesCtaSpy).toHaveBeenCalledWith(submerchant, {
      properties: { action: 'Perform KYC' },
    });
    // test redirection
    expect(openKYCFormUtilSpy).toHaveBeenCalled();
  });

  test('should trigger Resend KYC request successfully', async () => {
    renderApp(
      {
        isSubMerchantKYCAccess: false,
        kyc_access: { state: 'rejected', rejection_count: 1 },
      },
      { isRzpOrg: true, userExtra: { isPartnershipsInviteFlowEnabled: true } },
    );

    const resendKycButton = screen.getByRole('button', { name: 'Resend KYC request' });
    await userEvent.click(resendKycButton);

    expect(trackAcceptedInvitesCtaSpy).toHaveBeenCalledWith(submerchant, {
      properties: { action: 'Resend KYC request' },
    });
    // TODO: mock api calls to partner/kyc_access_request and test response handling
  });

  test('should trigger Request for KYC request successfully', async () => {
    renderApp(
      {
        isSubMerchantKYCAccess: false,
        kyc_access: { state: 'expired', rejection_count: 1 },
      },
      { isRzpOrg: true, userExtra: { isPartnershipsInviteFlowEnabled: true } },
    );

    const resendKycButton = screen.getByRole('button', { name: 'Request for KYC access' });
    await userEvent.click(resendKycButton);

    expect(trackAcceptedInvitesCtaSpy).toHaveBeenCalledWith(submerchant, {
      properties: { action: 'Request for KYC access' },
    });
  });
});
