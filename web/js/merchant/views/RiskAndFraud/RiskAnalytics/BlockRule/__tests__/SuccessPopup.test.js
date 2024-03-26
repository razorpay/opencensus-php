import SuccessPopup from 'merchant/views/RiskAndFraud/RiskAnalytics/BlockRule/SuccessPopup';
import { render, screen, userEvent } from 'test-utils';

const renderApp = (props = {}) => {
  render(<SuccessPopup isOpen={true} {...props} />);
};

describe('Tests for RequestBlacklist component (Risk Visibility)', () => {
  test('Should render without breaking', () => {
    renderApp();
    expect(screen.getByText('Request sent successfully')).toBeInTheDocument();
  });

  test('Should show dismiss button', () => {
    renderApp();
    expect(screen.getByRole('button', { name: 'Got it' })).toBeInTheDocument();
  });

  test('Should call onDismiss on button click', async () => {
    const onDismiss = jest.fn();
    renderApp({ onDismiss });

    await userEvent.click(screen.getByRole('button', { name: 'Got it' }));
    expect(onDismiss).toHaveBeenCalled();
  });
});
