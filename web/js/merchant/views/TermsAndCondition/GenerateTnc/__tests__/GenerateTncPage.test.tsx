/* eslint-disable @typescript-eslint/no-unused-expressions */
import React from 'react';
import 'regenerator-runtime/runtime';
import '@testing-library/jest-dom/extend-expect';
import * as TermsAndConditionDB from '../../services/TermsAndConditionDB';
import GenerateTncPage from '../index';
import TncSuccess from '../TncSuccess';
import { fireEvent, render, cleanup, waitFor, screen } from 'test-utils';

afterEach(() => {
  TermsAndConditionDB.reset();
  cleanup();
});

describe('GenerateTncPage', () => {
  it('should input value get filled and call api', () => {
    const { getAllByTestId } = render(<GenerateTncPage />, {});
    const checkbox = screen.getByTestId('ds-checkbox');
    const button = screen.getByRole('button', { name: /Generate Page/i });

    const supportEmail = getAllByTestId('ds-text-input')[0];
    const shippingPeriod = getAllByTestId('ds-text-input')[1];
    const refundRequest = getAllByTestId('ds-text-input')[2];
    const refundProcess = getAllByTestId('ds-text-input')[3];
    const warrantyPeriod = getAllByTestId('ds-text-input')[4];

    fireEvent.click(shippingPeriod);
    fireEvent.click(screen.getByText('0-2 days'));
    fireEvent.click(refundRequest);
    fireEvent.click(screen.getByText('1-2 days'));

    fireEvent.change(supportEmail, { target: { value: 'abc@gmail.com' } });
    fireEvent.change(shippingPeriod, { target: { value: '1-2 days' } });
    fireEvent.change(refundRequest, { target: { value: '3-5 days' } });
    fireEvent.change(refundProcess, { target: { value: '9-15 days' } });
    fireEvent.change(warrantyPeriod, { target: { value: 'NA' } });

    waitFor(() => expect(TermsAndConditionDB.read().support_email).toBe('abc@gmail.com'));
    waitFor(() => expect(TermsAndConditionDB.read().shipping_period).toBe('1-2 days'));
    waitFor(() => expect(TermsAndConditionDB.read().refund_request_period).toBe('3-5 days'));
    waitFor(() => expect(TermsAndConditionDB.read().refund_process_period).toBe('9-15 days'));
    waitFor(() => expect(TermsAndConditionDB.read().warranty_period).toBe('NA'));

    fireEvent.click(screen.getByText('Services'));
    fireEvent.click(checkbox);
    waitFor(() => fireEvent.click(button));
    fireEvent.click(screen.getByText('View Sample Page'));
  });

  it('should get success respose on api call', () => {
    render(<GenerateTncPage />, {});
    const button = screen.getByRole('button', { name: /Generate Page/i });
    const checkbox = screen.getByTestId('ds-checkbox');

    const supportEmail = screen.getAllByTestId('ds-text-input')[0];
    const shippingPeriod = screen.getAllByTestId('ds-text-input')[1];
    const refundRequest = screen.getAllByTestId('ds-text-input')[2];
    const refundProcess = screen.getAllByTestId('ds-text-input')[3];
    const warrantyPeriod = screen.getAllByTestId('ds-text-input')[4];

    fireEvent.change(supportEmail, { target: { value: 'abc@gmail.com' } });
    fireEvent.change(shippingPeriod, { target: { value: '1-2 days' } });
    fireEvent.change(refundRequest, { target: { value: '3-5 days' } });
    fireEvent.change(refundProcess, { target: { value: '9-15 days' } });
    fireEvent.change(warrantyPeriod, { target: { value: 'NA' } });

    fireEvent.click(checkbox);
    waitFor(() => fireEvent.click(button));
  });

  it('show success Tnc messages with link', () => {
    const mockFn = { push: jest.fn() };
    render(<TncSuccess history={mockFn} data={TermsAndConditionDB.read()} />, {});
    const button = screen.getByRole('button', { name: /Okay, got it/i });
    expect(screen.getByText('tnc.razorpay.com/Gmfaslj9AUIOH')).toBeInTheDocument();
    expect(
      screen.getByText(
        'You can make changes to this page from My account section in the web dashboard',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'This page link will be displayed on the apps that you would use to accept payments. (payment links, payment pages etc )',
      ),
    ).toBeInTheDocument();
    expect(screen.getByText('Okay, got it')).toBeInTheDocument();
    fireEvent.click(button);
  });
});
