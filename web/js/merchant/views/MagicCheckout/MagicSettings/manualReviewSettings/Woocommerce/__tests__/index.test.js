import { render, screen } from 'test-utils';
import WoocommerceModal from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce';

describe('testing the woocommerce api credentials modal', () => {
  test('component should render properly', () => {
    render(<WoocommerceModal platform="woocommerce" />);
    expect(screen.getByText(/^WooCommerce API credentials?/i)).toBeInTheDocument();
  });
});
