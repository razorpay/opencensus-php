import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import PageHeading from 'merchant/views/PartnerDashboard/Home/Components/PageHeading';

const defaultProps = {
  partnerName: 'partnerName',
  user: { partner_type: 'reseller' },
  org: { custom_code: 'rzp' },
};
describe('test suite for Partner Home Page Heading', () => {
  test('should show correct welcome text for razorpay reseller', () => {
    render(<PageHeading {...defaultProps} />);
    expect(screen.getByText('Welcome to Reseller Partner dashboard, partnerName!')).toBeVisible();
  });

  test('should show correct welcome text for razorpay aggregator', () => {
    const customUser = {
      ...defaultProps.user,
      partner_type: 'aggregator',
    };
    render(<PageHeading {...defaultProps} user={customUser} />);
    expect(screen.getByText('Welcome to Aggregator Partner dashboard, partnerName!')).toBeVisible();
  });

  test('should show correct welcome text for a curlec partner', () => {
    const customOrg = {
      ...defaultProps.org,
      custom_code: 'curlec',
    };
    render(<PageHeading {...defaultProps} org={customOrg} />);
    expect(screen.getByText('Welcome to Partner dashboard, partnerName!')).toBeVisible();
  });
});
