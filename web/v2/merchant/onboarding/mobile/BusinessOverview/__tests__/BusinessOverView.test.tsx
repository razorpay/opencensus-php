/* eslint-disable @typescript-eslint/no-unused-vars */
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent, render, screen, waitForElementToBeRemoved } from 'test-utils';
import BusinessOverview from '../index';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';
afterEach(() => {
  ActivationDB.reset();
});
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <BusinessOverview />;
};
test('renders all the input fields of the form correctly', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('About Your Business')).toBeInTheDocument();

  const [businessTypeInput, businessCategorySelect, billingLabelInput]: any = screen.getAllByTestId(
    'ds-text-input',
  );
  fireEvent.click(businessTypeInput);
  expect(screen.getByText('Private Limited')).toBeInTheDocument();
  fireEvent.click(businessCategorySelect);
  fireEvent.click(screen.getByText('Private Limited'));
  fireEvent.change(billingLabelInput, { target: { value: 'Some Label' } });
  expect(businessTypeInput.value).toBe('Private Limited');
  expect(billingLabelInput.value).toBe('Some Label');
  expect(screen.getByText('Website Details')).toBeInTheDocument();
});
