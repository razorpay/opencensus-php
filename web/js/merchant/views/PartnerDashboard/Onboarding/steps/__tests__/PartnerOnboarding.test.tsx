import React from 'react';
import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import '@testing-library/jest-dom/extend-expect';
import PartnerOnboarding from 'merchant/views/PartnerDashboard/Onboarding/partnerOnbr';
import { getUser } from 'merchant/store';
import type { RazorpayUser } from '@libs/shared-types';

// TODO : covered only conditional steps case, have to cover others later

const userData = getUser();

const location = {
  search: '',
  pathname: '/app/partners',
};

// Extend Window interface to include rzpQ
declare global {
  interface Window {
    rzpQ: {
      merchantActions: () => {
        initiated: jest.Mock;
      };
      onbr: () => {
        interaction: jest.Mock;
        clicked: jest.Mock;
      };
      component: jest.Mock;
    };
    rzp_user: RazorpayUser;
    trackHubs: jest.Mock;
  }
}

describe('PartnerOnboarding', () => {
  let mockTrackEvent: jest.Mock;
  let mockCloseModal: jest.Mock;
  let mockInteraction: jest.Mock;
  let mockClicked: jest.Mock;

  beforeEach(() => {
    mockTrackEvent = jest.fn();
    mockCloseModal = jest.fn();
    mockInteraction = jest.fn();
    mockClicked = jest.fn();

    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => ({
        initiated: jest.fn(),
      }),
      onbr: () => ({
        interaction: mockInteraction,
        clicked: mockClicked,
      }),
      component: jest.fn(),
    };
    window.trackHubs = jest.fn();
  });

  const defaultLocation = {
    search: '',
    pathname: '/app/partners',
  };

  const renderApp = ({
    disableClose = false,
    location = defaultLocation,
    isUnregisteredBusiness = true,
    isOrgCurlec = false,
    businessName = 'Test Business',
    customCode = 'rzp',
  } = {}) => {
    return render(
      <div className="partner-onboarding-base-screen new-screen">
        <PartnerOnboarding
          disableClose={disableClose}
          location={location}
          tracking={{
            trackEvent: mockTrackEvent,
          }}
          closeModal={mockCloseModal}
        />
      </div>,
      {
        initialState: {
          session: {
            org: {
              custom_code: customCode,
              business_name: businessName,
            },
            user: {
              ...userData,
              role: 'owner',
              isUnregisteredBusiness,
              isOrgCurlec,
              findTag: () => false,
              merchant: {
                id: 'test_merchant_id',
              },
            },
          },
        },
      },
    );
  };

  describe('Basic Rendering', () => {
    test('Should render the partner onboarding component with S0 step by default', () => {
      renderApp({ disableClose: false });

      expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
      expect(screen.getByText("Wondering if you're a fit?")).toBeInTheDocument();
      expect(screen.getByRole('button', { name: "Yes, I'm a Fit" })).toBeInTheDocument();
    });

    test('Should render S1 step when disableClose is true', () => {
      renderApp({ disableClose: true });

      expect(screen.getByText(/Awesome, Join the Razorpay Partner/)).toBeInTheDocument();
      expect(screen.getByText(/Program and:/)).toBeInTheDocument();
      expect(
        screen.getByText("Delight clients with Razorpay's powerful tech stack"),
      ).toBeInTheDocument();
    });

    test('Should render slider dots for navigation', () => {
      renderApp({ disableClose: false });

      expect(document.querySelector('.SliderDots')).toBeInTheDocument();
    });
  });

  describe('Close Button Behavior', () => {
    test('Should render close button when disableClose is false', () => {
      renderApp({ disableClose: false });

      const closeButton = document.querySelector('.partner-onboarding-base-screen button.close');
      expect(closeButton).toBeInTheDocument();
    });

    test('Should not render close button when disableClose is true', () => {
      renderApp({ disableClose: true });

      const closeButton = document.querySelector('.partner-onboarding-base-screen button.close');
      expect(closeButton).not.toBeInTheDocument();
    });

    test('Should call closeModal when close button is clicked', async () => {
      renderApp({ disableClose: false });

      const closeButton = document.querySelector('.partner-onboarding-base-screen button.close');
      await userEvent.click(closeButton!);

      expect(mockCloseModal).toHaveBeenCalled();
    });
  });

  describe('Organization-specific Behavior', () => {
    test('Should add curlec class when isOrgCurlec is true', () => {
      renderApp({ isOrgCurlec: true });

      const container = document.querySelector(
        '.partner-onboarding-base-screen.curlec-onboarding-img',
      );
      expect(container).toBeInTheDocument();
    });

    test('Should not add curlec class when isOrgCurlec is false', () => {
      renderApp({ isOrgCurlec: false });

      const container = document.querySelector('.partner-onboarding-base-screen');
      expect(container).not.toHaveClass('curlec-onboarding-img');
    });
  });

  describe('Location-based Behavior', () => {
    test('Should handle app-store pathname', () => {
      const appStoreLocation = {
        search: '',
        pathname: '/app-store',
      };

      renderApp({ location: appStoreLocation });

      // Component should still render normally
      expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
    });

    test('Should handle regular pathname', () => {
      const regularLocation = {
        search: '',
        pathname: '/app/partners',
      };

      renderApp({ location: regularLocation });

      expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
    });
  });

  describe('User Business Type', () => {
    test('Should handle unregistered business', () => {
      renderApp({ isUnregisteredBusiness: true });

      expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
    });

    test('Should handle registered business', () => {
      renderApp({ isUnregisteredBusiness: false });

      expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
    });
  });

  describe('Analytics Tracking', () => {
    test('Should track interaction on component mount', () => {
      renderApp();

      expect(mockInteraction).toHaveBeenCalledWith('partnerships.pure_platform_signup', {
        merchantId: 'test_merchant_id',
        lpVariant: null,
        lpFold: null,
      });
    });

    test('Should call trackHubs on component mount', () => {
      renderApp();

      expect(window.trackHubs).toHaveBeenCalledWith({
        name: 'update_property',
        data: {
          partner_signup_start: true,
        },
      });
    });
  });

  describe('Step Navigation', () => {
    test('Should navigate from S0 to S1 when "Yes, I\'m a Fit" is clicked', async () => {
      renderApp({ disableClose: false });

      const fitButton = screen.getByRole('button', { name: "Yes, I'm a Fit" });
      await userEvent.click(fitButton);

      // Should navigate to S1
      await waitFor(() => {
        expect(screen.getByText(/Awesome, Join the Razorpay Partner/)).toBeInTheDocument();
      });
    });

    test('Should show "Sign up as a Partner" button in S1', async () => {
      renderApp({ disableClose: false });

      const fitButton = screen.getByRole('button', { name: "Yes, I'm a Fit" });
      await userEvent.click(fitButton);

      await waitFor(() => {
        expect(screen.getByRole('button', { name: 'Sign up as a Partner' })).toBeInTheDocument();
      });
    });
  });

  describe('TnC Footer', () => {
    test('Should render TnC footer in S1 step', async () => {
      renderApp({ disableClose: true });

      // S1 should have TnC footer
      expect(screen.getByTestId('tnc-footer')).toBeInTheDocument();
    });
  });

  describe('Error Handling', () => {
    test('Should handle component rendering without errors', () => {
      expect(() => renderApp()).not.toThrow();
    });

    test('Should handle missing tracking prop gracefully', () => {
      expect(() => {
        render(
          <PartnerOnboarding
            disableClose={false}
            location={defaultLocation}
            closeModal={mockCloseModal}
          />,
          {
            initialState: {
              session: {
                org: {
                  custom_code: 'rzp',
                  business_name: 'Test Business',
                },
                user: {
                  ...userData,
                  merchant: { id: 'test_merchant_id' },
                },
              },
            },
          },
        );
      }).not.toThrow();
    });
  });
});
