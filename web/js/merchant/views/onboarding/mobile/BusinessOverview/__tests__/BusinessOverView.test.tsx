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

  const [
    businessTypeInput,
    businessCategorySelect,
    billingLabelInput,
    AovField,
  ]: any = screen.getAllByTestId('ds-text-input');

  const businessModal = screen.getByTestId('ds-text-area');
  fireEvent.click(businessTypeInput);
  expect(screen.getByText('Private Limited')).toBeInTheDocument();
  fireEvent.click(businessCategorySelect);
  fireEvent.change(businessCategorySelect, { target: { value: 'ecomerce' } });

  fireEvent.click(screen.getByText('Private Limited'));
  fireEvent.change(billingLabelInput, { target: { value: 'Some Label' } });
  expect(businessTypeInput.value).toBe('Private Limited');
  expect(billingLabelInput.value).toBe('Some Label');
  fireEvent.blur(billingLabelInput);

  fireEvent.change(businessModal, {
    target: {
      value:
        "Unregistered business type is for freelancers or small businesses who have not yet registered as a company. Don't choose this option if your business is already registered. Business type cannot be changed once submitted.",
    },
  });
  fireEvent.blur(businessModal);

  fireEvent.click(AovField);
  fireEvent.change(AovField, { target: { value: { value: '₹ 1 - ₹ 150' } } });
  fireEvent.blur(AovField);

  expect(screen.getByText('Website Details')).toBeInTheDocument();
  expect(screen.getByText('I have a live website/app')).toBeInTheDocument();

  fireEvent.click(screen.getByText('I have a live website/app'));
  expect(screen.getByText('Accept payments on website')).toBeInTheDocument();
  expect(screen.getByText('Accept payments on app')).toBeInTheDocument();
});
