import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';
import BusinessDetails from '../index';
import useActivation from '../../hooks/useActivation';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as DataPieces from '../../services/data/pieces';
afterEach(() => {
  ActivationDB.reset();
});
const App: React.FC = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <BusinessDetails />;
};
const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));
test('should render Address details field for all the merchants', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Address Details')).toBeInTheDocument();
  expect(screen.getByText('Enter Address')).toBeInTheDocument();
  expect(screen.getByText('Pincode')).toBeInTheDocument();
  expect(screen.getByText('City')).toBeInTheDocument();
  expect(screen.getByText('Select State')).toBeInTheDocument();
});
test('should render correct PAN details fields for unregsitered merchant', async () => {
  ActivationDB.update({
    business_type: '11',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText("Business Owner's PAN")).toBeInTheDocument();
  expect(screen.getByText("Business Owner's Name")).toBeInTheDocument();
});
test('should render correct PAN details fields for Private merchant', async () => {
  ActivationDB.update({
    business_type: '4',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Business PAN')).toBeInTheDocument();
  expect(screen.getByText('Business Name')).toBeInTheDocument();
  expect(screen.getByText('Authorised Signatory PAN')).toBeInTheDocument();
  expect(screen.getByText('Authorised Signatory Name')).toBeInTheDocument();
});
test('shoud render POI failed message when PAN verfication failed', async () => {
  ActivationDB.update({
    ...DataPieces.POIStatus.not_matched,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText('PAN Verification failed. Please review your details and submit again'),
  ).toBeInTheDocument();
});
test('should not render Company Details fields for unregistered business', async () => {
  ActivationDB.update({
    business_type: '11',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
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
