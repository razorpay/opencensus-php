import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import '@testing-library/jest-dom';
import { Provider } from 'react-redux';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { storeWithInitialState } from 'merchant/store';

import PaymentPagePaymentReceipt from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentPagePaymentReceipt';

import {
  getReceiptDetails,
  sendReceipt,
  saveReceipt,
} from 'merchant/views/PaymentPages/PaymentPages/model';

import * as NotificationsActions from 'merchant_common/reducers/notifications';

jest.mock('merchant/views/PaymentPages/PaymentPages/model', () => ({
  getReceiptDetails: jest.fn(),
  sendReceipt: jest.fn(),
  saveReceipt: jest.fn(),
}));

const initProps = {
  paymentId: '12345',
};

const renderApp = ({ state, ...props } = {}) => {
  return render(
    <BladeProvider themeTokens={bladeTheme}>
      <Provider store={storeWithInitialState({ ...state })}>
        <PaymentPagePaymentReceipt {...initProps} {...props} />
      </Provider>
    </BladeProvider>,
  );
};

describe('PaymentPagePaymentReceipt Component', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

  beforeEach(() => {
    showNotificationSpy.mockClear();
  });

  it('should display a spinner while loading receipt details', async () => {
    getReceiptDetails.mockResolvedValue({ data: {} });

    renderApp();

    expect(screen.getByLabelText('receipt-details-loader')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.queryByLabelText('receipt-details-loader')).not.toBeInTheDocument();
    });
  });

  it('should display receipt details when available', async () => {
    getReceiptDetails.mockResolvedValue({
      data: {
        invoice_id: 'INV123',
        receipt: 'REC123',
        receipt_download_url: 'http://example.com/receipt.pdf',
      },
    });

    renderApp();

    await waitFor(() => {
      expect(screen.getByText(/Reference ID: REC123/i)).toBeInTheDocument();
    });

    expect(screen.getByRole('button', { name: /Send/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Download/i })).toBeInTheDocument();
  });

  it('should show a placeholder if no receipt details are available', async () => {
    getReceiptDetails.mockResolvedValue({ data: {} });

    renderApp();

    await waitFor(() => {
      expect(screen.getByText('--')).toBeInTheDocument();
    });
  });

  it('should call sendReceipt and show notification when "Send" button is clicked', async () => {
    getReceiptDetails.mockResolvedValue({
      data: {
        invoice_id: 'INV123',
        receipt: 'REC123',
        receipt_download_url: 'http://example.com/receipt.pdf',
      },
    });
    sendReceipt.mockResolvedValue({ data: { success: true } });

    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Send/i })).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: /Send/i }));

    await waitFor(() => {
      expect(sendReceipt).toHaveBeenCalledWith(initProps.paymentId, 'REC123');
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Receipt sent successfully.',
      });
    });
  });

  it('should open the download URL when "Download" button is clicked', async () => {
    global.window = Object.create(window);
    const url = 'http://example.com/receipt.pdf';
    Object.defineProperty(window, 'location', {
      value: {
        href: '',
      },
      writable: true,
    });

    getReceiptDetails.mockResolvedValue({
      data: {
        invoice_id: 'INV123',
        receipt: 'REC123',
        receipt_download_url: url,
      },
    });

    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Download/i })).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: /Download/i }));

    await waitFor(() => {
      expect(window.location.href).toBe(url);
    });
  });

  it('should show error notification if sendReceipt fails', async () => {
    getReceiptDetails.mockResolvedValue({
      data: {
        invoice_id: 'INV123',
        receipt: 'REC123',
        receipt_download_url: 'http://example.com/receipt.pdf',
      },
    });
    sendReceipt.mockRejectedValue({
      errors: ['Network error'],
    });

    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Send/i })).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: /Send/i }));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: 'Network error',
      });
    });
  });

  it('should show input text container and call sendReceipt and show notification when "Send" button is clicked', async () => {
    getReceiptDetails.mockResolvedValue({
      data: {
        invoice_id: 'INV123',
        receipt_download_url: 'http://example.com/receipt.pdf',
      },
    });
    sendReceipt.mockResolvedValue({ data: { success: true } });

    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Send/i })).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: /Send/i }));

    await waitFor(() => {
      expect(screen.getByText('Reference ID')).toBeInTheDocument();
    });

    const textInput = screen.getByRole('textbox', {
      name: 'Reference ID',
    });

    expect(textInput).toBeInTheDocument();

    await userEvent.type(textInput, 'test_id');

    await userEvent.click(screen.getByRole('button', { name: /Send/i }));

    await waitFor(() => {
      expect(sendReceipt).toHaveBeenCalledWith(initProps.paymentId, 'test_id');
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Receipt sent successfully.',
      });
    });
  });

  it('should show input text container and call sendReceipt and show notification when "Send" button is clicked', async () => {
    getReceiptDetails.mockResolvedValue({
      data: {
        invoice_id: 'INV123',
        receipt_download_url: 'http://example.com/receipt.pdf',
      },
    });
    saveReceipt.mockResolvedValue({ success: true });

    renderApp();

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /Download/i })).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: /Download/i }));

    await waitFor(() => {
      expect(screen.getByText('Reference ID')).toBeInTheDocument();
    });

    const textInput = screen.getByRole('textbox', {
      name: 'Reference ID',
    });

    expect(textInput).toBeInTheDocument();

    await userEvent.type(textInput, 'test_id');

    await userEvent.click(screen.getByRole('button', { name: /Save/i }));

    await waitFor(() => {
      expect(saveReceipt).toHaveBeenCalledWith(initProps.paymentId, 'test_id');
    });
  });
});
