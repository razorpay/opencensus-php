import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import SupportAndFeedback from 'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback';

// Mocking the FeedbackForm component to simplify testing of the parent component
jest.mock(
  'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/FeedbackForm',
  () => {
    return function MockFeedbackForm() {
      return <div data-testid="feedback-form">Mocked Feedback Form</div>;
    };
  },
);

describe('SupportAndFeedback Component', () => {
  const originalWindow = { ...window };
  const mockAssign = jest.fn();

  beforeEach(() => {
    // Setup window.location mock
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: { href: '', assign: mockAssign },
      writable: true,
    });

    jest.clearAllMocks();
  });

  afterEach(() => {
    // Reset window.location
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: originalWindow.location,
    });
  });

  it('should render the component with correct header', () => {
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <SupportAndFeedback />
      </BladeProvider>,
    );

    expect(screen.getByText('Support & Feedback')).toBeInTheDocument();
    expect(
      screen.getByText('Get help with your Razorpay account or share your feedback with us'),
    ).toBeInTheDocument();
  });

  it('should render both support card and feedback form', () => {
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <SupportAndFeedback />
      </BladeProvider>,
    );

    expect(screen.getByText('Have an issue?')).toBeInTheDocument();
    expect(
      screen.getByText(/Connect with our support team for quick resolution of any problems/),
    ).toBeInTheDocument();
    expect(screen.getByTestId('feedback-form')).toBeInTheDocument();
  });

  it('should render "See Documentation" button with correct link', () => {
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <SupportAndFeedback />
      </BladeProvider>,
    );

    const docButton = screen.getByRole('link', { name: /see documentation/i });
    expect(docButton).toBeInTheDocument();
    expect(docButton).toHaveAttribute(
      'href',
      'https://razorpay.com/docs/payments/cod-magic-checkout/',
    );
  });

  it('should set mailto link correctly when "Contact Magic Support" button is clicked', () => {
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <SupportAndFeedback />
      </BladeProvider>,
    );

    const supportButton = screen.getByRole('button', { name: /contact magic support/i });
    expect(supportButton).toBeInTheDocument();

    fireEvent.click(supportButton);
    expect(window.location.href).toBe('mailto:magic-checkout-support@razorpay.com');
  });
});
