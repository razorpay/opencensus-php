import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import { CommissionEarningBreakUp } from 'merchant/views/PartnerDashboard/Commissions/Transactional/Details';

function getInitialProps(isRzpOrg) {
  return {
    user: {
      isOrgRZP: isRzpOrg,
    },
    org: {
      custom_code: isRzpOrg ? 'rzp' : 'curlec',
      business_name: isRzpOrg ? 'Razorpay' : 'Curlec',
    },
  };
}

describe('test suite for transactional details', () => {
  const App = ({ ...props }) => {
    return <CommissionEarningBreakUp {...props} />;
  };

  test('should render component with curlec text', () => {
    const props = getInitialProps(false);
    render(<App {...props} />);

    expect(screen.getByText(/Earnings from Curlec/i)).toBeInTheDocument();

    expect(screen.getByText('Tax')).toBeInTheDocument();
  });

  test('should render component with indian text', () => {
    const props = getInitialProps(true);
    render(<App {...props} />);

    expect(screen.getByText(/Earnings from Razorpay/i)).toBeInTheDocument();

    expect(screen.getByText('GST')).toBeInTheDocument();
  });
});
