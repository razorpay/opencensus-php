import React from 'react';
import { render, screen } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import StepIndicator from 'merchant/components/SelfServeRekyc/components/StepIndicator';
import { AlertCircleIcon, IconColors, HeadingProps } from '@razorpay/blade/components';
import { RekycStepInfo } from 'merchant/components/SelfServeRekyc/types';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn().mockReturnValue(false)
}));

describe('StepIndicator', () => {
  const mockStepsInfo: Record<string, RekycStepInfo> = {
    step1: {
      heading: 'Step 1',
      IconComponent: AlertCircleIcon,
      iconColor: 'feedback.icon.negative.intense' as IconColors,
      iconBackgroundColor: 'feedback.background.negative.subtle',
      headingColor: 'surface.text.gray.normal' as HeadingProps['color'],
      textContent: '1',
      textColor: 'surface.text.gray.normal' as HeadingProps['color'],
      completed: false,
      currentStep: true,
      borderColor: 'surface.border.primary.normal'
    },
    step2: {
      heading: 'Step 2',
      IconComponent: AlertCircleIcon,
      iconColor: 'feedback.icon.negative.intense' as IconColors,
      iconBackgroundColor: 'feedback.background.negative.subtle',
      headingColor: 'surface.text.gray.normal' as HeadingProps['color'],
      textContent: '2',
      textColor: 'surface.text.gray.normal' as HeadingProps['color'],
      completed: false,
      currentStep: false,
      borderColor: 'surface.border.primary.normal'
    },
    step3: {
      heading: 'Step 3',
      IconComponent: AlertCircleIcon,
      iconColor: 'feedback.icon.negative.intense' as IconColors,
      iconBackgroundColor: 'feedback.background.negative.subtle',
      headingColor: 'surface.text.gray.normal' as HeadingProps['color'],
      textContent: '3',
      textColor: 'surface.text.gray.normal' as HeadingProps['color'],
      completed: false,
      currentStep: false,
      borderColor: 'surface.border.primary.normal'
    }
  };

  it('renders all steps correctly in desktop view', () => {
    render(<StepIndicator stepsInfo={mockStepsInfo} />);

    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();

    expect(screen.getByText('2')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
  });

  it('renders all steps correctly in mobile view', () => {
    const useMobile = require('common/hooks/useMobile').useMobile;
    useMobile.mockReturnValue(true);

    render(<StepIndicator stepsInfo={mockStepsInfo} />);

    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();
  });

  it('renders completed steps correctly', () => {
    const completedStepsInfo = {
      ...mockStepsInfo,
      step1: {
        ...mockStepsInfo.step1,
        completed: true
      }
    };

    render(<StepIndicator stepsInfo={completedStepsInfo} />);

    expect(screen.getByText('Step 1')).toBeInTheDocument();

    expect(screen.queryByText('1')).not.toBeInTheDocument();
  });

  it('renders current step correctly', () => {
    render(<StepIndicator stepsInfo={mockStepsInfo} />);

    expect(screen.getByText('Step 1')).toBeInTheDocument();

    const svg = document.querySelector('svg');
    expect(svg).toBeInTheDocument();

    expect(screen.getByText('2')).toBeInTheDocument();
    expect(screen.getByText('3')).toBeInTheDocument();
  });

  it('renders dividers between steps', () => {
    const { container } = render(<StepIndicator stepsInfo={mockStepsInfo} />);

    const dividers = container.getElementsByClassName('StepIndicator__DividerWrapper-sc-4elvz-0');
    expect(dividers.length).toBe(2);
  });

  it('handles empty steps gracefully', () => {
    render(<StepIndicator stepsInfo={{}} />);

    expect(screen.queryByText('Step')).not.toBeInTheDocument();
  });
});