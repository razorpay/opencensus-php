import CreateCouponModal from 'merchant/views/MagicCheckout/CouponEngine/components/CreateCouponModal/CreateCouponModal';
import { render, screen, userEvent } from 'test-utils';

const couponVariantsSupported = [
  'Amount discounted on orders',
  'Amount discounted on products',
  'Buy X Get Y',
  'Bulk discount',
];

describe('coupon Modal', () => {
  test('should render create coupon modal', () => {
    render(<CreateCouponModal />);

    expect(screen.getByText('Coupon type')).toBeInTheDocument();
  });

  test.each(couponVariantsSupported)(
    'should render create %s coupon cta',
    async (couponVariant) => {
      render(<CreateCouponModal />);

      expect(screen.getByText(couponVariant)).toBeInTheDocument();

      await userEvent.click(screen.getByText(couponVariant));
    },
  );
});
