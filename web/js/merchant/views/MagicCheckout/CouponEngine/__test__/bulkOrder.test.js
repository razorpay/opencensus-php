// ui imports
import CreateCouponForm from 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm';

// test utils
import { render, screen, waitFor } from 'test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    couponName: 'bulk_order',
  }),
}));

test('should render bulk_order coupon correctly', async () => {
  render(<CreateCouponForm />);

  await waitFor(() => {
    expect(screen.getByText('Create new coupon')).toBeInTheDocument();
  });

  expect(screen.getByText('Bulk discount')).toBeInTheDocument();
  expect(
    screen.getByText('Enable this coupon code only for Prepaid Payment methods'),
  ).toBeInTheDocument();
  expect(screen.getByText('Coupon Code')).toBeInTheDocument();
  expect(screen.getByText('Coupon description')).toBeInTheDocument();
  const couponWidgets = [
    'Products purchased',
    'Discount Offered',
    'Coupon validity',
    'Coupon eligibility',
    'Usage restriction',
  ];
  couponWidgets.forEach((widget) => {
    expect(screen.getByText(widget)).toBeInTheDocument();
  });
});
