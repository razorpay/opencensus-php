import React from 'react';
import { render, screen } from 'test-utils';

import { ZaakpayPoints } from 'merchant/views/Navigator/components/Provider/SeamlessComponents/ZaakpayPoints';

describe('ZaakpayPoints component', () => {
  test('should render ZaakpayPoints without any errors', () => {
    expect(() => render(<ZaakpayPoints />)).not.toThrowError();
  });

  test('should render the points for Zaakpay gateway', () => {
    render(<ZaakpayPoints />);

    expect(
      screen.getByText(
        'Reach out to your relationship manager to get your server to server or seamless integration kit with credentials',
      ),
    ).toBeInTheDocument();
  });
});
