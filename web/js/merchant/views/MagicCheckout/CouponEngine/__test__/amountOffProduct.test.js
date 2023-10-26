// ui imports
import CreateCouponForm from 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm';

// test utils
import { render, screen, waitFor } from 'test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    couponName: 'amount_off_products',
  }),
}));

test('should render create amount_off_products coupon correctly', async () => {
  render(<CreateCouponForm />);

  await waitFor(() => {
    expect(screen.getByText('Create new coupon')).toBeInTheDocument();
  });

  expect(screen.getByText('Amount discounted on products')).toBeInTheDocument();
  expect(screen.getByText('Coupon Code')).toBeInTheDocument();
  expect(screen.getByText('Coupon description')).toBeInTheDocument();
  const couponWidgets = [
    'Discount Details',
    'Coupon validity',
    'Coupon eligibility',
    'Usage restriction',
  ];
  couponWidgets.forEach((widget) => {
    expect(screen.getByText(widget)).toBeInTheDocument();
  });
});
