import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import RzpSuccessContainer from 'merchant/views/PartnerDashboard/SubMerchant/components/RzpSuccessContainer';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

const props = {
  merchantContact: '9999888877',
  merchantEmail: 'paveve1791@breazeim.com',
  referralUrl: 'https://rzp.io/i/6cxEuFXr',
  tracking: {
    getTrackingData: jest.fn(),
    trackEvent: jest.fn(),
  },
  source: 'referral-guide',
  merchantType: PRODUCT_TYPE.PG,
  partnerID: 'Ao6iPyuWSzc3dr',
};

describe('success popup shown on successfull reseller addition', () => {
  const renderApp = (props) => {
    return render(<RzpSuccessContainer {...props} />);
  };

  test('should render component with rzp text', () => {
    renderApp(props);
    expect(screen.getByTestId('success-text')).toHaveTextContent(
      `Razorpay account access link will be sent to your affiliate's email at paveve1791@breazeim.com and via SMS on +91-9999888877`,
    );
    expect(
      screen.getByText('You can also copy and share the link via other mediums'),
    ).toBeInTheDocument();
  });

  test('should render component without mobile text if merchantContact not present', () => {
    renderApp({ ...props, merchantContact: undefined });
    expect(screen.getByTestId('success-text')).toHaveTextContent(
      `Razorpay account access link will be sent to your affiliate's email at paveve1791@breazeim.com`,
    );
  });
});
