import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import CouponManagement from '@apps/digital-bills/src/views/CouponManagement';

describe('CouponManagement', () => {
  test('should render CouponManagement component', async () => {
    const { getByText } = renderWithWrappers(<CouponManagement />);
    expect(getByText('Coupon Management')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/coupons');
  });
});
