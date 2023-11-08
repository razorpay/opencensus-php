import React from 'react';

import { render, screen } from 'common/services/test/test-utils';
import { CommissionEarningBreakUp } from 'merchant/views/PartnerDashboard/Commissions/Transactional/Details';

function getInitialProps(isRzpOrg, isSourceTypeRefund) {
  return {
    user: {
      isOrgRZP: isRzpOrg,
    },
    org: {
      custom_code: isRzpOrg ? 'rzp' : 'curlec',
      business_name: isRzpOrg ? 'Razorpay' : 'Curlec',
    },
    total: 100,
    base: 50,
    gst: 50,
    sourceType: isSourceTypeRefund ? 'refund' : 'payment',
  };
}

describe('test suite for transactional details', () => {
  const App = ({ ...props }) => {
    return <CommissionEarningBreakUp {...props} />;
  };

  test('should render component with curlec text', () => {
    const props = getInitialProps(false, false);
    render(<App {...props} />);

    expect(screen.getByText(/Earnings from Curlec/i)).toBeInTheDocument();

    expect(screen.getByText('Base + Tax')).toBeInTheDocument();
  });

  test('should render component with indian text', () => {
    const props = getInitialProps(true, false);
    render(<App {...props} />);

    expect(screen.getByText(/Earnings from Razorpay/i)).toBeInTheDocument();
    expect(screen.queryByTestId('tooltip-interactive-wrapper')).not.toBeInTheDocument();

    expect(screen.getByText('Base + GST')).toBeInTheDocument();
  });

  test('should show negative amount if source type is refund', () => {
    const props = getInitialProps(true, true);
    render(<App {...props} />);

    // checking here to see if minus sign exists
    expect(screen.getByText(/-/i)).toBeInTheDocument();
    expect(
      screen.getByLabelText(
        'These earnings are reversed because of a full or partial refund of the payment from your affiliate account.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText(/Earnings reversal due to refund from Razorpay/i)).toBeInTheDocument();
  });
});
