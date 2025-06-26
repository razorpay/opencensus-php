import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import VersioningBanner from '../Index';

// Mock VersioningModal directly
jest.mock('../VersioningModal', () => ({
  __esModule: true,
  default: ({ isOpen, closeModal }: any) =>
    isOpen ? <button data-testid="close" onClick={closeModal}>Close</button> : null,
}));

jest.mock('../utils', () => ({
  ...jest.requireActual('../utils'),
  shouldOpenVersioningModal: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useLocation: () => ({ pathname: '/dashboard' }),
  useSearchParams: () => [new URLSearchParams()],
}));

const renderApp = () => render(<VersioningBanner />);

describe('VersioningBanner', () => {
  const { shouldOpenVersioningModal } = require('../utils');

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders modal when shouldOpenVersioningModal returns true', async () => {
    (shouldOpenVersioningModal as jest.Mock).mockReturnValue({ shouldOpen: true });
    renderApp();
    expect(await screen.findByTestId('close')).toBeInTheDocument();
  });

  it('does not render modal when shouldOpenVersioningModal returns false', async () => {
    (shouldOpenVersioningModal as jest.Mock).mockReturnValue({ shouldOpen: false });
    renderApp();
    expect(screen.queryByTestId('close')).toBeNull();
  });

  it('closes modal when closeModal is called', async () => {
    // Return true on first call
    (shouldOpenVersioningModal as jest.Mock)
      .mockImplementationOnce(() => ({ shouldOpen: true }));
    renderApp();
    // Modal should be open
    const closeBtn = await screen.findByTestId('close');
    expect(closeBtn).toBeInTheDocument();
    // Use userEvent for click
    await userEvent.click(closeBtn);
    // Wait for modal to close
    await waitFor(() => {
      expect(screen.queryByTestId('close')).toBeNull();
    });
  });
}); 