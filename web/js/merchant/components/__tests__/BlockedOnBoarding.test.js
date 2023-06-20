import { render, screen } from 'test-utils';
import BlockOnBoarding, {
  BlockCustomerFeeBearerOnboarding,
} from 'merchant/components/BlockOnBoarding';

describe('<BlockOnBoarding />', () => {
  test('should render the details properly', () => {
    render(<BlockOnBoarding title="test-title" description="test-description" />);
    expect(screen.getByText('test-title')).toBeInTheDocument();
    expect(screen.getByText('test-description')).toBeInTheDocument();
  });
});

describe('<BlockCustomerFeeBearerOnboarding />', () => {
  test('should render the content properly with feature', () => {
    render(<BlockCustomerFeeBearerOnboarding feature="QR Codes" />);
    expect(screen.getByText('QR Codes')).toBeInTheDocument();
    expect(
      screen.getByText(
        'This product is not supported for merchants accepting payments as per the convenience fee model. If you wish to enable this product click',
        { exact: false },
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toHaveAttribute(
      'href',
      '/app/payments-and-refunds-settings/capture-refund-settings',
    );
  });
});
