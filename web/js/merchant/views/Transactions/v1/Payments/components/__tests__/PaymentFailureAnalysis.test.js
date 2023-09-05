import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentFailureAnalysis from 'merchant/views/Transactions/v1/Payments/components/PaymentFailureAnalysis';
import { render, screen, checkIfComponentIsEmpty } from 'test-utils';

describe('PaymentFailureAnalysis', () => {
  const defaultProps = {
    user: {
      showFAPaymantCount: 2,
      faTextVariant: 'variant1',
    },
    data: {
      summary: { number_of_total_payments: 14, number_of_successful_payments: 8 },
      failure_details: {
        customer_dropp_off: 1,
        bank_failure: 2,
        business_failure: 3,
        other_failure: 0,
      },
    },
  };

  const App = (props) => {
    return <PaymentFailureAnalysis {...defaultProps} {...props} />;
  };

  test('should render payment failure analysis', () => {
    const {
      data: {
        summary: { number_of_total_payments, number_of_successful_payments },
        failure_details: { customer_dropp_off, bank_failure, business_failure, other_failure },
      },
    } = defaultProps;
    render(<App />);
    expect(screen.getByText('Total Attempts')).toBeInTheDocument();
    expect(screen.getByText(number_of_total_payments)).toBeInTheDocument();
    expect(screen.getByText('Successful Payments')).toBeInTheDocument();
    expect(screen.getByText(number_of_successful_payments)).toBeInTheDocument();
    expect(screen.getByText('Customer Drop-offs')).toBeInTheDocument();
    expect(screen.getByText(customer_dropp_off)).toBeInTheDocument();
    expect(screen.getByText('Bank Failures')).toBeInTheDocument();
    expect(screen.getByText(bank_failure)).toBeInTheDocument();
    expect(screen.getByText('Business Failures')).toBeInTheDocument();
    expect(screen.getByText(business_failure)).toBeInTheDocument();
    expect(screen.getByText('Other Failures')).toBeInTheDocument();
    expect(screen.getByText(other_failure)).toBeInTheDocument();
  });

  test('should not render payment failure analysis when there is no data', () => {
    render(<App data={{}} />);
    checkIfComponentIsEmpty();
  });

  test('should render Razorpay Failures', () => {
    const {
      data: {
        failure_details: { other_failure },
      },
    } = defaultProps;
    render(
      <App
        user={{
          showFAPaymantCount: 2,
          faTextVariant: 'variant2',
        }}
      />,
    );
    expect(screen.getByText('Razorpay Failures')).toBeInTheDocument();
    expect(screen.getByText(other_failure)).toBeInTheDocument();
  });

  test('should render mismatchCount when there is mismatch in number of total payments', () => {
    const { data } = defaultProps;
    render(
      <App
        data={{
          ...data,
          summary: { number_of_total_payments: 16, number_of_successful_payments: 8 },
        }}
      />,
    );
    expect(
      screen.getByText(
        /2 payments are in created state and are yet to be processed. They are counted in total attempts but not in successful or failed payments./,
      ),
    ).toBeInTheDocument();
  });

  test('should not render mismatchCount when there is no mismatch in number of total payments', () => {
    render(<App />);
    expect(
      screen.queryByText(
        /2 payments are in created state and are yet to be processed. They are counted in total attempts but not in successful or failed payments./,
      ),
    ).not.toBeInTheDocument();
  });
});
