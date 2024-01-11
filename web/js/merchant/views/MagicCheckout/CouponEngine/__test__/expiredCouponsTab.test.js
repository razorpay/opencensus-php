import React from 'react';
import { render, screen } from '@testing-library/react';

// ui imports
import ExpiredCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/ExpiredCouponsTab';

jest.mock('merchant/views/MagicCheckout/CouponEngine/pages/GenericCouponTab', () => {
  return jest.fn((props) => (
    <div {...props}>Mocked Generic Coupons Tab for - {props.initialFilters.status} coupons tab</div>
  ));
});

describe('ActiveCoupons', () => {
  it('renders ActiveCoupons component with initial filters', () => {
    render(<ExpiredCouponsTab />);

    const mockedGenericCouponsComponent = screen.getByText(/Generic Coupons Tab/i);

    expect(mockedGenericCouponsComponent).toBeInTheDocument();
    expect(mockedGenericCouponsComponent).toHaveAttribute('initialFilters');
    expect(mockedGenericCouponsComponent).toHaveTextContent(
      'Mocked Generic Coupons Tab for - expired coupons tab',
    );
  });
});
