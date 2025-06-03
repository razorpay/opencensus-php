import React from 'react';
import {
  screen,
  fireEvent,
  act,
  cleanup,
} from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import SettlementsGuideModal from '../SettlementsGuideModal';
import { SETTLEMENTS_GUIDE_MODAL_DATA } from '@FTUX/constants/payments';
import { isMobileDevice } from '@libs/shared-utils';

// Mock dependencies
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

describe('SettlementsGuideModal Component', () => {
  const mockOnDismiss = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false);
  });

  // Clean up after each test to prevent memory leaks and act warnings
  afterEach(() => {
    cleanup();
    jest.useRealTimers();
  });

  test('renders the modal with correct title', async () => {
    await act(async () => {
      renderWithWrappers(<SettlementsGuideModal onDismiss={mockOnDismiss} />);
    });

    expect(screen.getByText('Settlements Guide')).toBeInTheDocument();
  });

  test('renders the settlements description text', async () => {
    await act(async () => {
      renderWithWrappers(<SettlementsGuideModal onDismiss={mockOnDismiss} />);
    });

    expect(
      screen.getByText(
        /Settlements are how payments collected from your customers are deposited in your bank account./,
        { exact: false },
      ),
    ).toBeInTheDocument();

    expect(
      screen.getByText(/Settlements are processed within/, { exact: false }),
    ).toBeInTheDocument();
  });

  test('calls onDismiss when the close button is clicked', async () => {
    await act(async () => {
      renderWithWrappers(<SettlementsGuideModal onDismiss={mockOnDismiss} />);
    });

    await act(async () => {
      const closeButton = screen.getByText('Got it');
      fireEvent.click(closeButton);
    });

    expect(mockOnDismiss).toHaveBeenCalledTimes(1);
  });

  test('renders the guide link', async () => {
    await act(async () => {
      renderWithWrappers(<SettlementsGuideModal onDismiss={mockOnDismiss} />);
    });

    expect(screen.getByText('View complete guide')).toBeInTheDocument();
  });

  test('renders with mobile layout when on mobile device', async () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    await act(async () => {
      renderWithWrappers(<SettlementsGuideModal onDismiss={mockOnDismiss} />);
    });

    // The test should still pass with mobile layout
    expect(screen.getByText('Settlements Guide')).toBeInTheDocument();
  });
});
