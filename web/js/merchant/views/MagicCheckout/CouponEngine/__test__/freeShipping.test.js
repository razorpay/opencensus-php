// ui imports
import CreateCouponForm from 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm';

// test utils
import { render, screen, waitFor } from 'test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    couponName: 'free_shipping',
  }),
}));

describe('Create Free Shipping Coupon Form', () => {
  test('should render create free shipping coupon form correctly', async () => {
    render(<CreateCouponForm />);

    await waitFor(() => {
      expect(screen.getByText('Create new coupon')).toBeInTheDocument();
    });

    expect(screen.getByText('Free shipping')).toBeInTheDocument();

    expect(screen.getAllByText('Coupon Code').length).toBeGreaterThan(1);
    expect(
      screen.getByText('Enable this coupon code only for Prepaid Payment methods'),
    ).toBeInTheDocument();
    expect(screen.getByText('Coupon description')).toBeInTheDocument();
  });
});

describe('Coupon Form Widgets', () => {
  test.each([
    'Purchase requirements',
    'Coupon validity',
    'Coupon eligibility',
    'Usage restriction',
  ])('should render widget: %s', (widget) => {
    render(<CreateCouponForm />);
    expect(screen.getByText(widget)).toBeInTheDocument();
  });
});
