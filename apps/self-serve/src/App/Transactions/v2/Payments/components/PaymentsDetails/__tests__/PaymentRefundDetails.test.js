import React from 'react';
import '@testing-library/jest-dom/extend-expect';

import * as ModalActions from '@dashboard/shared-utils/reducers/modals';
import { render, screen, userEvent, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import PaymentRefundDetails from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/PaymentRefundDetails';
import {
  initialState,
  appProps,
} from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentRefundDetails';
import {
  isIssueRefundDisabled,
  isPaymentEligibleForRefundAsPerStatus,
  isPaymentEligibleForRefund,
  hasPaymentOpenNonFraudDisputes,
  isGatewaySupportingRefund,
  isPaymentThroughSeamlessProviders,
} from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/utils';

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/utils',
  () => ({
    ...jest.requireActual(
      'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/utils',
    ),
    __esModule: true,
    isIssueRefundDisabled: jest.fn(),
    isPaymentEligibleForRefundAsPerStatus: jest.fn(),
    isPaymentEligibleForRefund: jest.fn(),
    hasPaymentOpenNonFraudDisputes: jest.fn(),
    isGatewaySupportingRefund: jest.fn(),
    isPaymentThroughSeamlessProviders: jest.fn(),
  }),
);

jest.mock('apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundMiniTimeline', () => ({
  __esModule: true,
  default: () => {
    return <span>Refund Timeline</span>;
  },
}));

describe('Payment Refund Details component', () => {
  const App = ({ props }) => {
    return <PaymentRefundDetails {...props} />;
  };

  describe(`Refund Fields`, () => {
    beforeEach(() => {
      isIssueRefundDisabled.mockReturnValue(false);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);
    });

    test('should render refund Ids', () => {
      render(<App props={appProps} />, { initialState });

      expect(screen.getAllByText('Refund ID')).toHaveLength(appProps.paymentIdRefundDetails.length);
      expect(screen.getByText(`${appProps.paymentIdRefundDetails[0].id}`)).toBeInTheDocument();
      expect(screen.getByText(`${appProps.paymentIdRefundDetails[1].id}`)).toBeInTheDocument();
    });

    test('should render refund speed', () => {
      render(<App props={appProps} />, { initialState });

      expect(screen.getAllByText('Refund speed')).toHaveLength(
        appProps.paymentIdRefundDetails.length,
      );
      expect(screen.getAllByText('Normal')).toHaveLength(appProps.paymentIdRefundDetails.length);
    });

    test('should render refund timeline', () => {
      render(<App props={appProps} />, { initialState });

      expect(screen.getAllByText('Timeline')).toHaveLength(appProps.paymentIdRefundDetails.length);
      expect(screen.getAllByText('Refund Timeline')).toHaveLength(
        appProps.paymentIdRefundDetails.length,
      );
    });
  });

  describe(`Issue refund modal`, () => {
    const openModalSpy = jest.spyOn(ModalActions, 'openModal');

    beforeEach(() => {
      isIssueRefundDisabled.mockReturnValue(false);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);

      openModalSpy.mockClear();
    });

    test('Should open the issue refund modal correctly', async () => {
      render(<App props={appProps} />, { initialState });

      const issueRefundBtn = screen.getByRole('button', { description: 'Issue refund' });
      expect(issueRefundBtn).toBeInTheDocument();
      userEvent.click(issueRefundBtn);

      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalledTimes(1);
      });
    });
  });

  describe(`No refunds issued yet`, () => {
    beforeEach(() => {
      isIssueRefundDisabled.mockReturnValue(false);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);
    });

    test('Should render no refunds issued yet', () => {
      const updatedAppProps = { ...appProps };
      updatedAppProps.paymentIdRefundDetails = [];
      render(<App props={updatedAppProps} />, { initialState });

      expect(screen.getByText(`No refund issued for this payment`)).toBeInTheDocument();
    });
  });

  describe(`No refunds allowed`, () => {
    test('Payment not eligible for refund as per status', () => {
      isIssueRefundDisabled.mockReturnValue(true);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(false);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);

      const updatedAppProps = { ...appProps };
      updatedAppProps.paymentIdRefundDetails = [];
      render(<App props={updatedAppProps} />, { initialState });

      expect(screen.getByText(`Only captured payments can be refunded`)).toBeInTheDocument();
    });

    test('Payment not eligible for refund due to disputes', () => {
      isIssueRefundDisabled.mockReturnValue(true);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(true);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);

      const updatedAppProps = { ...appProps };
      updatedAppProps.paymentIdRefundDetails = [];
      render(<App props={updatedAppProps} />, { initialState });

      expect(
        screen.getByText(
          `Refund can’t be issued by you for this payment because of open dispute(s)`,
        ),
      ).toBeInTheDocument();
    });

    test('Payment not eligible for refund due to seamless providers', () => {
      isIssueRefundDisabled.mockReturnValue(true);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(true);

      const updatedAppProps = { ...appProps };
      updatedAppProps.paymentIdRefundDetails = [];
      render(<App props={updatedAppProps} />, { initialState });

      expect(screen.getByText(/Refund for this payment can only be issued/i)).toBeInTheDocument();
    });

    test('Payment not eligible for refund due to gateway support', () => {
      isIssueRefundDisabled.mockReturnValue(true);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(true);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(false);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);

      const updatedAppProps = { ...appProps };
      updatedAppProps.paymentIdRefundDetails = [];
      render(<App props={updatedAppProps} />, { initialState });

      expect(screen.getByText(`Gateway doesn't support refund`)).toBeInTheDocument();
    });

    test('Payment not eligible for refund due to no access rights', () => {
      isIssueRefundDisabled.mockReturnValue(true);
      isPaymentEligibleForRefundAsPerStatus.mockReturnValue(true);
      isPaymentEligibleForRefund.mockReturnValue(false);
      hasPaymentOpenNonFraudDisputes.mockReturnValue(false);
      isGatewaySupportingRefund.mockReturnValue(true);
      isPaymentThroughSeamlessProviders.mockReturnValue(false);

      const updatedAppProps = { ...appProps };
      updatedAppProps.paymentIdRefundDetails = [];
      render(<App props={updatedAppProps} />, { initialState });

      expect(screen.getByText(`You cannot issue refund for this payment`)).toBeInTheDocument();
    });
  });
});
