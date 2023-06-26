import Onboarding from 'merchant/views/RazorpayXWidget/Onboarding';
import { fallbackViewData } from 'common/ui/XBankingWidget/fallbackViewData';
import { render, screen } from 'test-utils';

describe('RazorpayXWidget', () => {
  test('should be able to render default RazorpayXWidget', () => {
    const x_banking_widget = fallbackViewData.x_banking_widgets[0];
    render(<Onboarding x_banking_widget={x_banking_widget} />);

    // UI elements
    expect(screen.getByText('Go OTP-free with easy approvals')).toBeInTheDocument();
    expect(screen.getByText('Standard banking features')).toBeInTheDocument();
    expect(screen.getByText('Open Current Account')).toBeInTheDocument();
    expect(screen.getByText('(also available in X)')).toBeInTheDocument();
    expect(screen.getByText('Chequebook')).toBeInTheDocument();
  });
});
