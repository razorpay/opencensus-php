import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, fireEvent } from 'test-utils';
import {
  defaultProps,
  App,
} from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentDetails';
import { analyticsTrack } from 'common/utils/analytics';
import ShowWhen from 'merchant/components/ShowWhen';
import store from 'merchant/store';

const stateSpy = jest.spyOn(store, 'getState');

describe('PaymentDetails', () => {
  beforeEach(() => {
    stateSpy.mockClear();
  });
  test('should call onCreateTransfer when clicked on Create Transfer', () => {
    ShowWhen.mockImplementation(({ children }) => <div>{children}</div>);
    render(<App />);
    fireEvent.click(screen.getByText('Create Transfer'));
    expect(analyticsTrack).toHaveBeenCalledWith({
      actionName: 'clicked',
      objectName: 'payments detail transfer',
      properties: {
        location: 'payments',
      },
      screen: 'payments',
      toLumberjack: true,
    });
  });

  test('should call onUPIClick callback when clicked on UPI', () => {
    render(<App />);
    fireEvent.click(screen.getByText('UPI'));
    expect(analyticsTrack).toHaveBeenCalledWith({
      actionName: 'clicked',
      objectName: 'payments detail method viewed',
      properties: {
        location: 'payments',
      },
      screen: 'payments Details',
      toLumberjack: true,
    });
  });

  test('should call onRefundDetailsToggleClick when clicked on Refund Details Toggle', () => {
    render(<App />);
    fireEvent.click(screen.getByText('Refund Details Toggle'));
    expect(defaultProps.onRefundDetailsToggleClick).toHaveBeenCalled();
  });

  test('should call onUpdateReferenceId when clicked on Update Refrence Id', () => {
    render(<App />);
    fireEvent.click(screen.getByText('Update Refrence Id'));
    expect(defaultProps.onUpdateReferenceId).toHaveBeenCalled();
  });

  test('should render payment description', () => {
    render(<App />);
    expect(screen.getByText(defaultProps.payment.description)).toBeInTheDocument();
  });

  describe('Payment disputes', () => {
    test('should render payment disputes', () => {
      render(<App />);
      expect(screen.getByText('PaymentDisputes')).toBeInTheDocument();
    });

    test('should not render payment disputes when no disputes present', () => {
      render(<App payment={{ ...defaultProps.payment, disputes: {} }} />);
      expect(screen.queryByText('PaymentDisputes')).not.toBeInTheDocument();
    });
  });

  test('should render total convenience fee', () => {
    render(<App />);
    expect(screen.getByText('Total Convenience Fee')).toBeInTheDocument();
  });

  describe('Fee bearer', () => {
    test('should render fee bearer when fee_bearer is platform', () => {
      render(<App payment={{ ...defaultProps.payment, fee_bearer: 'platform' }} />);
      expect(screen.getByText('You are the fee bearer for this payment')).toBeInTheDocument();
    });

    test('should render fee bearer when fee_bearer is not platform', () => {
      render(<App />);
      expect(
        screen.getByText('The customer has paid the fees for this payment'),
      ).toBeInTheDocument();
    });

    test('should render fee bearer when fee_bearer is not platform', () => {
      render(<App />);
      expect(
        screen.getByText('The customer has paid the fees for this payment'),
      ).toBeInTheDocument();
    });
  });

  describe('Total Fee', () => {
    test('should render business name in fee label', () => {
      render(
        <App
          payment={{
            ...defaultProps.payment,
            fee: 898989,
          }}
          org={{ business_name: 'Test Business Name' }}
        />,
      );
      expect(screen.queryByText('Test Business Name Fee -')).toBeInTheDocument();
    });

    test("should hide Razorpay Text Link if feature flag doesn't exist", () => {
      stateSpy.mockReturnValueOnce({
        session: {
          org: {
            features: ['hide_razorpay_text_link'],
          },
        },
      });

      render(<App payment={{ ...defaultProps.payment, fee: 898989 }} />);
      expect(screen.queryByText('Razorpay Fee -')).not.toBeInTheDocument();
    });

    test('should render fee when transaction is not platform and is indian', () => {
      render(<App payment={{ ...defaultProps.payment, fee: 898989 }} />);
      expect(screen.getByText('8,989')).toBeInTheDocument();
    });

    test('should render fee when transaction is not platform and is international', () => {
      render(
        <App
          payment={{
            ...defaultProps.payment,
            fee_bearer: 'customer',
            fee_currency_amount: 696969,
            fee: 898989,
          }}
        />,
      );
      expect(screen.getByText('6,969')).toBeInTheDocument();
    });
  });

  describe('Payment order id', () => {
    test('should render payment order id', () => {
      render(<App />);
      expect(screen.getByText(defaultProps.payment.order_id)).toBeInTheDocument();
    });

    test('should not render payment order id when not present', () => {
      render(<App payment={{ ...defaultProps.payment, order_id: null }} />);
      expect(screen.queryByText(defaultProps.payment.order_id)).not.toBeInTheDocument();
    });

    test('should not render payment order id when not present', () => {
      render(<App payment={{ ...defaultProps.payment, order_id: null }} />);
      expect(screen.queryByText(defaultProps.payment.order_id)).not.toBeInTheDocument();
    });
  });

  describe('Payment invoice id', () => {
    test('should render payment invoice id', () => {
      render(<App />);
      expect(screen.getByText(defaultProps.payment.invoice_id)).toBeInTheDocument();
    });

    test('should not render payment invoice id when not present', () => {
      render(<App payment={{ ...defaultProps.payment, invoice_id: null }} />);
      expect(screen.queryByText(defaultProps.payment.invoice_id)).not.toBeInTheDocument();
    });
  });

  describe('Payment notes', () => {
    test('should render payment notes', () => {
      render(<App />);
      expect(screen.getByText(defaultProps.payment.notes.noteKey1)).toBeInTheDocument();
    });

    test('should not render payment notes when not present', () => {
      render(<App payment={{ ...defaultProps.payment, notes: {} }} />);
      expect(screen.queryByText(defaultProps.payment.notes.noteKey1)).not.toBeInTheDocument();
    });
  });
});
