import ExpiredCouponsTab from 'merchant/views/MagicCheckout/CouponEngine/pages/ExpiredCouponsTab';
import { render, screen, waitFor } from 'test-utils';
import { listCoupons } from 'merchant/views/MagicCheckout/CouponEngine/api';

import { EXPIRED_COUPONS } from 'merchant/views/MagicCheckout/CouponEngine/__test__/mocks/DummyResponses';

jest.mock('merchant/views/MagicCheckout/CouponEngine/api', () => ({
  ...jest.requireActual('merchant/views/MagicCheckout/CouponEngine/api'),
  listCoupons: jest.fn(),
}));

listCoupons.mockReturnValue({
  data: EXPIRED_COUPONS,
});

describe('expired coupons tab', () => {
  test.each(EXPIRED_COUPONS.coupons)('should render coupon with code %s', async (coupon) => {
    render(<ExpiredCouponsTab />);

    await waitFor(() => {
      expect(screen.getByText(coupon.code)).toBeInTheDocument();
    });
  });
});
