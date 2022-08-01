import React, { useEffect } from 'react';
import BankAccountUpdateStatus from './BankAccountUpdateStatus';
import { trackBankAccountDetailsChange } from './utils';

const BankDetailsError = ({ error, onButtonClick }) => {
  useEffect(() => {
    trackBankAccountDetailsChange({
      objectName: 'Bank Account Input Error Screen',
      actionName: 'Viewed',
      properties: {
        errorMessage: error?.title,
      },
    });
  }, []);

  return (
    <BankAccountUpdateStatus
      data={error}
      buttonText="Change Details"
      onButtonClick={onButtonClick}
    />
  );
};

export default BankDetailsError;
