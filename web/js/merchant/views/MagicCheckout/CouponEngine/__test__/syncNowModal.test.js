import ShopifySyncNow from 'merchant/views/MagicCheckout/CouponEngine/components/ShopifySyncModal';
import { render, screen } from 'test-utils';

describe('shopify coupon sync modal', () => {
  test('should render shopify coupon sync modal correctly', () => {
    render(<ShopifySyncNow />);

    expect(screen.getByText('Sync coupons from Shopify')).toBeInTheDocument();
    expect(
      screen.getByText('Coupons will be synced only for the dates chosen'),
    ).toBeInTheDocument();

    expect(screen.getByRole('button', { name: 'Start Sync' })).toBeInTheDocument();
  });
});
