import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import {
  render,
  screen,
  waitFor,
  fireEvent,
  checkIfComponentIsEmpty,
  COMPONENT_WRAPPER_TESTID,
} from 'test-utils';
import {
  AppWithRouter,
  defaultProps,
  showNotificationSpy,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentReceipt';

describe('PaymentReceipt', () => {
  test('should not render payment receipt details when showReceiptActions is false', () => {
    render(<AppWithRouter hash="" />);
    checkIfComponentIsEmpty();
  });

  test('should render payment receipt details when showReceiptActions is true', async () => {
    render(<AppWithRouter />);
    await waitFor(() => {
      expect(screen.getByText('Reference ID: IN1234567890')).toBeInTheDocument();
    });
  });

  describe('Send button', () => {
    test('should send receipt when send button is clicked', async () => {
      render(<AppWithRouter />);
      await waitFor(() => {
        expect(screen.getByTestId(COMPONENT_WRAPPER_TESTID)).not.toBeEmptyDOMElement();
      });
      fireEvent.click(screen.getByText('Send'));
      await waitFor(() => {
        expect(screen.getByText('Sending..')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(screen.getByText('Receipt is sent successfully')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(defaultProps.onUpdateReferenceId).toHaveBeenCalledWith();
      });
    });

    test('should show send receipt errors when send button is clicked and some server error occurs', async () => {
      render(
        <AppWithRouter
          payment={{
            id: '1234',
          }}
        />,
      );
      await waitFor(() => {
        expect(screen.getByText('Send')).toBeInTheDocument();
      });
      fireEvent.click(screen.getByText('Send'));
      await waitFor(() => {
        expect(screen.getByText('Sending..')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: '',
        });
      });
    });

    test('should send custom receipt when send button is clicked', async () => {
      render(
        <AppWithRouter
          payment={{
            id: '123',
          }}
        />,
      );
      let sendButton;
      await waitFor(() => {
        sendButton = screen.getByRole('button', {
          name: 'Send',
        });
        expect(sendButton).toBeEnabled();
      });
      fireEvent.click(sendButton);
      const receiptInput = screen.getByRole('textbox');
      fireEvent.change(receiptInput, { target: { value: 'IN1234567890' } });
      const sendButton2 = screen.getByRole('button', {
        name: 'Send',
      });
      expect(sendButton2).toBeEnabled();
      fireEvent.click(sendButton2);
      await waitFor(() => {
        expect(screen.getByText('Sending...')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(screen.getByText('Receipt is sent successfully')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(defaultProps.onUpdateReferenceId).toHaveBeenCalledWith();
      });
    });
  });
  describe('Download button', () => {
    test('should download receipt when download button is clicked', async () => {
      render(<AppWithRouter />);
      await waitFor(() => {
        expect(screen.getByText('Download')).toBeInTheDocument();
      });
      fireEvent.click(screen.getByText('Download'));
      await waitFor(() => {
        expect(screen.getByText('Receipt is downloading...')).toBeInTheDocument();
        expect(window.location.href).toBe('https://www.razorpay.com/receipt');
      });
    });

    test('should download custom receipt when download button is clicked', async () => {
      render(
        <AppWithRouter
          payment={{
            id: '123',
          }}
        />,
      );
      await waitFor(() => {
        expect(screen.getByText('Download')).toBeInTheDocument();
      });
      fireEvent.click(screen.getByText('Download'));
      const receiptInput = screen.getByRole('textbox');
      fireEvent.change(receiptInput, { target: { value: 'IN1234567890' } });
      fireEvent.click(screen.getByText('Download'));
      await waitFor(() => {
        expect(screen.getByText('Receipt is downloading...')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(defaultProps.onUpdateReferenceId).toHaveBeenCalledWith();
      });
    });

    test('should show download custom receipt errors when send download is clicked and some server error occurs', async () => {
      render(
        <AppWithRouter
          payment={{
            id: '123',
          }}
        />,
      );
      await waitFor(() => {
        expect(screen.getByText('Download')).toBeInTheDocument();
      });
      fireEvent.click(screen.getByText('Download'));
      const receiptInput = screen.getByRole('textbox');
      fireEvent.change(receiptInput, { target: { value: 'IN1234567891' } });
      fireEvent.click(screen.getByText('Download'));
      await waitFor(() => {
        expect(showNotificationSpy).toHaveBeenCalledWith({
          type: 'error',
          message: '',
        });
      });
    });
  });

  describe('Cancel button', () => {
    test('should not show custom receipt input field when cancel button is clicked', async () => {
      render(
        <AppWithRouter
          payment={{
            id: '123',
          }}
        />,
      );
      await waitFor(() => {
        expect(screen.getByText('Download')).toBeInTheDocument();
      });
      fireEvent.click(screen.getByText('Download'));
      fireEvent.click(screen.getByText('Cancel'));
      await waitFor(() => {
        const receiptInput = screen.queryByRole('textbox');
        expect(receiptInput).not.toBeInTheDocument();
      });
    });
  });
});
