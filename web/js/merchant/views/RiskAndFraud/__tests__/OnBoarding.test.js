import Onboarding from 'merchant/views/RiskAndFraud/OnBoarding';
import { getFeaturesData } from 'merchant/views/RiskAndFraud/OnBoarding/constant';
import { render, screen, userEvent } from 'test-utils';

const BUSINESS_NAME = 'Razorpay';

const renderApp = (props = {}) => {
  render(<Onboarding {...props} />);
};

describe('Tests for Onboarding component - RiskVisibility', () => {
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
      productOnboarding: () => ({
        success: jest.fn(),
        initiated: jest.fn(),
      }),
    };
  });

  test('Should render Onboarding component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('Should load initial components "Skip & Read More" ', () => {
    renderApp();
    expect(screen.getByText(/Read More/i)).toBeInTheDocument();
    expect(screen.getByText(/Skip And Get Started/i)).toBeInTheDocument();
  });

  test('Should show appropriate title and description', () => {
    renderApp();
    expect(screen.getByText(`${BUSINESS_NAME} Shield`)).toBeInTheDocument();
    expect(screen.getByText(/Analyze fraudulent and disputed transactions/)).toBeInTheDocument();
  });

  test('Should load feature page of Risk Visibility', async () => {
    renderApp();

    const readMoreCTA = screen.getByRole('button', {
      name: /Read More/,
    });
    expect(readMoreCTA).toBeInTheDocument();
    await userEvent.click(readMoreCTA);

    getFeaturesData(BUSINESS_NAME).forEach((feature) => {
      expect(screen.getByText(feature.title)).toBeInTheDocument();
      expect(screen.getByText(feature.desc)).toBeInTheDocument();
    });
  });
});
