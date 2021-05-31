import React from 'react';
import BankDetails from '../index';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';
import * as ActivationDB from '../../services/data/ActivationDB';
import useActivation from '../../hooks/useActivation';

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <BankDetails />;
};
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
test('should render Bank Details fields and not Company Details fields for unregistered business', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Beneficiary Name')).toBeInTheDocument();
  expect(screen.getByText('Account Number')).toBeInTheDocument();
  expect(screen.getByText('IFSC Code')).toBeInTheDocument();
  expect(screen.queryByText('Company Details')).not.toBeInTheDocument();
});
test('should render Company Details section for registered business', async () => {
  ActivationDB.update({
    business_type: '4',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.queryByText('Company Details')).toBeInTheDocument();
});
test('should render CIN field for Private or Public merchants', async () => {
  ActivationDB.update({
    business_type: '4',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Company Identification Number (CIN)')).toBeInTheDocument();
  expect(screen.queryByText('LLP Identification Number (LLPIN)')).not.toBeInTheDocument();
});
test('should render LLPIN field for LLP merchants', async () => {
  ActivationDB.update({
    business_type: '6',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('LLP Identification Number (LLPIN)')).toBeInTheDocument();
  expect(screen.queryByText('Company Identification Number (CIN)')).not.toBeInTheDocument();
});
