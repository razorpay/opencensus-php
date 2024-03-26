import SuccessScreen from 'merchant/views/Reconciliations/Onboarding/ReconInitiatedSuccess';
import { render, screen } from 'test-utils';

const renderSuccess = (props = {}) => {
  render(<SuccessScreen {...props} />);
};

describe('Tests for Onboarding component onboardingView - Recon Saas', () => {
  test('Should render success screen without errors', () => {
    expect(renderSuccess).not.toThrowError();
  });
  test('Should show config created success if mode is config creation', () => {
    renderSuccess({ countryCode: 'IN', isConfigCreation: true });
    expect(screen.getByText(/Process Setup completed/i)).toBeInTheDocument();
  });
  test('Should show recon initiated if mode is not config creation', () => {
    renderSuccess({ countryCode: 'IN' });
    expect(screen.getByText(/New Reconciliation Initiated/i)).toBeInTheDocument();
  });
});
