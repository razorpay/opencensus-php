import CreateCouponForm from 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm';
import { render, screen, waitFor } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    couponName: 'bulk_order',
  }),
}));

test('should not render coupon code for Prepaid Payment methods option for MagicX', async () => {
  const initState = {
    magicCheckout: {
      rcod: true,
    },
  };
  render(<CreateCouponForm />, {
    reduxStore: storeWithInitialState({ ...initState }),
  });

  await waitFor(() => {
    expect(screen.getByText('Create new coupon')).toBeInTheDocument();
    expect(screen.getAllByText('Coupon Code').length).toBeGreaterThan(0);
    expect(screen.getByText('Coupon description')).toBeInTheDocument();
    expect(screen.queryByText('Display this coupon at checkout')).toBeInTheDocument();

    expect(
      screen.queryByText('Enable this coupon code only for Prepaid Payment methods'),
    ).not.toBeInTheDocument();
  });
});
