import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
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
    isRefundDone: false,
    testAnotherPayment: jest.fn(),
    changeIntegrationTestingStep: jest.fn(),
    takeProviderLive: jest.fn(),
    isUpdatingProvider: false,
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
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
    expect(mockProps.changeIntegrationTestingStep).toHaveBeenCalledWith({ name: 'refund_testing' });
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

  it('should render the correct buttons for refund testing not done', async () => {
    const props = {
      ...mockProps,
      currentStep: 'refund_testing',
      isPaymentDone: true,
      isRefundDone: false,
    };
    render(<App {...props} />);
    expect(screen.getByText('Previous')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Previous'));
    expect(props.changeIntegrationTestingStep).toHaveBeenCalledWith({ name: 'payment_testing' });
    expect(screen.getByRole('button', { name: 'Continue' })).toBeDisabled();
  });

  it('should render the corect buttons for refund testing done', async () => {
    const props = {
      ...mockProps,
      currentStep: 'refund_testing',
      isPaymentDone: true,
      isRefundDone: true,
    };
    render(<App {...props} />);
    expect(screen.getByText('Previous')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Previous'));
    expect(props.changeIntegrationTestingStep).toHaveBeenCalledWith({ name: 'payment_testing' });
    expect(screen.getByText('Continue')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Continue'));
    expect(props.changeIntegrationTestingStep).toHaveBeenCalledWith({
      name: 'integration_audit_summary',
    });
  });

  it('should render the correct buttons for integration audit summary', async () => {
    const props = {
      ...mockProps,
      currentStep: 'integration_audit_summary',
    };
    render(<App {...props} />);
    expect(screen.getByText('Previous')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Previous'));
    expect(props.changeIntegrationTestingStep).toHaveBeenCalledWith({ name: 'refund_testing' });
    expect(screen.getByText('Continue')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Continue'));
    expect(props.changeIntegrationTestingStep).toHaveBeenCalledWith({ name: 'provider_settings' });
  });

  it('should render the correct buttons for provider settings', async () => {
    const props = {
      ...mockProps,
      currentStep: 'provider_settings',
    };
    render(<App {...props} />);
    expect(screen.getByText('Previous')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Previous'));
    expect(props.changeIntegrationTestingStep).toHaveBeenCalledWith({
      name: 'integration_audit_summary',
    });
    expect(screen.getByText('Go live')).toBeInTheDocument();
    await userEvent.click(screen.getByText('Go live'));
    expect(props.takeProviderLive).toHaveBeenCalled();
  });

  it('should render the disabled button for provider settings on updating provider', () => {
    const props = {
      ...mockProps,
      currentStep: 'provider_settings',
      isUpdatingProvider: true,
    };
    render(<App {...props} />);
    expect(screen.getByRole('button', { name: 'Previous' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Go live' })).toBeDisabled();
  });
});
