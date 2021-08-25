import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BusinessType from '../index';
import { render, screen, fireEvent, cleanup } from 'test-utils';

interface AppProps {
  onboardingMilestone: string;
  value?: string;
}

const App: React.FC<AppProps> = ({ onboardingMilestone, value = '11' }) => {
  return (
    <BusinessType value={value} onboardingMilestone={onboardingMilestone} onChange={() => {}} />
  );
};

afterEach(() => {
  cleanup();
});
test('should render not registered only when not registered is selected previously and when L1 is not submitted', () => {
  render(<App onboardingMilestone="L1" />, {});
  const businessTypeLabel = screen.getByTestId('ds-text');
  fireEvent.click(businessTypeLabel);
  expect(screen.queryByText('Partnership')).not.toBeInTheDocument();
});

test('should render only Not registered option when L1 is submitted and Not registered had been selected', () => {
  render(<App onboardingMilestone="L1" />, {});
  const businessTypeLabel = screen.getByTestId('ds-text');
  fireEvent.click(businessTypeLabel);
  expect(screen.queryByText('Private Limited')).not.toBeInTheDocument();
});

test('should render all but Not registered option when L1 is submitted and Not registered had not been selected', () => {
  render(<App onboardingMilestone="L1" value="6" />, {});
  const businessTypeLabel = screen.getByTestId('ds-text');
  fireEvent.click(businessTypeLabel);
  expect(screen.queryByText('Private Limited')).toBeInTheDocument();
  expect(screen.queryByText('Not registered')).not.toBeInTheDocument();
});
