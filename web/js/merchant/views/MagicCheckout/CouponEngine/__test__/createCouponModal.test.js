import CreateCouponModal from 'merchant/views/MagicCheckout/CouponEngine/components/CreateCouponModal/CreateCouponModal';
import { render, screen, userEvent } from 'test-utils';

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      magic_free_shipping_coupon: { variables: { result: 'on' } },
    },
  }),
  withSplitzService: jest.fn(),
}));

const couponVariantsSupported = [
  'Amount discounted on orders',
  'Amount discounted on products',
  'Buy X Get Y',
  'Bulk discount',
  'Free shipping',
];

describe('coupon Modal', () => {
  test('should render create coupon modal', () => {
    render(<CreateCouponModal />);

    expect(screen.getByText('Select a Coupon Type')).toBeInTheDocument();
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
