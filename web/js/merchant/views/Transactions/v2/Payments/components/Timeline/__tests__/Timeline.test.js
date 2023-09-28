import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { useBreakpoint } from '@razorpay/blade/utils';

import Timeline from 'merchant/views/Transactions/v2/Payments/components/Timeline';
import {
  initialState,
  happyFlowProps,
  paymentCaptureFailedBankTransfer,
  paymentCaptureFlow,
} from 'merchant/views/Transactions/v2/Payments/components/Timeline/__tests__/mocks/fixtures/Timeline';
import { mockPaymentCapture } from 'merchant/views/Transactions/v2/Payments/components/Timeline/__tests__/mocks/handlers';
import { render, screen, waitFor, userEvent } from 'test-utils';

jest.mock('@razorpay/blade/utils', () => ({
  ...jest.requireActual('@razorpay/blade/utils'),
  useBreakpoint: jest.fn(),
}));

describe('Timeline component', () => {
  const App = ({ props }) => {
    return <Timeline {...props} />;
  };

  describe(`Should render different entity statuses(Happy flows)`, () => {
    beforeEach(() => {
      useBreakpoint.mockReturnValue({
        matchedDeviceType: 'desktop',
      });
    });
    test('should show different payment status values', () => {
      render(<App props={happyFlowProps} />, { initialState });

      expect(screen.getByText('Payment created')).toBeInTheDocument();
      expect(screen.getByText('Payment authorized')).toBeInTheDocument();
      expect(screen.getByText('Payment captured')).toBeInTheDocument();
    });

    test('should show each refund status', () => {
      render(<App props={happyFlowProps} />, { initialState });

      expect(screen.getAllByText('Refund')).toHaveLength(3);
      expect(screen.getAllByText('View timeline')).toHaveLength(3);
    });

    test('should show settlement status', () => {
      render(<App props={happyFlowProps} />, { initialState });

      expect(screen.getByText('Settlement')).toBeInTheDocument();
      expect(screen.getByText('Net amount:')).toBeInTheDocument();
    });
  });

  describe(`Payment capture flow failed(Bank Transfer)`, () => {
    beforeEach(() => {
      useBreakpoint.mockReturnValue({
        matchedDeviceType: 'desktop',
      });
    });
    test('should show different payment status values', () => {
      render(<App props={paymentCaptureFailedBankTransfer} />, { initialState });

      expect(screen.getByText('Payment authorized')).toBeInTheDocument();
      expect(screen.getByText('Payment failed')).toBeInTheDocument();
      expect(screen.getByText('This payment will be refunded within 72 hours')).toBeInTheDocument();
    });
  });

  describe(`Payment capture flow`, () => {
    beforeEach(() => {
      useBreakpoint.mockReturnValue({
        matchedDeviceType: 'desktop',
      });
    });
    test('should call reFetchPageDetails on successful capture', async () => {
      mockPaymentCapture();
      const reFetchPageDetailsSpy = jest.spyOn(paymentCaptureFlow, 'reFetchPageDetails');
      render(<App props={paymentCaptureFlow} />, { initialState });

      expect(screen.getByText('Amount yet to be manually captured')).toBeInTheDocument();
      const captureBtn = screen.getByRole('button', { description: 'Capture payment' });
      expect(captureBtn).toBeInTheDocument();
      await userEvent.click(captureBtn);

      await waitFor(() => {
        expect(reFetchPageDetailsSpy).toHaveBeenCalled();
      });
    });
  });

  describe('Render mobile view correctly', () => {
    beforeEach(() => {
      useBreakpoint.mockReturnValue({
        matchedDeviceType: 'mobile',
      });
    });
    test('should show mWeb view for timeline', async () => {
      render(<App props={happyFlowProps} />, { initialState });

      const showTimelineCTA = screen.getByText('Show timeline');
      expect(showTimelineCTA).toBeInTheDocument();
      await userEvent.click(showTimelineCTA);
      await waitFor(() => {
        expect(screen.getByText('Collapse timeline')).toBeInTheDocument();
      });
    });
  });
});
