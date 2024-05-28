import React from 'react';

import { render, screen } from 'test-utils';

import MandatePaymentMethod from '../MandatePaymentMethod';

const walletMandate = {
  method: 'wallet',
  wallet: 'touchngo',
};

describe('MandatePaymentMethod Component', () => {
  test('renders wallet details correctly', () => {
    render(<MandatePaymentMethod mandate={walletMandate} user={{ country_code: 'MY' }} />);
    expect(screen.getByText('touchngo wallet')).toBeInTheDocument();
  });
});
