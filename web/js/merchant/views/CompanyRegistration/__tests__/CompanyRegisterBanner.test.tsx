import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import CompanyRegisterBanner from '../components/CompanyRegisterBanner';
import { RIZE_INCORPORATION, RIZE_JOURNEY } from '../constant';
import { trackStartRegistrationCtaClick, trackEventOnUserScreenCtaClick } from '../analytics';

jest.mock('../analytics', () => ({
  trackStartRegistrationCtaClick: jest.fn(),
  trackEventOnUserScreenCtaClick: jest.fn(),
}));

jest.mock('../utils', () => ({
  parseBannerData: jest.fn(() => ({
    firstLine: 'Welcome to Razorpay!',
    secondLineSubText: 'Create your account now.',
    highlightedText: 'Start your journey with us!',
    isButtonRequire: true,
    buttonText: 'Get Started',
  })),
  styleBasedOnDevice: jest.fn(() => ({
    margin: 'spacing.5',
    padding: 'spacing.5',
  })),
}));

describe('CompanyRegisterBanner Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });
  const renderComponent = ({ screen }) =>
    render(
        <CompanyRegisterBanner isSmallDevice={false} screen={screen} />
    );

  test('renders the banner data correctly for INITIAL_SCREEN', () => {
    renderComponent({
      screen: RIZE_JOURNEY.INITIAL_SCREEN,
    });
    expect(screen.getByText(/Welcome to Razorpay!/i)).toBeInTheDocument();
    expect(screen.getByText(/Create your account now./i)).toBeInTheDocument();
    expect(screen.getByText(/Start your journey with us!/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Get Started/i })).toBeInTheDocument();
  });

  test('renders the banner data correctly for RESUME_SCREEN', () => {
    renderComponent({
      screen: RIZE_JOURNEY.RESUME_SCREEN,
    });
    expect(screen.getByText(/Welcome to Razorpay!/i)).toBeInTheDocument();
    expect(screen.getByText(/Create your account now./i)).toBeInTheDocument();
    expect(screen.getByText(/Start your journey with us!/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Get Started/i })).toBeInTheDocument();
  });

  test('calls trackStartRegistrationCtaClick when INITIAL_SCREEN modal confirm is clicked', async () => {
    renderComponent({ screen: RIZE_JOURNEY.INITIAL_SCREEN });
  
    // Step 1: Click to open modal
    await userEvent.click(screen.getByRole('button', { name: /Get Started/i }));
  
    // Step 2: Find the confirm button in the modal and click
    await userEvent.click(screen.getByRole('button', { name: /Yes, let's begin/i }));
  
    // Now the function should be called
    expect(trackStartRegistrationCtaClick).toHaveBeenCalledTimes(1);
  });

  test('calls trackEventOnUserScreenCtaClick when RESUME_SCREEN button is clicked', async () => {
    renderComponent({
      screen: RIZE_JOURNEY.RESUME_SCREEN,
    });
    const button = screen.getByRole('button', { name: /Get Started/i });
    await userEvent.click(button);

    expect(trackEventOnUserScreenCtaClick).toHaveBeenCalledTimes(1);
  });

  test('does not show IconWithTextWrapper for STATUS_SCREEN', () => {
    renderComponent({
      screen: RIZE_JOURNEY.STATUS_SCREEN,
    });
    expect(
      screen.queryByText(/Sit back & relax while we take care of the entire process/i),
    ).toBeInTheDocument();
    expect(screen.queryByText('CheckCircle2Icon')).toBeNull();
  });

  test('renders IconWithTextWrapper for INITIAL_SCREEN and RESUME_SCREEN', () => {
    renderComponent({
      screen: RIZE_JOURNEY.INITIAL_SCREEN,
    });
    expect(screen.getAllByTestId('icon-wrapper')[0]).toBeInTheDocument();
    renderComponent({
      screen: RIZE_JOURNEY.RESUME_SCREEN,
    });
    expect(screen.getAllByTestId('icon-wrapper')[0]).toBeInTheDocument();
  });

  test('opens a new tab with the RIZE_INCORPORATION URL when the modal confirm button is clicked (INITIAL_SCREEN)', async () => {
    const openSpy = jest.spyOn(window, 'open').mockImplementation(() => null);
  
    renderComponent({
      screen: RIZE_JOURNEY.INITIAL_SCREEN,
    });
  
    // Open the modal
    const triggerButton = screen.getByRole('button', { name: /Get Started/i });
    await userEvent.click(triggerButton);
  
    // Simulate clicking the modal's confirm button
    const confirmButton = screen.getByRole('button', { name: /Yes, let's begin/i });
    await userEvent.click(confirmButton);
  
    expect(openSpy).toHaveBeenCalledWith(RIZE_INCORPORATION, '_blank', 'noopener');
  
    openSpy.mockRestore();
  });
});
