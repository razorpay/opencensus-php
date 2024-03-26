import OnboardingView from 'merchant/views/Reconciliations/Onboarding/OnboardingView';
import { render, screen } from 'test-utils';

const renderOnboardingView = (props = {}) => {
  render(<OnboardingView {...props} />);
};

describe('Tests for onboarding component onboarding view - Recon Saas', () => {
  test('Should render onboarding view without errors', () => {
    expect(renderOnboardingView).not.toThrowError();
  });
  test('Should render onboarding view with title', () => {
    renderOnboardingView({ title: 'Test title' });
    expect(screen.getByText('Test title')).toBeInTheDocument();
  });
  test('Should render onboarding view with jsx  as children', () => {
    renderOnboardingView({ children: <div>Test children</div> });
    expect(screen.getByText('Test children')).toBeInTheDocument();
  });
});
