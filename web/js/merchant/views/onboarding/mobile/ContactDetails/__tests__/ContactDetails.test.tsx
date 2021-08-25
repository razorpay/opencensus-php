import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent, render, screen, waitFor, waitForElementToBeRemoved } from 'test-utils';
import ContactDetails from '../index';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';
afterEach(() => {
  ActivationDB.reset();
});
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <ContactDetails />;
};
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
test('should render all input Fields correctly', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Contact Name')).toBeInTheDocument();
  expect(screen.getByText('Contact Email')).toBeInTheDocument();
  expect(screen.getByText('Contact Number')).toBeInTheDocument();
});
test('should render correct valdiation error in case input is not valid', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getAllByTestId('ds-text-input').length).toBe(3);
  const contactNumberInput = screen.getAllByTestId('ds-text-input')[2];
  fireEvent.change(contactNumberInput, { target: { value: '12345' } });
  fireEvent.blur(contactNumberInput);
  await waitFor(() =>
    expect(screen.getByText('Please enter a valid 10-digit mobile number.')).toBeInTheDocument(),
  );
});
test('should reflect the changes in the api response', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  const [contactNameInput, contactEmailInput, contactNumber] = screen.getAllByTestId(
    'ds-text-input',
  );
  fireEvent.change(contactNameInput, { target: { value: 'Neeraj' } });
  fireEvent.change(contactEmailInput, { target: { value: 'Neeraj@abc.com' } });
  fireEvent.change(contactNumber, { target: { value: '1234567890' } });
  waitFor(() => expect(ActivationDB.read().contact_name).toBe('Neeraj'));
  waitFor(() => expect(ActivationDB.read().contact_email).toBe('Neeraj@abc.com'));
  waitFor(() => expect(ActivationDB.read().contact_mobile).toBe('1234567890'));
});
