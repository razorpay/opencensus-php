import React from 'react';
import { render, screen } from 'test-utils';

import { Pricing } from 'merchant/views/Optimizer/OnBoarding/components/Pricing';

const renderComponent = (props) => {
  return render(<Pricing {...props} />);
};

describe('Optimizer OnBoarding - Pricing', () => {
  test('Should render without errors', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should render pricing plan details', () => {
    renderComponent({ mode: 'live', nextStep: jest.fn() });
    expect(
      screen.getByText("Optimizer - Razorpay's AI powered payments router"),
    ).toBeInTheDocument();
    expect(screen.getByText('Get started with Optimizer')).toBeInTheDocument();
    expect(screen.getByText('/ month')).toBeInTheDocument();
    expect(screen.getByText('Startup Plan')).toBeInTheDocument();
    expect(
      screen.getByText('Free up to 1 crore per month, 0.25% per txn after'),
    ).toBeInTheDocument();
    expect(screen.getByText('One click integration with 15+ PGs')).toBeInTheDocument();
    expect(screen.getByText('Boost success rates by as much as 10%')).toBeInTheDocument();
    expect(screen.getByText('Single dashboard to manage all PGs')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Learn more' })).toBeInTheDocument();
    const getStartedBtn = screen.getByRole('button', { name: 'Get started' });
    expect(getStartedBtn).toBeInTheDocument();
    expect(getStartedBtn).not.toBeDisabled();
  });

  test('should render disabled button for test mode', () => {
    renderComponent({ mode: 'test', nextStep: jest.fn() });
    const getStartedBtn = screen.getByRole('button', { name: 'Get started' });
    expect(getStartedBtn).toBeInTheDocument();
    expect(getStartedBtn).toBeDisabled();
  });
});
