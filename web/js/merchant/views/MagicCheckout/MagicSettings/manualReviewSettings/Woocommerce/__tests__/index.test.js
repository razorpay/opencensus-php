import { render, screen } from 'test-utils';
import WoocommerceModal from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce';

jest.mock('merchant/views/MagicCheckout/common/components/CommonModal', () => () => {
  return <p>Wooc API credentials modal</p>;
});

describe('testing the woocommerce api credentials modal', () => {
  test('component should render properly', () => {
    render(<WoocommerceModal />);
    expect(screen.getByText(/^Wooc API credentials modal?/i)).toBeInTheDocument();
  });
});
