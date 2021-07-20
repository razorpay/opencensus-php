import React from 'react';
import BankDetails from '../index';
import { fireEvent, render, screen, waitForElementToBeRemoved } from 'test-utils';
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
