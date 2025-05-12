import React from 'react';
import { render, screen } from 'test-utils';
import UserJourney from '../components/UserJourney';

// Mock constants
jest.mock('../constant', () => ({
  RESUME_COMPANY_REG: 'Resume Your Registration',
  UserJourneyProgress: [
    { screen: 'step1', title: 'Step 1' },
    { screen: 'step2', title: 'Step 2' },
    { screen: 'step3', title: 'Step 3' },
  ],
}));

describe('UserJourney', () => {
  const renderComponent = () => render(<UserJourney userJourney="step2" isSmallDevice={false} />);
  test('renders all step items with correct titles and markers', () => {
    renderComponent();

    // Check heading
    expect(screen.getByText('Resume Your Registration')).toBeInTheDocument();

    // Step Titles
    expect(screen.getByText('Step 1')).toBeInTheDocument();
    expect(screen.getByText('Step 2')).toBeInTheDocument();
    expect(screen.getByText('Step 3')).toBeInTheDocument();

    // Check "Up Next" badge only appears for active step
    expect(screen.getByText('Up Next')).toBeInTheDocument();
  });

  test('renders CheckIcon for completed steps and StepItemIndicator for current/upcoming', () => {
    const { container } = render(<UserJourney userJourney="step2" isSmallDevice={false} />);

    // One completed = CheckIcon
    expect(container.querySelectorAll('svg')).toHaveLength(3);
  });
});
