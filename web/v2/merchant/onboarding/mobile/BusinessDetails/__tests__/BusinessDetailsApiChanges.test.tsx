import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent, render, screen, waitFor, waitForElementToBeRemoved } from 'test-utils';
import BusinessDetails from '../index';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <BusinessDetails />;
};
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
test('should copy address details when operational address is same as permanent address', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  const addressInput = screen.getByTestId('ds-text-area');
  expect(screen.getByText('Enter Address')).toBeInTheDocument();
  expect(screen.queryByText('Business Operational Address')).not.toBeInTheDocument();
  await fireEvent.change(addressInput, { target: { value: 'abc' } });
  waitFor(() => expect(ActivationDB.read().business_registered_address).toBe('abc'));
  waitFor(() => expect(ActivationDB.read().business_operation_address).toBe('abc'));
});
