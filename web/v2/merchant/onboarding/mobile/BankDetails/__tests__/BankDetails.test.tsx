import React from 'react';
import BankDetails from '../index';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';
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
});
