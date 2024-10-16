import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import moment from 'moment';

import { titleCase } from 'common/utils/rzp-utils';
import * as SettlementActions from 'merchant/reducers/settlements/details';
import PaymentDetailsOverview from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsOverview';
import {
  initialState,
  capturedPaymentProps,
  createdPaymentProps,
  refundedPaymentProps,
  failedPaymentProps,
  authorizedPaymentProps,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentDetailsOverview';
import {
  mockfetchHolidayList,
  mockfetchSchedule,
  mockfetchSettlementConfig,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/handlers';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent, waitFor } from 'test-utils';

const mockAbExperiments = {
  toggle_payments_v2_revamp: {
    variables: {
      result: 'on',
    },
  },
};

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('Payment Details Overview component', () => {
  const App = ({ props }) => {
    return <PaymentDetailsOverview {...props} />;
  };

  const fetchHolidayListSpy = jest.spyOn(SettlementActions, 'fetchHolidayList');
  const fetchScheduleSpy = jest.spyOn(SettlementActions, 'fetchSchedule');
  const fetchSettlementConfigSpy = jest.spyOn(SettlementActions, 'fetchSettlementConfig');
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  beforeEach(() => {
    mockfetchHolidayList();
    mockfetchSchedule();
    mockfetchSettlementConfig();

    openModalSpy.mockClear();
    fetchHolidayListSpy.mockClear();
    fetchScheduleSpy.mockClear();
    fetchSettlementConfigSpy.mockClear();
  });

  describe('Render header details', () => {
    test('should render payment captured status', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      const paymentStatus = capturedPaymentProps.paymentDetails.status;
      expect(screen.getByText(`${titleCase(paymentStatus)}`)).toBeInTheDocument();
    });

    test('should render payment created status', () => {
      render(<App props={createdPaymentProps} />, { initialState });
      const paymentStatus = createdPaymentProps.paymentDetails.status;
      expect(screen.getByText(`${titleCase(paymentStatus)}`)).toBeInTheDocument();
    });

    test('should render payment failed status', () => {
      render(<App props={failedPaymentProps} />, { initialState });
      const paymentStatus = failedPaymentProps.paymentDetails.status;
      expect(screen.getByText(`${titleCase(paymentStatus)}`)).toBeInTheDocument();
    });

    test('should render payment refunded status', () => {
      render(<App props={refundedPaymentProps} />, { initialState });
      const paymentStatus = refundedPaymentProps.paymentDetails.status;
      expect(screen.getByText(`${titleCase(paymentStatus)}`)).toBeInTheDocument();
    });

    test('should render payment authorized status', () => {
      render(<App props={authorizedPaymentProps} />, { initialState });
      const paymentStatus = authorizedPaymentProps.paymentDetails.status;
      expect(screen.getByText(`${titleCase(paymentStatus)}`)).toBeInTheDocument();
    });

    test('should render payment amount', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      const paymentAmount = capturedPaymentProps.paymentDetails.amount;
      expect(screen.getAllByLabelText('amount-info')).toHaveLength(1);
      expect(screen.getByTestId('gross-amount')).toBeInTheDocument();
      expect(screen.getByTestId('net-amount')).toBeInTheDocument();
      expect(screen.getByTestId('deductions')).toBeInTheDocument();

      //for total amount shown like a header
      expect(screen.getByText(`${paymentAmount / 100}`)).toBeInTheDocument();

      //for gross amount
      expect(screen.getByText(`${paymentAmount / 100}.00`)).toBeInTheDocument();
    });

    test('should render payment timestamp', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      const paymentCreatedAt = capturedPaymentProps.paymentDetails.created_at;
      const [createdDay, createdTime] = moment
        .unix(paymentCreatedAt)
        .format('ddd MMM D,hh:mma')
        .split(',');
      expect(screen.getByText(`Created on:`)).toBeInTheDocument();
      expect(screen.getByText(`${createdDay}, ${createdTime}`)).toBeInTheDocument();
    });

    test('should render the badge with application name', () => {
      render(<App props={refundedPaymentProps} />, { initialState });
      expect(screen.getByText(`Payment initiated via:`)).toBeInTheDocument();
      expect(
        screen.getByText(`${refundedPaymentProps.applicationDetails.name}`),
      ).toBeInTheDocument();
    });
  });

  describe('Render deductions details', () => {
    test('should render deduction, net amount & gross amount labels', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      expect(screen.getByText('Gross amount')).toBeInTheDocument();
      expect(screen.getByText('Deductions')).toBeInTheDocument();
      expect(screen.getByText('Net amount')).toBeInTheDocument();
    });

    test('should render deduction, net amount & gross amount values', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      const { fee, tax, amount } = capturedPaymentProps.paymentDetails;

      const totalDeductions = Number(tax) + Number(fee);

      expect(screen.getByText(`${totalDeductions / 100}.00`)).toBeInTheDocument();
      expect(screen.getByText(`${(amount - totalDeductions) / 100}.00`)).toBeInTheDocument();
    });

    test('should toggle deductions view', async () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      const toggleBtn = screen.getByTestId(`chevron-down`);
      expect(toggleBtn).toBeInTheDocument();
      userEvent.click(toggleBtn);
      await waitFor(() => {
        expect(screen.getByTestId(`chevron-up`)).toBeInTheDocument();
      });
    });
  });

  describe('Render footer correctly', () => {
    test('should render settlement cycle cta', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      expect(screen.getByText('settlement cycle')).toBeInTheDocument();
    });

    test('should render settlement cycle cta', () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      expect(screen.getByText('settlement cycle')).toBeInTheDocument();
    });

    test('should open settlement cycle modal', async () => {
      render(<App props={capturedPaymentProps} />, { initialState });
      const settlementCycleCTA = screen.getByText('settlement cycle');
      userEvent.click(settlementCycleCTA);
      await waitFor(() => {
        expect(openModalSpy).toHaveBeenCalled();
      });
    });
  });
});
