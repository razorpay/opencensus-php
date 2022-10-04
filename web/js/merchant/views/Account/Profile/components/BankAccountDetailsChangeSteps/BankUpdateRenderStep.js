import { useState } from 'react';
import PennyTestingUserDetailsError from './BankDetailsError';
import BankAccountUpdateForm from './BankAccountUpdateForm';
import BankAccountUpdateAsyncFlow from './BankAccountUpdateAsyncFlow';
import BankAccountUpdateState from './BankAccountUpdateState';
import {
  BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING,
  BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS,
} from './constants';

const BankUpdateRenderStep = ({ setStep, step, onSave, closeModal }) => {
  const [verificationError, setVerificationError] = useState();

  const renderBankAccountUpdateForm = () => {
    setStep('init');
  };
  switch (step) {
    case 'penny-testing-started':
      return (
        <BankAccountUpdateState
          data={BANK_ACCOUNT_UPDATE_PENNY_TESTING_LOADING}
          lottieDivClass={['mb-20']}
        />
      );
    case 'penny-testing-success': {
      setTimeout(() => {
        closeModal();
      }, 1500);
      return <BankAccountUpdateState data={BANK_ACCOUNT_UPDATE_PENNY_TESTING_SUCCESS} />;
    }
    case 'penny-testing-details-error':
      return (
        <PennyTestingUserDetailsError
          error={verificationError}
          onButtonClick={renderBankAccountUpdateForm}
        />
      );
    case 'sync-failed-async-started':
      return <BankAccountUpdateAsyncFlow />;
    default:
      return (
        <BankAccountUpdateForm
          onSave={onSave}
          setVerificationError={setVerificationError}
          setStep={setStep}
        />
      );
  }
};

export default BankUpdateRenderStep;
