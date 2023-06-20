import { render, screen } from 'test-utils';
import CustomerFeeBearerPopover from 'merchant/components/CustomerFeeBearerPopover';

describe('<CustomerFeeBearerPopover />', () => {
  test('should render the popover content properly without any feature', () => {
    render(<CustomerFeeBearerPopover />);
    expect(
      screen.getByText(
        'This feature is not supported for merchants accepting payments as per the convenience fee model.',
        { exact: false },
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'here' })).toHaveAttribute(
      'href',
      '/app/payments-and-refunds-settings/capture-refund-settings',
    );
  });
  test('should render the popover content properly with feature', () => {
    render(<CustomerFeeBearerPopover feature="QR Code" />);
    expect(
      screen.getByText(
        'QR Code is not supported for merchants accepting payments as per the convenience fee model.',
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
