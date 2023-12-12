import '@testing-library/jest-dom/extend-expect';
import TurnoffNotify from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/TurnoffNotify';
import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';

const onCloseMock = jest.fn();
const updateNotificationFeatureMock = jest.fn();

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

describe('WhatsappSetup - TurnoffNotify', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = () => {
    const defaultProps = {
      modalState: {
        isOpen: true,
        info: {},
      },
      onClose: onCloseMock,
      updateNotificationFeature: updateNotificationFeatureMock,
    };
    render(<TurnoffNotify {...defaultProps} />);
  };

  test('should render turn off notify modal', () => {
    renderApp();
    expect(
      screen.getByText(
        'If you turn off notifications, customers will no longer receive any updates for Payment Links on Whatsapp',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Cancel',
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Yes, I understand',
      }),
    ).toBeInTheDocument();
  });

  test('should render turn off notify modal in mobile', () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    expect(
      screen.getByText(
        'If you turn off notifications, customers will no longer receive any updates for Payment Links on Whatsapp',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Cancel',
      }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'Yes, I understand',
      }),
    ).toBeInTheDocument();
  });

  test('should show cancek button and call onClose on click', async () => {
    renderApp();
    const cancelBtn = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledWith({ action: 'close' });
    });
  });

  test('should show cancek button and call onClose on click in mobile', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    const cancelBtn = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    await waitFor(() => {
      expect(onCloseMock).toHaveBeenCalledWith({ action: 'close' });
    });
  });

  test('should show continue button and update notification on click', async () => {
    renderApp();
    const continueBtn = screen.getByRole('button', {
      name: 'Yes, I understand',
    });
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(updateNotificationFeatureMock).toHaveBeenCalled();
    });
  });

  test('should show continue button and update notification on click on mobile', async () => {
    (useMobile as jest.Mock).mockImplementation(() => true);
    renderApp();
    const continueBtn = screen.getByRole('button', {
      name: 'Yes, I understand',
    });
    expect(continueBtn).toBeInTheDocument();
    await userEvent.click(continueBtn);
    await waitFor(() => {
      expect(updateNotificationFeatureMock).toHaveBeenCalled();
    });
  });
});
