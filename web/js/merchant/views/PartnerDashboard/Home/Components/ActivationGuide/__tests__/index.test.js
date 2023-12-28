import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import ActivationGuide from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide';
import { createMemoryHistory } from 'history';

// TODO: only basic render test added, other tests can be added later.

let history;
const defaultProps = {
  history,
  handleReferClient: jest.fn(),

  fuxStatus: {
    isFetching: false,
    value: {
      first_submerchant_added: true,
      first_earning_generated: false,
      first_commission_payout: false,
      api_integration: false,
      first_submerchant_accept_payments: true,
    },
  },
  user: { activation_status: 'activated', partner_type: 'reseller', isPartner: jest.fn() },
  partnerName: 'partner name',
  tracking: {
    trackEvent: jest.fn(),
  },
  org: {
    business_name: 'Razorpay',
  },
};

describe('ActivationGuide', () => {
  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
    window.cdnBaseUrl = 'https://razorpay-cdn.com';
  });
  test('should render with default props', () => {
    render(<ActivationGuide {...defaultProps} />);
    expect(screen.getByText('Start your journey as Razorpay Partner')).toBeVisible();
  });
});
