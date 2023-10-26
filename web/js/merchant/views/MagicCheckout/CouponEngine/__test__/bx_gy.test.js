// ui imports
import CreateCouponForm from 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm';

// test utils
import { render, screen, waitFor } from 'test-utils';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    couponName: 'buyx_gety',
  }),
}));

test('should render buyx_gety coupon correctly', async () => {
  render(<CreateCouponForm />);

  await waitFor(() => {
    expect(screen.getByText('Create new coupon')).toBeInTheDocument();
  });

  expect(screen.getByText('Buy X Get Y')).toBeInTheDocument();
  expect(screen.getByText('Coupon Code')).toBeInTheDocument();
  expect(screen.getByText('Coupon description')).toBeInTheDocument();
  const couponWidgets = [
    'Products purchased',
    'Discount Offered',
    'Coupon validity',
    'Coupon eligibility',
  ];
  couponWidgets.forEach((widget) => {
    expect(screen.getByText(widget)).toBeInTheDocument();
  });
});
