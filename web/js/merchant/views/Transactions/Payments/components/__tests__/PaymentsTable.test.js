import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentsTable from 'merchant/views/Transactions/Payments/components/PaymentsTable';
import { render, screen, userEvent } from 'test-utils';
import { paymentMethod, description, paymentReceiverType } from 'common/ui/item/pair';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

describe('PaymentsTable', () => {
  const payment = {
    id: 'payment_id_1',
    status: 'authorized',
    method: 'upi_transfer',
    notes: {
      noteKey1: 'Payment note for key 1',
    },
    provider: 'payment provider',
    gateway_provider: 'payment gateway provider',
    transaction: 'payment transaction',
    optimizer_provider: 'Razorpay',
    description: 'payment description',
    customer_fee: '123',
    customer_fee_gst: '12',
    order_id: 'order_id_1',
    invoice_id: 'paymentInvoiceID',
    amount: 20000,
    email: 'paymentEmail@gmail.com',
    currency: 'INR',
    createdAt: 1665222570,
  };
  const defaultProps = {
    items: [
      { ...payment },
      { ...payment, id: 'payment_id_2', order_id: 'order_id_2', notes: null },
      {
        ...payment,
        id: 'payment_id_3',
        order_id: null,
        notes: {
          order_id: 'order_id_3',
        },
      },
      {
        ...payment,
        id: 'payment_id_4',
        order_id: null,
        notes: {
          some_order_id: 'order_id_4',
          receiver_type: 'offline',
        },
      },
    ],
    location: {},
    selfServeActionsPage: 'Transactions.Payments',
  };

  const renderApp = ({ props } = {}) => {
    return render(<PaymentsTable {...defaultProps} {...props} />);
  };

  test('should render payment table when items are present', () => {
    renderApp();
    expect(screen.getByRole('table')).toBeInTheDocument();
    [
      'Payment Id',
      'Razorpay Order Id',
      'Order Id',
      'Amount',
      'Email',
      'Contact',
      'Created At',
      'Status',
    ].forEach((paymentColumn) =>
      expect(screen.getByRole('columnheader', { name: paymentColumn })).toBeInTheDocument(),
    );
  });

  test('should not render payments when no items are present', () => {
    renderApp({
      props: {
        items: [],
      },
    });
    expect(screen.getByText('No Payments Found!')).toBeInTheDocument();
  });

  test('should render payment provider column when payment optimizer is enabled', () => {
    renderApp({
      props: {
        user: {
          isSingleReconEnabled: true,
          isOptimizerEnabled: true,
        },
      },
    });
    ['Payment Provider'].forEach((paymentColumn) =>
      expect(screen.getByRole('columnheader', { name: paymentColumn })).toBeInTheDocument(),
    );
  });

  test('should render Receiver Type columns', () => {
    renderApp({
      props: {
        paymentColumns: [paymentReceiverType],
      },
    });

    expect(screen.getByRole('columnheader', { name: 'Receiver Type' })).toBeInTheDocument();
    expect(screen.getByText('Offline')).toBeInTheDocument();
  });

  test('should render custom payment columns', () => {
    renderApp({
      props: {
        paymentColumns: [paymentMethod, description],
      },
    });
    ['Payment Method', 'Description'].forEach((paymentColumn) =>
      expect(screen.getByRole('columnheader', { name: paymentColumn })).toBeInTheDocument(),
    );
  });

  // TODO there is a problem with will pick it soon
  test.skip('should call analytics event when order id link is clicked', async () => {
    renderApp();
    await userEvent.click(screen.getAllByRole('link', { name: /order_id_1/ })[0]);
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
      actionName: 'Initiated',
      objectName: 'Self Serve',
      properties: {
        page: 'Order Listing',
        screen: 'Transactions',
        selfServeAction: 'Order Details Fetched',
        source: 'Dashboard',
      },
      screen: 'Transactions',
      toLumberjack: true,
    });
  });
});
