import About from 'merchant/views/Reconciliations/Onboarding/About';
import { render, screen } from 'test-utils';

const renderAbout = (props = {}) => {
  render(<About {...props} />);
};

describe('Tests for onboarding component about - Recon Saas', () => {
  test('Should render intro banner screen without errors', () => {
    expect(renderAbout).not.toThrowError();
  });
  test('Should load the recon onboarding banner', () => {
    renderAbout();
    expect(screen.getByText(/Redefining Reconciliation/i)).toBeInTheDocument();
    expect(screen.getByText(/Automated reconciliations solution/i)).toBeInTheDocument();
  });
  test('Should have the get started button on banner', () => {
    renderAbout();
    expect(
      screen.getByRole('button', {
        name: /Get Started/i,
      }),
    ).toBeInTheDocument();
  });
});
