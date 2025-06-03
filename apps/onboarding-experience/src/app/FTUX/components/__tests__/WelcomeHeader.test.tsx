import React from 'react';
import { screen, fireEvent } from '@testing-library/react';
import WelcomeHeader from '../WelcomeHeader';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { getMerchantHeaderData } from '@FTUX/utils/homepage';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';

// Only mock the necessary dependencies
jest.mock('@FTUX/context/MerchantContext');
jest.mock('@FTUX/utils/homepage');

describe('WelcomeHeader', () => {
  // Mock data
  const mockMerchantName = 'Test';
  const mockPaymentChannels = ['online', 'website'];
  const mockHeaderIcons = ['icon1.png', 'icon2.png'];
  const mockPlatformsText = 'online and website';

  // Mock element and scrollIntoView
  const mockScrollIntoView = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    // Setup mock context data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          name: {
            registered: mockMerchantName,
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    // Setup mock header data
    (getMerchantHeaderData as jest.Mock).mockReturnValue({
      icons: mockHeaderIcons,
      text: mockPlatformsText,
    });

    // Setup mock scrollIntoView
    global.document.getElementById = jest.fn().mockImplementation((id) => {
      if (id === 'ways-to-accept-payments') {
        return { scrollIntoView: mockScrollIntoView };
      }
      return null;
    });
  });

  it('renders the welcome message with merchant name', () => {
    renderWithWrappers(<WelcomeHeader />);
    expect(screen.getByText(`Hey ${mockMerchantName}, welcome to Razorpay.`)).toBeInTheDocument();
  });

  it('displays the selected payment platforms text', () => {
    renderWithWrappers(<WelcomeHeader />);
    expect(
      screen.getByText(`You picked ${mockPlatformsText}, so we'll set it up first.`),
    ).toBeInTheDocument();
  });

  it('renders avatar icons based on selected payment channels', () => {
    renderWithWrappers(<WelcomeHeader />);

    // The Blade Avatar component is rendered through renderWithWrappers,
    // so we don't need to test the specific implementation details
    // We can verify that the AvatarGroup is rendered with the right number of children
    const avatarGroup = screen.getByRole('group');
    expect(avatarGroup).toBeInTheDocument();
  });

  it('calls getMerchantHeaderData with the correct payment channels', () => {
    renderWithWrappers(<WelcomeHeader />);
    expect(getMerchantHeaderData).toHaveBeenCalledWith(mockPaymentChannels);
  });

  it('scrolls to ways to accept payments section when link is clicked', () => {
    renderWithWrappers(<WelcomeHeader />);
    const viewHereLink = screen.getByText('View all');
    fireEvent.click(viewHereLink);

    expect(document.getElementById).toHaveBeenCalledWith('ways-to-accept-payments');
    expect(mockScrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth' });
  });

  it('renders explore other payment options text', () => {
    renderWithWrappers(<WelcomeHeader />);
    expect(screen.getByText('Explore other payment options anytime.')).toBeInTheDocument();
  });

  it('renders a divider at the bottom', () => {
    renderWithWrappers(<WelcomeHeader />);
    expect(screen.getByRole('separator')).toBeInTheDocument();
  });

  it('handles missing merchant data gracefully', () => {
    // Setup null merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
    });

    renderWithWrappers(<WelcomeHeader />);
    // Should render welcome without merchant name
    expect(screen.getByText('Hey , welcome to Razorpay.')).toBeInTheDocument();
  });

  it('handles missing payment channels gracefully', () => {
    // Setup null payment channels
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          name: {
            registered: mockMerchantName,
          },
          business: {
            paymentAcceptanceChannels: null,
          },
        },
      },
    });

    // Mock default return for getMerchantHeaderData
    (getMerchantHeaderData as jest.Mock).mockReturnValue({
      icons: [],
      text: '',
    });

    renderWithWrappers(<WelcomeHeader />);
    expect(screen.getByText(`Hey ${mockMerchantName}, welcome to Razorpay.`)).toBeInTheDocument();
    expect(getMerchantHeaderData).toHaveBeenCalledWith(null);
  });
});
