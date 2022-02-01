/* eslint-disable @typescript-eslint/no-unused-vars */
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ActivationForm from '../index';
import * as ActivationDB from '../../../services/data/ActivationDB';
import * as DataPieces from '../../../services/data/pieces';
import { waitForElementToBeRemoved, screen, render, fireEvent, waitFor, delay } from 'test-utils';

/* eslint-disable func-names */
window.HTMLElement.prototype.scrollIntoView = function () {};

afterEach(() => {
  ActivationDB.reset();
});

const waitForLoaderToFinish = () =>
  waitForElementToBeRemoved(() => [...screen.queryAllByRole('loader')], { timeout: 4000 });

test('ActivationForm Flow', async () => {
  ActivationDB.update({
    ...DataPieces.ActivationFlowWW,
    ...DataPieces.regBusinessOverview,
    business_model: 'asdas',
    merchant_avg_order_value: {
      min_aov: 151,
      max_aov: 300,
    },
    ...DataPieces.businessDetails,
    ...DataPieces.bankAndCompanyDetails,
    ...DataPieces.Documents,
  });
  jest.setTimeout(30000);
  render(<ActivationForm />, {});
  await waitForLoaderToFinish();
  expect(screen.getByText('Contact Name')).toBeInTheDocument();
  const [contactNameInput, contactNumber, contactEmailInput]: any = screen.getAllByTestId(
    'ds-text-input',
  );

  fireEvent.change(contactNameInput, { target: { value: 'Neeraj' } });
  fireEvent.change(contactEmailInput, { target: { value: 'Neeraj@abc.com' } });
  fireEvent.change(contactNumber, { target: { value: '1234567890' } });
  expect(contactNameInput.value).toBe('Neeraj');
  expect(contactEmailInput.value).toBe('Neeraj@abc.com');
  expect(contactNumber.value).toBe('1234567890');
  const tabButton = screen.getByText('Business Overview');
  fireEvent.click(tabButton);

  const nextButton = screen.getByText('Next');

  expect(screen.getByText('About Your Business')).toBeInTheDocument();
  const [businessTypeInput, businessCategorySelect]: any = screen.getAllByTestId('ds-text-input');
  fireEvent.click(businessTypeInput);
  expect(screen.getByText('Private Limited')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Private Limited'));
  expect(businessTypeInput.value).toBe('Private Limited');

  ActivationDB.update({
    business_type: '4',
  });
  await delay(1000);
  fireEvent.click(nextButton);
  await waitFor(() => expect(screen.getByText('PAN Details')).toBeInTheDocument());

  expect(screen.getByText('Authorised Signatory PAN')).toBeInTheDocument();
  expect(screen.getByText('Address Details')).toBeInTheDocument();
  /* eslint-disable one-var */
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
    ]: any = screen.getAllByTestId('ds-text-input'),
    addressInput = screen.getByTestId('ds-text-area');

  fireEvent.change(businessPanInput, { target: { value: 'ABCDE1234F' } });
  fireEvent.change(buseinessNameInput, { target: { value: 'NEERAJ' } });
  fireEvent.change(authSignatoryProofInput, { target: { value: 'AAPFA3421J' } });
  fireEvent.change(authSignatoryNameInput, { target: { value: 'testName' } });
  fireEvent.change(billingLabelInput, { target: { value: 'Some Label' } });
  fireEvent.change(cinInput, { target: { value: 'U74899DL2000PLC105530' } });

  expect(businessPanInput.value).toBe('ABCDE1234F');
  expect(buseinessNameInput.value).toBe('NEERAJ');
  expect(authSignatoryProofInput.value).toBe('AAPFA3421J');
  expect(authSignatoryNameInput.value).toBe('testName');
  expect(billingLabelInput.value).toBe('Some Label');
  expect(cinInput.value).toBe('U74899DL2000PLC105530');

  fireEvent.change(addressInput, { target: { value: 'Flat no 12, opp Adugodi Police Station' } });
  fireEvent.change(pincodeInput, { target: { value: '530068' } });
  fireEvent.change(cityInput, { target: { value: 'Delhi' } });
  fireEvent.change(stateInput, { target: { value: 'DL' } });

  expect(pincodeInput.value).toBe('530068');
  expect(cityInput.value).toBe('Delhi');
  expect(stateInput.value).toBe('DL');
  fireEvent.click(nextButton);

  await waitFor(() => fireEvent.click(screen.getByText('Submit KYC')));
  fireEvent.click(screen.getByText('Business Overview'));
  await waitFor(() => fireEvent.click(screen.getByText('FAQs')));
});

test('should be able to click on back button', async () => {
  render(<ActivationForm />, {});
  await waitForLoaderToFinish();
  expect(screen.getByText('Account Activation')).toBeInTheDocument();
  fireEvent.click(screen.getByTestId('backIcon'));
});

test('should be open save and exit modal', async () => {
  ActivationDB.update({
    ...DataPieces.OnboardingMileStoneL1,
  });
  render(<ActivationForm />, {});
  await waitForLoaderToFinish();
  const saveAndExit = screen.getByText('Save and Exit');
  expect(saveAndExit).toBeInTheDocument();
  fireEvent.click(saveAndExit);
});
