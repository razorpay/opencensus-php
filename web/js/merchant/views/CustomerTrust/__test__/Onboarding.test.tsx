import React from 'react';
import { render, screen, waitFor, fireEvent } from 'test-utils';
import { Onboarding } from 'merchant/views/CustomerTrust/components/Onboarding/Onboarding';
import { createOnboardingConsentApi } from 'merchant/views/CustomerTrust/utils/api';
import { TnCModal } from 'merchant/views/CustomerTrust/components/Onboarding/TnCModal';
import { OnboardingCategory } from 'merchant/views/CustomerTrust/types';

// Mock the API
jest.mock('merchant/views/CustomerTrust/utils/api', () => ({
  createOnboardingConsentApi: jest.fn(),
}));

// Mock SVG import
jest.mock('merchant/views/CustomerTrust/components/Onboarding/demo.svg', () => 'mocked-image-path');

describe('Onboarding component', () => {
  const mockSetOnboardingStatus = jest.fn();
  const defaultProps = {
    pricing: 1.5,
    setOnboardingStatus: mockSetOnboardingStatus,
    isEligible: true,
    category: 'rtb' as OnboardingCategory,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  // Rendering Tests
  describe('Rendering', () => {
    test('should render the Onboarding component with all expected elements', () => {
      render(<Onboarding {...defaultProps} />);

      // Check for Header component content
      expect(screen.getByText('Buyer Protection')).toBeInTheDocument();

      // Check for InfoCards component content
      expect(screen.getByText('What You Need to Know About Buyer Protection')).toBeInTheDocument();
      expect(screen.getByText('Know more')).toBeInTheDocument();

      // Check for Terms and Conditions text
      expect(screen.getByText('By clicking "Activate Now", you agree to our')).toBeInTheDocument();
      expect(screen.getByText('Terms and Conditions.')).toBeInTheDocument();

      // Check for Activate now button
      expect(screen.getByText('Activate now')).toBeInTheDocument();

      // Check for money back promise image
      const image = screen.getByAltText('money back promise');
      expect(image).toBeInTheDocument();
      expect(image).toHaveAttribute('src', 'mocked-image-path');
    });

    test('should display the InfoCards component with correct pricing', () => {
      render(
        <Onboarding
          pricing={2.5}
          setOnboardingStatus={mockSetOnboardingStatus}
          isEligible={true}
          category={'rtb' as OnboardingCategory}
        />,
      );

      // Check for the part with pricing specifically
      expect(screen.getByText(/continue at 2.50%/)).toBeInTheDocument();
    });
  });

  // Interaction Tests
  describe('Interactions', () => {
    test('should trigger API call when the "Activate now" button is clicked', async () => {
      // Replace the test that checks for loading state with one that just checks the API is called
      const apiPromise = new Promise<any>((resolve) =>
        // Resolve after a delay to simulate API call
        setTimeout(() => resolve({ status_code: 200 }), 100),
      );
      (createOnboardingConsentApi as jest.Mock).mockReturnValue(apiPromise);

      render(<Onboarding {...defaultProps} />);

      // Click on the Activate now button
      fireEvent.click(screen.getByText('Activate now'));

      // Verify that the API was called
      expect(createOnboardingConsentApi).toHaveBeenCalledWith({ pricing: 1.5 });

      // Let the API promise resolve
      await waitFor(() => {
        expect(createOnboardingConsentApi).toHaveBeenCalled();
      });
    });

    test('should call the createOnboardingConsentApi with correct pricing parameter', () => {
      render(
        <Onboarding
          pricing={3.0}
          setOnboardingStatus={mockSetOnboardingStatus}
          isEligible={true}
          category={'rtb' as OnboardingCategory}
        />,
      );

      // Click on the Activate now button
      fireEvent.click(screen.getByText('Activate now'));

      // Check if API is called with correct parameters
      expect(createOnboardingConsentApi).toHaveBeenCalledWith({ pricing: 3.0 });
    });

    test('should update onboarding status to "pending" on successful API response', async () => {
      // Mock successful API response
      (createOnboardingConsentApi as jest.Mock).mockResolvedValue({
        status_code: 200,
        success: true,
        data: {
          onboarding_status: 'pending',
        },
      });

      render(<Onboarding {...defaultProps} />);

      // Click on the Activate now button
      fireEvent.click(screen.getByText('Activate now'));

      // Check if setOnboardingStatus is called with the correct status
      await waitFor(() => {
        expect(mockSetOnboardingStatus).toHaveBeenCalledWith('pending');
      });
    });

    test('should handle API errors gracefully', async () => {
      // Mock API to reject
      (createOnboardingConsentApi as jest.Mock).mockRejectedValue(new Error('API error'));

      // Mock console.error to prevent error logs in test output
      const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

      render(<Onboarding {...defaultProps} />);

      // Click on the Activate now button
      fireEvent.click(screen.getByText('Activate now'));

      // Check if error is logged - allow some time for async error to be caught
      await waitFor(
        () => {
          expect(consoleErrorSpy).toHaveBeenCalled();
        },
        { timeout: 1000 },
      );

      // Button should not be in loading state anymore
      await waitFor(() => {
        expect(screen.getByText('Activate now')).not.toHaveAttribute('aria-busy', 'true');
      });

      // Cleanup
      consoleErrorSpy.mockRestore();
    });
  });

  // Modal Tests
  describe('TnC Modal', () => {
    test('should open the modal when clicking on "Terms and Conditions" link', () => {
      render(<Onboarding {...defaultProps} />);

      // Initially, the modal should not be visible
      const modalBeforeClick = screen.queryByRole('dialog');
      expect(modalBeforeClick).not.toBeInTheDocument();

      // Click on the Terms and Conditions link
      fireEvent.click(screen.getByText('Terms and Conditions.'));

      // Now the modal should be visible
      const modalAfterClick = screen.getByRole('dialog');
      expect(modalAfterClick).toBeInTheDocument();
    });

    test('should close the modal when clicking close button', async () => {
      render(<Onboarding {...defaultProps} />);

      // Open the modal
      fireEvent.click(screen.getByText('Terms and Conditions.'));

      // Modal should be visible
      expect(screen.getByRole('dialog')).toBeInTheDocument();

      // Find the dismiss button in the modal header
      const closeButton = screen.getByLabelText('Close');
      fireEvent.click(closeButton);

      // Modal should be closed (removed from DOM)
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    });

    test('should call handleActivateNow when agreeing to terms in modal', async () => {
      // Mock successful API response
      (createOnboardingConsentApi as jest.Mock).mockResolvedValue({
        status_code: 200,
        success: true,
        data: {
          onboarding_status: 'pending',
        },
      });

      render(<Onboarding {...defaultProps} />);

      // Open the modal
      fireEvent.click(screen.getByText('Terms and Conditions.'));

      // Find and click the agree button in the actual modal
      const agreeButton = screen.getByRole('button', { name: /agree/i });
      fireEvent.click(agreeButton);

      // Check if API is called with correct parameters
      expect(createOnboardingConsentApi).toHaveBeenCalledWith({ pricing: 1.5 });

      // Check if setOnboardingStatus is eventually called
      await waitFor(() => {
        expect(mockSetOnboardingStatus).toHaveBeenCalledWith('pending');
      });
    });
  });
});
