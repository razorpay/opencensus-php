import React from 'react';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import { Overview } from 'merchant/views/CustomerTrust/components/Overview';
import { OnboardingStatus } from '../types';

// Mock window.open
const mockOpen = jest.fn();
Object.defineProperty(window, 'open', {
  writable: true,
  value: mockOpen,
});

// Mock clipboard.writeText
const mockClipboardWriteText = jest.fn();
Object.assign(navigator, {
  clipboard: {
    writeText: mockClipboardWriteText,
  },
});

describe('Overview Component', () => {
  // Reset mocks between tests
  beforeEach(() => {
    mockOpen.mockReset();
    mockClipboardWriteText.mockReset();
  });

  const mockActivatedStatus: OnboardingStatus = 'activated';
  const mockPendingStatus: OnboardingStatus = 'pending';

  it('renders without crashing', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);
    expect(screen.getByText('Buyer Protection')).toBeInTheDocument();
  });

  it('renders all main components correctly', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    // Header
    expect(screen.getByText('Buyer Protection')).toBeInTheDocument();
    expect(screen.getByText(/Buyer Protection boosts sales by building trust/)).toBeInTheDocument();

    // ActivationStatus
    expect(screen.getByText('Buyer protection is active on your checkout')).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();

    // IntegrationSteps
    expect(screen.getByText('Select your platform')).toBeInTheDocument();
    expect(screen.getByText("I'm on shopify")).toBeInTheDocument();
    expect(screen.getByText("I'm on another platform")).toBeInTheDocument();

    // HelpBanner
    expect(screen.getByText(/Need assistance with onboarding?/)).toBeInTheDocument();
    expect(screen.getByText('Write an email')).toBeInTheDocument();
  });

  it('displays correct status when onboardingStatus is "pending"', () => {
    render(<Overview onboardingStatus={mockPendingStatus} />);

    expect(
      screen.getByText('Your request is processing. Buyer protection will be active shortly.'),
    ).toBeInTheDocument();
    expect(screen.getByText('Pending')).toBeInTheDocument();
  });

  it('displays correct status when onboardingStatus is "activated"', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    expect(screen.getByText('Buyer protection is active on your checkout')).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();
  });

  it('opens menu when more options button is clicked', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const moreButton = screen.getByLabelText('options');
    fireEvent.click(moreButton);

    expect(screen.getByText('Deactivate Buyer Protection')).toBeInTheDocument();
    expect(screen.getByText('Contact support')).toBeInTheDocument();
  });

  it('opens deactivation modal when "Deactivate Buyer Protection" is clicked', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const moreButton = screen.getByLabelText('options');
    fireEvent.click(moreButton);

    const deactivateOption = screen.getByText('Deactivate Buyer Protection');
    fireEvent.click(deactivateOption);

    expect(screen.getByText('Cannot deactivate directly')).toBeInTheDocument();
    expect(screen.getByText(/This feature cannot be disabled directly/)).toBeInTheDocument();
  });

  it('copies email when "Copy Email ID" button is clicked in deactivation modal', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const moreButton = screen.getByLabelText('options');
    fireEvent.click(moreButton);

    const deactivateOption = screen.getByText('Deactivate Buyer Protection');
    fireEvent.click(deactivateOption);

    const copyEmailButton = screen.getByText('Copy Email ID');
    fireEvent.click(copyEmailButton);

    expect(mockClipboardWriteText).toHaveBeenCalledWith('magicsales@razorpay.com');
  });

  it('closes deactivation modal when "Got it" button is clicked', async () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const moreButton = screen.getByLabelText('options');
    fireEvent.click(moreButton);

    const deactivateOption = screen.getByText('Deactivate Buyer Protection');
    fireEvent.click(deactivateOption);

    const gotItButton = screen.getByText('Got it');
    fireEvent.click(gotItButton);

    await waitFor(() => {
      expect(screen.queryByText('Cannot deactivate directly')).not.toBeInTheDocument();
    });
  });

  it('opens email client when "Write an email" button is clicked in help banner', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const writeEmailButton = screen.getByText('Write an email');
    fireEvent.click(writeEmailButton);

    expect(mockOpen).toHaveBeenCalledWith('mailto:magicsales@razorpay.com', '_blank');
  });

  it('switches to non-shopify tab when radio button is clicked', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const nonShopifyRadio = screen.getByText("I'm on another platform");
    fireEvent.click(nonShopifyRadio);

    expect(screen.getByText('Custom Integration')).toBeInTheDocument();
    expect(screen.getByText('View integration steps')).toBeInTheDocument();
  });

  it('opens integration guide when "View integration steps" button is clicked', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const nonShopifyRadio = screen.getByText("I'm on another platform");
    fireEvent.click(nonShopifyRadio);

    const viewStepsButton = screen.getByText('View integration steps');
    fireEvent.click(viewStepsButton);

    expect(mockOpen).toHaveBeenCalledWith(
      'https://razorpay.com/docs/payments/widgets/buyer-protection/shopify/',
      '_blank',
    );
  });

  it('shows Shopify steps when Shopify option is selected', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    // Shopify is selected by default
    expect(screen.getByText('Complete setup for Product Page')).toBeInTheDocument();
    expect(screen.getByText('Install the App from Shopify Appstore')).toBeInTheDocument();
    expect(screen.getByText('Enable Buyer Protection')).toBeInTheDocument();
    expect(screen.getByText('Add the Buyer Protection Widget')).toBeInTheDocument();
  });

  it('opens video modal when thumbnail is clicked', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const videoThumbnail = screen.getByAltText('Video Thumbnail');
    fireEvent.click(videoThumbnail);

    const iframe = screen.getByTitle('YouTube video player');
    expect(iframe).toBeInTheDocument();
  });

  it('starts Shopify setup when "Start set up" button is clicked', () => {
    render(<Overview onboardingStatus={mockActivatedStatus} />);

    const startSetupButton = screen.getByText('Start set up');
    fireEvent.click(startSetupButton);

    expect(mockOpen).toHaveBeenCalledWith(
      'https://razorpay.com/docs/payments/widgets/buyer-protection/shopify/',
      '_blank',
    );
  });
});
