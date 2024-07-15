import React from 'react';
import { render } from 'test-utils';

import { ProviderView } from '../ProviderView';

const PROVIDER = {
  Provider_name: 'payu_token_testing_hoad',
  Description: 'payu token testing hoad desc',
  Gateway: 'payu',
  Gateway_details: {
    Key: 'V4AJoU',
    'Payment Methods': ['card', 'emi'],
    Recurring: false,
    Salt: '',
    Sodexo: false,
    optimizer_seamless_disabled: false,
  },
  Currency: ['INR'],
  Gateway_acquirer: 'payu',
  Terminal_id: 'JmObIfQKpSRQEL',
  Status: 'activated',
  created_at: 1656306007,
  updated_at: 1720676823,
};

describe('Provider View', () => {
  const renderApp = () => {
    return render(<ProviderView provider={PROVIDER} />);
  };

  it('should render provider view without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render provider view', () => {
    const { getByText, getByTestId, queryByText } = renderApp();
    expect(getByText(PROVIDER.Provider_name)).toBeInTheDocument();
    expect(getByText(PROVIDER.Gateway_details['Payment Methods'].join(', '))).toBeInTheDocument();
    const img = getByTestId('gateway-logo');
    expect(img).toBeInTheDocument();
    expect(img).toHaveAttribute('alt', 'payu-logo');
    expect(queryByText('pending')).not.toBeInTheDocument();
  });
});
