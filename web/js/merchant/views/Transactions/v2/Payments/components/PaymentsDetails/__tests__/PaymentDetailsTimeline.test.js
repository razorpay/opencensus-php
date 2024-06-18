import React from 'react';
import '@testing-library/jest-dom/extend-expect';

import * as PaymentFetchFunctions from 'merchant/views/Transactions/model';
import PaymentDetailsTimeline from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsTimeline';
import {
  initialState,
  happyFlowProps,
  bankTransferPaymentFlow,
  failedPaymentFlow,
  createdPaymentFlow,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentDetailsTimeline';
import {
  mockPaymentIdTimelineDetails,
  mockBankTransferDetails,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/handlers';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  render,
  screen,
  waitForElement,
  userEvent,
  waitForElementToBeRemoved,
  waitFor,
} from 'test-utils';

jest.mock('merchant/views/Transactions/v2/Payments/components/Timeline', () => ({
  __esModule: true,
  default: ({ paymentIdDetails }) => {
    return (
      <div>
        Payments Timeline
        <p>{paymentIdDetails.status}</p>
      </div>
    );
  },
}));

const mockIsConfigTagEnabled = jest.fn();
jest.mock('common/i18', () => ({
  __esModule: true,
  withI18Service: (Component) => (props) =>
    <Component i18={{ isConfigTagEnabled: jest.fn() }} {...props} />,
  useI18Service: () => ({
    isConfigTagEnabled: mockIsConfigTagEnabled,
  }),
}));

describe('Payment Timeline parent component', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  const fetchBankTransferSpy = jest.spyOn(PaymentFetchFunctions, 'fetchBankTransfer');
  const fetchPaymentIdTimelineDataSpy = jest.spyOn(
    PaymentFetchFunctions,
    'fetchPaymentIdTimelineData',
  );

  beforeEach(() => {
    openModalSpy.mockClear();
    fetchBankTransferSpy.mockClear();
  });

  const App = ({ props }) => {
    return <PaymentDetailsTimeline {...props} />;
  };

  describe(`Should render timeline component correctly`, () => {
    beforeEach(() => {
      mockPaymentIdTimelineDetails('captured');
    });
    test('should show different payment status values', async () => {
      render(<App props={happyFlowProps} />, { initialState });

      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(fetchPaymentIdTimelineDataSpy).toHaveBeenCalled();
      await waitForElement(async () => {
        const ele = screen.getByText('Payments Timeline');
        await expect(ele).toBeInTheDocument();
      });
    });
  });

  describe(`Issue refund modal opens properly`, () => {
    beforeEach(() => {
      mockPaymentIdTimelineDetails('captured');
    });
    test('should show different payment status values', async () => {
      render(<App props={happyFlowProps} />, { initialState });

      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await waitForElement(async () => {
        const ele = screen.getByText('Payments Timeline');
        await expect(ele).toBeInTheDocument();
      });

      const issueRefundBtn = screen.getByRole('button', { description: 'Issue refund' });
      expect(issueRefundBtn).toBeInTheDocument();
      userEvent.click(issueRefundBtn);

      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalledTimes(1);
      });
    });
  });

  describe(`Show/Hide Issue refund button`, () => {
    test(`Hide issue refund on isConfigTagEnabled return true`, () => {
      mockPaymentIdTimelineDetails('captured');

      mockIsConfigTagEnabled.mockReturnValue(true);

      render(<App props={happyFlowProps} />, { initialState });

      expect(screen.queryByText('Issue refund')).not.toBeInTheDocument();

      mockIsConfigTagEnabled.mockReset();
    });

    test(`Show issue refund on isConfigTagEnabled return false`, () => {
      mockPaymentIdTimelineDetails('captured');

      mockIsConfigTagEnabled.mockReturnValue(false);

      render(<App props={happyFlowProps} />, { initialState });

      expect(screen.queryByText('Issue refund')).toBeInTheDocument();

      mockIsConfigTagEnabled.mockReset();
    });
  });

  describe(`Should call fetchTransfers when payment method is bank transfer`, () => {
    beforeEach(() => {
      mockPaymentIdTimelineDetails('authorized');
    });
    test('should show different payment status values', async () => {
      mockBankTransferDetails();
      render(<App props={bankTransferPaymentFlow} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(fetchBankTransferSpy).toHaveBeenCalled();
    });
  });

  describe(`Different payment state flow(s)`, () => {
    test('should show failed payment in timeline', async () => {
      mockPaymentIdTimelineDetails('failed');
      render(<App props={failedPaymentFlow} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(screen.getByText('failed')).toBeInTheDocument();
    });

    test('should show created payment in timeline', async () => {
      mockPaymentIdTimelineDetails('created');
      render(<App props={createdPaymentFlow} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(screen.getByText('created')).toBeInTheDocument();
    });
  });
});
