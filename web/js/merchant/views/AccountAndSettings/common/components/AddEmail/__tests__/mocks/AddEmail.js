import React from 'react';
import { render } from 'test-utils';
import AddEmail from 'merchant/views/AccountAndSettings/common/components/AddEmail';
import { OTPMETHOD as mockOtpMethod } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/services';

jest.mock(
  'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/OTPModal',
  () =>
    ({ otpMethod, onSubmit }) =>
      (
        <div>
          {otpMethod === mockOtpMethod.EMAIL ? 'Email' : 'Phone'} OTPModal{' '}
          <button type="button" onClick={() => onSubmit({ token: 'token' })}>
            Submit
          </button>
        </div>
      ),
);

jest.mock(
  'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2/AccountDetailsUpdate',
  () =>
    ({ onEnterEmailSubmit }) =>
      (
        <div>
          AccountDetailsUpdate{' '}
          <button
            type="button"
            onClick={() => onEnterEmailSubmit({ userInput: 'userInput', setIsLoading: jest.fn() })}
          >
            Submit
          </button>
        </div>
      ),
);

jest.mock(
  'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/SuccessModal',
  () =>
    ({ onClose }) =>
      (
        <div>
          SuccessModal{' '}
          <button type="button" onClick={() => onClose()}>
            Close
          </button>
        </div>
      ),
);

const onEmailAdd = (args, callback) => {
  callback();
};

export const renderApp = () => {
  return render(<AddEmail screen="add email" entity={{ onEmailAdd, queryParam: 'queryParam' }} />, {
    showModal: true,
  });
};
