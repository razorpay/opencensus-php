import React from 'react';
import OnboardingStepCard from '../OnboardingStepCard';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';

describe('OnboardingStepCard', () => {
  const defaultProps = {
    slug: 'test-slug',
    title: 'Test Title',
    description: 'Test Description',
    icon: <svg data-testid="test-icon" />,
    status: 'under_review',
    isDisabled: false,
    onClick: jest.fn(),
  };

  const renderComponent = (props = {}) => {
    const combinedProps = { ...defaultProps, ...props };
    return render(<OnboardingStepCard {...combinedProps} />);
  };

  test('should render without crashing', () => {
    renderComponent();
    expect(screen.getByText('Test Title')).toBeInTheDocument();
  });

  test('should render title, description, and icon', () => {
    renderComponent();
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
    expect(screen.getByTestId('test-icon')).toBeInTheDocument();
  });

  test('should render with disabled behavior when isDisabled is true', async () => {
    renderComponent({ isDisabled: true });
    await userEvent.click(screen.getByTestId('onboarding-step-card'));
    expect(defaultProps.onClick).not.toHaveBeenCalled();
  });

  test('should render StatusBadge with correct status', () => {
    renderComponent();
    expect(screen.getByText('Under Review')).toBeInTheDocument();
  });
});
