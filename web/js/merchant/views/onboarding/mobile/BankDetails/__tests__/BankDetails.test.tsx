import React from 'react';
import BankDetails from '../index';
import { fireEvent, render, screen, waitForElementToBeRemoved } from 'test-utils';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';

afterEach(() => {
  ActivationDB.reset();
});

const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <BankDetails />;
};

const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
const UNREG_BANK_ERROR =
  'Kindly make sure you enter your Personal Bank Account details. Beneficiary Name of this bank account should match your Personal Pan Name';
const REG_BANK_ERROR =
  'Make sure you enter your Company Bank Account details. Beneficiary Name of this bank account should match your Company Pan Name';

test('should render Bank Details fields and not Company Details fields for unregistered business', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Beneficiary Name')).toBeInTheDocument();
  expect(screen.getByText('Account Number')).toBeInTheDocument();
  expect(screen.getByText('Re-Enter Account Number')).toBeInTheDocument();
  expect(screen.getByText('IFSC Code')).toBeInTheDocument();
  const [beneficaryName, accNumber, reAccNumber]: any = screen.getAllByTestId('ds-text-input');
  fireEvent.change(beneficaryName, { target: { value: 'xeno' } });
  fireEvent.blur(beneficaryName);

  fireEvent.change(accNumber, { target: { value: '123456778' } });
  fireEvent.blur(accNumber);

  fireEvent.change(reAccNumber, { target: { value: '123456778' } });
  fireEvent.blur(reAccNumber);
});

test('should show bank verification status error for unreg type', async () => {
  ActivationDB.update({
    business_type: '11',
    bank_details_verification_status: 'failed',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(UNREG_BANK_ERROR)).toBeInTheDocument();
});

test('should show bank verification status error for reg type', async () => {
  ActivationDB.update({
    business_type: '1',
    bank_details_verification_status: 'failed',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText(REG_BANK_ERROR)).toBeInTheDocument();
});
