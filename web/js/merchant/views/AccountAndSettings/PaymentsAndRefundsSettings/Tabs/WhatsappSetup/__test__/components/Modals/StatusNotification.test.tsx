import '@testing-library/jest-dom/extend-expect';
import StatusNotification from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/StatusNotification';
import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import { Config } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/components/Modals/StatusNotification/StatusNotification';
import { BusinessServiceProvider } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';

const onCloseMock = jest.fn();

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn(() => false),
}));

describe('WhatsappSetup - StatusNotification', () => {
  beforeEach(() => {
    (useMobile as jest.Mock).mockReset();
  });

  const renderApp = ({ type }) => {
    const defaultProps = {
      modalState: {
        isOpen: true,
        info: {
          type,
        },
      },
      onClose: onCloseMock,
      businessProvider: BusinessServiceProvider[0],
    };
    render(<StatusNotification {...defaultProps} />);
  };

  describe('type - initate', () => {
    test('should show status for initiate type', () => {
      renderApp({
        type: 'initiate',
      });
      expect(screen.getByRole('heading', { name: Config.initiate.title })).toBeInTheDocument();
      expect(screen.getByText(Config.initiate.subtext)).toBeInTheDocument();
    });

    test('should show status for initiate type in mobile', () => {
      (useMobile as jest.Mock).mockImplementation(() => true);
      renderApp({
        type: 'initiate',
      });
      expect(screen.getByRole('heading', { name: Config.initiate.title })).toBeInTheDocument();
      expect(screen.getByText(Config.initiate.subtext)).toBeInTheDocument();
    });

    test('should call onClose passed in prop on Close click', async () => {
      renderApp({
        type: 'initiate',
      });
      const closeBtn = screen.getByRole('button', { name: 'Close' });
      expect(closeBtn).toBeInTheDocument();
      await userEvent.click(closeBtn);
      await waitFor(() => {
        expect(onCloseMock).toHaveBeenCalled();
      });
    });

    test('should call onClose passed in prop on Close click in mobile', async () => {
      (useMobile as jest.Mock).mockImplementation(() => true);
      renderApp({
        type: 'initiate',
      });
      const closeBtn = screen.getByRole('button', { name: 'Close' });
      expect(closeBtn).toBeInTheDocument();
      await userEvent.click(closeBtn);
      await waitFor(() => {
        expect(onCloseMock).toHaveBeenCalled();
      });
    });
  });

  describe('type - success', () => {
    test('should show status for success type', () => {
      renderApp({
        type: 'success',
      });
      expect(screen.getByRole('heading', { name: Config.success.title })).toBeInTheDocument();
      expect(screen.getByText(Config.success.subtext)).toBeInTheDocument();
    });

    test('should show status for success type in mobile', () => {
      (useMobile as jest.Mock).mockImplementation(() => true);
      renderApp({
        type: 'success',
      });
      expect(screen.getByRole('heading', { name: Config.success.title })).toBeInTheDocument();
      expect(screen.getByText(Config.success.subtext)).toBeInTheDocument();
    });

    test('should call onClose passed in prop on Close click', async () => {
      renderApp({
        type: 'success',
      });
      const closeBtn = screen.getByRole('button', { name: 'Close' });
      expect(closeBtn).toBeInTheDocument();
      await userEvent.click(closeBtn);
      await waitFor(() => {
        expect(onCloseMock).toHaveBeenCalled();
      });
    });

    test('should call onClose passed in prop on Close click on mobile', async () => {
      (useMobile as jest.Mock).mockImplementation(() => true);
      renderApp({
        type: 'success',
      });
      const closeBtn = screen.getByRole('button', { name: 'Close' });
      expect(closeBtn).toBeInTheDocument();
      await userEvent.click(closeBtn);
      await waitFor(() => {
        expect(onCloseMock).toHaveBeenCalled();
      });
    });
  });
});
