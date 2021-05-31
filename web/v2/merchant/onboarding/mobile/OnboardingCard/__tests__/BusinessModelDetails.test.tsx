import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import useActivation from '../../hooks/useActivation';
import OnboardingCardShimmer from '../OnboardingCardShimmer';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as DataPieces from '../../services/data/pieces';
import BusinessModelDetails from 'v2/merchant/onboarding/mobile/OnboardingCard/BusinessModelDetails';
import { render, screen, fireEvent, waitForElementToBeRemoved } from 'test-utils';
afterEach(() => {
  ActivationDB.reset();
});
const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('shimmer')], { timeout: 4000 });
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <OnboardingCardShimmer />;
  return <BusinessModelDetails />;
};
test.skip('should render Business Type and Business Category for those Not having Business Model', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Start Activation')).toBeInTheDocument();
  const businessTypeSelect = screen.getAllByTestId('ds-text-input')[0];
  fireEvent.click(businessTypeSelect);
  expect(screen.getByText('Private Limited')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Private Limited'));
  expect(screen.queryByText('LLP')).not.toBeInTheDocument();
});
test.skip('should render Business Type and Business Category for those Not having Business Model', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Start Activation')).toBeInTheDocument();
  expect(screen.getAllByTestId('ds-text-input').length).toBe(2);
  expect(screen.getByText('Business Type')).toBeInTheDocument();
  expect(screen.getByText('Your Business Category')).toBeInTheDocument();
  expect(screen.queryByText('Tell us a bit about your business model')).not.toBeInTheDocument();
});
test.skip('should render Business Model along with Business Type and Business Category for those having Business Model', async () => {
  ActivationDB.update({
    ...DataPieces.BusinessModelPicker,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Start Activation')).toBeInTheDocument();
  expect(screen.getAllByTestId('ds-text-input').length).toBe(2);
  expect(screen.getByText('Business Type')).toBeInTheDocument();
  expect(screen.getByText('Your Business Category')).toBeInTheDocument();
  expect(screen.queryByText('Tell us a bit about your business model')).toBeInTheDocument();
});
