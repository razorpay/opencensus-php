import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, fireEvent } from 'test-utils';
import {
  BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS,
  BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING,
} from '../constants';
import BankUpdateRenderStep from '../BankUpdateRenderStep';

const setStep = jest.fn();
const onSave = jest.fn();
const closeModal = jest.fn();

const App = ({ step }) => {
  return (
    <BankUpdateRenderStep step={step} setStep={setStep} onSave={onSave} closeModal={closeModal} />
  );
};

const loaderIdentifier = BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING.title;
const successIdentifier = BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS.title;
const updateFormIdentifier = 'Add your new bank account details';
const fileUploadIdentifier = 'Upload a bank proof (any one) for our team to review:';
const detailsErrorIdentifier = 'Change Details';

describe('Bank account update render step component', () => {
  it('should render BankAccountUpdateForm by default or if step is init', () => {
    const { getByText } = render(<App />);
    const uniqueText = getByText(updateFormIdentifier);
    expect(uniqueText).toBeInTheDocument();
  });

  it('should render loader component if step is penny-testing-started', () => {
    const { getByText } = render(<App step="penny-testing-started" />);
    const uniqueText = getByText(loaderIdentifier);
    expect(uniqueText).toBeInTheDocument();
  });

  it('should render success tick component if step is penny-testing-success and close modal after 1.5s', async () => {
    const { getByText } = render(<App step="penny-testing-success" />);
    const uniqueText = getByText(successIdentifier);
    expect(uniqueText).toBeInTheDocument();
    jest.useFakeTimers();
    jest.runAllTimers();
    await new Promise((r) => setTimeout(r, 1500));
    expect(closeModal).toHaveBeenCalled();
  });

  it('should render loader component if step is penny-testing-details-error', () => {
    const { getByText } = render(<App step="penny-testing-details-error" />);
    const uniqueText = getByText(detailsErrorIdentifier);
    expect(uniqueText).toBeInTheDocument();
  });

  it('should render loader component if step is sync-failed-async-started', () => {
    const { getByText } = render(<App step="sync-failed-async-started" />);
    const uniqueText = getByText(fileUploadIdentifier);
    expect(uniqueText).toBeInTheDocument();
  });

  it('should change step to init and reset form on details error btn cta click', () => {
    const { getByText } = render(<App step="penny-testing-details-error" />);
    const changeDetialsBtn = getByText(detailsErrorIdentifier);
    expect(changeDetialsBtn).toBeInTheDocument();
    fireEvent.click(changeDetialsBtn);
    expect(setStep).toHaveBeenCalled();
    expect(setStep).toHaveBeenCalledWith('init');
  });
});
