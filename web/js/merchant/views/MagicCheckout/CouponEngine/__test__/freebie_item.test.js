// ui imports
import CreateCouponForm from 'merchant/views/MagicCheckout/CouponEngine/pages/CreateCouponForm';

// test utils
import { render, screen, waitFor } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    couponName: 'freebie_item',
  }),
}));

describe('Freebie Coupon widgets', () => {
  test('should render freebie item coupon widgets correctly - MagicX', async () => {
    const initState = {
      session: {
        user: {
          isMultiCouponsEnabled: true,
        },
      },
      magicCheckout: {
        rcod: true,
      },
    };
    render(<CreateCouponForm />, {
      reduxStore: storeWithInitialState({ ...initState }),
    });

    await waitFor(() => {
      expect(screen.getByText('Create new coupon')).toBeInTheDocument();
    });

    expect(screen.getByText('Freebie Item')).toBeInTheDocument();
    // Prepaid check is not available for magicX
    expect(
      screen.queryByText('Enable this coupon code only for Prepaid Payment methods'),
    ).not.toBeInTheDocument();
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
    // MagicX does not support free shipping coupon type and hence no coupon combinations for freebie coupon on magicX
    expect(screen.queryByText('Coupon combinations (optional)')).not.toBeInTheDocument();
  });
});
