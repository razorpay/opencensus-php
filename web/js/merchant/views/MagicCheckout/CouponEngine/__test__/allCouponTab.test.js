import React from 'react';
import { render, screen } from '@testing-library/react';

//ui imports
import AllCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/AllCouponsTab';

jest.mock('merchant/views/MagicCheckout/CouponEngine/pages/GenericCouponTab', () => {
  return jest.fn((props) => (
    <div {...props}>Mocked Generic Coupons Tab for - {props.initialFilters.status} coupons tab</div>
  ));
});

describe('all coupons', () => {
  it('renders ActiveCoupons component with initial filters', () => {
    render(<AllCouponsTab />);

    const mockedGenericCouponsComponent = screen.getByText(/Generic Coupons Tab/i);

    expect(mockedGenericCouponsComponent).toHaveAttribute('initialFilters');
    expect(mockedGenericCouponsComponent).toHaveTextContent(
      'Mocked Generic Coupons Tab for - all coupons tab',
    );
  });
});
