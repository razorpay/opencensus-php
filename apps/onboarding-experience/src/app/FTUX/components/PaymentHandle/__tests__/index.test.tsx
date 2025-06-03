import React from 'react';
import { screen, fireEvent, act } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import PaymentHandle from '../index';
import SettlementsGuideModal from '@FTUX/modals/SettlementsGuideModal';

// Mock dependencies
jest.mock('@FTUX/modals/SettlementsGuideModal', () => {
  return jest.fn(() => (
    <div data-testid="settlements-guide-modal">Mocked SettlementsGuideModal</div>
  ));
});

jest.mock('../PaymentHandleActions', () => {
  return jest.fn(() => <div data-testid="payment-handle-actions">Mocked PaymentHandleActions</div>);
});

describe('PaymentHandle Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders the component with correct heading', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    expect(screen.getByText('Use your Payment Handle')).toBeInTheDocument();
  });

  it('renders the description text correctly', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    expect(
      screen.getByText(
        'Use this personalised link to accept payments instantly from your customers.',
      ),
    ).toBeInTheDocument();
  });

  it('renders the PaymentHandleActions component', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    expect(screen.getByTestId('payment-handle-actions')).toBeInTheDocument();
  });

  it('renders the settlement information text', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    expect(screen.getByText(/Your money will be credited to your account/)).toBeInTheDocument();
    expect(screen.getByText('settlement schedule')).toBeInTheDocument();
  });

  it('opens the SettlementsGuideModal when the link is clicked', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    // Click the link to open modal
    await act(async () => {
      const link = screen.getByText('settlement schedule');
      fireEvent.click(link);
    });

    // Check if modal is rendered
    expect(screen.getByTestId('settlements-guide-modal')).toBeInTheDocument();
    expect(SettlementsGuideModal).toHaveBeenCalledWith(
      expect.objectContaining({
        onDismiss: expect.any(Function),
      }),
      expect.anything(),
    );
  });

  it('closes the SettlementsGuideModal when onDismiss is called', async () => {
    let onDismissHandler;

    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    // Click the link to open modal
    await act(async () => {
      const link = screen.getByText('settlement schedule');
      fireEvent.click(link);
    });

    // Store the onDismiss function from the mock call
    onDismissHandler = (SettlementsGuideModal as jest.Mock).mock.calls[0][0].onDismiss;

    // Call onDismiss to close the modal within act
    await act(async () => {
      onDismissHandler();
    });

    // Check that modal is no longer rendered
    expect(screen.queryByTestId('settlements-guide-modal')).not.toBeInTheDocument();
  });

  it('renders mobile layout with image container on small screens', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    // Mobile image container should be present (but hidden on large screens via CSS)
    const mobileImage = screen.getAllByAltText('payment-handle')[0];
    expect(mobileImage).toBeInTheDocument();
  });

  it('renders desktop layout with image container on large screens', async () => {
    await act(async () => {
      renderWithWrappers(<PaymentHandle />);
    });

    // Desktop image container should be present (but hidden on small screens via CSS)
    const desktopImage = screen.getAllByAltText('payment-handle')[1];
    expect(desktopImage).toBeInTheDocument();
  });
});
