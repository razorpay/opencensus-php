import React from 'react';
import { render, screen } from '@testing-library/react';

// ui imports
import ActiveCoupons from 'merchant/views/MagicCheckout/CouponEngine/pages/ActiveCouponsTab';

jest.mock('merchant/views/MagicCheckout/CouponEngine/pages/GenericCouponTab', () => {
  return jest.fn((props) => (
    <div {...props}>Mocked Generic Coupons Tab for - {props.initialFilters.status} coupons tab</div>
  ));
});

describe('active coupons tab component', () => {
  it('renders active coupons tab component with initial filters', () => {
    render(<ActiveCoupons />);

    const mockedGenericCouponsComponent = screen.getByText(/Generic Coupons Tab/i);

    expect(mockedGenericCouponsComponent).toHaveAttribute('initialFilters');
    expect(mockedGenericCouponsComponent).toHaveTextContent(
      'Mocked Generic Coupons Tab for - active coupons tab',
    );
  });
});
