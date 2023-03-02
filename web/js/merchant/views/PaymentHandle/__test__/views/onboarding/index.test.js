import {
  App,
  paymentHandle,
} from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/Onboarding';
import { render, screen, userEvent } from 'test-utils';

describe('Payment Handle Onboarding', () => {
  const renderApp = (props = {}) => {
    return render(<App {...props} />, {
      initialState: {
        session: {
          user: {
            isPaymentHandleEnabled: false,
          },
        },
        paymentHandle,
      },
    });
  };

  test('Payment Handle Onboarding App to be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render onboarding screen title', () => {
    renderApp();
    expect(screen.getByText('Introducing Razorpay.me')).toBeInTheDocument();
  });

  test('should be clickable on "Get Started" button', async () => {
    const setOnboardingVisible = jest.fn();
    const props = {
      setOnboardingVisible,
    };
    renderApp(props);
    const getStartedBtn = screen.getByTestId('ph-onboarding-btn');
    expect(getStartedBtn).toBeInTheDocument();
    await userEvent.click(getStartedBtn);
    expect(setOnboardingVisible).toHaveBeenCalled();
  });

  test('should open "edit" modal', () => {
    renderApp();
    expect(screen.getByText('@bhaskar1298')).toBeInTheDocument();
  });
});
