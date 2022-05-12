import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BusinessType from '../index';
import { render, screen, fireEvent, cleanup, waitFor } from 'test-utils';
interface AppProps {
  onboardingMilestone?: string;
  value?: string;
}

const App: React.FC<AppProps> = ({ onboardingMilestone = '', value = '11' }) => {
  return (
    <BusinessType value={value} onboardingMilestone={onboardingMilestone} onChange={() => {}} />
  );
};

afterEach(() => {
  cleanup();
});

test('should render all business types except Individual when not registered is selected previously and when L1 is not submitted', async () => {
  render(<App value="11" />, {});
  await waitFor(() => {
    expect(screen.getByTestId('ds-text-input')).toHaveValue('Unregistered');
  });
  const businessTypeLabel = screen.getByTestId('ds-text');
  fireEvent.click(businessTypeLabel);
  expect(screen.queryByText('Trust')).toBeInTheDocument();
  expect(screen.queryByText('Private Limited')).toBeInTheDocument();
  expect(screen.queryByText('Individual')).not.toBeInTheDocument();
});

test('should render only Not registered option when L1 is submitted and Not registered had been selected', async () => {
  render(<App onboardingMilestone="L1" value="11" />, {});
  await waitFor(() => {
    expect(screen.getByTestId('ds-text-input')).toHaveValue('Unregistered');
  });
  const businessTypeLabel = screen.getByTestId('ds-text');
  fireEvent.click(businessTypeLabel);
  expect(screen.queryByText('Private Limited')).not.toBeInTheDocument();
});

test('should render all but Not registered option when L1 is submitted and Not registered had not been selected', async () => {
  render(<App onboardingMilestone="L1" value="9" />, {});
  await waitFor(() => {
    expect(screen.getByTestId('ds-text-input')).toHaveValue('Trust');
  });
  const businessTypeLabel = screen.getByTestId('ds-text');
  fireEvent.click(businessTypeLabel);
  expect(screen.queryByText('Private Limited')).toBeInTheDocument();
  expect(screen.queryByText('Not registered')).not.toBeInTheDocument();
});
