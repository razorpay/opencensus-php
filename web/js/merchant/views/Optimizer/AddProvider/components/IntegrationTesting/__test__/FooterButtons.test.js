import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { FooterButtons } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/FooterButtons';

describe('Optimizer IntegrationTesting FooterButtons', () => {
  const mockProps = {
    currentStep: 'payment_testing',
    isPaymentSuccessfull: true,
    isPaymentDone: true,
    testPayment: jest.fn(),
    raiseTicket: jest.fn(),
    testAnotherPayment: jest.fn(),
    changeIntegrationTestingStep: jest.fn(),
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={paymentTheme}>
        <FooterButtons {...props} />
      </BladeProvider>
    );
  };

  it('should render FooterButtons without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrow();
  });

  it('should render the correct buttons for payment testing', async () => {
    const props = {
      ...mockProps,
      isPaymentDone: false,
      isPaymentSuccessfull: false,
    };
    render(<App {...props} />);
    expect(screen.getByText('Test payment')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Test payment'));
    expect(props.testPayment).toHaveBeenCalled();
  });

  it('should render the correct buttons for successful payment', async () => {
    render(<App {...mockProps} />);
    expect(screen.getByText('Test another')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Test another'));
    expect(mockProps.testAnotherPayment).toHaveBeenCalled();
    expect(screen.getByText('Continue')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Continue'));
    expect(mockProps.changeIntegrationTestingStep).toHaveBeenCalledWith('refund_testing');
  });

  it('should render the correct buttons for failed payment', async () => {
    const props = {
      ...mockProps,
      isPaymentSuccessfull: false,
    };
    render(<App {...props} />);
    expect(screen.getByText('Test another')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Test another'));
    expect(props.testAnotherPayment).toHaveBeenCalled();
    expect(screen.getByText('Raise a ticket')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Raise a ticket'));
    expect(props.raiseTicket).toHaveBeenCalled();
  });
});
