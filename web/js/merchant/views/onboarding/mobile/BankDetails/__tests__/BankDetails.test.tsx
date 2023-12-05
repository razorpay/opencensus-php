import React from 'react';
import BankDetails from 'merchant/views/onboarding/mobile/BankDetails/index';
import { fireEvent, render, screen, waitForElementToBeRemoved, waitFor, cleanup } from 'test-utils';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import * as ActivationDB from 'merchant/views/onboarding/mobile/services/data/ActivationDB';

beforeEach(() => {
  ActivationDB.reset();
  cleanup();
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
  expect(screen.getByText('Account Number')).toBeInTheDocument();
  expect(screen.getByText('IFSC Code')).toBeInTheDocument();
  const [beneficaryName, accNumber]: any = screen.getAllByTestId('ds-text-input');
  fireEvent.change(beneficaryName, { target: { value: 'xeno' } });
  fireEvent.blur(beneficaryName);

  fireEvent.change(accNumber, { target: { value: '123456778' } });
  fireEvent.blur(accNumber);
  await waitFor(() => {
    expect(accNumber.value).toBe('123456778');
  });
});

test('should show bank verification status error for reg type', async () => {
  ActivationDB.update({
    business_type: '1',
    bank_details_verification_status: 'not_matched',
  });
  render(<App />, {});
  await waitFor(() => {
    expect(screen.getByText(REG_BANK_ERROR)).toBeInTheDocument();
  });
});

test('should show bank verification status error for unreg type', async () => {
  ActivationDB.update({
    business_type: '11',
    bank_details_verification_status: 'not_matched',
  });
  render(<App />, {});

  await waitFor(() => {
    expect(screen.getByText(UNREG_BANK_ERROR)).toBeInTheDocument();
  });
});
