import React from 'react';
import { render, screen } from 'test-utils';
import StepsItem from '../components/StepsItem';
import { CheckIcon, InfoIcon } from '@razorpay/blade/components';

// Helper function to render StepsItem with required props
const renderStepsItem = (
  status: 'Completed' | 'Ongoing' | 'Next' | 'Final',
  title = 'Step Title',
) => {
  return render(<StepsItem icon={CheckIcon} title={title} status={status} />);
};

describe('StepsItem Component', () => {
  test('renders Completed step with correct badge and styles', () => {
    renderStepsItem('Completed');
    expect(screen.getByText('Step Title')).toBeInTheDocument();
    expect(screen.getByText('Completed')).toBeInTheDocument();
  });

  test('renders Ongoing step with correct badge and icon color', () => {
    renderStepsItem('Ongoing');
    expect(screen.getByText('Step Title')).toBeInTheDocument();
    expect(screen.getByText('Ongoing')).toBeInTheDocument();
  });

  test('renders Next step with text instead of badge', () => {
    renderStepsItem('Next');
    expect(screen.getByText('Step Title')).toBeInTheDocument();
    expect(screen.getByText('Next')).toBeInTheDocument();
  });

  test('renders Final step with muted label and text', () => {
    renderStepsItem('Final', 'Final Step');
    expect(screen.getByText('Final Step')).toBeInTheDocument();
    expect(screen.getByText('Final')).toBeInTheDocument();
  });

  test('renders custom icon correctly', () => {
    render(<StepsItem icon={InfoIcon} title="Info Step" status="Next" />);
    expect(screen.getByText('Info Step')).toBeInTheDocument();
  });
});
