import React from 'react';
import * as useMobile from '@dashboard/shared-ui/hooks/useMobile';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import PaymentsTable from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsTable';
import 'jest-location-mock';

const generatePayment = ({ method, status = 'authorized', cardType = 'debit' }) => {
  const id = `payment_id_${Math.floor(Math.random() * 100)}`;
  const order_id = `order_id_${Math.floor(Math.random() * 100)}`;

  const random = Math.floor(Math.random() * 3);
  let arn = null;
  let rrn = null;
  if (random === 1) {
    arn = `arn_${Math.floor(Math.random() * 100)}`;
  } else if (random === 2) {
    rrn = `rrn_${Math.floor(Math.random() * 100)}`;
  }
  const source_channel = Math.random() < 0.5 ? 'online' : 'in_person';

  return {
    id,
    order_id,
    status,
    method,
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
    invoice_id: 'paymentInvoiceID',
    amount: 20000,
    email: 'paymentEmail@gmail.com',
    currency: 'INR',
    createdAt: 1665222570,
    wallet: 'payment wallet',
    card: {
      type: cardType,
    },
    acquirer_data: {
      auth_code: '036317',
      arn,
      rrn,
    },
    source_channel,
  };
};

const paymentMethods = [
  'card',
  'wallet',
  'cod',
  'bank_transfer',
  'upi',
  'emi',
  'offline',
  'emandate',
  'netbanking',
  'intl_bank_transfer',
  'paylater',
  'app',
  'aeps',
  'fpx',
  'transfer',
  'cardless_emi',
  'nach',
  'paynow',
  'unselected',
];
export const mockFetchPaymentItems = (n = 25) => {
  const items = [
    generatePayment({
      method: 'card',
      cardType: null,
      status: 'unknown',
    }),
  ];
  for (let i = 0; i < n - 1; i++) {
    items.push(
      generatePayment({
        method: paymentMethods[i % paymentMethods.length],
      }),
    );
  }
  return items;
};

export const useMobileSpy = jest.spyOn(useMobile, 'useMobile');

export const renderApp = (props = {}) => {
  return render(<PaymentsTable location={{}} {...props} />);
};
