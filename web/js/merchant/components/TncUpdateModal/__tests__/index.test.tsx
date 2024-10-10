import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { useGetTncUpdate } from 'merchant/components/TncUpdateModal/hooks/useGetTncUpdateDetails';
import { useSaveTncUpdateAcceptance } from 'merchant/components/TncUpdateModal/hooks/useSaveTncUpdateAcceptance';
import TncUpdateModal from 'merchant/components/TncUpdateModal';
import { analyticsTrack } from 'common/utils/analytics';

jest.mock('merchant/components/TncUpdateModal/hooks/useGetTncUpdateDetails');
jest.mock('merchant/components/TncUpdateModal/hooks/useSaveTncUpdateAcceptance');
jest.mock('common/utils/analytics');

describe('TncUpdateModal', () => {
  const mockTncUpdateData = {
    show_tnc: true,
    version: '1.0.0',
  };

  beforeEach(() => {
    jest.clearAllMocks();

    (useGetTncUpdate as jest.Mock).mockReturnValue({
      data: mockTncUpdateData,
      isLoading: false,
      isError: false,
    });

    (useSaveTncUpdateAcceptance as jest.Mock).mockReturnValue({
      mutate: jest.fn().mockResolvedValue({
        success: true,
        message: 'acceptance updated successfully',
      }),
      isLoading: false,
    });
  });

  it('renders the modal and displays T&C text', () => {
    render(<TncUpdateModal />);

    expect(screen.getByText(/Razorpay terms and conditions/i)).toBeInTheDocument();
    expect(screen.getByText(/please accept our/i)).toBeInTheDocument();
    expect(screen.getByText(/read and understand them/i)).toBeInTheDocument();
  });

  it('calls analyticsTrack when modal is displayed', async () => {
    render(<TncUpdateModal />);

    await waitFor(() => {
      expect(analyticsTrack).toHaveBeenCalledWith({
        objectName: 'Razorpay Terms and Conditions',
        actionName: 'displayed',
        screen: 'tnc update modal',
        properties: {
          version: mockTncUpdateData.version,
        },
      });
    });
  });

  it('calls onAccept and saves T&C acceptance', async () => {
    render(<TncUpdateModal />);

    const acceptButton = screen.getByRole('button', { name: /Okay, Got it!/i });

    await userEvent.click(acceptButton);

    await waitFor(() => {
      expect(useSaveTncUpdateAcceptance(jest.fn).mutate).toHaveBeenCalledWith({
        accepted_version: mockTncUpdateData.version,
        accepted_at: expect.any(Number),
      });

      expect(analyticsTrack).toHaveBeenCalledWith({
        objectName: 'Razorpay T&C Okay Got it',
        actionName: 'clicked',
        screen: 'tnc update modal',
        properties: {
          version: mockTncUpdateData.version,
        },
      });
    });
  });
});
