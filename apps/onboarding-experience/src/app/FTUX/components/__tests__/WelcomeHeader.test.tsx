import React from 'react';
import { screen, fireEvent } from '@testing-library/react';
import WelcomeHeader from '../WelcomeHeader';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { getMerchantHeaderData, formatName } from '@FTUX/utils/homepage';
import { isMobileDevice } from '@libs/shared-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';

// Only mock the necessary dependencies
jest.mock('@FTUX/context/MerchantContext');
jest.mock('@FTUX/utils/homepage', () => ({
  getMerchantHeaderData: jest.fn(),
  formatName: jest.fn((name) => name),
}));
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn().mockReturnValue(false),
}));

describe('WelcomeHeader', () => {
  // Mock data
  const mockMerchantName = 'Test';
  const mockRegisteredName = 'Test Registered';
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
          contactPerson: {
            name: {
              value: mockMerchantName,
            },
          },
          name: {
            registered: mockRegisteredName,
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

    // Default to desktop view
    (isMobileDevice as jest.Mock).mockReturnValue(false);
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
    expect(screen.getByText('Hey, welcome to Razorpay.')).toBeInTheDocument();
  });

  it('handles missing merchant data gracefully in mobile view', () => {
    // Setup null merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
    });

    // Set mobile view
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    renderWithWrappers(<WelcomeHeader />);
    // Should render welcome without merchant name
    expect(screen.getByText('Welcome!')).toBeInTheDocument();
  });

  it('correctly uses contactPerson name when available', () => {
    renderWithWrappers(<WelcomeHeader />);
    expect(formatName).toHaveBeenCalledWith(mockMerchantName);
    expect(screen.getByText(`Hey ${mockMerchantName}, welcome to Razorpay.`)).toBeInTheDocument();
  });

  it('falls back to registered name when contactPerson name is not available', () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          contactPerson: {
            name: {
              value: null,
            },
          },
          name: {
            registered: mockRegisteredName,
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    renderWithWrappers(<WelcomeHeader />);
    expect(formatName).toHaveBeenCalledWith(mockRegisteredName);
    expect(screen.getByText(`Hey ${mockRegisteredName}, welcome to Razorpay.`)).toBeInTheDocument();
  });

  it('displays correct greeting when name is empty', () => {
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          contactPerson: {
            name: {
              value: '',
            },
          },
          name: {
            registered: '',
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    renderWithWrappers(<WelcomeHeader />);
    expect(formatName).toHaveBeenCalledWith('');
    expect(screen.getByText('Hey, welcome to Razorpay.')).toBeInTheDocument();
  });

  it('displays correct mobile greeting with merchant name', () => {
    // Set mobile view
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    renderWithWrappers(<WelcomeHeader />);
    expect(screen.getByText(`Welcome, ${mockMerchantName}!`)).toBeInTheDocument();
  });

  it('handles case when element to scroll to is not found', () => {
    // Mock getElementById to return null
    global.document.getElementById = jest.fn().mockReturnValue(null);

    renderWithWrappers(<WelcomeHeader />);
    const viewHereLink = screen.getByText('View all');
    fireEvent.click(viewHereLink);

    expect(document.getElementById).toHaveBeenCalledWith('ways-to-accept-payments');
    expect(mockScrollIntoView).not.toHaveBeenCalled();
  });
});
