import React from 'react';
import { render, screen } from 'test-utils';
import DetailsAction from 'merchant/views/PartnerDashboard/SubMerchant/components/DetailsAction';
import { submerchantWithKYCAccess as submerchant } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import moment from 'moment';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  activation_status: submerchant.details.activation_status,
  kyc_access: submerchant.kyc_access,
  submerchant,
  getPannelData: jest.fn(),
  trackUserEvent: jest.fn(),
  isSubMerchantKYCAccess: false,
};

describe('DetailsAction', () => {
  test('should render for approved request', () => {
    let notExpiredTime = new Date().getTime() + 100000;
    notExpiredTime = moment(notExpiredTime).unix();
    render(
      <DetailsAction
        {...defaultProps}
        kyc_access={{ state: 'approved', rejection_count: 1, expiry: notExpiredTime }}
      />,
      {},
    );
    expect(screen.getByText('Merchant has approved your KYC access request')).toBeVisible();
  });
});
