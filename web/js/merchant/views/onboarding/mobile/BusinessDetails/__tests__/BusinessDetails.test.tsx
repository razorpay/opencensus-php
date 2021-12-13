import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent, render, screen, waitForElementToBeRemoved } from 'test-utils';
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
    ...DataPieces.OnboardingMileStoneL1,
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

test('should copy address details when operational address is same as permanent address', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  const addressInput = screen.getByTestId('ds-text-area');
  expect(screen.getByText('Enter Address')).toBeInTheDocument();
  expect(screen.queryByText('Business Operational Address')).not.toBeInTheDocument();
  await fireEvent.change(addressInput, { target: { value: 'abc' } });
});

test('should render correct flow', async () => {
  ActivationDB.update({
    business_type: '4',
    business_operation_pin: '530068',
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  const [
    businessPanInput,
    buseinessNameInput,
    cinInput,
    authSignatoryProofInput,
    authSignatoryNameInput,
    billingLabelInput,
    pincodeInput,
    cityInput,
    stateInput,
  ]: any = screen.getAllByTestId('ds-text-input');
  const addressInput = screen.getByTestId('ds-text-area');

  fireEvent.change(businessPanInput, { target: { value: 'ABCDE1234F' } });
  fireEvent.blur(businessPanInput);
  fireEvent.change(buseinessNameInput, { target: { value: 'testing' } });
  fireEvent.blur(buseinessNameInput);

  fireEvent.change(authSignatoryProofInput, { target: { value: 'AAPFA3421J' } });
  fireEvent.change(authSignatoryNameInput, { target: { value: 'testName' } });
  fireEvent.change(billingLabelInput, { target: { value: 'Some Label' } });
  fireEvent.change(cinInput, { target: { value: 'U74899DL2000PLC105530' } });
  fireEvent.blur(cinInput);

  expect(businessPanInput.value).toBe('ABCDE1234F');
  expect(buseinessNameInput.value).toBe('testing');
  expect(authSignatoryProofInput.value).toBe('AAPFA3421J');
  expect(authSignatoryNameInput.value).toBe('testName');
  expect(billingLabelInput.value).toBe('Some Label');
  expect(cinInput.value).toBe('U74899DL2000PLC105530');

  fireEvent.change(addressInput, { target: { value: 'abc' } });
  fireEvent.blur(addressInput);
  fireEvent.change(pincodeInput, { target: { value: '530068' } });
  fireEvent.blur(pincodeInput);
  expect(pincodeInput.value).toBe('530068');
  fireEvent.change(cityInput, { target: { value: 'Delhi' } });
  fireEvent.blur(cityInput);
  fireEvent.change(stateInput, { target: { value: 'DL' } });
  fireEvent.blur(stateInput);
});
