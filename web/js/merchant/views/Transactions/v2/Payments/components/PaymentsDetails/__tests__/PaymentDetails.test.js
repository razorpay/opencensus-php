import React from 'react';
import '@testing-library/jest-dom/extend-expect';

import * as PaymentFetchFunctions from 'merchant/views/Transactions/model';
import PaymentsDetails from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentsDetails';
import {
  initialState,
  paymentPageProps,
  refundPageProps,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentDetails';
import {
  mockPaymentIdDetails,
  mockPaymentIdRefunds,
  mockRefundIdDetails,
  mockApplicationDetails,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/handlers';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import { render, screen, waitForElementToBeRemoved, userEvent, waitFor } from 'test-utils';

jest.mock('merchant/views/Transactions/v2/Payments/components/PaymentsDetails/utils.tsx', () => ({
  __esModule: true,
  isIssueRefundDisabled: (_) => {
    return false;
  },
}));

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsTimeline',
  () => ({
    __esModule: true,
    default: ({ reFetchPageDetails, _ }) => {
      return (
        <>
          <span>Timeline</span>
          <div>
            <button onClick={reFetchPageDetails}>Refetch</button>
          </div>
        </>
      );
    },
  }),
);

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsSection',
  () => ({
    __esModule: true,
    default: () => {
      return <div>Payment Details</div>;
    },
  }),
);

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentRefundDetails',
  () => ({
    __esModule: true,
    default: () => {
      return <div>Payment Refund Details</div>;
    },
  }),
);

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsOverview',
  () => ({
    __esModule: true,
    default: () => {
      return <div>Payment Details Overview</div>;
    },
  }),
);

describe('Payment Details component', () => {
  const App = ({ props }) => {
    return <PaymentsDetails {...props} />;
  };

  const showNotificationSpy = jest.spyOn(NotificationActions, 'showNotification');
  const fetchPaymentIdDetailsSpy = jest.spyOn(PaymentFetchFunctions, 'fetchPaymentIdDetails');
  const fetchPaymentIdRefundDetailsSpy = jest.spyOn(
    PaymentFetchFunctions,
    'fetchPaymentIdRefundDetails',
  );
  const fetchRefundIdDetailsSpy = jest.spyOn(PaymentFetchFunctions, 'fetchRefundIdDetails');
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  beforeEach(() => {
    fetchPaymentIdDetailsSpy.mockClear();
    fetchRefundIdDetailsSpy.mockClear();
    showNotificationSpy.mockClear();
  });

  describe('Render children component successfully', () => {
    beforeEach(() => {
      mockPaymentIdDetails({});
      mockPaymentIdRefunds({});
      mockRefundIdDetails({});
      mockApplicationDetails({});
    });
    test('should render Payment Details overview component', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      expect(screen.getByText('Payment Details Overview')).toBeInTheDocument();
    });

    test('should render Payment Details component', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      expect(screen.getByText('Payment Details')).toBeInTheDocument();
    });

    test('should render Payment Refund Details', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      expect(screen.getByText('Payment Refund Details')).toBeInTheDocument();
    });

    test('should render Payment Timeline component', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      expect(screen.getByText('Timeline')).toBeInTheDocument();
    });
  });

  describe('Opened from Payments page', () => {
    beforeEach(() => {
      mockPaymentIdDetails({});
      mockPaymentIdRefunds({});
      mockRefundIdDetails({});
      mockApplicationDetails({});
    });
    test('should call Payment fetch API', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(fetchPaymentIdDetailsSpy).toHaveBeenCalled();
    });
  });

  describe('Opened from Refunds page', () => {
    beforeEach(() => {
      mockPaymentIdDetails({});
      mockPaymentIdRefunds({});
      mockRefundIdDetails({});
      mockApplicationDetails({});
    });
    test('should call Refund fetch API', async () => {
      render(<App props={refundPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(fetchRefundIdDetailsSpy).toHaveBeenCalled();
    });
  });

  describe('Opened from Payments page - Error state', () => {
    beforeEach(() => {
      mockPaymentIdDetails({ error: 'Internal server error' });
      mockPaymentIdRefunds({});
      mockRefundIdDetails({});
      mockApplicationDetails({});
    });
    test('should render error state', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(showNotificationSpy).toHaveBeenCalled();
    });
  });

  describe('Opened from Refunds page - Error state', () => {
    beforeEach(() => {
      mockPaymentIdDetails({});
      mockPaymentIdRefunds({});
      mockApplicationDetails({});
      mockRefundIdDetails({ error: 'Internal server error' });
    });
    test('should render error state', async () => {
      render(<App props={refundPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));
      await expect(showNotificationSpy).toHaveBeenCalled();
    });
  });

  describe('Issue refund', () => {
    beforeEach(() => {
      mockPaymentIdDetails({});
      mockPaymentIdRefunds({});
      mockRefundIdDetails({});
      mockApplicationDetails({});
      openModalSpy.mockClear();
    });

    test('should open issue refund modal', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));

      const issueRefundBtn = screen.getByText('Issue refund');
      expect(issueRefundBtn).toBeInTheDocument();
      userEvent.click(issueRefundBtn);

      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalled();
      });
    });
  });

  describe('Refetch page details', () => {
    beforeEach(() => {
      mockPaymentIdDetails({});
      mockPaymentIdRefunds({});
      mockRefundIdDetails({});
      mockApplicationDetails({});
      openModalSpy.mockClear();
    });

    test('should call refetch APIs on trigger', async () => {
      render(<App props={paymentPageProps} />, { initialState });
      await waitForElementToBeRemoved(() => screen.getByRole('progressbar'));

      const refetchBtn = screen.getByText('Refetch');
      expect(refetchBtn).toBeInTheDocument();
      userEvent.click(refetchBtn);

      await expect(fetchPaymentIdDetailsSpy).toHaveBeenCalled();
      await expect(fetchPaymentIdRefundDetailsSpy).toHaveBeenCalled();
    });
  });
});
