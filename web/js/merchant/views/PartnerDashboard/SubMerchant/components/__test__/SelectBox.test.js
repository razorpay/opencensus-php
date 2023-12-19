import React from 'react';
import { render, screen } from 'test-utils';
import SelectBox from 'merchant/views/PartnerDashboard/SubMerchant/components/SelectBox';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  label: `Razorpay Payments`,
  description: `Invite affiliates to use Razorpay Payment products to collect payments`,
  onClick: jest.fn(),
  checked: true,
  disabled: false,
  isMaintenance: false,
  orgName: 'Razorpay',
};
describe('SelectBox', () => {
  test('should render in default setting', () => {
    render(<SelectBox {...defaultProps} />, {});
    expect(screen.queryByText(/Note: New Business onboarding is temporarily paused!.*/)).toBeNull();
  });
});
