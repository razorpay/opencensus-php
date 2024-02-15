import React from 'react';

import { render, screen } from 'common/services/test/test-utils';
import CurlecSuccessContainer from 'merchant/views/PartnerDashboard/SubMerchant/components/CurlecSuccessContainer';

describe('success popup shown on successfull reseller addition', () => {
  const renderApp = () => {
    return render(<CurlecSuccessContainer />);
  };

  test('should render component with curlec text', () => {
    renderApp();

    expect(
      screen.getByText(
        'Thank you for refferring! Our Agent will verify the details and reachout for further steps.',
      ),
    ).toBeInTheDocument();
  });
});
