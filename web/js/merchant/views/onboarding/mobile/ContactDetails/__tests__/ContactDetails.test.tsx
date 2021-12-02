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

test('should render all component correctly', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Contact Name')).toBeInTheDocument();
  expect(screen.getByText('Contact Number')).toBeInTheDocument();
  expect(screen.getByText('Contact Email')).toBeInTheDocument();
  expect(screen.getByText('Contact Email')).toBeInTheDocument();
  expect(screen.getByText('Verify With OTP')).toBeInTheDocument();
});

test('should render correct valdiation error in case input is not valid', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getAllByTestId('ds-text-input').length).toBe(3);
  const contactNumberInput = screen.getAllByTestId('ds-text-input')[1];
  fireEvent.change(contactNumberInput, { target: { value: '12345' } });
  fireEvent.blur(contactNumberInput);
  await waitFor(() =>
    expect(screen.getByText('Please enter a valid 10-digit mobile number.')).toBeInTheDocument(),
  );
});

test('should reflect the changes in the api response', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  const [contactNameInput, contactNumber, contactEmailInput] = screen.getAllByTestId(
    'ds-text-input',
  );
  fireEvent.change(contactNameInput, { target: { value: 'Neeraj' } });
  fireEvent.change(contactNumber, { target: { value: '1234567890' } });
  fireEvent.change(contactEmailInput, { target: { value: 'Neeraj@abc.com' } });
  ActivationDB.update({
    contact_name: 'Neeraj',
    contact_mobile: '1234567890',
    contact_email: 'Neeraj@abc.com',
  });
  waitFor(() => expect(ActivationDB.read().contact_name).toBe('Neeraj'));
  waitFor(() => expect(ActivationDB.read().contact_mobile).toBe('1234567890'));
  waitFor(() => expect(ActivationDB.read().contact_email).toBe('Neeraj@abc.com'));
});

test('should be able to send OTP and verify OTP', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();

  //send otp
  const contactEmailInput: any = screen.getAllByTestId('ds-text-input')[2];
  fireEvent.change(contactEmailInput, { target: { value: 'Neeraj@abc.com' } });
  const sendOTPButton = screen.getByText('Verify With OTP');
  fireEvent.click(sendOTPButton);
  await waitFor(() => expect(screen.getByText('OTP')).toBeInTheDocument());
  await waitFor(() => expect(screen.getByText('Submit OTP')).toBeInTheDocument());

  //verify otp
  const verfiyButton = screen.getByText('Submit OTP');
  const enterOTP = screen.getAllByTestId('ds-text-input')[3];
  fireEvent.change(enterOTP, { target: { value: '000007' } });
  fireEvent.click(verfiyButton);
  await waitFor(() => expect(contactEmailInput.value).toBe('Neeraj@abc.com'));
  await waitFor(() => expect(screen.queryByText(/Verify With OTP/i)).not.toBeInTheDocument());
});
