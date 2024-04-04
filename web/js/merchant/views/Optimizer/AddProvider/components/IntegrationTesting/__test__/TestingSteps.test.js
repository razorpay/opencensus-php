import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { TestingSteps } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/TestingSteps';

describe('Optimizer IntegrationTesting TestingSteps', () => {
  const mockProps = {
    steps: [
      {
        title: 'Payment testing',
        value: 'payment_testing',
        active: true,
        success: false,
        failed: false,
      },
      {
        title: 'Refund testing',
        value: 'refund_testing',
        active: false,
        success: false,
        failed: false,
      },
      {
        title: 'Integration audit summary',
        value: 'integration_audit_summary',
        active: false,
        success: false,
        failed: false,
      },
      {
        title: 'Provider settings',
        value: 'provider_settings',
        active: false,
        success: false,
        failed: false,
      },
    ],
    changeIntegrationTestingStep: jest.fn(),
  };

  const App = (props) => {
    return (
      <BladeProvider themeTokens={bladeTheme}>
        <TestingSteps {...props} />
      </BladeProvider>
    );
  };

  it('should render TestingSteps without any errors', () => {
    expect(() => render(<App {...mockProps} />)).not.toThrow();
  });

  it('should render all steps', async () => {
    render(<App {...mockProps} />);
    const steps = [
      'Payment testing',
      'Refund testing',
      'Integration audit summary',
      'Provider settings',
    ];
    steps.forEach((step) => {
      expect(screen.getByText(step)).toBeInTheDocument();
    });
    expect(screen.getByTestId('integration-right-icon')).toBeInTheDocument();
    expect(screen.queryByTestId('integration-check-icon')).not.toBeInTheDocument();
    expect(screen.queryByTestId('integration-close-icon')).not.toBeInTheDocument();
    await userEvent.click(screen.getByText('Refund testing'));
    expect(mockProps.changeIntegrationTestingStep).toHaveBeenCalledWith('refund_testing');
  });

  it('should render steps with successful and active state', () => {
    const props = {
      ...mockProps,
      steps: [
        {
          title: 'Payment testing',
          value: 'payment_testing',
          active: false,
          success: true,
          failed: false,
        },
        {
          title: 'Refund testing',
          value: 'refund_testing',
          active: true,
          success: false,
          failed: false,
        },
        {
          title: 'Integration audit summary',
          value: 'integration_audit_summary',
          active: false,
          success: false,
          failed: false,
        },
        {
          title: 'Provider settings',
          value: 'provider_settings',
          active: false,
          success: false,
          failed: false,
        },
      ],
    };
    render(<App {...props} />);
    expect(screen.getByTestId('integration-right-icon')).toBeInTheDocument();
    expect(screen.getByTestId('integration-check-icon')).toBeInTheDocument();
    expect(screen.queryByTestId('integration-close-icon')).not.toBeInTheDocument();
  });

  it('should render steps with failed and active state', () => {
    const props = {
      ...mockProps,
      steps: [
        {
          title: 'Payment testing',
          value: 'payment_testing',
          active: false,
          success: false,
          failed: true,
        },
        {
          title: 'Refund testing',
          value: 'refund_testing',
          active: true,
          success: false,
          failed: false,
        },
        {
          title: 'Integration audit summary',
          value: 'integration_audit_summary',
          active: false,
          success: false,
          failed: false,
        },
        {
          title: 'Provider settings',
          value: 'provider_settings',
          active: false,
          success: false,
          failed: false,
        },
      ],
    };
    render(<App {...props} />);
    expect(screen.getByTestId('integration-right-icon')).toBeInTheDocument();
    expect(screen.queryByTestId('integration-check-icon')).not.toBeInTheDocument();
    expect(screen.getByTestId('integration-close-icon')).toBeInTheDocument();
  });
});
